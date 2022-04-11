<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module
 */

class Gateways {
    private $_gateways;

    // Constructor, connect to database
    public function __construct() {
        $directories = glob(ROOT_PATH . '/modules/Store/gateways/*' , GLOB_ONLYDIR);
        foreach ($directories as $directory) {
            $folders = explode('/', $directory);
            
            if (file_exists(ROOT_PATH . '/modules/Store/gateways/' . $folders[count($folders) - 1] . '/gateway.php')) {
                require_once(ROOT_PATH . '/modules/Store/gateways/' . $folders[count($folders) - 1] . '/gateway.php');

                $this->_gateways[$gateway->getName()] = $gateway;
            }
        }
    }
    
    // Get all gateways
    public function getAll() {
        return $this->_gateways;
    }
    
    // Get gateway by name
    public function get($name) {
        if (array_key_exists($name, $this->_gateways)) {
            return $this->_gateways[$name];
        }

        return null;
    }
}