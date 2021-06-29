<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel categories page
 */

// Can the user view the StaffCP?
if(!$user->handlePanelPageLoad('staffcp.store.packages')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_packages');
$page_title = $store_language->get('admin', 'categories');
require_once(ROOT_PATH . '/core/templates/backend_init.php');
require_once(ROOT_PATH . '/modules/Store/classes/Store.php');

if(!isset($_GET['action'])) {
	Redirect::to(URL::build('/panel/core/packages'));
	die();
} else {
	switch($_GET['action']) {
		case 'new';
			// Create new category
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'name' => array(
							'required' => true,
							'min' => 1,
							'max' => 128
						),
						'description' => array(
							'max' => 100000
						)
					));
					
					if ($validation->passed()){
						// Get last order
						$last_order = DB::getInstance()->query('SELECT * FROM nl2_store_categories ORDER BY `order` DESC LIMIT 1')->results();
						if(count($last_order)) $last_order = $last_order[0]->order;
						else $last_order = 0;
						
						// Save to database
						$queries->create('store_categories', array(
							'name' => Output::getClean(Input::get('name')),
							'description' => Output::getClean(Input::get('description')),
							'order' => $last_order + 1,
						));
						
						Session::flash('packages_success', $store_language->get('admin', 'category_created_successfully'));
						Redirect::to(URL::build('/panel/store/packages'));
						die();
					} else {
						$errors[] = $store_language->get('admin', 'description_max_100000');
					}
				} else {
					// Invalid token
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
			
			$smarty->assign(array(
				'NEW_CATEGORY' => $store_language->get('admin', 'new_category'),
				'BACK' => $language->get('general', 'back'),
				'CATEGORY_NAME' => $store_language->get('admin', 'category_name'),
				'CATEGORY_DESCRIPTION' => $store_language->get('admin', 'category_description'),
			));
			
			$template->addJSFiles(array(
				(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
				(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => array()
			));

			$template->addJSScript(Input::createEditor('inputDescription'));
			
			$template_file = 'store/categories_new.tpl';
		break;
		case 'edit';
			// Edit category
			if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
				Redirect::to(URL::build('/panel/store/packages'));
				die();
			}
			
			$category = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE id = ?', array($_GET['id']))->results();
			if(!count($category)) {
				Redirect::to(URL::build('/panel/store/packages'));
				die();
			}
			$category = $category[0];
			
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'name' => array(
							'required' => true,
							'min' => 1,
							'max' => 128
						),
						'description' => array(
							'max' => 100000
						)
					));
					
					if ($validation->passed()){
						// Save to database
						$queries->update('store_categories', $category->id, array(
							'name' => Output::getClean(Input::get('name')),
							'description' => Output::getClean(Input::get('description')),
						));
						
						Session::flash('packages_success', $store_language->get('admin', 'category_updated_successfully'));
						Redirect::to(URL::build('/panel/store/packages'));
						die();
					} else {
						$errors[] = $store_language->get('admin', 'description_max_100000');
					}
				} else {
					// Invalid token
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
			
			$smarty->assign(array(
				'EDITING_CATEGORY' => str_replace('{x}', Output::getClean($category->name), $store_language->get('admin', 'editing_category_x')),
				'BACK' => $language->get('general', 'back'),
				'CATEGORY_NAME' => $store_language->get('admin', 'category_name'),
				'CATEGORY_NAME_VALUE' => Output::getClean($category->name),
				'CATEGORY_DESCRIPTION' => $store_language->get('admin', 'category_description'),
				'CATEGORY_DESCRIPTION_VALUE' => Output::getPurified(Output::getDecoded($category->description)),
			));
			
			$template->addJSFiles(array(
				(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
				(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => array()
			));

			$template->addJSScript(Input::createEditor('inputDescription'));
			
			$template_file = 'store/categories_edit.tpl';
		break;
		case 'delete';
			// Delete category
			if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
				Redirect::to(URL::build('/panel/store/packages'));
				die();
			}
			
			$category = DB::getInstance()->query('SELECT * FROM `nl2_store_categories` WHERE id = ?', array($_GET['id']))->results();
			if(!count($category)) {
				Redirect::to(URL::build('/panel/store/packages'));
				die();
			}
			$category = $category[0];
			
			$packages = DB::getInstance()->query('SELECT id FROM `nl2_store_packages` WHERE category_id = ? AND deleted = 0', array($_GET['id']))->results();
			if(count($packages)) {
				foreach($packages as $package) {
					$queries->update('store_packages', $package->id, array(
						'deleted' => date('U')
					));
				}
			}
			
			$queries->update('store_categories', $category->id, array(
				'deleted' => date('U')
			));
			
			Session::flash('packages_success', $store_language->get('admin', 'category_deleted_successfully'));
			Redirect::to(URL::build('/panel/store/packages'));
			die();
		break;
		default:
			Redirect::to(URL::build('/panel/core/packages'));
			die();
		break;
	}
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

$smarty->assign(array(
	'PARENT_PAGE' => PARENT_PAGE,
	'DASHBOARD' => $language->get('admin', 'dashboard'),
	'STORE' => $store_language->get('general', 'store'),
	'PAGE' => PANEL_PAGE,
	'TOKEN' => Token::get(),
	'SUBMIT' => $language->get('general', 'submit'),
    'STORE' => $store_language->get('general', 'store'),
    'CATEGORIES' => $store_language->get('admin', 'categories'),
	'PACKAGES' => $store_language->get('general', 'packages')
));

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);