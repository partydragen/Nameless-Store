<?php
/*
 *	Made by Partydragen
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
    
    public function __construct($value, $field = 'id') {
        $this->_db = DB::getInstance();
        
        $data = $this->_db->get('store_payments', array($field, '=', $value));
        if ($data->count()) {
            $this->_data = $data->first();
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
     * Get the currently logged in payment's data.
     *
     * @return object This payment's data.
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
                    
                    $this->addPendingCommands(1);
                break;
                case 'REFUNDED':
                    // Payment refunded
                    $update_array = array(
                        'status_id' => 2,
                        'last_updated' => date('U')
                    );
                    
                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));
                    
                    $this->deletePendingCommands();
                    $this->addPendingCommands(2);
                break;
                case 'REVERSED':
                    // Payment reversed
                    $update_array = array(
                        'status_id' => 3,
                        'last_updated' => date('U')
                    );
                    
                    $this->_db->update('store_payments', $this->data()->id, array_merge($update_array, $extra_data));
                    
                    $this->deletePendingCommands();
                    $this->addPendingCommands(3);
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
                    
                    $this->addPendingCommands(1);
                break;
            }
        }
    }
    
    /**
     * Add commands from products to pending commands
     */
    public function addPendingCommands($type) {
        $products = $this->_db->query('SELECT product_id, player_id FROM nl2_store_orders_products INNER JOIN nl2_store_orders ON order_id=nl2_store_orders.id INNER JOIN nl2_store_products ON nl2_store_products.id=product_id WHERE order_id = ?', array($this->data()->order_id))->results();
        foreach($products as $product) {
            $commands = $this->_db->query('SELECT * FROM nl2_store_products_commands WHERE product_id = ? AND type = ? ORDER BY `order`', array($product->product_id, $type))->results();
            foreach($commands as $command) {
                $this->_db->insert('store_pending_commands', array(
                    'order_id' => $this->data()->order_id,
                    'command_id' => $command->id,
                    'product_id' => $product->product_id,
                    'player_id' => $product->player_id,
                    'server_id' => $command->server_id,
                    'type' => $command->type,
                    'command' => $command->command,
                    'require_online' => $command->require_online,
                    'order' => $command->order,
                ));
            }
        }
    }
    
    /**
     * Delete any pending commands
     */
    public function deletePendingCommands() {
        $this->_db->createQuery('DELETE FROM nl2_store_pending_commands WHERE order_id = ? AND status = 0', array($this->data()->order_id))->results();
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