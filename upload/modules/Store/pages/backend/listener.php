<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Gateway Listener
 */

if (!isset($_GET['gateway'])) {
    die('Invalid');
}

require_once(ROOT_PATH . '/modules/Store/config.php');
// Load Store config
if (isset($store_conf) && is_array($store_conf)) {
    $GLOBALS['store_config'] = $store_conf;
}

$gateways = new Gateways();
$gateway = $gateways->get($_GET['gateway']);
if ($gateway) {
    // Load gateway listener
    require_once(ROOT_PATH . '/modules/Store/gateways/'.$gateway->getName().'/listener.php');
} else {
    die('Invalid gateway');
}