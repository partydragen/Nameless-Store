<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr8
 *
 *  License: MIT
 *
 *  Panel users page
 */

if(!$user->handlePanelPageLoad('staffcp.store.payments')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

if (!isset($_GET['user']) || !is_numeric($_GET['user'])) {
    Redirect::to(URL::build('/panel/users'));
    die();
}

$view_user = new User($_GET['user']);
if (!$view_user->exists()) {
    Redirect::to('/panel/users');
    die();
}
$customer = new Customer($view_user);

define('PAGE', 'panel');
define('PARENT_PAGE', 'users');
define('PANEL_PAGE', 'users');
$page_title = $store_language->get('general', 'store');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $mod_nav], $widgets);

if (Input::exists()) {
    $errors = array();

    if (Token::check()) {
        // Validation
        $validate = new Validate();
        $validation = $validate->check($_POST, [
            'credits' => [
                Validate::REQUIRED => true,
                Validate::MIN => 1,
                Validate::MAX => 11,
                Validate::NUMERIC => true
            ],
        ]);

        if ($validation->passed()) {
            $credits = Input::get('credits');

            if (Input::get('action') == 'addCredits') {
                $customer->addCredits($credits);

                Session::flash('users_store_success', str_replace('{amount}', $credits, $store_language->get('admin', 'successfully_added_credits')));
            } else if (Input::get('action') == 'removeCredits') {
                $customer->removeCredits($credits);

                Session::flash('users_store_success', str_replace('{amount}', $credits, $store_language->get('admin', 'successfully_removed_credits')));
            }

            Redirect::to(URL::build('/panel/users/store/', 'user=' . $view_user->data()->id));
            die();
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

if(Session::exists('users_store_success'))
    $success = Session::flash('users_store_success');

if(isset($success))
    $smarty->assign(array(
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ));

if(isset($errors) && count($errors))
    $smarty->assign(array(
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ));

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'PAGE' => PANEL_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'USER_MANAGEMENT' => $language->get('admin', 'user_management'),
    'STORE' => $store_language->get('general', 'store'),
    'VIEWING_USER' => str_replace('{x}', $view_user->getDisplayname(), $language->get('moderator', 'viewing_user_x')),
    'BACK_LINK' => URL::build('/panel/user/' . $view_user->data()->id),
    'BACK' => $language->get('general', 'back'),
    'CREDITS' => $store_language->get('general', 'credits'),
    'CREDITS_VALUE' => $customer->getCredits(),
    'AMOUNT' => $store_language->get('admin', 'amount'),
    'STATUS' => $store_language->get('admin', 'status'),
    'DATE' => $store_language->get('admin', 'date'),
    'VIEW' => $store_language->get('admin', 'view'),
    'CANCEL' => $language->get('general', 'cancel'),
    'VIEWING_PAYMENTS_FOR_USER' => str_replace('{x}', $view_user->getDisplayname(), $store_language->get('admin', 'viewing_payments_for_user_x')),
    'PAYMENTS_LIST' => $payments,
    'NO_PAYMENTS' => $store_language->get('admin', 'no_payments_for_user'),
    'TOKEN' => Token::get(),
]);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate('store/users_store.tpl', $smarty);