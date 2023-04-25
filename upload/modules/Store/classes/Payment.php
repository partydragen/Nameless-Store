<?php
/**
 * Payment class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.1.0
 * @license MIT
 */
class Payment {
    public const PENDING = 'PENDING';
    public const COMPLETED = 'COMPLETED';
    public const REFUNDED = 'REFUNDED';
    public const REVERSED = 'REVERSED';
    public const DENIED = 'DENIED';

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
     * @return PaymentData This payment data.
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

    /**
     * Handle payment event change
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
                    $update_array = [
                        'status_id' => 1,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

                    $this->executeAllActions(Action::PURCHASE);

                    EventHandler::executeEvent(new PaymentCompletedEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                break;
                case self::REFUNDED:
                    // Payment refunded
                    $update_array = [
                        'status_id' => 2,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

                    $this->deletePendingActions();
                    $this->executeAllActions(Action::REFUND);

                    EventHandler::executeEvent(new PaymentRefundedEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                break;
                case self::REVERSED:
                    // Payment reversed
                    $update_array = [
                        'status_id' => 3,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

                    $this->deletePendingActions();
                    $this->executeAllActions(Action::CHANGEBACK);

                    EventHandler::executeEvent(new PaymentReversedEvent(
                        $this,
                        $this->getOrder(),
                        $this->getOrder()->customer(),
                        $this->getOrder()->recipient()
                    ));
                break;
                case self::DENIED:
                    // Payment denied
                    $update_array = [
                        'status_id' => 4,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

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

                    $this->executeAllActions(Action::PURCHASE);

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

                    $this->executeAllActions(Action::REFUND);

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

                    $this->executeAllActions(Action::CHANGEBACK);

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
     * Execute all actions for the called trigger for each product in this order
     */
    public function executeAllActions($type): void {
        $order = $this->getOrder();

        foreach ($order->getProducts() as $product) {
            if ($product->data()->deleted == 0) {
                foreach ($product->getActions($type) as $action) {
                    $action->execute($order, $product, $this);
                }
            }
        }
    }

    /**
     * Delete any pending actions
     */
    public function deletePendingActions(): void {
        $this->_db->query('DELETE FROM nl2_store_pending_actions WHERE order_id = ? AND status = 0', [$this->data()->order_id])->results();
    }

    public function getStatusHtml(): string {
        $status = '<span class="badge badge-danger">Unknown</span>';

        switch ($this->data()->status_id) {
            case 0;
                $status = '<span class="badge badge-warning">Pending</span>';
            break;
            case 1;
                $status = '<span class="badge badge-success">Complete</span>';
            break;
            case 2;
                $status = '<span class="badge badge-primary">Refunded</span>';
            break;
            case 3;
                $status = '<span class="badge badge-info">Changeback</span>';
            break;
            case 4;
                $status = '<span class="badge badge-danger">Denied</span>';
            break;
            default:
                $status = '<span class="badge badge-danger">Unknown</span>';
            break;
        }

        return $status;
    }

    /**
     * Get gateway used for this payment
     *
     * @return GatewayBase Gateway used for this payment.
     */
    public function getGateway(): ?GatewayBase {
        if ($this->exists() && $this->data()->gateway_id != 0) {
            return Gateways::getInstance()->get($this->data()->gateway_id);
        }

        return null;
    }

    public function delete(): bool {
        if ($this->exists()) {
            $this->_db->query('DELETE FROM `nl2_store_payments` WHERE `id` = ?', [$this->data()->id]);
            $this->_db->query('DELETE FROM `nl2_store_orders` WHERE `id` = ?', [$this->data()->order_id]);
            $this->_db->query('DELETE FROM `nl2_store_orders_products` WHERE `order_id` = ?', [$this->data()->order_id]);
            $this->_db->query('DELETE FROM `nl2_store_orders_products_fields` WHERE `order_id` = ?', [$this->data()->order_id]);

            return true;
        }

        return false;
    }
}