<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel subscriptions page
 */

// Can the user view the StaffCP?
if (!$user->handlePanelPageLoad('staffcp.store.subscriptions')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_subscriptions');
$page_title = $store_language->get('admin', 'payments');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (!isset($_GET['subscription'])) {

    $subscriptions_query = DB::getInstance()->query('SELECT * FROM nl2_store_subscriptions ORDER BY id DESC');
    if ($subscriptions_query->count()) {

        $subscriptions_list = [];
        foreach ($subscriptions_query->results() as $item) {
            $subscription = new Subscription(null, null, $item);
            $customer = new Customer(null, $subscription->data()->customer_id);

            if ($customer->exists() && $customer->getUser()->exists()) {
                $customer_user = $customer->getUser();
                $username = $customer->getUsername();
                $avatar = $customer_user->getAvatar();
                $style = $customer_user->getGroupStyle();
                $identifier = Output::getClean($customer->getIdentifier());
                $link = URL::build('/panel/users/store/', 'user=' . $customer_user->data()->id);
            } else {
                $username = $customer->getUsername();
                $avatar = AvatarSource::getAvatarFromUUID(Output::getClean($customer->getIdentifier()));
                $style = '';
                $identifier = Output::getClean($customer->getIdentifier());
                $link = URL::build('/panel/store/payments/', 'customer=' . $username);
            }

            $subscriptions_list[] = [
                'user_link' =>  $link,
                'user_style' => $style,
                'user_avatar' => $avatar,
                'username' => $username,
                'uuid' => $identifier,
                'status' => $subscription->getStatusHtml(),
                'last_billing_date' => $subscription->data()->last_payment_date != 0 ? date(DATE_FORMAT, $subscription->data()->last_payment_date) : 'Never',
                'next_billing_date' => date(DATE_FORMAT, $subscription->data()->next_billing_date),
                'amount_format' => Output::getPurified(
                    Store::formatPrice(
                        $subscription->data()->amount_cents,
                        $subscription->data()->currency,
                        Store::getCurrencySymbol(),
                        STORE_CURRENCY_FORMAT,
                    )
                ),
                'link' => URL::build('/panel/store/subscriptions/', 'subscription=' . $subscription->data()->id)
            ];
        }

        $smarty->assign([
            'SUBSCRIPTIONS_LIST' => $subscriptions_list
        ]);
    } else {
        $smarty->assign('NO_SUBSCRIPTIONS', $store_language->get('admin', 'no_subscriptions'));
    }

    $template_file = 'store/subscriptions.tpl';
} else {
    $subscription = new Subscription($_GET['subscription']);
    if (!$subscription->exists()) {
        Redirect::to(URL::build('/panel/store/subscriptions'));
    }

    if (Input::exists()) {
        $errors = [];

        if (Token::check()) {
            if (Input::get('action') == 'sync') {
                // Sync subscription
                if (!$user->hasPermission('staffcp.store.subscriptions.sync')) {
                    Redirect::to(URL::build('/panel/store/subscriptions'));
                }

                if ($subscription->sync()) {
                    Session::flash('store_subscriptions_success', $store_language->get('admin', 'subscription_updated_successfully'));
                    Redirect::to(URL::build('/panel/store/subscriptions/', 'subscription=' . $subscription->data()->id));
                } else {
                    $errors[] = 'Something went wrong syncing subscription!';
                }
            } else if (Input::get('action') == 'cancel') {
                // Cancel subscription
                if (!$user->hasPermission('staffcp.store.subscriptions.cancel')) {
                    Redirect::to(URL::build('/panel/store/subscriptions'));
                }

                if ($subscription->cancel()) {
                    Session::flash('store_subscriptions_success', $store_language->get('admin', 'subscription_cancelled_successfully'));
                    Redirect::to(URL::build('/panel/store/subscriptions/', 'subscription=' . urlencode($subscription->data()->id)));
                } else {
                    $errors[] = 'Something went wrong cancelling subscription!';
                }
            }

        } else {
            $errors[] = $language->get('general', 'invalid_token');
        }
    }

    // Customer
    $customer = new Customer(null, $subscription->data()->customer_id);
    if ($customer->exists() && $customer->getUser()->exists()) {
        $customer_user = $customer->getUser();
        $username = $customer->getUsername();
        $avatar = $customer_user->getAvatar();
        $style = $customer_user->getGroupStyle();
        $uuid = Output::getClean($customer->getIdentifier());
        $link = URL::build('/panel/users/store/', 'user=' . $customer_user->data()->id);
    } else {
        $username = $customer->getUsername();
        $avatar = AvatarSource::getAvatarFromUUID(Output::getClean($customer->getIdentifier()));
        $style = '';
        $uuid = Output::getClean($customer->getIdentifier());
        $link = URL::build('/panel/store/payments/', 'customer=' . $username);
    }

    // Gateway
    $gateway = $subscription->getGateway();
    if ($gateway != null) {
        $payment_method = $gateway->getName();
    } else {
        $payment_method = 'Unknown';
    }

    // Payments
    $payments_list = [];
    $payments_query = DB::getInstance()->query('SELECT * FROM nl2_store_payments WHERE subscription_id = ? ORDER BY created DESC', [$subscription->data()->id]);
    if ($payments_query->count()) {
        foreach ($payments_query->results() as $payment) {
            $payments_list[] = [
                'date' => date(DATE_FORMAT, $payment->created),
                'link' => URL::build('/panel/store/payments', 'payment=' . $payment->id)
            ];
        }
    }

    $smarty->assign([
        'VIEWING_SUBSCRIPTION' => $store_language->get('admin', 'viewing_subscription', ['subscription' => Output::getClean($subscription->data()->agreement_id)]),
        'BACK' => $language->get('general', 'back'),
        'BACK_LINK' => URL::build('/panel/store/subscriptions'),
        'CUSTOMER' => $store_language->get('admin', 'customer'),
        'USERNAME' => $username,
        'USER_LINK' => $link,
        'AVATAR' => $avatar,
        'STYLE' => $style,
        'STATUS_VALUE' => $subscription->getStatusHtml(),
        'AGREEMENT_ID' => $store_language->get('general', 'agreement_id'),
        'AGREEMENT_ID_VALUE' => Output::getClean($subscription->data()->agreement_id),
        'PAYMENT_METHOD' => $store_language->get('admin', 'payment_method'),
        'PAYMENT_METHOD_VALUE' => Output::getClean($payment_method),
        "FREQUENCY"  => $store_language->get('general', 'frequency'),
        "FREQUENCY_VALUE" => Output::getClean($subscription->data()->frequency_interval . ' ' . ucfirst(strtolower($subscription->data()->frequency))),
        'AMOUNT_VALUE' => Store::fromCents($subscription->data()->amount_cents),
        'AMOUNT_FORMAT_VALUE' => Output::getPurified(
            Store::formatPrice(
                $subscription->data()->amount_cents,
                $subscription->data()->currency,
                Store::getCurrencySymbol(),
                STORE_CURRENCY_FORMAT,
            )
        ),
        'LAST_PAYMENT_DATE_VALUE' => $subscription->data()->last_payment_date != 0 ? date(DATE_FORMAT, $subscription->data()->last_payment_date) : 'Never',
        'NEXT_BILLING_DATE_VALUE' => date(DATE_FORMAT, $subscription->data()->next_billing_date),
        'PAYMENTS'  => $store_language->get('admin', 'payments'),
        'PAYMENTS_LIST' => $payments_list
    ]);

    // Can cancel subscription?
    if ($user->hasPermission('staffcp.store.subscriptions.cancel')) {
        $smarty->assign('CANCEL_SUBSCRIPTION', $store_language->get('general', 'cancel_subscription'));
    }

    // Can sync subscription?
    if ($user->hasPermission('staffcp.store.subscriptions.sync')) {
        $smarty->assign('SYNC_SUBSCRIPTION', $store_language->get('admin', 'sync_subscription'));
    }

    $template_file = 'store/subscription.tpl';
}

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('general', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'SUBSCRIPTIONS' => $store_language->get('admin', 'subscriptions'),
    'USER' => $store_language->get('admin', 'user'),
    'AMOUNT' => $store_language->get('admin', 'amount'),
    'STATUS' => $store_language->get('admin', 'status'),
    'LAST_PAYMENT_DATE' => $store_language->get('general', 'last_payment_date'),
    'NEXT_BILLING_DATE' => $store_language->get('general', 'next_billing_date'),
    'VIEW' => $store_language->get('admin', 'view'),
]);

if (Session::exists('store_subscriptions_success')) {
    $success = Session::flash('store_subscriptions_success');
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

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);