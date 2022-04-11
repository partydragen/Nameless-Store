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

class PayPal_Gateway extends GatewayBase {
    public function __construct() {
        $name = 'PayPal';
        $settings = ROOT_PATH . '/modules/Store/gateways/PayPal/gateway_settings/settings.php';
            
        parent::__construct($name, $settings);
    }

    public function processOrder(Order $order) {

    }

    public function onCancel() {

    }

    public function onReturn() {

    }

    public function onWebhookCall() {

    }
}

$gateway = new PayPal_Gateway();