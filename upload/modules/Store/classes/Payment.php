<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Payment class
 */

class Payment {
    
    private $_db,
            $_data,
            $_order;
    
    public function __construct($value = null, $field = 'id') {
        $this->_db = DB::getInstance();
        
        if($value != null) {
            $data = $this->_db->get('store_payments', array($field, '=', $value));
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
    public function update($fields = array(), $id = null) {
        if (!$this->_db->update('store_payments', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating payment');
        }
    }

    /**
     * Create a new payment.
     *
     * @param array $fields Column names and values to insert to database.
     */
    public function create($fields = array()) {
        if (!$this->_db->insert('store_payments', $fields)) {
            throw new Exception('There was a problem registering the payment');
        }
        $last_id = $this->_db->lastId();
        
        $data = $this->_db->get('store_payments', array('id', '=', $last_id));
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
    public function exists() {
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
        if($this->_order == null) {
            $this->_order = new Order($this->data()->order_id);
        }
        
        return $this->_order;
    }
    
    /**
     * Handle payment event change
     */
    public function handlePaymentEvent($event, $extra_data) {
        if($this->exists()) {
            // Payment exist, Continue with event handling
            switch($event) {
                case 'PENDING':
                    // Payment pending
                    $update_array = array(
                        'status_id' => 0,
                        'last_updated' => date('U')
                    );
                    
                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));
                break;
                case 'COMPLETED':
                    // Payment completed
                    $update_array = array(
                        'status_id' => 1,
                        'last_updated' => date('U')
                    );
                    
                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));
                    
                    $this->addPendingActions(1);
                break;
                case 'REFUNDED':
                    // Payment refunded
                    $update_array = array(
                        'status_id' => 2,
                        'last_updated' => date('U')
                    );
                    
                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));
                    
                    $this->deletePendingActions();
                    $this->addPendingActions(2);
                break;
                case 'REVERSED':
                    // Payment reversed
                    $update_array = array(
                        'status_id' => 3,
                        'last_updated' => date('U')
                    );
                    
                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));
                    
                    $this->deletePendingActions();
                    $this->addPendingActions(3);
                break;
                case 'DENIED':
                    // Payment denied
                    $update_array = array(
                        'status_id' => 4,
                        'last_updated' => date('U')
                    );
                    
                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));
                break;
                default:
                    // Invalid event type, Throw error
                    throw new Exception('Invalid payment event');
                break;
            }
        } else {
            // Register payment
            switch($event) {
                case 'PENDING':
                    // Payment pending
                    $insert_array = array(
                        'status_id' => 0,
                        'created' => date('U'),
                        'last_updated' => date('U')
                    );
                    
                    $this->create(array_merge($insert_array, $extra_data));
                break;
                case 'COMPLETED':
                    // Payment completed
                    $insert_array = array(
                        'status_id' => 1,
                        'created' => date('U'),
                        'last_updated' => date('U')
                    );
                    
                    $this->create(array_merge($insert_array, $extra_data));
                    
                    $this->addPendingActions(1);
                break;
            }
        }
    }
    
    /**
     * Add actions from products to pending actions
     */
    public function addPendingActions($type) {
        
        
        $products = $this->_db->query('SELECT product_id, player_id FROM nl2_store_orders_products INNER JOIN nl2_store_orders ON order_id=nl2_store_orders.id INNER JOIN nl2_store_products ON nl2_store_products.id=product_id WHERE order_id = ?', array($this->data()->order_id))->results();
        foreach($products as $product) {
            
            $product = new Product($product->product_id);
            if($product->exists() && $product->data()->deleted == 0) {
                
                // Foreach all actions that belong to this product
                foreach($product->getActions() as $action) {
                    if($action->data()->type != $type) {
                        continue;
                    }
                    
                    $connections = ($action->data()->own_connections ? $action->getConnections() : $product->getConnections());
                    foreach($connections as $connection) {
                        $this->_db->insert('store_pending_actions', array(
                            'order_id' => $this->data()->order_id,
                            'action_id' => $action->data()->id,
                            'product_id' => $product->data()->id,
                            'player_id' => $product->data()->player_id,
                            'connection_id' => $connection->id,
                            'type' => $action->data()->type,
                            'command' => $action->data()->command,
                            'require_online' => $action->data()->require_online,
                            'order' => $action->data()->order,
                        ));
                    }
                }
            }
        }
    }
    
    /**
     * Delete any pending actions
     */
    public function deletePendingActions() {
        $this->_db->createQuery('DELETE FROM nl2_store_pending_actions WHERE order_id = ? AND status = 0', array($this->data()->order_id))->results();
    }
    
    public function getStatusHtml() {
        $status = '<span class="badge badge-danger">Unknown</span>';
        
        switch($this->data()->status_id) {
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
            default:
                $status = '<span class="badge badge-danger">Unknown</span>';
            break;
        }
        
        return $status;
    }
}