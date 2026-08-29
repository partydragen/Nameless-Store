<?php
/**
 * Payment class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.2.0
 * @license MIT
 */
class Payment {
    public const PENDING = 'PENDING';
    public const COMPLETED = 'COMPLETED';
    public const REFUNDED = 'REFUNDED';
    public const REVERSED = 'REVERSED';
    public const DENIED = 'DENIED';

    public const REFUND_PENDING = 0;
    public const REFUND_COMPLETED = 1;
    public const REFUND_FAILED = 2;

    private DB $_db;

    /**
     * @var PaymentData|null The product data. Basically just the row from `nl2_store_payments` where the payment ID is the key.
     */
    private ?PaymentData $_data;

    /**
     * @var Order The order this payment belong to.
     */
    private $_order;

    public function __construct($value = null, $field = 'id', $query_data = null) {
        $this->_db = DB::getInstance();

        if (!$query_data && $value) {
            $data = $this->_db->get('store_payments', [$field, '=', $value]);
            if ($data->count()) {
                $this->_data = new PaymentData($data->first());
            }
        } else if ($query_data) {
            // Load data from existing query.
            $this->_data = new PaymentData($query_data);
        }
    }

    /**
     * Update a payment data in the database.
     *
     * @param array $fields Column names and values to update.
     */
    public function update(array $fields = []) {
        if (!$this->_db->update('store_payments', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating payment');
        }
    }

    /**
     * Create a new payment.
     *
     * @param array $fields Column names and values to insert to database.
     */
    public function create(array $fields = []) {
        if (!$this->_db->insert('store_payments', $fields)) {
            throw new Exception('There was a problem registering the payment');
        }
        $last_id = $this->_db->lastId();

        $data = $this->_db->get('store_payments', ['id', '=', $last_id]);
        if ($data->count()) {
            $this->_data = new PaymentData($data->first());
        }

        return $last_id;
    }

    /**
     * Does this payment exist?
     *
     * @return bool Whether the payment exists (has data) or not.
     */
    public function exists(): bool {
        return (!empty($this->_data));
    }

    /**
     * Get the payment data.
     *
     * @return null|PaymentData This payment data.
     */
    public function data(): ?PaymentData {
        return $this->_data;
    }

    public function getOrder(): Order {
        if ($this->_order == null) {
            $this->_order = new Order($this->data()->order_id);
        }
 
        return $this->_order;
    }

    /** Find the most recent pending payment for an order and gateway. */
    public static function findPendingForOrder(int $order_id, int $gateway_id): self {
        $payment = DB::getInstance()->query(
            'SELECT * FROM nl2_store_payments WHERE order_id = ? AND gateway_id = ? AND status_id = 0 ORDER BY id DESC LIMIT 1',
            [$order_id, $gateway_id]
        );

        return $payment->count()
            ? new self(null, 'id', $payment->first())
            : new self();
    }

    /**
     * Get all refund attempts for this payment, newest first.
     *
     * @return array Refund database rows.
     */
    public function getRefunds(): array {
        if (!$this->exists()) {
            return [];
        }

        return $this->_db->query(
            'SELECT * FROM nl2_store_payment_refunds WHERE payment_id = ? ORDER BY created DESC, id DESC',
            [$this->data()->id]
        )->results();
    }

    /**
     * Get the amount which has been successfully refunded.
     */
    public function getRefundedAmountCents(): int {
        if (!$this->exists()) {
            return 0;
        }

        $refunded = $this->getRecordedRefundedAmountCents();

        // Payments refunded before refund auditing was introduced have no rows.
        if ($this->data()->status_id === 2) {
            return max($refunded, (int) ($this->data()->amount_cents ?? 0));
        }

        return $refunded;
    }

    /**
     * Get completed refund rows without the legacy fully-refunded fallback.
     */
    private function getRecordedRefundedAmountCents(): int {
        $result = $this->_db->query(
            'SELECT COALESCE(SUM(amount_cents), 0) AS total FROM nl2_store_payment_refunds WHERE payment_id = ? AND status_id = ?',
            [$this->data()->id, self::REFUND_COMPLETED]
        )->first();

        return (int) ($result->total ?? 0);
    }

    /**
     * Get the amount reserved by accepted refunds which are still pending.
     */
    public function getPendingRefundAmountCents(): int {
        if (!$this->exists()) {
            return 0;
        }

        $result = $this->_db->query(
            'SELECT COALESCE(SUM(amount_cents), 0) AS total FROM nl2_store_payment_refunds WHERE payment_id = ? AND status_id = ?',
            [$this->data()->id, self::REFUND_PENDING]
        )->first();

        return (int) ($result->total ?? 0);
    }

    /**
     * Get the amount which can still be refunded, excluding pending refunds.
     */
    public function getRefundableAmountCents(): int {
        $amount = (int) ($this->data()->amount_cents ?? 0);

        return max(0, $amount - $this->getRefundedAmountCents() - $this->getPendingRefundAmountCents());
    }

    /**
     * Store a refund returned by a gateway and finalise the payment when fully refunded.
     * Existing provider refund IDs are updated, making webhook delivery idempotent.
     *
     * @throws Exception If the refund is invalid or cannot be stored.
     */
    public function recordRefund(
        GatewayRefundResult $result,
        int $amount_cents,
        string $reason = '',
        ?int $user_id = null
    ): int {
        if (!$this->exists() || $amount_cents < 1 || $result->getTransactionId() === '') {
            throw new Exception('Invalid payment refund');
        }

        $existing = $this->_db->query(
            'SELECT * FROM nl2_store_payment_refunds WHERE payment_id = ? AND gateway_refund_id = ?',
            [$this->data()->id, $result->getTransactionId()]
        );

        $status_id = $result->isCompleted() ? self::REFUND_COMPLETED : self::REFUND_PENDING;
        if ($this->data()->status_id === 2) {
            // A completion webhook can arrive before the StaffCP request stores
            // the provider response. The provider event is authoritative.
            $status_id = self::REFUND_COMPLETED;
        }

        if ($existing->count()) {
            $refund = $existing->first();
            if ((int) $refund->status_id === self::REFUND_COMPLETED) {
                $status_id = self::REFUND_COMPLETED;
            }

            $fields = [
                'status_id' => $status_id,
                'updated' => date('U')
            ];

            if ($user_id !== null && $refund->user_id === null) {
                $fields['user_id'] = $user_id;
            }

            if ($reason !== '' && empty($refund->reason)) {
                $fields['reason'] = $reason;
            }

            if (!$this->_db->update('store_payment_refunds', $refund->id, $fields)) {
                throw new Exception('There was a problem updating the payment refund');
            }

            if ($status_id === self::REFUND_COMPLETED) {
                $this->finaliseRefundedPayment();
            }

            return (int) $refund->id;
        }

        $can_record_after_webhook = $this->data()->status_id === 2
            && $amount_cents <= max(
                0,
                (int) ($this->data()->amount_cents ?? 0)
                    - $this->getRecordedRefundedAmountCents()
                    - $this->getPendingRefundAmountCents()
            );

        if (
            ($this->data()->status_id !== 1 && !$can_record_after_webhook)
            || ($this->data()->status_id === 1 && $amount_cents > $this->getRefundableAmountCents())
        ) {
            throw new Exception('Refund amount exceeds the refundable payment amount');
        }

        if (!$this->_db->insert('store_payment_refunds', [
            'payment_id' => $this->data()->id,
            'gateway_refund_id' => $result->getTransactionId(),
            'amount_cents' => $amount_cents,
            'reason' => $reason !== '' ? $reason : null,
            'user_id' => $user_id,
            'status_id' => $status_id,
            'created' => date('U'),
            'updated' => date('U')
        ])) {
            throw new Exception('There was a problem recording the payment refund');
        }

        $refund_id = (int) $this->_db->lastId();
        if ($status_id === self::REFUND_COMPLETED) {
            $this->finaliseRefundedPayment();
        }

        return $refund_id;
    }

    /**
     * Mark a pending provider refund as failed so its amount becomes refundable again.
     */
    public function failRefund(string $gateway_refund_id, string $error = ''): void {
        if (!$this->exists() || $gateway_refund_id === '') {
            return;
        }

        $refund = $this->_db->query(
            'SELECT id FROM nl2_store_payment_refunds WHERE payment_id = ? AND gateway_refund_id = ? AND status_id = ?',
            [$this->data()->id, $gateway_refund_id, self::REFUND_PENDING]
        );

        if ($refund->count()) {
            $this->_db->update('store_payment_refunds', $refund->first()->id, [
                'status_id' => self::REFUND_FAILED,
                'error' => $error !== '' ? mb_substr($error, 0, 255) : null,
                'updated' => date('U')
            ]);
        }
    }

    /**
     * Complete provider refunds which were accepted as pending.
     */
    public function completePendingRefunds(): void {
        if (!$this->exists()) {
            return;
        }

        $this->_db->query(
            'UPDATE nl2_store_payment_refunds SET status_id = ?, updated = ? WHERE payment_id = ? AND status_id = ?',
            [self::REFUND_COMPLETED, date('U'), $this->data()->id, self::REFUND_PENDING]
        );
    }

    /**
     * Run the existing full-refund lifecycle after completed refunds cover the payment.
     */
    private function finaliseRefundedPayment(): void {
        if (
            $this->data()->status_id === 1
            && $this->getRefundedAmountCents() >= (int) ($this->data()->amount_cents ?? 0)
        ) {
            $this->handlePaymentEvent(self::REFUNDED);
        }
    }

    /**
     * Handle payment event change
     *
     * @param string $event Payment event.
     * @param array $extra_data Payment data to save to database.
     * @throws Exception
     */
    public function handlePaymentEvent(string $event, array $extra_data = []): void {
        $store_language = new Language(ROOT_PATH . '/modules/Store/language', LANGUAGE);

        if ($this->exists()) {
            // Payment exist, Continue with event handling

            $username = $this->getOrder()->recipient()->getUsername();
            switch ($event) {
                case self::PENDING:
                    // Payment pending
                    $update_array = [
                        'status_id' => 0,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

                    EventHandler::executeEvent(new PaymentPendingEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));

                break;
                case self::COMPLETED:
                    // Payment completed
                    if ($this->data()->status_id == 1) {
                        return;
                    }

                    $update_array = [
                        'status_id' => 1,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));
                    OrderCredit::completeForOrder((int) $this->data()->order_id, (int) $this->data()->id);

                    if ($this->hasOtherCompletedOrderPayment()) {
                        ErrorHandler::logWarning('[Store] Payment #' . $this->data()->id
                            . ' completed after its order had already been paid; product actions were skipped.');
                        return;
                    }

                    if ($this->data()->subscription_id == null) {
                        // This is a non-subscription payment
                        // Schedule any products for expiration?
                        foreach ($this->getOrder()->items()->getItems() as $item) {
                            if ($item->getProduct()->data()->durability != null) {
                                ExpireCustomerProductTask::schedule($this->getOrder(), $item, $this);
                            }
                        }

                        $this->executeActions(Action::PURCHASE);
                    } else {
                        // Handle subscription payment
                        // Is this a first or renewal payment?
                        $subscription_payments = DB::getInstance()->query('SELECT count(*) AS c FROM nl2_store_payments WHERE subscription_id = ?', [$this->data()->subscription_id])->first()->c;
                        if ($subscription_payments == 1) {
                            $this->executeActions(Action::PURCHASE);
                        } else {
                            $this->executeActions(Action::RENEWAL);
                        }
                    }

                    EventHandler::executeEvent(new PaymentCompletedEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                break;
                case self::REFUNDED:
                    // Payment refunded
                    if ($this->data()->status_id == 2) {
                        return;
                    }

                    $update_array = [
                        'status_id' => 2,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));
                    OrderCredit::refundForOrder((int) $this->data()->order_id, (int) $this->data()->id);

                    if ($this->hasOtherCompletedOrderPayment()) {
                        return;
                    }

                    $this->deletePendingActions();
                    $this->executeActions(Action::REFUND);

                    EventHandler::executeEvent(new PaymentRefundedEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                break;
                case self::REVERSED:
                    // Payment reversed
                    if ($this->data()->status_id == 3) {
                        return;
                    }

                    $update_array = [
                        'status_id' => 3,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

                    $this->deletePendingActions();
                    $this->executeActions(Action::CHANGEBACK);

                    EventHandler::executeEvent(new PaymentReversedEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                break;
                case self::DENIED:
                    // Payment denied
                    if ($this->data()->status_id == 4) {
                        return;
                    }

                    $update_array = [
                        'status_id' => 4,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));
                    OrderCredit::releaseForOrder((int) $this->data()->order_id);

                    EventHandler::executeEvent('paymentDenied', [
                        'event' => 'paymentDenied',
                        'order' => $this->getOrder(),
                        'order_id' => $this->data()->order_id,
                        'payment_id' => $this->data()->id,
                        'username' => $username,
                        'content_full' => $store_language->get('general', 'denied_payment_text', ['user' => $username]),
                    ]);
                break;
                default:
                    // Invalid event type, Throw error
                    throw new Exception('Invalid payment event');
                break;
            }
        } else {
            // Register payment
            switch ($event) {
                case self::PENDING:
                    // Payment pending
                    $insert_array = [
                        'status_id' => 0,
                        'created' => date('U'),
                        'last_updated' => date('U')
                    ];

                    $this->create(array_merge($insert_array, $extra_data));

                    EventHandler::executeEvent(new PaymentPendingEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                break;
                case self::COMPLETED:
                    // Payment completed
                    $insert_array = [
                        'status_id' => 1,
                        'created' => date('U'),
                        'last_updated' => date('U')
                    ];

                    $this->create(array_merge($insert_array, $extra_data));
                    OrderCredit::completeForOrder((int) $this->data()->order_id, (int) $this->data()->id);

                    if ($this->hasOtherCompletedOrderPayment()) {
                        ErrorHandler::logWarning('[Store] Payment #' . $this->data()->id
                            . ' completed after its order had already been paid; product actions were skipped.');
                        return;
                    }

                    if ($this->data()->subscription_id == null) {
                        // This is a non-subscription payment
                        // Schedule any products for expiration?
                        foreach ($this->getOrder()->items()->getItems() as $item) {
                            if ($item->getProduct()->data()->durability != null) {
                                ExpireCustomerProductTask::schedule($this->getOrder(), $item, $this);
                            }
                        }

                        $this->executeActions(Action::PURCHASE);
                    } else {
                        // Handle subscription payment
                        // Is this a first or renewal payment?
                        $subscription_payments = DB::getInstance()->query('SELECT count(*) AS c FROM nl2_store_payments WHERE subscription_id = ?', [$this->data()->subscription_id])->first()->c;
                        if ($subscription_payments == 1) {
                            $this->executeActions(Action::PURCHASE);
                        } else {
                            $this->executeActions(Action::RENEWAL);
                        }
                    }

                    EventHandler::executeEvent(new PaymentCompletedEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                break;
                case self::REFUNDED:
                    // Payment refunded
                    $insert_array = [
                        'status_id' => 2,
                        'created' => date('U'),
                        'last_updated' => date('U')
                    ];

                    $this->create(array_merge($insert_array, $extra_data));
                    OrderCredit::refundForOrder((int) $this->data()->order_id, (int) $this->data()->id);

                    if ($this->hasOtherCompletedOrderPayment()) {
                        return;
                    }

                    $this->executeActions(Action::REFUND);

                    EventHandler::executeEvent(new PaymentRefundedEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                    break;
                case self::REVERSED:
                    // Payment reversed
                    $insert_array = [
                        'status_id' => 3,
                        'created' => date('U'),
                        'last_updated' => date('U')
                    ];

                    $this->create(array_merge($insert_array, $extra_data));

                    $this->executeActions(Action::CHANGEBACK);

                    EventHandler::executeEvent(new PaymentReversedEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                    break;
                case self::DENIED:
                    // Payment denied
                    $insert_array = [
                        'status_id' => 4,
                        'created' => date('U'),
                        'last_updated' => date('U')
                    ];

                    $this->create(array_merge($insert_array, $extra_data));
                    OrderCredit::releaseForOrder((int) $this->data()->order_id);

                    EventHandler::executeEvent(new PaymentDeniedEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                    break;
            }
        }
    }

    /**
     * A one-time order may receive more than one provider callback, but its
     * fulfilment actions must only run for the first completed payment.
     */
    private function hasOtherCompletedOrderPayment(): bool {
        if (!$this->exists() || $this->data()->subscription_id !== null) {
            return false;
        }

        return $this->_db->query(
            'SELECT id FROM nl2_store_payments WHERE order_id = ? AND status_id = 1 AND id <> ? LIMIT 1',
            [(int) $this->data()->order_id, (int) $this->data()->id]
        )->count() > 0;
    }

    /**
     * Execute all actions for the called trigger all products or specific product.
     *
     * @param int $type Action type.
     * @param Item|null $item execute actions from specific item if isset.
     */
    public function executeActions(int $type, Item $item = null): void {
        $order = $this->getOrder();

        if ($item) {
            foreach ($item->getProduct()->getActions($type) as $action) {
                if ($action->data()->product_id != null || $action->data()->each_product)
                    $action->execute($order, $item, $this);
            }
        } else {
            foreach ($order->items()->getItems() as $item) {
                $product = $item->getProduct();

                if ($product->data()->deleted == 0) {
                    foreach ($product->getActions($type) as $action) {
                        if ($action->data()->product_id != null || $action->data()->each_product)
                            $action->execute($order, $item, $this);
                    }
                }
            }
        }

        // Global actions without assigning product for products with
        $actions = ActionsHandler::getInstance()->getActions(null, $type);
        foreach ($actions as $action) {
            if (!$action->data()->each_product)
                $action->execute($order, $item ?? $this->getOrder()->items()->getItems()[0], $this);
        }
    }

    /**
     * Delete any pending actions for all products or specific product.
     *
     * @param int|null $product_id Delete pending actions from specific product if isset.
     */
    public function deletePendingActions(int $product_id = null): void {
        if ($product_id) {
            $this->_db->query('DELETE FROM nl2_store_pending_actions WHERE order_id = ? AND status = 0 AND product_id = ?', [$this->data()->order_id, $product_id])->results();
        } else {
            $this->_db->query('DELETE FROM nl2_store_pending_actions WHERE order_id = ? AND status = 0', [$this->data()->order_id])->results();
        }
    }

    public function getStatusHtml(): string {
        switch ($this->data()->status_id) {
            case 0;
                $status = '<span class="badge badge-warning">' . Store::getLanguage()->get('general', 'pending') .'</span>';
            break;
            case 1;
                $status = '<span class="badge badge-success">' . Store::getLanguage()->get('general', 'completed') .'</span>';
            break;
            case 2;
                $status = '<span class="badge badge-primary">' . Store::getLanguage()->get('general', 'refunded') .'</span>';
            break;
            case 3;
                $status = '<span class="badge badge-info">' . Store::getLanguage()->get('general', 'reversed') .'</span>';
            break;
            case 4;
                $status = '<span class="badge badge-danger">' . Store::getLanguage()->get('general', 'denied') .'</span>';
            break;
            default:
                $status = '<span class="badge badge-danger">' . Store::getLanguage()->get('general', 'unknown') .'</span>';
            break;
        }

        return $status;
    }

    /**
     * Get gateway used for this payment
     *
     * @return null|GatewayBase Gateway used for this payment.
     */
    public function getGateway(): ?GatewayBase {
        if ($this->exists() && $this->data()->gateway_id != 0) {
            return Gateways::getInstance()->get($this->data()->gateway_id);
        }

        return null;
    }

    public function delete(): bool {
        if ($this->exists()) {
            $this->_db->query('DELETE FROM `nl2_store_payment_refunds` WHERE `payment_id` = ?', [$this->data()->id]);
            $this->_db->query('DELETE FROM `nl2_store_payments` WHERE `id` = ?', [$this->data()->id]);
            $this->_db->query('DELETE FROM `nl2_store_orders` WHERE `id` = ?', [$this->data()->order_id]);
            $this->_db->query('DELETE FROM `nl2_store_orders_products` WHERE `order_id` = ?', [$this->data()->order_id]);
            $this->_db->query('DELETE FROM `nl2_store_orders_products_fields` WHERE `order_id` = ?', [$this->data()->order_id]);

            return true;
        }

        return false;
    }
}
