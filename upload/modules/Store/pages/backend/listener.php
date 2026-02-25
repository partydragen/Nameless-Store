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
$gateway = Gateways::getInstance()->get($_GET['gateway']);
if ($gateway) {
    try {
        $gateway->handleListener();
    } catch (Exception $e) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode(['error' => 'gateway_listener_error', 'details' => $e->getMessage()]);

        $gateway->logError($e->getMessage());
    }
} else {
    die('Invalid gateway');
}