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

$store = new Store();
$shopping_cart = ShoppingCart::getInstance();
$from_customer = $shopping_cart->getCustomer();
$to_customer = $shopping_cart->getRecipient();

// Check if customer tries to logout
if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
        if (Input::get('type') == 'store_logout') {
            // Logout the store customer
            $to_customer->logout();
        }
    }
}

// Assign variables
if ($store->isPlayerSystemEnabled() && $to_customer->isLoggedIn()) {
    $template->getEngine()->addVariables([
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
    $template->getEngine()->addVariable('SHOW_CREDITS_AMOUNT', true);
}

$template->getEngine()->addVariables([
    'SHOPPING_CART_PRODUCTS' => $shopping_cart->items()->getItems(),
    'X_ITEMS_FOR_Y' => $store_language->get('general', 'x_items_for_y', [
        'items' => count($shopping_cart->items()->getItems()),
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