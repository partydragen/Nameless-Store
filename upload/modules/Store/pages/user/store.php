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

// Handle input
if (Input::exists()) {
    $errors = [];

    if (Token::check() && Settings::get('user_send_credits')) {
        $validation = Validate::check($_POST, [
            'to' => [
                Validate::REQUIRED => true,
                Validate::MIN => 3,
                Validate::MAX => 20
            ],
            'credits' => [
                Validate::REQUIRED => true,
                Validate::MIN => 1,
                Validate::MAX => 11,
                Validate::NUMERIC => true
            ]
        ]);

        if ($validation->passed()) {
            $cents = Store::toCents($_POST['credits']);
            if ($cents <= 0) {
                $errors[] = $store_language->get('general', 'must_be_greater_then_x', ['amount' => 0]);
            }

            if ($cents > 0 && $cents > $customer->data()->cents) {
                $errors[] = $store_language->get('general', 'not_enough_credits');
            }

            $target_user = new User($_POST['to'], 'username');
            if (!$target_user->exists()) {
                $errors[] = $store_language->get('general', 'user_not_found');
            }

            if (!count($errors)) {
                // Success perform transaction
                $target_customer = new Customer($target_user);
                if ($target_customer->exists()) {
                    $customer->removeCents($cents);
                    $target_customer->addCents($cents);

                    $credits = Store::fromCents($cents);
                    Alert::create(
                        $target_user->data()->id,
                        'received_credits',
                        ['path' => ROOT_PATH . '/modules/Store/language', 'file' => 'general', 'term' => 'received_x_credits_from_x', 'replace' => ['{{amount}}', '{{user}}'], 'replace_with' => [$credits, $user->getDisplayname(true)]],
                        ['path' => ROOT_PATH . '/modules/Store/language', 'file' => 'general', 'term' => 'received_x_credits_from_x', 'replace' => ['{{amount}}', '{{user}}'], 'replace_with' => [$credits, $user->getDisplayname(true)]],
                        URL::build('/user/store')
                    );
                    Session::flash('user_store_success', $store_language->get('general', 'successfully_sent_credits', [
                        'amount' => $credits,
                        'user' => $target_user->getDisplayname(true)
                    ]));
                    Redirect::to(URL::build('/user/store'));
                }
            }
        } else {
            // Validation errors
            $errors = $validation->errors();
        }
    } else {
        // Invalid token
        $errors[] = $language->get('general', 'invalid_token');
    }
}

$transactions_list = [];
$transactions = DB::getInstance()->query('SELECT nl2_store_payments.* FROM nl2_store_payments INNER JOIN nl2_store_orders ON order_id=nl2_store_orders.id WHERE from_customer_id = ? ORDER BY nl2_store_payments.created DESC', [$customer->data()->id]);
if ($transactions->count()) {
    foreach ($transactions->results() as $transaction) {
        $transactions_list[] = [
            'gateway' => Output::getClean($transaction->gateway_id),
            'transaction' => Output::getClean($transaction->transaction),
            'amount' => Store::fromCents($transaction->amount_cents),
            'amount_format' => Output::getPurified(
                Store::formatPrice(
                    $transaction->amount_cents,
                    $currency,
                    $currency_symbol,
                    STORE_CURRENCY_FORMAT,
                )
            ),
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
    'CREDITS_FORMAT_VALUE' => Output::getPurified(
        Store::formatPrice(
            $customer->data()->cents,
            $currency,
            $currency_symbol,
            STORE_CURRENCY_FORMAT,
        )
    ),
    'MY_TRANSACTIONS' => $store_language->get('general', 'my_transactions'),
    'NO_TRANSACTIONS' => $store_language->get('general', 'no_transactions'),
    'TRANSACTION' => $store_language->get('admin', 'transaction'),
    'AMOUNT' => $store_language->get('admin', 'amount'),
    'DATE' => $store_language->get('admin', 'date'),
    'TRANSACTIONS_LIST' => $transactions_list,
    'CURRENCY' => $currency,
    'CURRENCY_SYMBOL' => $currency_symbol
]);

$can_send_credits = Settings::get('user_send_credits');
if ($can_send_credits) {
    $smarty->assign([
        'CAN_SEND_CREDITS' => true,
        'SEND_CREDITS' => $store_language->get('general', 'send_credits'),
        'TO' => $language->get('user', 'to'),
        'CANCEL' => $language->get('general', 'cancel'),
        'AMOUNT' => $store_language->get('admin', 'amount'),
        'YOU_HAVE_X_CREDITS' => $store_language->get('general', 'you_have_x_credits', [
            'credits' => $customer->getCredits()
        ]),
        'ALL_USERS' => $user->listAllOtherUsers(),
        'TOKEN' => Token::get()
    ]);

    $template->addJSScript('
        $(\'.ui.search\').dropdown({
            minCharacters: 3
        });
    ');
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('user_store_success')) {
    $success = Session::flash('user_store_success');
}

if (isset($success))
    $smarty->assign([
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ]);

if (isset($errors) && count($errors))
    $smarty->assign([
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ]);

require(ROOT_PATH . '/core/templates/cc_navbar.php');

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate('store/user/store.tpl', $smarty);