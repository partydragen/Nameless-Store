<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Frontend init
 */
 
$store = new Store($cache, $store_language);
$player = new Player();
$shopping_cart = new ShoppingCart();
$customer = new Customer($user);

// Check if player tries to logout
if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
        if (Input::get('type') == 'store_logout') {
            // Logout the store player
            $player->logout();
        }
    }
}

// Assign smarty variables
if ($store->isPlayerSystemEnabled() && $player->isLoggedIn()) {
    $smarty->assign([
        'STORE_PLAYER' => $player->getUsername(),
        'LOGOUT' => $store_language->get('general', 'logout'),
    ]);
}

$currency = Output::getClean($configuration->get('store', 'currency'));
$currency_symbol = Output::getClean($configuration->get('store', 'currency_symbol'));

$smarty->assign([
    'SHOPPING_CART_PRODUCTS' => $shopping_cart->getProducts(),
    'X_ITEMS_FOR_Y' => str_replace(['{x}', '{y}'], [count($shopping_cart->getItems()), $currency_symbol . $shopping_cart->getTotalPrice() . ' ' . $currency], $store_language->get('general', 'x_items_for_y')),
    'CHECKOUT_LINK' => URL::build($store->getStoreURL() . '/checkout/'),
    'CURRENCY' => $currency,
    'CURRENCY_SYMBOL' => $currency_symbol
]);