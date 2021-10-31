<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Category Page
 */

// Always define page name
define('PAGE', 'store');

require_once(ROOT_PATH . '/modules/Store/core/frontend_init.php');

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
                // Attempt to load player
                if(!$player->login(Output::getClean(Input::get('username')))) {
                    $errors[] = $language->get('user', 'invalid_mcname');
                }
                
                Redirect::to(URL::build($store_url . '/category/' . $category->id));
                die();
            } else {
                $errors[] = 'Unable to find a player with that username';
            }
        }
    }
}

// Get products
$products = DB::getInstance()->query('SELECT id, name, `order`, price, description, image FROM nl2_store_products WHERE category_id = ? AND deleted = 0 ORDER BY `order` ASC', array($category_id));

if(!$products->count()){
    $smarty->assign('NO_PRODUCTS', $store_language->get('general', 'no_products'));
} else {
    $products = $products->results();
    $category_products = array();

    foreach($products as $product){
        $content = Output::getDecoded($product->description);
        $content = $emojione->unicodeToImage($content);
        $content = Output::getPurified($content);

        $image = (isset($product->image) && !is_null($product->image) ? (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/' . 'uploads/store/' . Output::getClean(Output::getDecoded($product->image))) : null);

        $category_products[] = array(
            'id' => Output::getClean($product->id),
            'name' => Output::getClean($product->name),
            'price' => Output::getClean($product->price),
            'real_price' => Output::getClean($product->price),
            'description' => $content,
            'image' => $image,
            'link' => URL::build($store_url . '/checkout', 'add=' . Output::getClean($product->id))
        );
    }

    $smarty->assign('PRODUCTS', $category_products);
}

$smarty->assign(array(
    'ACTIVE_CATEGORY' => Output::getClean($category->name),
    'BUY' => $store_language->get('general', 'buy'),
    'CLOSE' => $language->get('general', 'close'),
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

if(!$player->isLoggedIn()) {
    $smarty->assign(array(
        'PLEASE_ENTER_USERNAME' => $store_language->get('general', 'please_enter_username'),
        'CONTINUE' => $store_language->get('general', 'continue'),
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
