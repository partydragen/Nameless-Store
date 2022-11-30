<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  UserCP store page
 */

// Must be logged in
if(!$user->isLoggedIn()){
    Redirect::to(URL::build('/'));
}

// Always define page name for navbar
const PAGE = 'cc_store';
$page_title = $language->get('user', 'user_cp');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

$timeago = new TimeAgo(TIMEZONE);
$customer = new Customer($user);

$currency = Output::getClean(Store::getCurrency());
$currency_symbol = Output::getClean(Store::getCurrencySymbol());

$transactions_list = [];
$transactions = DB::getInstance()->query('SELECT nl2_store_payments.* FROM nl2_store_payments INNER JOIN nl2_store_orders ON order_id=nl2_store_orders.id WHERE from_customer_id = ? ORDER BY nl2_store_payments.created DESC', [$customer->data()->id]);
if ($transactions->count()) {
    foreach ($transactions->results() as $transaction) {
        $transactions_list[] = [
            'gateway' => Output::getClean($transaction->gateway_id),
            'transaction' => Output::getClean($transaction->transaction),
            'amount' => Output::getClean($transaction->amount),
            'currency' => Output::getClean($transaction->currency),
            'currency_symbol' => $currency_symbol,
            'fee' => Output::getClean($transaction->fee),
            'date_full' => date(DATE_FORMAT, $transaction->created),
            'date_friendly' => $timeago->inWords($transaction->created, $language)
        ];
    }
}

$smarty->assign([
    'STORE' => $store_language->get('general', 'store'),
    'CREDITS' => $store_language->get('general', 'credits'),
    'CREDITS_VALUE' => $customer->getCredits(),
    'MY_TRANSACTIONS' => $store_language->get('general', 'my_transactions'),
    'NO_TRANSACTIONS' => $store_language->get('general', 'no_transactions'),
    'TRANSACTION' => $store_language->get('admin', 'transaction'),
    'AMOUNT' => $store_language->get('admin', 'amount'),
    'DATE' => $store_language->get('admin', 'date'),
    'TRANSACTIONS_LIST' => $transactions_list,
    'CURRENCY' => $currency,
    'CURRENCY_SYMBOL' => $currency_symbol
]);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

require(ROOT_PATH . '/core/templates/cc_navbar.php');

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate('store/user/store.tpl', $smarty);