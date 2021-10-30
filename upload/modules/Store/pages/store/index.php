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

require_once(ROOT_PATH . '/modules/Store/classes/Store.php');
require_once(ROOT_PATH . '/modules/Store/classes/Player.php');
require(ROOT_PATH . '/core/includes/emojione/autoload.php'); // Emojione
$emojione = new Emojione\Client(new Emojione\Ruleset());
$store = new Store($cache, $store_language);
$player = new Player();

$content = $queries->getWhere('store_settings', array('name', '=', 'store_content'));
$content = Output::getDecoded($content[0]->value);
$content = $emojione->unicodeToImage($content);
$content = Output::getPurified($content);

if(Input::exists()){
	if(Token::check(Input::get('token'))){
		if(Input::get('type') == 'store_logout') {
			// Logout the store player
			$player->logout();
		}
	}
}

$smarty->assign(array(
	'STORE' => $store_language->get('general', 'store'),
	'STORE_URL' => URL::build($store->getStoreURL()),
	'CATEGORIES' => $store->getNavbarMenu('Home'),
	'CONTENT' => $content,
	'TOKEN' => Token::get(),
));

if($player->isLoggedIn()) {
	$smarty->assign(array(
		'STORE_PLAYER' => $player->getUsername()
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
$template->displayTemplate('store/index.tpl', $smarty);
