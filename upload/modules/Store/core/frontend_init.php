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

$currency = Output::getClean(Store::getCurrency());
$currency_symbol = Output::getClean(Store::getCurrencySymbol());

// Get user credits if user is logged in
$cents = 0;
if ($from_customer->exists()) {
    $cents = $from_customer->data()->cents;
}

$show_credits_amount = Settings::get('show_credits_amount', '1');
if ($show_credits_amount === '1' || $show_credits_amount === null) {
    $smarty->assign('SHOW_CREDITS_AMOUNT', true);
}

$smarty->assign([
    'SHOPPING_CART_PRODUCTS' => $shopping_cart->getProducts(),
    'X_ITEMS_FOR_Y' => $store_language->get('general', 'x_items_for_y', [
        'items' => count($shopping_cart->getItems()),
        'amount' => Store::fromCents($shopping_cart->getTotalRealPriceCents()),
        'currency' => $currency,
        'currency_symbol' => $currency_symbol,
    ]),
    'CHECKOUT_LINK' => URL::build($store->getStoreURL() . '/checkout/'),
    'CURRENCY' => $currency,
    'CURRENCY_SYMBOL' => $currency_symbol,
    'ACCOUNT' => $language->get('user', 'user_cp'),
    'CREDITS' => $store_language->get('general', 'credits'),
    'CREDITS_VALUE' => Store::fromCents($cents),
    'CREDITS_FORMAT_VALUE' => Output::getPurified(
        Store::formatPrice(
            $cents,
            $currency,
            $currency_symbol,
            STORE_CURRENCY_FORMAT,
        )
    ),
]);