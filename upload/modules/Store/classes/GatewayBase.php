<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module
 */

class GatewayBase {
    private $_id,
            $_name,
            $_displayname,
            $_enabled,
            $_settings;

    // Constructor, connect to database
    public function __construct($name, $settings) {
        $this->_name = $name;
        $this->_settings = $settings;
        
        $db = DB::getInstance();
        $gateway_query = $db->query('SELECT id, displayname, enabled FROM nl2_store_gateways WHERE `name` = ?', array($name))->first();
        if($gateway_query) {
            $this->_id = $gateway_query->id;
            $this->_displayname = $gateway_query->displayname;
            $this->_enabled = $gateway_query->enabled;
        } else {
            $gateway_query = $db->createQuery('INSERT INTO `nl2_store_gateways` (`name`, `displayname`, `enabled`) VALUES (?, ?, ?)', array($name, $name, 0));
            $this->_id = $db->lastId();
            $this->_displayname = $name;
            $this->_enabled = 0;
        }
    }
    
    public function getId() {
        return $this->_id;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function getDisplayname() {
        return $this->_displayname;
    }
    
    public function isEnabled() {
        return $this->_enabled;
    }
    
    public function getSettings() {
        return $this->_settings;
    }
}