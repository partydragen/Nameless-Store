<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Order class
 */
 
class Order {

    private $_db,
            $_data;
    
    // Constructor
    public function __construct($value, $field = 'id') {
        $this->_db = DB::getInstance();
        
        $data = $this->_db->get('store_orders', array($field, '=', $value));
        if ($data->count()) {
            $this->_data = $data->first();
        }
    }
    
    /**
     * Does this payment exist?
     *
     * @return bool Whether the order exists (has data) or not.
     */
    public function exists() {
        return (!empty($this->_data));
    }
    
    /**
     * @return object This order's data.
     */
    public function data() {
        return $this->_data;
    }
    
    public function getProducts() {
        return $this->_db->query('SELECT * FROM nl2_store_orders_products WHERE order_id = ?', array($this->data()->id))->results();
    }
}