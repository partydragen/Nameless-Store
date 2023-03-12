<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Gateway Processer
 */

if (!isset($_GET['gateway'])) {
    die('Invalid');
}

require_once(ROOT_PATH . '/modules/Store/config.php');
require_once(ROOT_PATH . '/modules/Store/core/frontend_init.php');
$gateways = Gateways::getInstance();

// Load Store config
if (isset($store_conf) && is_array($store_conf)) {
    $GLOBALS['store_config'] = $store_conf;
}

// Handle return from gateway
$gateway = $gateways->get($_GET['gateway']);
if ($gateway) {
    if ($gateway->handleReturn()) {
        // Success 
        $shopping_cart->clear();
        Redirect::to(URL::build($store->getStoreURL() . '/checkout/', 'do=complete'));
    } else {
        // Canceled or failed
        Redirect::to(URL::build($store->getStoreURL() . '/checkout/', 'do=cancel'));
    }
} else {
    die('Invalid gateway');
}