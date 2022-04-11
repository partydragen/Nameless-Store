<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel categories page
 */

// Can the user view the StaffCP?
if (!$user->handlePanelPageLoad('staffcp.store.products')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_products');
$page_title = $store_language->get('admin', 'categories');
require_once(ROOT_PATH . '/core/templates/backend_init.php');
require_once(ROOT_PATH . '/modules/Store/classes/Store.php');

$store = new Store($cache, $store_language);

if (!isset($_GET['action'])) {
    Redirect::to(URL::build('/panel/core/products'));
    die();
} else {
    switch ($_GET['action']) {
        case 'new';
            // Create new category
            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    $validate = new Validate();
                    $validation = $validate->check($_POST, [
                        'name' => [
                            'required' => true,
                            'min' => 1,
                            'max' => 128
                        ],
                        'description' => [
                            'max' => 100000
                        ]
                    ]);
                    
                    if ($validation->passed()) {
                        // Get last order
                        $last_order = DB::getInstance()->query('SELECT * FROM nl2_store_categories ORDER BY `order` DESC LIMIT 1')->results();
                        if (count($last_order)) $last_order = $last_order[0]->order;
                        else $last_order = 0;
                        
                        $parent_category = Input::get('parent_category');
                        
                        // Hide category?
                        if (isset($_POST['hidden']) && $_POST['hidden'] == 'on') $hidden = 1;
                        else $hidden = 0;
                        
                        // Hide category from dropdown?
                        if (isset($_POST['only_subcategories']) && $_POST['only_subcategories'] == 'on') $only_subcategories = 1;
                        else $only_subcategories = 0;
                        
                        // Disable category?
                        if (isset($_POST['disabled']) && $_POST['disabled'] == 'on') $disabled = 1;
                        else $disabled = 0;
                        
                        // Save to database
                        $queries->create('store_categories', [
                            'name' => Output::getClean(Input::get('name')),
                            'description' => Output::getClean(Input::get('description')),
                            'parent_category' => $parent_category != 0 ? $parent_category : null,
                            'only_subcategories' => $only_subcategories,
                            'hidden' => $hidden,
                            'disabled' => $disabled,
                            'order' => $last_order + 1,
                        ]);
                        
                        Session::flash('products_success', $store_language->get('admin', 'category_created_successfully'));
                        Redirect::to(URL::build('/panel/store/products'));
                        die();
                    } else {
                        $errors[] = $store_language->get('admin', 'description_max_100000');
                    }
                } else {
                    // Invalid token
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }
            
            $categories_list = [];
            $categories = DB::getInstance()->query('SELECT id, name FROM nl2_store_categories WHERE deleted = 0')->results();
            foreach ($categories as $category) {
                $categories_list[] = [
                    'id' => Output::getClean($category->id),
                    'name' => Output::getClean($category->name),
                ];
            }
            
            $smarty->assign([
                'CATEGORY_TITLE' => $store_language->get('admin', 'new_category'),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/products'),
                'CATEGORY_NAME' => $store_language->get('admin', 'category_name'),
                'CATEGORY_NAME_VALUE' => ((isset($_POST['name']) && $_POST['name']) ? Output::getClean(Input::get('name')) : ''),
                'CATEGORY_DESCRIPTION' => $store_language->get('admin', 'category_description'),
                'CATEGORY_DESCRIPTION_VALUE' => ((isset($_POST['description']) && $_POST['description']) ? Output::getClean(Input::get('description')) : ''),
                'PARENT_CATEGORY' => $store_language->get('admin', 'parent_category'),
                'PARENT_CATEGORY_LIST' => $categories_list,
                'PARENT_CATEGORY_VALUE' => ((isset($_POST['parent_category']) && $_POST['parent_category']) ? Output::getClean(Input::get('parent_category')) : 0),
                'NO_PARENT' => $store_language->get('admin', 'no_parent'),
                'ONLY_SUBCATEGORIES' => $store_language->get('admin', 'hide_category_from_dropdown_menu'),
                'ONLY_SUBCATEGORIES_VALUE' => ((isset($_POST['only_subcategories'])) ? 1 : 0),
                'HIDE_CATEGORY' => $store_language->get('admin', 'hide_category_from_menu'),
                'HIDE_CATEGORY_VALUE' => ((isset($_POST['hidden'])) ? 1 : 0),
                'DISABLE_CATEGORY' => $store_language->get('admin', 'disable_category'),
                'DISABLE_CATEGORY_VALUE' => ((isset($_POST['disabled'])) ? 1 : 0),
            ]);
            
            $template->addJSFiles([
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => [],
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => []
            ]);

            $template->addJSScript(Input::createEditor('inputDescription'));
            
            $template_file = 'store/categories_form.tpl';
        break;
        case 'edit';
            // Edit category
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/store/products'));
                die();
            }
            
            $category = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE id = ?', [$_GET['id']])->results();
            if (!count($category)) {
                Redirect::to(URL::build('/panel/store/products'));
                die();
            }
            $category = $category[0];

            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    $validate = new Validate();
                    $validation = $validate->check($_POST, [
                        'name' => [
                            'required' => true,
                            'min' => 1,
                            'max' => 128
                        ],
                        'description' => [
                            'max' => 100000
                        ]
                    ]);
                    
                    if ($validation->passed()) {
                        $parent_category = Input::get('parent_category');
                        
                        // Hide category?
                        if (isset($_POST['hidden']) && $_POST['hidden'] == 'on') $hidden = 1;
                        else $hidden = 0;
                        
                        // Hide category from dropdown?
                        if (isset($_POST['only_subcategories']) && $_POST['only_subcategories'] == 'on') $only_subcategories = 1;
                        else $only_subcategories = 0;
                        
                        // Disable category?
                        if (isset($_POST['disabled']) && $_POST['disabled'] == 'on') $disabled = 1;
                        else $disabled = 0;
                        
                        // Save to database
                        $queries->update('store_categories', $category->id, [
                            'name' => Output::getClean(Input::get('name')),
                            'description' => Output::getClean(Input::get('description')),
                            'parent_category' => $parent_category != 0 ? $parent_category : null,
                            'only_subcategories' => $only_subcategories,
                            'hidden' => $hidden,
                            'disabled' => $disabled
                        ]);
                        
                        Session::flash('products_success', $store_language->get('admin', 'category_updated_successfully'));
                        Redirect::to(URL::build('/panel/store/products'));
                        die();
                    } else {
                        $errors[] = $store_language->get('admin', 'description_max_100000');
                    }
                } else {
                    // Invalid token
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }
            
            $categories_list = [];
            $categories = DB::getInstance()->query('SELECT id, name FROM nl2_store_categories WHERE id <> ? AND deleted = 0', [$category->id])->results();
            foreach ($categories as $item) {
                $categories_list[] = [
                    'id' => Output::getClean($item->id),
                    'name' => Output::getClean($item->name),
                ];
            }
            
            $smarty->assign([
                'CATEGORY_TITLE' => str_replace('{x}', Output::getClean($category->name), $store_language->get('admin', 'editing_category_x')),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/products'),
                'CATEGORY_NAME' => $store_language->get('admin', 'category_name'),
                'CATEGORY_NAME_VALUE' => Output::getClean($category->name),
                'CATEGORY_DESCRIPTION' => $store_language->get('admin', 'category_description'),
                'CATEGORY_DESCRIPTION_VALUE' => Output::getPurified(Output::getDecoded($category->description)),
                'PARENT_CATEGORY' => $store_language->get('admin', 'parent_category'),
                'PARENT_CATEGORY_LIST' => $categories_list,
                'PARENT_CATEGORY_VALUE' => Output::getClean($category->parent_category),
                'NO_PARENT' => $store_language->get('admin', 'no_parent'),
                'ONLY_SUBCATEGORIES' => $store_language->get('admin', 'hide_category_from_dropdown_menu'),
                'ONLY_SUBCATEGORIES_VALUE' => $category->only_subcategories,
                'HIDE_CATEGORY' => $store_language->get('admin', 'hide_category_from_menu'),
                'HIDE_CATEGORY_VALUE' => $category->hidden,
                'DISABLE_CATEGORY' => $store_language->get('admin', 'disable_category'),
                'DISABLE_CATEGORY_VALUE' => $category->disabled,
            ]);
            
            $template->addJSFiles([
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => [],
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => []
            ]);

            $template->addJSScript(Input::createEditor('inputDescription'));
            
            $template_file = 'store/categories_form.tpl';
        break;
        case 'delete';
            // Delete category
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/store/products'));
                die();
            }
            
            $category = DB::getInstance()->query('SELECT * FROM `nl2_store_categories` WHERE id = ?', [$_GET['id']])->results();
            if (!count($category)) {
                Redirect::to(URL::build('/panel/store/products'));
                die();
            }
            $category = $category[0];
            
            $products = DB::getInstance()->query('SELECT id FROM `nl2_store_products` WHERE category_id = ? AND deleted = 0', [$_GET['id']])->results();
            if (count($products)) {
                foreach ($products as $product) {
                    $queries->update('store_products', $product->id, [
                        'deleted' => date('U')
                    ]);
                }
            }
            
            $queries->update('store_categories', $category->id, [
                'deleted' => date('U')
            ]);
            
            Session::flash('products_success', $store_language->get('admin', 'category_deleted_successfully'));
            Redirect::to(URL::build('/panel/store/products'));
            die();
        break;
        default:
            Redirect::to(URL::build('/panel/core/products'));
            die();
        break;
    }
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

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('general', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'STORE' => $store_language->get('general', 'store'),
    'CATEGORIES' => $store_language->get('admin', 'categories'),
    'PRODUCTS' => $store_language->get('general', 'products')
]);

$template->addCSSFiles([
    (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.css' => []
]);

$template->addJSFiles([
    (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.js' => []
]);

$template->addJSScript('
    var elems = Array.prototype.slice.call(document.querySelectorAll(\'.js-switch\'));
    elems.forEach (function(html) {
        var switchery = new Switchery(html, {color: \'#23923d\', secondaryColor: \'#e56464\'});
    });
');

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);