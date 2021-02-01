<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr6
 *
 *  License: MIT
 *
 *  Store page
 */

// Always define page name
define('PAGE', 'store');
$page_title = $store_language->get('general', 'store');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

require(ROOT_PATH . '/core/includes/emojione/autoload.php'); // Emojione
$emojione = new Emojione\Client(new Emojione\Ruleset());

// Get variables from cache
$cache->setCache('store_settings');
if($cache->isCached('store_url')){
	$store_url = Output::getClean(rtrim($cache->retrieve('store_url'), '/'));
} else {
	$store_url = '/store';
}

$content = $queries->getWhere('store_settings', array('name', '=', 'store_content'));
$content = Output::getDecoded($content[0]->value);
$content = $emojione->unicodeToImage($content);
$content = Output::getPurified($content);

if(Input::exists()){
	if(Token::check(Input::get('token'))){
		if(Input::get('type') == 'store_logout') {
			// Logout the store player
			unset($_SESSION['store_player']);
		}
	}
}

$categories_query = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE parent_category IS NULL AND deleted = 0 ORDER BY `order` ASC')->results();
$categories = array();

if(count($categories_query)){
	foreach($categories_query as $item){
		$subcategories_query = DB::getInstance()->query('SELECT id, `name` FROM nl2_store_categories WHERE parent_category = ? AND deleted = 0 ORDER BY `order` ASC', array($item->id))->results();
		
		$subcategories = array();
		if(count($subcategories_query)){
			foreach($subcategories_query as $subcategory){
				$subcategories[] = array(
					'url' => URL::build($store_url . '/category/' . Output::getClean($subcategory->id)),
					'title' => Output::getClean($subcategory->name)
				);
			}
		}

		$categories[$item->id] = array(
			'url' => URL::build($store_url . '/category/' . Output::getClean($item->id)),
			'title' => Output::getClean($item->name),
			'subcategories' => $subcategories
		);
	}
}

$smarty->assign(array(
	'STORE' => $store_language->get('general', 'store'),
	'STORE_URL' => URL::build($store_url),
	'HOME' => $store_language->get('general', 'home'),
	'HOME_URL' => URL::build($store_url),
	'CATEGORIES' => $categories,
	'CONTENT' => $content,
	'TOKEN' => Token::get(),
));

if(isset($_SESSION['store_player'])) {
	$smarty->assign(array(
		'STORE_PLAYER' => Output::getClean($_SESSION['store_player']['username'])
	));
}

$template->addCSSFiles(array(
	'https://cdn.namelesshosting.com/assets/plugins/ckeditor/plugins/spoiler/css/spoiler.css' => array(),
	'https://cdn.namelesshosting.com/assets/plugins/emoji/css/emojione.min.css' => array()
));

$template->addJSFiles(array(
	'https://cdn.namelesshosting.com/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array()
));

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets, $template);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

$smarty->assign('WIDGETS', $widgets->getWidgets());

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate('store/index.tpl', $smarty);
