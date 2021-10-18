<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com
 *  NamelessMC version 2.0.0-pr8
 *
 *  License: MIT
 *
 *  Store page - category view
 */

// Always define page name
define('PAGE', 'store');

require_once(ROOT_PATH . '/modules/Store/classes/Store.php');
$store = new Store($cache, $store_language);

// Get category ID
$category_id = explode('/', $route);
$category_id = $category_id[count($category_id) - 1];

if (!strlen($category_id)) {
	require_once(ROOT_PATH . '404.php');
	die();
}

$category_id = explode('-', $category_id);
if(!is_numeric($category_id[0])){
	require_once(ROOT_PATH . '/404.php');
	die();
}
$category_id = $category_id[0];

// Query category
$category = DB::getInstance()->query('SELECT id, name, parent_category, description, image FROM nl2_store_categories WHERE id = ?', array($category_id));
if(!$category->count()){
	require_once(ROOT_PATH . '/404.php');
	die();
}

$category = $category->first();
$store_url = $store->getStoreURL();

$page_metadata = $queries->getWhere('page_descriptions', array('page', '=', $store_url . '/view'));
if(count($page_metadata)){
	define('PAGE_DESCRIPTION', str_replace(array('{site}', '{category_title}', '{description}'), array(SITE_NAME, Output::getClean($category->name), Output::getClean(strip_tags(Output::getDecoded($category->description)))), $page_metadata[0]->description));
	define('PAGE_KEYWORDS', $page_metadata[0]->tags);
}

$page_title = Output::getClean($category->name);
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

require(ROOT_PATH . '/core/includes/emojione/autoload.php'); // Emojione
$emojione = new Emojione\Client(new Emojione\Ruleset());

$currency = $queries->getWhere('store_settings', array('name', '=', 'currency_symbol'));
$currency = Output::getPurified($currency[0]->value);

if(Input::exists()){
	if(Token::check(Input::get('token'))){
        $errors = array();
        
		if(Input::get('type') == 'store_login') {
			$validate = new Validate();
			$validation = $validate->check($_POST, array(
				'username' => array(
					'required' => true,
					'min' => 3,
					'max' => 16
				)
			));
			
			if($validation->passed()){
				$username = Output::getClean(Input::get('username'));
				$uuid = '';
				
				require(ROOT_PATH . '/core/integration/uuid.php'); // For UUID stuff
				
				$profile = ProfileUtils::getProfile(str_replace(' ', '%20', Input::get('username')));
				$mcname_result = $profile ? $profile->getProfileAsArray() : array();
				if(isset($mcname_result['username']) && !empty($mcname_result['username']) && isset($mcname_result['uuid']) && !empty($mcname_result['uuid'])){
					$username = Output::getClean($mcname_result['username']);
					$uuid = ProfileUtils::formatUUID(Output::getClean($mcname_result['uuid']));
				} else {
					// Invalid Minecraft name
					$errors[] = $language->get('user', 'invalid_mcname');
				}

				if(!count($errors)) {
					// Save store player into session
					$store_player = array(
						'username' => Output::getClean($username),
						'uuid' => Output::getClean($uuid)
					);
					
					$_SESSION['store_player'] = $store_player;
				}
			} else {
				$errors[] = 'Unable to find a player with that username';
			}
		} else if(Input::get('type') == 'store_logout') {
			// Logout the store player
			unset($_SESSION['store_player']);
		}
	}
}

// Get packages
$packages = DB::getInstance()->query('SELECT id, name, `order`, price, description, image FROM nl2_store_packages WHERE category_id = ? AND deleted = 0 ORDER BY `order` ASC', array($category_id));

if(!$packages->count()){
	$smarty->assign('NO_PACKAGES', $store_language->get('general', 'no_packages'));
} else {
	$packages = $packages->results();
	$category_packages = array();

	foreach($packages as $package){
		$content = Output::getDecoded($package->description);
		$content = $emojione->unicodeToImage($content);
		$content = Output::getPurified($content);

		$image = (isset($package->image) && !is_null($package->image) ? (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/' . 'uploads/store/' . Output::getClean(Output::getDecoded($package->image))) : null);

		$category_packages[] = array(
			'id' => Output::getClean($package->id),
			'name' => Output::getClean($package->name),
			'price' => Output::getClean($package->price),
			'real_price' => Output::getClean($package->price),
			'description' => $content,
			'image' => $image,
			'link' => URL::build($store_url . '/checkout', 'add=' . Output::getClean($package->id))
		);
	}

	$smarty->assign('PACKAGES', $category_packages);
}

$smarty->assign(array(
	'ACTIVE_CATEGORY' => Output::getClean($category->name),
	'BUY' => $store_language->get('general', 'buy'),
	'CLOSE' => $language->get('general', 'close'),
	'CURRENCY' => $currency,
	'SALE' => $store_language->get('general', 'sale')
));

if(isset($errors) && count($errors))
	$smarty->assign('ERRORS', $errors);

$smarty->assign(array(
	'STORE' => $store_language->get('general', 'store'),
	'STORE_URL' => URL::build($store_url),
	'HOME' => $store_language->get('general', 'home'),
	'HOME_URL' => URL::build($store_url),
	'CATEGORIES' => $store->getNavbarMenu($category->name),
	'CONTENT' => $content,
	'TOKEN' => Token::get(),
));

if(!isset($_SESSION['store_player'])) {
	$smarty->assign(array(
		'PLEASE_ENTER_USERNAME' => $store_language->get('general', 'please_enter_username'),
		'CONTINUE' => $store_language->get('general', 'continue'),
	));
} else {
	$smarty->assign(array(
		'STORE_PLAYER' => Output::getClean($_SESSION['store_player']['username'])
	));
}

$template->addCSSFiles(array(
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/css/spoiler.css' => array(),
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/css/emojione.min.css' => array()
));

$template->addJSFiles(array(
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array()
));

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets, $template);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

$smarty->assign('WIDGETS_LEFT', $widgets->getWidgets('left'));
$smarty->assign('WIDGETS_RIGHT', $widgets->getWidgets('right'));

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate('store/category.tpl', $smarty);
