<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel gateways page
 */

// Can the user view the StaffCP?
if(!$user->handlePanelPageLoad('staffcp.store.gateways')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_gateways');
$page_title = $store_language->get('admin', 'gateways');

require_once(ROOT_PATH . '/core/templates/backend_init.php');
require_once(ROOT_PATH . '/modules/Store/classes/Store.php');
require_once(ROOT_PATH . '/modules/Store/classes/Gateways.php');
require_once(ROOT_PATH . '/modules/Store/classes/GatewayBase.php');
require_once(ROOT_PATH . '/modules/Store/classes/StoreConfig.php');

$store = new Store($cache, $store_language);
$gateways = new Gateways();

if(!isset($_GET['gateway'])) {

    // Make sure config exist
    $config_path = ROOT_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'Store' . DIRECTORY_SEPARATOR . 'config.php';
    if(!file_exists($config_path)){
        StoreConfig::set(array('installed' => true));
    }

    $gateways_list = array();
    foreach($gateways->getAll() as $gateway) {
        $gateways_list[] = array(
            'name' => Output::getClean($gateway->getName()),
            'enabled' => $gateway->isEnabled(),
            'edit_link' => URL::build('/panel/store/gateways/', 'gateway=' . Output::getClean($gateway->getName())),
        );
    }

	$smarty->assign(array(
		'GATEWAYS_LIST' => $gateways_list,
        'PAYMENT_METHOD' => $store_language->get('admin', 'payment_method'),
        'EDIT' => $language->get('general', 'edit'),
        'ENABLED' => $language->get('admin', 'enabled'),
        'DISABLED' => $language->get('admin', 'disabled'),
	));

	$template_file = 'store/gateways.tpl';
} else {
    $gateway = $gateways->get($_GET['gateway']);
    
    require_once($gateway->getSettings());
    
    $securityPolicy->secure_dir = array(ROOT_PATH . '/modules/Store', ROOT_PATH . '/custom/panel_templates');
    
    if (file_exists(ROOT_PATH . '/modules/Store/config.php')) {
        // File exist, Make sure its writeable
        if(!is_writable(ROOT_PATH . '/modules/Store/config.php')) {
            $errors = array($store_language->get('admin', 'config_not_writable'));
        }
    } else if (!is_writable(ROOT_PATH . '/modules/Store')){
        // File don't exist, make sure directory is writeable to be able to generate config.php
        $errors = array($store_language->get('admin', 'config_not_writable'));
    }
    
    $smarty->assign(array(
        'EDITING_GATEWAY' => str_replace('{x}', Output::getClean($gateway->getName()), $store_language->get('admin', 'editing_gateway_x')),
        'BACK' => $language->get('general', 'back'),
        'BACK_LINK' => URL::build('/panel/store/gateways')
    ));
    
    $template->addCSSFiles(array(
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.css' => array()
    ));

    $template->addJSFiles(array(
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.js' => array()
    ));

    $template->addJSScript('
        var elems = Array.prototype.slice.call(document.querySelectorAll(\'.js-switch\'));

        elems.forEach(function(html) {
            var switchery = new Switchery(html, {color: \'#23923d\', secondaryColor: \'#e56464\'});
        });
    ');
    
    $template_file = 'store/gateway_settings.tpl';
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

if(Session::exists('gateways_success'))
	$success = Session::flash('gateways_success');

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

$smarty->assign(array(
	'PARENT_PAGE' => PARENT_PAGE,
	'DASHBOARD' => $language->get('admin', 'dashboard'),
	'STORE' => $store_language->get('general', 'store'),
	'PAGE' => PANEL_PAGE,
	'TOKEN' => Token::get(),
	'SUBMIT' => $language->get('general', 'submit'),
	'GATEWAYS' => $store_language->get('admin', 'gateways')
));

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);