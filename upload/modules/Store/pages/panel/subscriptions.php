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
        foreach ($subscriptions_query->results() as $subscription) {
            $customer = new Customer(null, $subscription->customer_id);

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

            $status = '';
            switch ($subscription->status_id) {
                case 0;
                    $status = '<span class="badge badge-warning">Pending</span>';
                break;
                case 1;
                    $status = '<span class="badge badge-success">Active</span>';
                break;
                case 2;
                    $status = '<span class="badge badge-secondary">Cancelled</span>';
                break;
            }

            $last_payment = DB::getInstance()->query('SELECT * FROM nl2_store_payments WHERE subscription_id = ? ORDER BY id DESC', [$subscription->id]);

            $subscriptions_list[] = [
                'user_link' =>  $link,
                'user_style' => $style,
                'user_avatar' => $avatar,
                'username' => $username,
                'uuid' => $identifier,
                'status' => $status,
                'last_billing_date' => $last_payment->count() ? date(DATE_FORMAT, $last_payment->first()->created) : 'Never',
                'next_billing_date' => date(DATE_FORMAT, $subscription->next_billing_date),
                'amount_format' => '$100 USD',
            ];
        }

        $smarty->assign([
            'VIEW' => $store_language->get('admin', 'view'),
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


    $template_file = 'store/subscription.tpl';
}

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('admin', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'SUBSCRIPTIONS' => $store_language->get('admin', 'subscriptions'),
    'USER' => $store_language->get('admin', 'user'),
    'AMOUNT' => $store_language->get('admin', 'amount'),
    'STATUS' => $store_language->get('admin', 'status'),
    'DATE' => $store_language->get('admin', 'date'),
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