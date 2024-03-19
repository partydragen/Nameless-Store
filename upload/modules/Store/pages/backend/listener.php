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

// Handle listener from gateway
$gateways = Gateways::getInstance();
$gateway = $gateways->get($_GET['gateway']);
if ($gateway) {
    $gateway->handleListener();
} else {
    die('Invalid gateway');
}