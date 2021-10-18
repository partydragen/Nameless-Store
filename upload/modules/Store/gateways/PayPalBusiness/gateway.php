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

class PayPal_Business_Gateway extends GatewayBase {
    public function __construct(){
        $id = 2;
        $name = 'PayPalBusiness';
        $settings = ROOT_PATH . '/modules/Store/gateways/PayPalBusiness/gateway_settings/settings.php';
            
        parent::__construct($id, $name, $settings);
    }
}

$gateway = new PayPal_Business_Gateway();