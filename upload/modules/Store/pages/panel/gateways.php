<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel gateways page
 */

// Can the user view the StaffCP?
if (!$user->handlePanelPageLoad('staffcp.store.gateways')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store_configuration');
define('PANEL_PAGE', 'store_gateways');
$page_title = $store_language->get('admin', 'gateways');

require_once(ROOT_PATH . '/core/templates/backend_init.php');

$store = new Store($cache, $store_language);

if (!isset($_GET['gateway'])) {

    // Make sure config exist
    $config_path = ROOT_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'Store' . DIRECTORY_SEPARATOR . 'config.php';
    if (!file_exists($config_path)) {
        if (is_writable(ROOT_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'Store')) {
            StoreConfig::set(['installed' => true]);
        } else {
            $errors = [$store_language->get('admin', 'unavailable_generate_config')];
        }
    }

    if (!isset($errors)) {
        $gateways_list = [];
        foreach (Gateways::getInstance()->getAll() as $gateway) {
            $gateways_list[] = [
                'name' => Output::getClean($gateway->getName()),
                'version' => Output::getClean($gateway->getVersion()),
                'store_version' => Output::getClean($gateway->getStoreVersion()),
                'author' => Output::getPurified($gateway->getAuthor()),
                'author_x' => $language->get('admin', 'author_x', ['author' => Output::getPurified($gateway->getAuthor())]),
                'enabled' => $gateway->isEnabled(),
                'edit_link' => URL::build('/panel/store/gateways/', 'gateway=' . Output::getClean($gateway->getName())),
            ];
        }

        $smarty->assign([
            'GATEWAYS_LIST' => $gateways_list
        ]);
    }

    $smarty->assign([
        'PAYMENT_METHOD' => $store_language->get('admin', 'payment_method'),
        'EDIT' => $language->get('general', 'edit'),
        'ENABLED' => $language->get('admin', 'enabled'),
        'DISABLED' => $language->get('admin', 'disabled'),
    ]);

    $template_file = 'store/gateways.tpl';
} else {
    $gateway = Gateways::getInstance()->get($_GET['gateway']);

    $securityPolicy->secure_dir = [ROOT_PATH . '/modules/Store', ROOT_PATH . '/custom/panel_templates'];

    if (file_exists(ROOT_PATH . '/modules/Store/config.php')) {
        // File exist, Make sure its writeable
        if (!is_writable(ROOT_PATH . '/modules/Store/config.php')) {
            $errors = [$store_language->get('admin', 'config_not_writable')];
        }
    } else if (!is_writable(ROOT_PATH . '/modules/Store')) {
        // File don't exist
        Redirect::to(URL::build('/panel/store/gateways'));
    }

    require_once($gateway->getSettings());

    $smarty->assign([
        'EDITING_GATEWAY' => $store_language->get('admin', 'editing_gateway_x', ['gateway' => Output::getClean($gateway->getName())]),
        'BACK' => $language->get('general', 'back'),
        'BACK_LINK' => URL::build('/panel/store/gateways')
    ]);

    $template_file = 'store/gateway_settings.tpl';
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('gateways_success'))
    $success = Session::flash('gateways_success');

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
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('general', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'GATEWAYS' => $store_language->get('admin', 'gateways')
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);