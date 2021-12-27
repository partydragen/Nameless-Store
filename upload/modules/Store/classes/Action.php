<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Action class
 */

class Action {
    
    private $_db,
            $_data,
            $_connections;
            
    public function __construct($value = null, $field = 'id') {
        $this->_db = DB::getInstance();
        
        if($value != null) {
            $data = $this->_db->get('store_products_actions', array($field, '=', $value));
            if ($data->count()) {
                $this->_data = $data->first();
            }
        }
    }
    
    /**
     * Update a action data in the database.
     *
     * @param array $fields Column names and values to update.
     */
    public function update($fields = array()) {
        if (!$this->_db->update('store_products_actions', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating action');
        }
    }
    
    /**
     * Does this action exist?
     *
     * @return bool Whether the action exists (has data) or not.
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
     * Get the action data.
     *
     * @return object This action data.
     */
    public function data() {
        return $this->_data;
    }

    /**
     * Get the action connections.
     *
     * @return array Their connections.
     */
    public function getConnections() {
        if($this->_connections == null) {
            $this->_connections = array();
            
            $connections_query = $this->_db->query('SELECT nl2_store_connections.* FROM nl2_store_products_connections INNER JOIN nl2_store_connections ON connection_id = nl2_store_connections.id WHERE action_id = ?', array($this->data()->id));
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
     * Add a connection to this action.
     *
     * @return bool True on success, false if action already have it.
     */
    public function addConnection($connection_id) {
        if (array_key_exists($connection_id, $this->getConnections())) {
            return false;
        }
        
        $this->_db->createQuery('INSERT INTO `nl2_store_products_connections` (`product_id`, `action_id`, `connection_id`) VALUES (?, ?, ?)',
            array(
                $this->data()->product_id,
                $this->data()->id,
                $connection_id
            )
        );
    }

    /**
     * Remove a connection to this action.
     *
     * @return bool Returns false if they did not have this connection
     */
    public function removeConnection($connection_id) {
        if (!array_key_exists($connection_id, $this->getConnections())) {
            return false;
        }
        
        $this->_db->createQuery('DELETE FROM `nl2_store_products_connections` WHERE `action_id` = ? AND `connection_id` = ?',
            array(
                $this->data()->id,
                $connection_id
            )
        );
    }
    
    public function delete() {
        if($this->exists()) {
            $this->_db->createQuery('DELETE FROM `nl2_store_products_actions` WHERE `id` = ?', array($this->data()->id));
            $this->_db->createQuery('DELETE FROM `nl2_store_products_connections` WHERE `action_id` = ?', array($this->data()->id));
            $this->_db->createQuery('DELETE FROM `nl2_store_pending_actions` WHERE `action_id` = ?', array($this->data()->id));
            
            return true;
        }
        
        return false;
    }
}