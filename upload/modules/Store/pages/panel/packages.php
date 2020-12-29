<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/
 *
 *  Store module
 */

// Can the user view the StaffCP?
if($user->isLoggedIn()){
	if(!$user->canViewACP()){
		// No
		Redirect::to('/');
		die();
	} else {
		// Check the user has re-authenticated
		if(!$user->isAdmLoggedIn()){
			// They haven't, do so now
			Redirect::to('/panel/auth');
			die();
		} else {
			if(!$user->hasPermission('staffcp.store.packages')){
				Redirect::to('/panel');
				die();
			}
		}
	}
} else {
	// Not logged in
	Redirect::to('/login');
	die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_packages');
$page_title = $store_language->get('general', 'packages');
require_once(ROOT_PATH . '/core/templates/backend_init.php');
require_once(ROOT_PATH . '/modules/Store/classes/Store.php');
$store = new Store();

if(!isset($_GET['action'])) {
	// Get all packages and categories
	$categories = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE deleted = 0 ORDER BY `order` ASC', array());
	$all_categories = [];

	if($categories->count()){
		$categories = $categories->results();
		
		$currency = $queries->getWhere('store_settings', array('name', '=', 'currency_symbol'));
		if(count($currency))
			$currency = Output::getPurified($currency[0]->value);
		else
			$currency = '';

		foreach($categories as $category){
			$new_category = array(
				'name' => Output::getClean(Output::getDecoded($category->name)),
				'packages' => array(),
				'edit_link' => '/panel/store/categories/?action=edit&id=' . Output::getClean($category->id),
				'delete_link' => '/panel/store/categories/?action=delete&id=' . Output::getClean($category->id)
			);

			$packages = DB::getInstance()->query('SELECT * FROM nl2_store_packages WHERE category_id = ? AND deleted = 0 ORDER BY `order` ASC', array(Output::getClean($category->id)));

			if($packages->count()){
				$packages = $packages->results();

				foreach($packages as $package){
					$new_package = array(
						'id' => Output::getClean($package->id),
						'id_x' => str_replace('{x}', Output::getClean($package->id), $store_language->get('admin', 'id_x')),
						'name' => Output::getClean($package->name),
						'price' => $currency . Output::getClean($package->price) . ' USD',
						'edit_link' => '/panel/store/packages/?action=edit&id=' . Output::getClean($package->id),
						'delete_link' => '/panel/store/packages/?action=delete&id=' . Output::getClean($package->id)
					);

					$new_category['packages'][] = $new_package;
				}
			}

			$all_categories[] = $new_category;
		}
		
	} else {
		$smarty->assign('NO_PACKAGES', $store_language->get('general', 'no_packages'));
	}

	$smarty->assign(array(
		'ALL_CATEGORIES' => $all_categories,
		'CURRENCY' => $currency,
		'NEW_CATEGORY' => $store_language->get('admin', 'new_category'),
		'NEW_PACKAGE' => $store_language->get('admin', 'new_package'),
		'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
		'CONFIRM_DELETE_CATEGORY' => $store_language->get('admin', 'category_confirm_delete'),
		'CONFIRM_DELETE_PACKAGE' => $store_language->get('admin', 'package_confirm_delete'),
		'YES' => $language->get('general', 'yes'),
		'NO' => $language->get('general', 'no'),
	));
	
	$template->addJSFiles(array(
		'https://cdn.namelesshosting.com/assets/js/jquery-ui.min.js' => array()
	));

	$template_file = 'store/packages.tpl';
} else {
	switch($_GET['action']) {
		case 'new';
			// Create new package
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
						// Validate if category exist
						$category = DB::getInstance()->query('SELECT id FROM nl2_store_categories WHERE id = ?', array(Input::get('category')))->results();
						if(!count($category)) {
							$errors[] = $store_language->get('admin', 'invalid_category');
						}
						
						// Get price
						if(!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0.01 || $_POST['price'] > 1000 || !preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])){
							$errors[] = $store_language->get('admin', 'invalid_price');
						} else {
							$price = number_format($_POST['price'], 2, '.', '');
						}

						// insert into database if there is no errors
						if(!count($errors)) {
							// Get last order
							$last_order = DB::getInstance()->query('SELECT * FROM nl2_store_packages ORDER BY `order` DESC LIMIT 1')->results();
							if(count($last_order)) $last_order = $last_order[0]->order;
							else $last_order = 0;

							// Save to database
							$queries->create('store_packages', array(
								'name' => Output::getClean(Input::get('name')),
								'description' => Output::getClean(Input::get('description')),
								'category_id' => $category[0]->id,
								'price' => $price,
								'order' => $last_order + 1,
							));
							
							$lastId = $queries->getLastId();
							
							Session::flash('packages_success', $store_language->get('admin', 'package_created_successfully'));
							Redirect::to('/panel/store/packages/?action=edit&id=' . $lastId);
							die();
						}
					} else {
						$errors[] = $store_language->get('admin', 'description_max_100000');
					}
				} else {
					// Invalid token
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
			
			$smarty->assign(array(
				'NEW_PACKAGE' => $store_language->get('admin', 'new_package'),
				'BACK' => $language->get('general', 'back'),
				'PACKAGE_NAME' => $store_language->get('admin', 'package_name'),
				'PACKAGE_DESCRIPTION' => $store_language->get('admin', 'package_description'),
				'PRICE' => $store_language->get('admin', 'price'),
				'CATEGORY' => $store_language->get('admin', 'category'),
				'CATEGORY_LIST' => $store->getAllCategories()
			));
			
			$template->addJSFiles(array(
				'https://cdn.namelesshosting.com/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
				'https://cdn.namelesshosting.com/assets/plugins/ckeditor/ckeditor.js' => array()
			));

			$template->addJSScript(Input::createEditor('inputDescription'));
			
			$template_file = 'store/packages_new.tpl';
		break;
		case 'edit';
			// Edit package
			if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
				Redirect::to('/panel/store/packages');
				die();
			}
			
			$package = DB::getInstance()->query('SELECT * FROM nl2_store_packages WHERE id = ?', array($_GET['id']))->results();
			if(!count($package)) {
				Redirect::to('/panel/store/packages');
				die();
			}
			$package = $package[0];
			
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					// Update Package
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
						// Validate if category exist
						$category = DB::getInstance()->query('SELECT id FROM nl2_store_categories WHERE id = ?', array(Input::get('category')))->results();
						if(!count($category)) {
							$errors[] = $store_language->get('admin', 'invalid_category');
						}
							
						// Get price
						if(!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0.01 || $_POST['price'] > 1000 || !preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])){
							$errors[] = $store_language->get('admin', 'invalid_price');
						} else {
							$price = number_format($_POST['price'], 2, '.', '');
						}

						// insert into database if there is no errors
						if(!count($errors)) {
							// Save to database
							$queries->update('store_packages', $package->id, array(
								'name' => Output::getClean(Input::get('name')),
								'description' => Output::getClean(Input::get('description')),
								'category_id' => $category[0]->id,
								'price' => $price
							));
								
							Session::flash('packages_success', $store_language->get('admin', 'package_updated_successfully'));
							Redirect::to('/panel/store/packages');
							die();
						}
					} else {
						$errors[] = $store_language->get('admin', 'description_max_100000');
					}
				} else {
					// Invalid token
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
			
			// Get package commands
			$commands = DB::getInstance()->query('SELECT * FROM nl2_store_packages_commands WHERE package_id = ? ORDER BY `order` ASC', array($package->id));
			$commands_array = array();
			if($commands->count()){
				$commands = $commands->results();
				foreach($commands as $command) {
					$type = 'Unknown';
					switch($command->type) {
						case 1:
							$type = 'Purchase';
						break;
						case 2:
							$type = 'Refund';
						break;
						case 3:
							$type = 'Changeback';
						break;
					}
					
					$commands_array[] = array(
						'id' => Output::getClean($command->id),
						'command' => Output::getClean($command->command),
						'type' => $type,
						'requirePlayer' => ($command->require_online ? 'Yes' : 'No')
					);
				}
			}
			
			
			$smarty->assign(array(
				'EDITING_PACKAGE' => str_replace('{x}', Output::getClean($package->name), $store_language->get('admin', 'editing_package_x')),
				'ID' => Output::getClean($package->id),
				'BACK' => $language->get('general', 'back'),
				'PACKAGE_NAME' => $store_language->get('admin', 'package_name'),
				'PACKAGE_NAME_VALUE' => Output::getClean($package->name),
				'PACKAGE_DESCRIPTION' => $store_language->get('admin', 'package_description'),
				'PACKAGE_DESCRIPTION_VALUE' => Output::getPurified(Output::getDecoded($package->description)),
				'PRICE' => $store_language->get('general', 'price'),
				'PACKAGE_PRICE_VALUE' => Output::getClean($package->price),
				'PACKAGE_CATEGORY_VALUE' => Output::getClean($package->category_id),
				'CATEGORY' => $store_language->get('admin', 'category'),
				'CATEGORY_LIST' => $store->getAllCategories(),
				'COMMANDS' => $store_language->get('admin', 'commands'),
				'NEW_COMMAND' => $store_language->get('admin', 'new_command'),
				'COMMAND_LIST' => $commands_array
			));
			
			$template->addJSFiles(array(
				'https://cdn.namelesshosting.com/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
				'https://cdn.namelesshosting.com/assets/plugins/ckeditor/ckeditor.js' => array()
			));

			$template->addJSScript(Input::createEditor('inputDescription'));
			
			$template_file = 'store/packages_edit.tpl';
		break;
		case 'delete';
			// Delete package
			if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
				Redirect::to('/panel/store/packages');
				die();
			}
			
			$package = DB::getInstance()->query('SELECT id FROM `nl2_store_packages` WHERE id = ?', array($_GET['id']))->results();
			if(!count($package)) {
				Redirect::to('/panel/store/packages');
				die();
			}
			$package = $package[0];
			
			$queries->update('store_packages', $package->id, array(
				'deleted' => date('U')
			));
			
			Session::flash('packages_success', $store_language->get('admin', 'package_deleted_successfully'));
			Redirect::to('/panel/store/packages');
			die();
		break;
		case 'new_command';
			// New command for package
			if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
				Redirect::to('/panel/store/packages');
				die();
			}
			
			$package = DB::getInstance()->query('SELECT id, name FROM nl2_store_packages WHERE id = ?', array($_GET['id']))->results();
			if(!count($package)) {
				Redirect::to('/panel/store/packages');
				die();
			}
			$package = $package[0];
			
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					// New Command
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'command' => array(
							'required' => true,
							'min' => 1,
							'max' => 500
						)
					));
						
					if ($validation->passed()){
						$trigger = Input::get('trigger');
						if(!in_array($trigger, array(1,2,3))) {
							$errors[] = 'Invalid Trigger';
						}
							
						$require_player = Input::get('requirePlayer');
						if(!in_array($require_player, array(0,1))) {
							$errors[] = 'Invalid requirePlayer';
						}
							
						if(!count($errors)) {
							// Get last order
							$last_order = DB::getInstance()->query('SELECT id FROM nl2_store_packages_commands WHERE package_id = ? ORDER BY `order` DESC LIMIT 1', array($package->id))->results();
							if(count($last_order)) $last_order = $last_order[0]->order;
							else $last_order = 0;
							
							// Save to database
							$queries->create('store_packages_commands', array(
								'package_id' => $package->id,
                                'server_id' => 0,
								'type' => $trigger,
								'command' => Output::getClean(Input::get('command')),
								'require_online' => $require_player,
								'order' => $last_order + 1,
							));
							
							Session::flash('packages_success', $store_language->get('admin', 'command_created_successfully'));
							Redirect::to('/panel/store/packages/?action=edit&id=' . $package->id);
							die();
						}
					} else {
						$errors[] = $store_language->get('admin', 'command_max');
					}
				} else {
					// Invalid token
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
			
			$smarty->assign(array(
				'ID' => Output::getClean($package->id),
				'NEW_COMMAND' => str_replace('{x}', Output::getClean($package->name), $store_language->get('admin', 'new_command_for_x')),
				'BACK' => $language->get('general', 'back'),
			));
			
			$template_file = 'store/packages_command_new.tpl';
		break;
		case 'edit_command';
			// Editing command for package
			if(!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['command']) || !is_numeric($_GET['command'])){
				Redirect::to('/panel/store/packages');
				die();
			}
			
			$package = DB::getInstance()->query('SELECT id, name FROM nl2_store_packages WHERE id = ?', array($_GET['id']))->results();
			if(!count($package)) {
				Redirect::to('/panel/store/packages');
				die();
			}
			$package = $package[0];
			
			$command = DB::getInstance()->query('SELECT * FROM nl2_store_packages_commands WHERE id = ?', array($_GET['command']))->results();
			if(!count($command)) {
				Redirect::to('/panel/store/packages');
				die();
			}
			$command = $command[0];
			
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					// New Command
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'command' => array(
							'required' => true,
							'min' => 1,
							'max' => 500
						)
					));
						
					if ($validation->passed()){
						$trigger = Input::get('trigger');
						if(!in_array($trigger, array(1,2,3))) {
							$errors[] = 'Invalid Trigger';
						}
							
						$require_player = Input::get('requirePlayer');
						if(!in_array($require_player, array(0,1))) {
							$errors[] = 'Invalid requirePlayer';
						}
							
						if(!count($errors)) {
							// Save to database
							$queries->update('store_packages_commands', $command->id, array(
								'type' => $trigger,
								'command' => Output::getClean(Input::get('command')),
								'require_online' => $require_player
							));
							
							Session::flash('packages_success', $store_language->get('admin', 'command_updated_successfully'));
							Redirect::to('/panel/store/packages/?action=edit&id=' . $package->id);
							die();
						}
					} else {
						$errors[] = $store_language->get('admin', 'command_max');
					}
				} else {
					// Invalid token
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
			
			$smarty->assign(array(
				'ID' => Output::getClean($package->id),
				'EDITING_COMMAND' => str_replace('{x}', Output::getClean($package->name), $store_language->get('admin', 'editing_command_for_x')),
				'BACK' => $language->get('general', 'back'),
				'TRIGGER_VALUE' => Output::getClean($command->type),
				'REQUIRE_PLAYER_VALUE' => Output::getClean($command->require_online),
				'COMMAND_VALUE' => Output::getClean($command->command),
			));
		
			$template_file = 'store/packages_command_edit.tpl';
		break;
		case 'delete_command';
			// Delete package
			if(!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['command']) || !is_numeric($_GET['command'])){
				Redirect::to('/panel/store/packages');
				die();
			}
			
			$queries->delete('store_packages_commands', array('id', '=', $_GET['command']));

			Session::flash('packages_success', $store_language->get('admin', 'command_deleted_successfully'));
			Redirect::to('/panel/store/packages/?action=edit&id=' . $_GET['id']);
			die();
		break;
		default:
			Redirect::to('/panel/core/packages');
			die();
		break;
	}
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

if(Session::exists('packages_success'))
	$success = Session::flash('packages_success');

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
	'PACKAGES' => $store_language->get('general', 'packages')
));

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);