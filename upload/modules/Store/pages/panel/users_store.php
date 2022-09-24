<?php
/*
 *	Made by Partydragen
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
 *
 *  License: MIT
 *
 *  Panel users page
 */

if (!$user->handlePanelPageLoad('staffcp.store.payments')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

if (!isset($_GET['user']) || !is_numeric($_GET['user'])) {
    Redirect::to(URL::build('/panel/users'));
}

$view_user = new User($_GET['user']);
if (!$view_user->exists()) {
    Redirect::to('/panel/users');
}
$customer = new Customer($view_user);

define('PAGE', 'panel');
define('PARENT_PAGE', 'users');
define('PANEL_PAGE', 'users');
$page_title = $store_language->get('general', 'store');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Input::exists()) {
    $errors = [];

    if (Token::check()) {
        // Validation
        $validation = Validate::check($_POST, [
            'credits' => [
                Validate::REQUIRED => true,
                Validate::MIN => 1,
                Validate::MAX => 11,
                Validate::NUMERIC => true
            ]
        ]);

        if ($validation->passed()) {
            if ($user->hasPermission('staffcp.store.manage_credits')) {
                $credits = Input::get('credits');

                if (Input::get('action') == 'addCredits') {
                    $customer->addCents(Store::toCents($credits));

                    Session::flash('users_store_success', $store_language->get('admin', 'successfully_added_credits', ['amount' => $credits]));
                } else if (Input::get('action') == 'removeCredits') {
                    $customer->removeCents(Store::toCents($credits));

                    Session::flash('users_store_success', $store_language->get('admin', 'successfully_removed_credits', ['amount' => $credits]));
                }

                Redirect::to(URL::build('/panel/users/store/', 'user=' . $view_user->data()->id));
            }
        } else {
            $errors = $validation->errors();
        }
    } else {
        $errors[] = $language->get('general', 'invalid_token');
    }
}

// Get payments for user
$payments = $customer->getPayments();

if ($user->hasPermission('staffcp.store.manage_credits')) {
    $smarty->assign([
        'ADD_CREDITS' => $store_language->get('admin', 'add_credits'),
        'REMOVE_CREDITS' => $store_language->get('admin', 'remove_credits'),
        'ENTER_AMOUNT_TO_ADD' => $store_language->get('admin', 'enter_amount_to_add'),
        'ENTER_AMOUNT_TO_REMOVE' => $store_language->get('admin', 'enter_amount_to_remove'),
    ]);
}

if (Session::exists('users_store_success'))
    $success = Session::flash('users_store_success');

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

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'PAGE' => PANEL_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'USER_MANAGEMENT' => $language->get('admin', 'user_management'),
    'STORE' => $store_language->get('general', 'store'),
    'VIEWING_USER' => $language->get('moderator', 'viewing_user_x', ['user' => $view_user->getDisplayname()]),
    'BACK_LINK' => URL::build('/panel/user/' . $view_user->data()->id),
    'BACK' => $language->get('general', 'back'),
    'CREDITS' => $store_language->get('general', 'credits'),
    'CREDITS_VALUE' => $customer->getCredits(),
    'AMOUNT' => $store_language->get('admin', 'amount'),
    'STATUS' => $store_language->get('admin', 'status'),
    'DATE' => $store_language->get('admin', 'date'),
    'VIEW' => $store_language->get('admin', 'view'),
    'CANCEL' => $language->get('general', 'cancel'),
    'VIEWING_PAYMENTS_FOR_USER' => $store_language->get('admin', 'viewing_payments_for_user_x', ['user' => $view_user->getDisplayname()]),
    'PAYMENTS_LIST' => $payments,
    'NO_PAYMENTS' => $store_language->get('admin', 'no_payments_for_user'),
    'TOKEN' => Token::get(),
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate('store/users_store.tpl', $smarty);