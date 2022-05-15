<?php
/**
 * Payment class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.0-pr12
 * @license MIT
 */
class Payment {

    private $_db,
            $_data,
            $_order;

    public function __construct($value = null, $field = 'id') {
        $this->_db = DB::getInstance();
        
        if ($value != null) {
            $data = $this->_db->get('store_payments', [$field, '=', $value]);
            if ($data->count()) {
                $this->_data = $data->first();
            }
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
            $this->_data = $data->first();
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
     * @return object This payment data.
     */
    public function data() {
        return $this->_data;
    }

    public function getOrder() {
        if ($this->_order == null) {
            $this->_order = new Order($this->data()->order_id);
        }
 
        return $this->_order;
    }

    /**
     * Handle payment event change
     */
    public function handlePaymentEvent($event, $extra_data) {
        $store_language = new Language(ROOT_PATH . '/modules/Store/language', LANGUAGE);

        if ($this->exists()) {
            // Payment exist, Continue with event handling

            // Temp solution
            $username = 'Unknown';
            if ($this->getOrder()->data()->player_id != null) {
                $player = new Player($this->getOrder()->data()->player_id);

                $username = $player->getUsername();
            } else if ($this->getOrder()->data()->user_id != null) {
                $user = new User($this->getOrder()->data()->user_id);

                $username = $user->getDisplayname(true);
            }

            switch ($event) {
                case 'PENDING':
                    // Payment pending
                    $update_array = [
                        'status_id' => 0,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

                    HookHandler::executeEvent('paymentPending', [
                        'event' => 'paymentPending',
                        'order_id' => $this->data()->order_id,
                        'payment_id' => $this->data()->id,
                        'username' => $username,
                        'content_full' => str_replace(['{x}'], [$username], $store_language->get('general', 'pending_payment_text')),
                    ]);
                break;
                case 'COMPLETED':
                    // Payment completed
                    $update_array = [
                        'status_id' => 1,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

                    $this->executeAllActions(1);

                    HookHandler::executeEvent('paymentCompleted', [
                        'event' => 'paymentCompleted',
                        'username' => $username,
                        'content_full' => str_replace(['{x}', '{y}'], [$username, $this->getOrder()->getDescription()], $store_language->get('general', 'completed_payment_text')),
                        'order_id' => $this->data()->order_id,
                        'payment_id' => $this->data()->id,
                    ]);
                break;
                case 'REFUNDED':
                    // Payment refunded
                    $update_array = [
                        'status_id' => 2,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

                    $this->deletePendingActions();
                    $this->executeAllActions(2);

                    HookHandler::executeEvent('paymentRefunded', [
                        'event' => 'paymentRefunded',
                        'order_id' => $this->data()->order_id,
                        'payment_id' => $this->data()->id,
                        'username' => $username,
                        'content_full' => str_replace(['{x}'], [$username], $store_language->get('general', 'refunded_payment_text')),
                    ]);
                break;
                case 'REVERSED':
                    // Payment reversed
                    $update_array = [
                        'status_id' => 3,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

                    $this->deletePendingActions();
                    $this->executeAllActions(3);

                    HookHandler::executeEvent('paymentReversed', [
                        'event' => 'paymentReversed',
                        'order_id' => $this->data()->order_id,
                        'payment_id' => $this->data()->id,
                        'username' => $username,
                        'content_full' => str_replace(['{x}'], [$username], $store_language->get('general', 'reversed_payment_text')),
                    ]);
                break;
                case 'DENIED':
                    // Payment denied
                    $update_array = [
                        'status_id' => 4,
                        'last_updated' => date('U')
                    ];

                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));

                    HookHandler::executeEvent('paymentDenied', [
                        'event' => 'paymentDenied',
                        'order_id' => $this->data()->order_id,
                        'payment_id' => $this->data()->id,
                        'username' => $username,
                        'content_full' => str_replace(['{x}'], [$username], $store_language->get('general', 'denied_payment_text')),
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
                case 'PENDING':
                    // Payment pending
                    $insert_array = [
                        'status_id' => 0,
                        'created' => date('U'),
                        'last_updated' => date('U')
                    ];

                    $this->create(array_merge($insert_array, $extra_data));

                    // Temp solution
                    $username = 'Unknown';
                    if ($this->getOrder()->data()->player_id != null) {
                        $player = new Player($this->getOrder()->data()->player_id);

                        $username = $player->getUsername();
                    } else if ($this->getOrder()->data()->user_id != null) {
                        $user = new User($this->getOrder()->data()->user_id);

                        $username = $user->getDisplayname(true);
                    }

                    HookHandler::executeEvent('paymentPending', [
                        'event' => 'paymentPending',
                        'order_id' => $this->data()->order_id,
                        'payment_id' => $this->data()->id,
                        'username' => $username,
                        'content_full' => str_replace(['{x}'], [$username], $store_language->get('general', 'pending_payment_text')),
                    ]);
                break;
                case 'COMPLETED':
                    // Payment completed
                    $insert_array = [
                        'status_id' => 1,
                        'created' => date('U'),
                        'last_updated' => date('U')
                    ];

                    $this->create(array_merge($insert_array, $extra_data));

                    // Temp solution
                    $username = 'Unknown';
                    if ($this->getOrder()->data()->player_id != null) {
                        $player = new Player($this->getOrder()->data()->player_id);

                        $username = $player->getUsername();
                    } else if ($this->getOrder()->data()->user_id != null) {
                        $user = new User($this->getOrder()->data()->user_id);

                        $username = $user->getDisplayname(true);
                    }

                    $this->executeAllActions(1);

                    HookHandler::executeEvent('paymentCompleted', [
                        'event' => 'paymentCompleted',
                        'order_id' => $this->data()->order_id,
                        'payment_id' => $this->data()->id,
                        'username' => $username,
                        'content_full' => str_replace(['{x}', '{y}'], [$username, $this->getOrder()->getDescription()], $store_language->get('general', 'completed_payment_text')),
                    ]);
                break;
            }
        }
    }

    /**
     * Execute all actions for the called trigger for each product in this order
     */
    public function executeAllActions($type) {
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
    public function deletePendingActions() {
        $this->_db->createQuery('DELETE FROM nl2_store_pending_actions WHERE order_id = ? AND status = 0', [$this->data()->order_id])->results();
    }

    public function getStatusHtml() {
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

    public function delete() {
        if ($this->exists()) {
            $this->_db->createQuery('DELETE FROM `nl2_store_payments` WHERE `id` = ?', [$this->data()->id]);
            $this->_db->createQuery('DELETE FROM `nl2_store_orders` WHERE `id` = ?', [$this->data()->order_id]);
            $this->_db->createQuery('DELETE FROM `nl2_store_orders_products` WHERE `order_id` = ?', [$this->data()->order_id]);
            $this->_db->createQuery('DELETE FROM `nl2_store_orders_products_fields` WHERE `order_id` = ?', [$this->data()->order_id]);

            return true;
        }

        return false;
    }
}