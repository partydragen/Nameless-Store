<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel store page
 */

// Can the user view the StaffCP?
if(!$user->handlePanelPageLoad('staffcp.store.settings')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store');
$page_title = $store_language->get('general', 'store');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

if(isset($_POST) && !empty($_POST)){
	$errors = array();

	if(Token::check(Input::get('token'))){
		$validate = new Validate();

		$validation = $validate->check($_POST, array(
			'store_content' => array(
				'max' => 100000
			)
		));

		if($validation->passed()){
			if(isset($_POST['allow_guests']) && $_POST['allow_guests'] == 'on')
				$allow_guests = 1;
			else
				$allow_guests = 0;

			try {
				$allow_guests_query = $queries->getWhere('store_settings', array('name', '=', 'allow_guests'));

				if(count($allow_guests_query)){
					$allow_guests_query = $allow_guests_query[0]->id;
					$queries->update('store_settings', $allow_guests_query, array(
						'value' => $allow_guests
					));
				} else {
					$queries->create('store_settings', array(
						'name' => 'allow_guests',
						'value' => $allow_guests
					));
				}

			} catch(Exception $e){
				$errors[] = $e->getMessage();
			}

			try {
				$store_index_content = $queries->getWhere('store_settings', array('name', '=', 'store_content'));

				if(count($store_index_content)){
					$store_index_content = $store_index_content[0]->id;
					$queries->update('store_settings', $store_index_content, array(
						'value' => Output::getClean(Input::get('store_content'))
					));
				} else {
					$queries->create('store_settings', array(
						'name' => 'store_content',
						'value' => Output::getClean(Input::get('store_content'))
					));
				}

			} catch(Exception $e){
				$errors[] = $e->getMessage();
			}
			
			try {
				$store_checkout_content = $queries->getWhere('store_settings', array('name', '=', 'store_checkout_content'));

				if(count($store_checkout_content)){
					$store_checkout_content = $store_checkout_content[0]->id;
					$queries->update('store_settings', $store_checkout_content, array(
						'value' => Output::getClean(Input::get('store_checkout_content'))
					));
				} else {
					$queries->create('store_settings', array(
						'name' => 'store_checkout_content',
						'value' => Output::getClean(Input::get('store_checkout_content'))
					));
				}

			} catch(Exception $e){
				$errors[] = $e->getMessage();
			}

			try {
				$store_path = $queries->getWhere('store_settings', array('name', '=', 'store_path'));

				if(isset($_POST['store_path']) && strlen(str_replace(' ', '', $_POST['store_path'])) > 0)
					$store_path_input = rtrim(Output::getClean($_POST['store_path']), '/');
				else
					$store_path_input = '/store';

				if(count($store_path)){
					$store_path = $store_path[0]->id;
					$queries->update('store_settings', $store_path, array(
						'value' => $store_path_input
					));
				} else {
					$queries->create('store_settings', array(
						'name' => 'store_path',
						'value' => $store_path_input
					));
				}

				$cache->setCache('store_settings');
				$cache->store('store_url', $store_path_input);

			} catch(Exception $e){
				$errors[] = $e->getMessage();
			}

			if(!count($errors))
				$success = $store_language->get('admin', 'updated_successfully');

		} else {
			$errors[] = $store_language->get('admin', 'store_content_max');
		}

	} else
		$errors[] = $language->get('general', 'invalid_token');
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

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

$allow_guests = $queries->getWhere('store_settings', array('name', '=', 'allow_guests'));

if(count($allow_guests))
	$allow_guests = $allow_guests[0]->value;
else
	$allow_guests = 0;

$store_index_content = $queries->getWhere('store_settings', array('name', '=', 'store_content'));
if(count($store_index_content)){
	$store_index_content = Output::getClean(Output::getPurified(Output::getDecoded($store_index_content[0]->value)));
} else {
	$store_index_content = '';
}

$store_checkout_content = $queries->getWhere('store_settings', array('name', '=', 'store_checkout_content'));
if(count($store_checkout_content)){
	$store_checkout_content = Output::getClean(Output::getPurified(Output::getDecoded($store_checkout_content[0]->value)));
} else {
	$store_checkout_content = '';
}

$store_path = $queries->getWhere('store_settings', array('name', '=', 'store_path'));
if(count($store_path)){
	$store_path = Output::getClean($store_path[0]->value);
} else {
	$store_path = '/store';
}

$smarty->assign(array(
	'PARENT_PAGE' => PARENT_PAGE,
	'DASHBOARD' => $language->get('admin', 'dashboard'),
	'STORE' => $store_language->get('general', 'store'),
	'PAGE' => PANEL_PAGE,
	'TOKEN' => Token::get(),
	'SUBMIT' => $language->get('general', 'submit'),
	'SETTINGS' => $store_language->get('admin', 'settings'),
	'ALLOW_GUESTS' => $store_language->get('admin', 'allow_guests'),
	'ALLOW_GUESTS_VALUE' => ($allow_guests == 1),
	'STORE_INDEX_CONTENT' => $store_language->get('admin', 'store_index_content'),
	'STORE_INDEX_CONTENT_VALUE' => $store_index_content,
	'STORE_CHECKOUT_CONTENT' => $store_language->get('admin', 'store_checkout_content'),
	'STORE_CHECKOUT_CONTENT_VALUE' => $store_checkout_content,
	'STORE_PATH' => $store_language->get('admin', 'store_path'),
	'STORE_PATH_VALUE' => URL::build($store_path)
));

if(!defined('TEMPLATE_STORE_SUPPORT')){
	$template->addCSSFiles(array(
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.css' => array()
	));

	$template->addJSFiles(array(
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.js' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/js/emojione.min.js' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => array()
	));

	$template->addJSScript(Input::createEditor('inputStoreContent', true));
	$template->addJSScript(Input::createEditor('inputCheckoutContent', true));
	$template->addJSScript('
	var elems = Array.prototype.slice.call(document.querySelectorAll(\'.js-switch\'));

	elems.forEach(function(html) {
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