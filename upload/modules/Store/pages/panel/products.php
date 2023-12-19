<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel products page
 */

// Can the user view the StaffCP?
if (!$user->handlePanelPageLoad('staffcp.store.products')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}
define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_products');
$page_title = $store_language->get('general', 'products');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

$store = new Store($cache, $store_language);
if (!isset($_GET['action'])) {
    // Get all products and categories
    $categories = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE deleted = 0 ORDER BY `order` ASC', []);
    $all_categories = [];

    if ($categories->count()) {
        $categories = $categories->results();

        $currency = Output::getClean(Store::getCurrency());
        $currency_symbol = Output::getClean(Store::getCurrencySymbol());

        foreach ($categories as $category) {
            $new_category = [
                'id' => Output::getClean($category->id),
                'name' => Output::getClean(Output::getDecoded($category->name)),
                'products' => [],
                'edit_link' => URL::build('/panel/store/categories/', 'action=edit&id=' . Output::getClean($category->id)),
                'delete_link' => URL::build('/panel/store/categories/', 'action=delete&id=' . Output::getClean($category->id))
            ];

            $products = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE category_id = ? AND deleted = 0 ORDER BY `order` ASC', [Output::getClean($category->id)]);

            if ($products->count()) {
                $products = $products->results();

                foreach ($products as $product) {
                    $new_product = [
                        'id' => Output::getClean($product->id),
                        'id_x' => $store_language->get('admin', 'id_x', ['id' => Output::getClean($product->id)]),
                        'name' => Output::getClean($product->name),
                        'price' => Store::fromCents($product->price_cents),
                        'price_format' => Output::getPurified(
                            Store::formatPrice(
                                $product->price_cents,
                                $currency,
                                $currency_symbol,
                                STORE_CURRENCY_FORMAT,
                            )
                        ),
                        'edit_link' => URL::build('/panel/store/product/', 'product=' . Output::getClean($product->id)),
                        'delete_link' => URL::build('/panel/store/product/', 'product=' . Output::getClean($product->id) . '&action=delete')
                    ];

                    $new_category['products'][] = $new_product;
                }
            }

            $all_categories[] = $new_category;
        }
        
    } else {
        $smarty->assign('NO_PRODUCTS', $store_language->get('general', 'no_products'));
    }

    $template->assets()->include(
        AssetTree::JQUERY_UI
    );

    $smarty->assign([
        'ALL_CATEGORIES' => $all_categories,
        'CURRENCY' => $currency,
        'CURRENCY_SYMBOL' => $currency_symbol,
        'NEW_CATEGORY' => $store_language->get('admin', 'new_category'),
        'NEW_CATEGORY_LINK' => URL::build('/panel/store/categories/', 'action=new'),
        'NEW_PRODUCT' => $store_language->get('admin', 'new_product'),
        'NEW_PRODUCT_LINK' => URL::build('/panel/store/products/', 'action=new'),
        'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
        'CONFIRM_DELETE_CATEGORY' => $store_language->get('admin', 'category_confirm_delete'),
        'CONFIRM_DELETE_PRODUCT' => $store_language->get('admin', 'product_confirm_delete'),
        'YES' => $language->get('general', 'yes'),
        'NO' => $language->get('general', 'no'),
        'REORDER_CATEGORY_URL' => URL::build('/panel/store/products', 'action=order_categories'),
        'REORDER_PRODUCTS_URL' => URL::build('/panel/store/products', 'action=order_products'),
    ]);

    $template_file = 'store/products.tpl';
} else {
    switch ($_GET['action']) {
        case 'new';
            // Create new product
            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    $validation = Validate::check($_POST, [
                        'name' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 1,
                            Validate::MAX => 128
                        ],
                        'description' => [
                            Validate::MAX => 100000
                        ]
                    ])->messages([
                        'name' => [
                            Validate::REQUIRED => $store_language->get('admin', 'name_required'),
                            Validate::MIN => $store_language->get('admin', 'name_minimum_x', ['min' => '1']),
                            Validate::MAX => $store_language->get('admin', 'name_maximum_x', ['max' => '128'])
                        ],
                        'description' => [
                            Validate::MAX => $store_language->get('admin', 'description_max_100000')
                        ]
                    ]);

                    if ($validation->passed()) {
                        // Validate if category exist
                        $category = DB::getInstance()->query('SELECT id FROM nl2_store_categories WHERE id = ?', [Input::get('category')])->results();
                        if (!count($category)) {
                            $errors[] = $store_language->get('admin', 'invalid_category');
                        }

                        // Get price
                        if (!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0.00 || $_POST['price'] > 20000000 || !preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])) {
                            $errors[] = $store_language->get('admin', 'invalid_price');
                        }

                        // insert into database if there is no errors
                        if (!count($errors)) {
                            // Get last order
                            $last_order = DB::getInstance()->query('SELECT * FROM nl2_store_products ORDER BY `order` DESC LIMIT 1')->results();
                            if (count($last_order)) $last_order = $last_order[0]->order;
                            else $last_order = 0;

                            // Hide category?
                            if (isset($_POST['hidden']) && $_POST['hidden'] == 'on') $hidden = 1;
                            else $hidden = 0;

                            // Disable category?
                            if (isset($_POST['disabled']) && $_POST['disabled'] == 'on') $disabled = 1;
                            else $disabled = 0;

                            // Remove from customer after (Expire)
                            if (isset($_POST['durability_period']) && $_POST['durability_period'] != 'never') {
                                $durability = json_encode([
                                    'interval' => $_POST['durability_interval'] ?? 1,
                                    'period' => $_POST['durability_period'] ?? 'never'
                                ]);
                            } else {
                                $durability = null;
                            }

                            // Save to database
                            DB::getInstance()->insert('store_products', [
                                'name' => Input::get('name'),
                                'description' => Input::get('description'),
                                'category_id' => $category[0]->id,
                                'price_cents' => Store::toCents(Input::get('price')),
                                'hidden' => $hidden,
                                'disabled' => $disabled,
                                'order' => $last_order + 1,
                                'durability' => $durability
                            ]);
                            $lastId = DB::getInstance()->lastId();
                            $product = new Product($lastId);

                            // Add the selected connections, if isset
                            if (isset($_POST['connections']) && is_array($_POST['connections'])) {
                                foreach ($_POST['connections'] as $connection) {
                                    if (!array_key_exists($connection, $product->getConnections())) {
                                        $product->addConnection($connection);
                                    }
                                }
                            }

                            // Add the selected fields, if isset
                            if (isset($_POST['fields']) && is_array($_POST['fields'])) {
                                foreach ($_POST['fields'] as $field) {
                                    if (!array_key_exists($field, $product->getFields())) {
                                        $product->addField($field);
                                    }
                                }
                            }

                            Session::flash('products_success', $store_language->get('admin', 'product_created_successfully'));
                            Redirect::to(URL::build('/panel/store/product/', 'product=' . $lastId));
                        }
                    } else {
                        $errors = $validation->errors();
                    }
                } else {
                    // Invalid token
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }

            // Connections
            $connections_array = [];
            $connections = DB::getInstance()->query('SELECT * FROM nl2_store_connections')->results();
            foreach ($connections as $connection) {
                $connections_array[] = [
                    'id' => Output::getClean($connection->id),
                    'name' => Output::getClean($connection->name),
                    'selected' => ((isset($_POST['connections']) && is_array($_POST['connections'])) ? in_array($connection->id, $_POST['connections']) : false)
                ];
            }

            // Fields
            $fields_array = [];
            $fields = DB::getInstance()->query('SELECT * FROM nl2_store_fields WHERE deleted = 0')->results();
            foreach ($fields as $field) {
                $fields_array[] = [
                    'id' => Output::getClean($field->id),
                    'identifier' => Output::getClean($field->identifier),
                    'selected' => ((isset($_POST['fields']) && is_array($_POST['fields'])) ? in_array($field->id, $_POST['fields']) : false)
                ];
            }

            // Remove from customer after (Expire)
            $durability = [
                'interval' => ((isset($_POST['durability_interval']) && $_POST['durability_interval']) ? Output::getClean(Input::get('durability_interval')) : '1'),
                'period' => ((isset($_POST['durability_period']) && $_POST['durability_period']) ? Output::getClean(Input::get('durability_period')) : 'never'),
            ];

            $smarty->assign([
                'PRODUCT_TITLE' => $store_language->get('admin', 'new_product'),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/products/'),
                'PRODUCT_NAME' => $store_language->get('admin', 'product_name'),
                'PRODUCT_NAME_VALUE' => ((isset($_POST['name']) && $_POST['name']) ? Output::getClean(Input::get('name')) : ''),
                'PRODUCT_DESCRIPTION' => $store_language->get('admin', 'product_description'),
                'PRODUCT_DESCRIPTION_VALUE' => ((isset($_POST['description']) && $_POST['description']) ? Output::getClean(Input::get('description')) : ''),
                'PRICE' => $store_language->get('admin', 'price'),
                'PRODUCT_PRICE_VALUE' => ((isset($_POST['price']) && $_POST['price']) ? Output::getClean(Input::get('price')) : ''),
                'CATEGORY' => $store_language->get('admin', 'category'),
                'CATEGORY_LIST' => $store->getAllCategories(),
                'CONNECTIONS' => $store_language->get('admin', 'service_connections'),
                'CONNECTIONS_LIST' => $connections_array,
                'FIELDS' => $store_language->get('admin', 'fields'),
                'FIELDS_LIST' => $fields_array,
                'CURRENCY' => Output::getClean(Store::getCurrency()),
                'DURABILITY' => $durability,
                'HIDE_PRODUCT' => $store_language->get('admin', 'hide_product_from_store'),
                'HIDE_PRODUCT_VALUE' => ((isset($_POST['hidden'])) ? 1 : 0),
                'DISABLE_PRODUCT' => $store_language->get('admin', 'disable_product'),
                'DISABLE_PRODUCT_VALUE' => ((isset($_POST['disabled'])) ? 1 : 0),
            ]);

            $template->assets()->include([
                AssetTree::TINYMCE,
            ]);

            $template->addJSScript(Input::createTinyEditor($language, 'inputDescription', null, false, true));

            $template_file = 'store/product_new.tpl';
            break;

        case 'order_categories':
            if (isset($_POST['categories']) && Token::check($_POST['token'])) {
                $categories = json_decode($_POST['categories']);
                $i = 1;

                foreach ($categories as $item) {
                    DB::getInstance()->query('UPDATE nl2_store_categories SET `order` = ? WHERE id = ?', [$i, $item]);
                    $i++;
                }
            }
            die('Complete');

        case 'order_products':
            if (isset($_POST['products']) && Token::check($_POST['token'])) {
                $products = json_decode($_POST['products']);
                $i = 1;

                foreach ($products as $item) {
                    DB::getInstance()->query('UPDATE nl2_store_products SET `order` = ? WHERE id = ?', [$i, $item]);
                    $i++;
                }
            }
            die('Complete');
        default:
            Redirect::to(URL::build('/panel/store/products'));
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('products_success'))
    $success = Session::flash('products_success');

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
    'PRODUCTS' => $store_language->get('general', 'products')
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);
