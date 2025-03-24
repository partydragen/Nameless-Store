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

// Handle return from gateway
$gateway = Gateways::getInstance()->get($_GET['gateway']);
if ($gateway) {
    if ($gateway->handleReturn()) {
        // Success 
        ShoppingCart::getInstance()->clear();
        Redirect::to(URL::build(Store::getStorePath() . '/checkout/', 'do=complete'));
    } else {
        // Canceled or failed
        Redirect::to(URL::build(Store::getStorePath() . '/checkout/', 'do=cancel'));
    }
} else {
    die('Invalid gateway');
}