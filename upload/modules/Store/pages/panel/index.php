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
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store');
$page_title = $store_language->get('general', 'store');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

if (isset($_POST) && !empty($_POST)) {
    $errors = [];

    if (Token::check(Input::get('token'))) {
        $validate = new Validate();

        $validation = $validate->check($_POST, [
            'store_content' => [
                'max' => 100000
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

            $configuration->set('store', 'allow_guests', $allow_guests);
            $configuration->set('store', 'player_login', $player_login);
            $configuration->set('store', 'currency', Output::getClean(Input::get('currency')));
            $configuration->set('store', 'currency_symbol', Output::getClean(Input::get('currency_symbol')));
            
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

            // Update store content
            try {
                $store_index_content = $queries->getWhere('store_settings', ['name', '=', 'store_content']);

                if (count($store_index_content)) {
                    $store_index_content = $store_index_content[0]->id;
                    $queries->update('store_settings', $store_index_content, [
                        'value' => Output::getClean(Input::get('store_content'))
                    ]);
                } else {
                    $queries->create('store_settings', [
                        'name' => 'store_content',
                        'value' => Output::getClean(Input::get('store_content'))
                    ]);
                }

            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
            
            // Update checkout content
            try {
                $checkout_complete_content = $queries->getWhere('store_settings', ['name', '=', 'checkout_complete_content']);

                if (count($checkout_complete_content)) {
                    $checkout_complete_content = $checkout_complete_content[0]->id;
                    $queries->update('store_settings', $checkout_complete_content, [
                        'value' => Output::getClean(Input::get('checkout_complete_content'))
                    ]);
                } else {
                    $queries->create('store_settings', [
                        'name' => 'checkout_complete_content',
                        'value' => Output::getClean(Input::get('checkout_complete_content'))
                    ]);
                }

            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }

            // Update store path
            try {
                $store_path = $queries->getWhere('store_settings', ['name', '=', 'store_path']);

                if (isset($_POST['store_path']) && strlen(str_replace(' ', '', $_POST['store_path'])) > 0)
                    $store_path_input = rtrim(Output::getClean($_POST['store_path']), '/');
                else
                    $store_path_input = '/store';

                if (count($store_path)) {
                    $store_path = $store_path[0]->id;
                    $queries->update('store_settings', $store_path, [
                        'value' => $store_path_input
                    ]);
                } else {
                    $queries->create('store_settings', [
                        'name' => 'store_path',
                        'value' => $store_path_input
                    ]);
                }

                $cache->setCache('store_settings');
                $cache->store('store_url', $store_path_input);

            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }

            if (!count($errors))
                $success = $store_language->get('admin', 'updated_successfully');

        } else {
            $errors[] = $store_language->get('admin', 'store_content_max');
        }

    } else
        $errors[] = $language->get('general', 'invalid_token');
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $mod_nav], $widgets);

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
$allow_guests = $configuration->get('store', 'allow_guests');

// Require player to enter minecraft username when visiting store
$player_login = $configuration->get('store', 'player_login');

// Store content
$store_index_content = $queries->getWhere('store_settings', ['name', '=', 'store_content']);
if (count($store_index_content)) {
    $store_index_content = Output::getClean(Output::getPurified(Output::getDecoded($store_index_content[0]->value)));
} else {
    $store_index_content = '';
}

// Checkout complete content
$checkout_complete_content = $queries->getWhere('store_settings', ['name', '=', 'checkout_complete_content']);
if (count($checkout_complete_content)) {
    $checkout_complete_content = Output::getClean(Output::getPurified(Output::getDecoded($checkout_complete_content[0]->value)));
} else {
    $checkout_complete_content = '';
}

// Store Path
$store_path = $queries->getWhere('store_settings', ['name', '=', 'store_path']);
if (count($store_path)) {
    $store_path = Output::getClean($store_path[0]->value);
} else {
    $store_path = '/store';
}

// Currency
$currency_list = ['USD', 'EUR', 'GBP', 'NOK', 'SEK', 'PLN', 'DKK', 'CAD', 'BRL', 'AUD'];
$currency = $configuration->get('store', 'currency');

// Currency Symbol
$currency_symbol = $configuration->get('store', 'currency_symbol');

// Retrieve Link Location from cache
$cache->setCache('nav_location');
$link_location = $cache->retrieve('store_location');

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
    'STORE_PATH' => $store_language->get('admin', 'store_path'),
    'STORE_PATH_VALUE' => $store_path,
    'CURRENCY' => $store_language->get('admin', 'currency'),
    'CURRENCY_LIST' => $currency_list,
    'CURRENCY_VALUE' => Output::getClean($currency),
    'CURRENCY_SYMBOL' => $store_language->get('admin', 'currency_symbol'),
    'CURRENCY_SYMBOL_VALUE' => Output::getClean($currency_symbol),
    'STORE_INDEX_CONTENT' => $store_language->get('admin', 'store_index_content'),
    'STORE_INDEX_CONTENT_VALUE' => $store_index_content,
    'CHECKOUT_COMPLETE_CONTENT' => $store_language->get('admin', 'checkout_complete_content'),
    'CHECKOUT_COMPLETE_CONTENT_VALUE' => $checkout_complete_content,
    'LINK_LOCATION' => $language->get('admin', 'page_link_location'),
    'LINK_LOCATION_VALUE' => $link_location,
    'LINK_NAVBAR' => $language->get('admin', 'page_link_navbar'),
    'LINK_MORE' => $language->get('admin', 'page_link_more'),
    'LINK_FOOTER' => $language->get('admin', 'page_link_footer'),
    'LINK_NONE' => $language->get('admin', 'page_link_none'),
]);

if (!defined('TEMPLATE_STORE_SUPPORT')) {
    $template->addCSSFiles([
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.css' => []
    ]);

    $template->addJSFiles([
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.js' => [],
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/js/emojione.min.js' => [],
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => [],
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => []
    ]);

    $template->addJSScript(Input::createEditor('inputStoreContent', true));
    $template->addJSScript(Input::createEditor('inputCheckoutCompleteContent', true));
    $template->addJSScript('
    var elems = Array.prototype.slice.call(document.querySelectorAll(\'.js-switch\'));

    elems.forEach (function(html) {
      var switchery = new Switchery(html, {color: \'#23923d\', secondaryColor: \'#e56464\'});
    });
    ');
}

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate('store/index.tpl', $smarty);