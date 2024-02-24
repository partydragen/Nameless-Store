<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel store page
 */

// Can the user view the StaffCP?
if (!$user->handlePanelPageLoad('staffcp.store.settings')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store_configuration');
define('PANEL_PAGE', 'general_settings');
$page_title = $store_language->get('general', 'store');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

// Supported currency
$currency_list = ['USD', 'EUR', 'GBP', 'NOK', 'SEK', 'PLN', 'DKK', 'CAD', 'BRL', 'AUD'];

if (isset($_POST) && !empty($_POST)) {
    $errors = [];

    if (Token::check(Input::get('token'))) {
        $validation = Validate::check($_POST, [
            'currency_format' => [
                Validate::REQUIRED => true,
                Validate::MIN => 7,
                Validate::MAX => 64,
            ],
            'checkout_complete_content' => [
                Validate::MAX => 60000
            ],
            'custom_currency' => [
                Validate::MIN => 3,
                Validate::MAX => 3,
            ],
        ])->messages([
            'currency_format' => [
                Validate::REQUIRED => $store_language->get('admin', 'currency_format_required'),
                Validate::MIN => $store_language->get('admin', 'currency_format_min', ['min' => 7]),
                Validate::MAX => $store_language->get('admin', 'currency_format_max', ['max' => 64]),
            ],
            'checkout_complete_content' => [
                Validate::MAX => $store_language->get('admin', 'checkout_complete_content_max')
            ]
        ]);

        if ($validation->passed()) {
            // Update allow guests
            if (isset($_POST['allow_guests']) && $_POST['allow_guests'] == 'on')
                $allow_guests = 1;
            else
                $allow_guests = 0;

            // Enable Player Login
            if (isset($_POST['player_login']) && $_POST['player_login'] == 'on')
                $player_login = 1;
            else
                $player_login = 0;

            // Show credits amount on all pages?
            if (isset($_POST['show_credits_amount']) && $_POST['show_credits_amount'] == 'on')
                $show_credits_amount = 1;
            else
                $show_credits_amount = 0;
            
            // Allow users to send credits to other users?
            if (isset($_POST['user_send_credits']) && $_POST['user_send_credits'] == 'on')
                $user_send_credits = 1;
            else
                $user_send_credits = 0;

            // Update store path
            if (isset($_POST['store_path']) && strlen(str_replace(' ', '', $_POST['store_path'])) > 0)
                $store_path_input = rtrim(Output::getClean($_POST['store_path']), '/');
            else
                $store_path_input = '/store';

            $custom_currency = strtoupper(Input::get('custom_currency'));
            $currency = empty($custom_currency) ? Input::get('currency') : $custom_currency;

            Settings::set('store_path', $store_path_input, 'Store');
            Settings::set('allow_guests', $allow_guests, 'Store');
            Settings::set('player_login', $player_login, 'Store');
            Settings::set('currency', $currency, 'Store');
            Settings::set('currency_symbol', Input::get('currency_symbol'), 'Store');
            Settings::set('currency_format', Input::get('currency_format'), 'Store');
            Settings::set('checkout_complete_content', Input::get('checkout_complete_content'), 'Store');
            Settings::set('username_validation_method', Input::get('validation_method'), 'Store');
            Settings::set('discord_message', Input::get('discord_message'), 'Store');

            Settings::set('show_credits_amount', $show_credits_amount);
            Settings::set('user_send_credits', $user_send_credits);

            // Update link location
            if (isset($_POST['link_location'])) {
                switch ($_POST['link_location']) {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                        $location = $_POST['link_location'];
                    break;
                    default:
                        $location = 1;
                }
            } else
                $location = 1;

            // Update Link location cache
            $cache->setCache('nav_location');
            $cache->store('store_location', $location);

            Session::flash('store_success', $store_language->get('admin', 'updated_successfully'));
            Redirect::to(URL::build('/panel/store/general_settings'));
        } else {
            $errors = $validation->errors();
        }

    } else
        $errors[] = $language->get('general', 'invalid_token');
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('store_success'))
    $success = Session::flash('store_success');

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

// Can guest make purchases
$allow_guests = Settings::get('allow_guests', '0', 'Store');

// Require player to enter minecraft username when visiting store
$player_login = Settings::get('player_login', '0', 'Store');

// Checkout complete content
$checkout_complete_content = Output::getClean(Output::getPurified(Output::getDecoded(Settings::get('checkout_complete_content', '', 'Store'))));

// Store Path
$store_path = Settings::get('store_path', '/store', 'Store');

// Currency
$currency = Settings::get('currency', 'USD', 'Store');

// Currency Symbol
$currency_symbol = Settings::get('currency_symbol', '$', 'Store');

// Retrieve Link Location from cache
$cache->setCache('nav_location');
$link_location = $cache->retrieve('store_location');

$show_credits_amount = Settings::get('show_credits_amount', '1');
$show_credits_amount = ($show_credits_amount === '1' || $show_credits_amount === null ? true : false);

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('general', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'SETTINGS' => $store_language->get('admin', 'settings'),
    'ALLOW_GUESTS' => $store_language->get('admin', 'allow_guests'),
    'ALLOW_GUESTS_VALUE' => ($allow_guests == 1),
    'PLAYER_LOGIN' => $store_language->get('admin', 'enable_player_login'),
    'PLAYER_LOGIN_VALUE' => ($player_login == 1),
    'SHOW_CREDITS_AMOUNT' => $store_language->get('admin', 'show_credits_amount'),
    'SHOW_CREDITS_AMOUNT_VALUE' => $show_credits_amount,
    'ALLOW_USERS_TO_SEND_CREDITS' => $store_language->get('admin', 'allow_users_to_send_credits'),
    'ALLOW_USERS_TO_SEND_CREDITS_VALUE' => Settings::get('user_send_credits', '0'),
    'STORE_PATH' => $store_language->get('admin', 'store_path'),
    'STORE_PATH_VALUE' => $store_path,
    'CURRENCY_FORMAT' => $store_language->get('admin', 'currency_format'),
    'CURRENCY_FORMAT_INFO' => $store_language->get('admin', 'currency_format_info'),
    'CURRENCY_FORMAT_VALUE' => Settings::get('currency_format', '{currencySymbol}{price} {currencyCode}', 'Store'),
    'CURRENCY' => $store_language->get('admin', 'currency'),
    'CURRENCY_LIST' => $currency_list,
    'CURRENCY_VALUE' => Output::getClean($currency),
    'CUSTOM_CURRENCY_VALUE' => in_array($currency, $currency_list) ? null : $currency,
    'CURRENCY_SYMBOL' => $store_language->get('admin', 'currency_symbol'),
    'CURRENCY_SYMBOL_VALUE' => Output::getClean($currency_symbol),
    'CHECKOUT_COMPLETE_CONTENT' => $store_language->get('admin', 'checkout_complete_content'),
    'CHECKOUT_COMPLETE_CONTENT_VALUE' => $checkout_complete_content,
    'DISCORD_MESSAGE_VALUE' => Settings::get('discord_message', 'New payment from {username} who bought the following products {products}', 'Store'),
    'LINK_LOCATION' => $language->get('admin', 'page_link_location'),
    'LINK_LOCATION_VALUE' => $link_location,
    'LINK_NAVBAR' => $language->get('admin', 'page_link_navbar'),
    'LINK_MORE' => $language->get('admin', 'page_link_more'),
    'LINK_FOOTER' => $language->get('admin', 'page_link_footer'),
    'LINK_NONE' => $language->get('admin', 'page_link_none'),
    'MCSTATISTICS_ENABLED' => Util::isModuleEnabled('MCStatistics'),
    'VALIDATION_METHOD_VALUE' => Settings::get('username_validation_method', 'nameless', 'Store'),
]);

$template->assets()->include([
    AssetTree::TINYMCE,
]);

$template->addJSScript(Input::createTinyEditor($language, 'inputStoreContent', null, false, true));
$template->addJSScript(Input::createTinyEditor($language, 'inputCheckoutCompleteContent', null, false, true));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate('store/general_settings.tpl', $smarty);