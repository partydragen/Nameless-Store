<?php
/*
 *  Made by Partydragen
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
    public function __construct($value = null, $field = 'id') {
        $this->_db = DB::getInstance();
        
        if($value != null) {
            $data = $this->_db->get('store_orders', array($field, '=', $value));
            if ($data->count()) {
                $this->_data = $data->first();
            }
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
        return $this->_db->query('SELECT nl2_store_products.* FROM nl2_store_orders_products INNER JOIN nl2_store_products ON nl2_store_products.id=product_id WHERE order_id = ?', array($this->data()->id))->results();
    }
    
    public function create($user, $player, $items) {
        $this->_db->insert('store_orders', array(
            'user_id' => $user->data() ? $user->data()->id : null,
            'player_id' => $player->data() ? $player->data()->id : null,
            'created' => date('U'),
            'ip' => $user->getIP(),
        ));
        $last_id = $this->_db->lastId();
        
        // Register products to order
        foreach($items as $item) {
            $this->_db->insert('store_orders_products', array(
                'order_id' => $last_id,
                'product_id' => $item['id']
            ));
        }
        
        // Load order
        $data = $this->_db->get('store_orders', array('id', '=', $last_id));
        if ($data->count()) {
            $this->_data = $data->first();
        }
    }
}