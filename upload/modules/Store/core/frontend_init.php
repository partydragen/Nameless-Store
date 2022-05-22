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
$shopping_cart = new ShoppingCart();

$from_customer = new Customer($user);
if ($store->isPlayerSystemEnabled()) {
    // Customer will need to enter minecraft username to buy the products for
    $to_customer = new Customer();
} else {
    // Customer will buy the products for them self
    $to_customer = $from_customer;
}

// Check if customer tries to logout
if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
        if (Input::get('type') == 'store_logout') {
            // Logout the store customer
            $to_customer->logout();
        }
    }
}

// Assign smarty variables
if ($store->isPlayerSystemEnabled() && $to_customer->isLoggedIn()) {
    $smarty->assign([
        'STORE_PLAYER' => $to_customer->getUsername(),
        'LOGOUT' => $store_language->get('general', 'logout'),
    ]);
}

$currency = Output::getClean($configuration->get('store', 'currency'));
$currency_symbol = Output::getClean($configuration->get('store', 'currency_symbol'));

$smarty->assign([
    'SHOPPING_CART_PRODUCTS' => $shopping_cart->getProducts(),
    'X_ITEMS_FOR_Y' => $store_language->get('general', 'x_items_for_y', [
        'items' => count($shopping_cart->getItems()),
        'amount' => $shopping_cart->getTotalPrice(),
        'currency' => $currency,
        'currency_symbol' => $currency_symbol,
    ]),
    'CHECKOUT_LINK' => URL::build($store->getStoreURL() . '/checkout/'),
    'CURRENCY' => $currency,
    'CURRENCY_SYMBOL' => $currency_symbol
]);