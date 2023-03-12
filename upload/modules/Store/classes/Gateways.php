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

class Gateways extends Instanceable {
    private static array $_gateways;

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
    public function get($value): ?GatewayBase {
        if (!is_numeric($value)) {
            // Get gateway by name
            if (array_key_exists($value, $this->_gateways)) {
                return $this->_gateways[$value];
            }
        } else {
            // Get gateway by id
            foreach ($this->_gateways as $gateway) {
                if ($gateway->getId() == $value) {
                    return $gateway;
                }
            }
        }

        return null;
    }
}