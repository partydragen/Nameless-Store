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
$gateways = new Gateways();

// Load Store config
if (isset($store_conf) && is_array($store_conf)) {
    $GLOBALS['store_config'] = $store_conf;
}

// Get variables from cache
$cache->setCache('store_settings');
if ($cache->isCached('store_url')) {
    $store_url = Output::getClean(rtrim($cache->retrieve('store_url'), '/'));
} else {
    $store_url = '/store';
}

$gateway = $gateways->get($_GET['gateway']);
if ($gateway) {
    // Load gateway process
    require_once(ROOT_PATH . '/modules/Store/gateways/'.$gateway->getName().'/process.php');
} else {
    die('Invalid gateway');
}