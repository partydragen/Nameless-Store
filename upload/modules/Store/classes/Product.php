<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Product class
 */

class Product {
    
    private $_db,
            $_data,
            $_connections,
            $_fields,
            $_actions;
            
    public function __construct($value = null, $field = 'id') {
        $this->_db = DB::getInstance();
        
        if($value != null) {
            $data = $this->_db->get('store_products', array($field, '=', $value));
            if ($data->count()) {
                $this->_data = $data->first();
            }
        }
    }
    
    /**
     * Update a product data in the database.
     *
     * @param array $fields Column names and values to update.
     */
    public function update($fields = array()) {
        if (!$this->_db->update('store_products', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating product');
        }
    }
    
    /**
     * Does this product exist?
     *
     * @return bool Whether the product exists (has data) or not.
     */
    public function exists() {
        return (!empty($this->_data));
    }
    
    /**
     * Set action data to load it as pre loaded
     */
    public function setData($data) {
        $this->_data = $data;
    }
    
    /**
     * Get the product data.
     *
     * @return object This product data.
     */
    public function data() {
        return $this->_data;
    }

    /**
     * Get the product connections.
     *
     * @return array Their connections.
     */
    public function getConnections() {
        if($this->_connections == null) {
            $this->_connections = array();
            
            $connections_query = $this->_db->query('SELECT nl2_store_connections.* FROM nl2_store_products_connections INNER JOIN nl2_store_connections ON connection_id = nl2_store_connections.id WHERE product_id = ? AND action_id IS NULL', array($this->data()->id));
            if ($connections_query->count()) {
                $connections_query = $connections_query->results();
                foreach ($connections_query as $item) {
                    $this->_connections[$item->id] = $item;
                }
            }
        }
        
        return $this->_connections;
    }

    /**
     * Add a connection to this product.
     *
     * @return bool True on success, false if product already have it.
     */
    public function addConnection($connection_id) {
        if (array_key_exists($connection_id, $this->getConnections())) {
            return false;
        }
        
        $this->_db->createQuery('INSERT INTO `nl2_store_products_connections` (`product_id`, `connection_id`) VALUES (?, ?)',
            array(
                $this->data()->id,
                $connection_id
            )
        );
    }

    /**
     * Remove a connection to this product.
     *
     * @return bool Returns false if they did not have this connection
     */
    public function removeConnection($connection_id) {
        if (!array_key_exists($connection_id, $this->getConnections())) {
            return false;
        }
        
        $this->_db->createQuery('DELETE FROM `nl2_store_products_connections` WHERE `product_id` = ? AND `connection_id` = ? AND action_id IS NULL',
            array(
                $this->data()->id,
                $connection_id
            )
        );
    }
    
    /**
     * Get the product fields.
     *
     * @return array Their fields.
     */
    public function getFields() {
        if($this->_fields == null) {
            $this->_fields = array();
            
            $fields_query = $this->_db->query('SELECT nl2_store_fields.* FROM nl2_store_products_fields INNER JOIN nl2_store_fields ON field_id = nl2_store_fields.id WHERE product_id = ? AND deleted = 0', array($this->data()->id));
            if ($fields_query->count()) {
                $fields_query = $fields_query->results();
                foreach ($fields_query as $field) {
                    $this->_fields[$field->id] = $field;
                }
            }
        }
        
        return $this->_fields;
    }

    /**
     * Add a field to this product.
     *
     * @return bool True on success, false if product already have it.
     */
    public function addField($field_id) {
        if (array_key_exists($field_id, $this->getFields())) {
            return false;
        }
        
        $this->_db->createQuery('INSERT INTO `nl2_store_products_fields` (`product_id`, `field_id`) VALUES (?, ?)',
            array(
                $this->data()->id,
                $field_id
            )
        );
    }

    /**
     * Remove a field to this product.
     *
     * @return bool Returns false if they did not have this field
     */
    public function removeField($field_id) {
        if (!array_key_exists($field_id, $this->getFields())) {
            return false;
        }
        
        $this->_db->createQuery('DELETE FROM `nl2_store_products_fields` WHERE `product_id` = ? AND `field_id` = ?',
            array(
                $this->data()->id,
                $field_id
            )
        );
    }
    
    public function getActions() {
        if($this->_actions == null) {
            $this->_connections = array();
            
            $actions = $this->_db->query('SELECT * FROM nl2_store_products_actions WHERE product_id = ? ORDER BY `order` ASC', array($this->data()->id));
            if ($actions->count()) {
                $actions = $actions->results();
                
                foreach($actions as $data) {
                    $action = new Action();
                    $action->setData($data);
                    
                    $this->_actions[$action->data()->id] = $action;
                }
            }
        }
        
        return $this->_actions;
    }
    
    public function delete() {
        if($this->exists()) {
            $this->update(array(
                'deleted' => date('U')
            ));
            
            $this->_db->createQuery('DELETE FROM `nl2_store_pending_actions` WHERE `product_id` = ?', array($this->data()->id));
            
            return true;
        }
        
        return false;
    }
}