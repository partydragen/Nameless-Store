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

$store = new Store();
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
                        'clone_link' => URL::build('/panel/store/products/', 'action=clone&product=' . Output::getClean($product->id)),
                        'delete_link' => URL::build('/panel/store/product/', 'product=' . Output::getClean($product->id) . '&action=delete')
                    ];

                    $new_category['products'][] = $new_product;
                }
            }

            $all_categories[] = $new_category;
        }
        
    } else {
        $template->getEngine()->addVariable('NO_PRODUCTS', $store_language->get('general', 'no_products'));
    }

    $template->assets()->include(
        AssetTree::JQUERY_UI
    );

    $template->getEngine()->addVariables([
        'ALL_CATEGORIES' => $all_categories,
        'CURRENCY' => $currency,
        'CURRENCY_SYMBOL' => $currency_symbol,
        'NEW_CATEGORY' => $store_language->get('admin', 'new_category'),
        'NEW_CATEGORY_LINK' => URL::build('/panel/store/categories/', 'action=new'),
        'NEW_PRODUCT' => $store_language->get('admin', 'new_product'),
        'NEW_PRODUCT_LINK' => URL::build('/panel/store/products/', 'action=new'),
        'CLONE_PRODUCT' => $store_language->get('admin', 'clone_product'),
        'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
        'CONFIRM_DELETE_CATEGORY' => $store_language->get('admin', 'category_confirm_delete'),
        'CONFIRM_DELETE_PRODUCT' => $store_language->get('admin', 'product_confirm_delete'),
        'YES' => $language->get('general', 'yes'),
        'NO' => $language->get('general', 'no'),
        'REORDER_CATEGORY_URL' => URL::build('/panel/store/products', 'action=order_categories'),
        'REORDER_PRODUCTS_URL' => URL::build('/panel/store/products', 'action=order_products'),
    ]);

    $template_file = 'store/products';
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

                        // Validate durability period
                        if (in_array(Input::get('payment_type'), [2,3]) && Input::get('durability_period') == 'never' && Input::get('durability_interval') > 0) {
                            $errors[] = $store_language->get('admin', 'invalid_durability_period');
                        }

                        if (in_array(Input::get('payment_type'), [2,3]) && (Input::get('durability_period') == 'min' || Input::get('durability_period') == 'hour')) {
                            $errors[] = $store_language->get('admin', 'invalid_durability_period_short_time');
                        }

                        // insert into a database if there are no errors
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
                                'durability' => $durability,
                                'payment_type' => Input::get('payment_type')
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

            $template->getEngine()->addVariables([
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
                'PRODUCT_CATEGORY_VALUE' => ((isset($_POST['category']) && $_POST['category']) ? Output::getClean(Input::get('category')) : ''),
                'CONNECTIONS' => $store_language->get('admin', 'service_connections'),
                'CONNECTIONS_LIST' => $connections_array,
                'FIELDS' => $store_language->get('admin', 'fields'),
                'FIELDS_LIST' => $fields_array,
                'CURRENCY' => Output::getClean(Store::getCurrency()),
                'DURABILITY' => $durability,
                'REMOVE_AFTER_EXPIRE' => $store_language->get('admin', 'remove_after_expire'),
                'RECURRING_PAYMENT' => $store_language->get('admin', 'recurring_payment'),
                'RECURRING_PAYMENT_VALUE' => ((isset($_POST['payment_type']) && $_POST['payment_type']) ? Output::getClean(Input::get('payment_type')) : '1'),
                'CHARGE_CUSTOMER_ONCE' => $store_language->get('admin', 'charge_customer_once'),
                'CHARGE_RECURRING_SUBSCRIPTION' => $store_language->get('admin', 'charge_recurring_subscription'),
                'ONE_OFF_AND_RECURRING' => $store_language->get('admin', 'one_off_and_recurring'),
                'HIDE_PRODUCT' => $store_language->get('admin', 'hide_product_from_store'),
                'HIDE_PRODUCT_VALUE' => ((isset($_POST['hidden'])) ? 1 : 0),
                'DISABLE_PRODUCT' => $store_language->get('admin', 'disable_product'),
                'DISABLE_PRODUCT_VALUE' => ((isset($_POST['disabled'])) ? 1 : 0),
            ]);

            $template->assets()->include([
                AssetTree::TINYMCE,
            ]);

            $template->addJSScript(Input::createTinyEditor($language, 'inputDescription', null, false, true));

            $template_file = 'store/product_new';
            break;

        case 'clone';
            if (!isset($_GET['product']) || !is_numeric($_GET['product'])) {
                Redirect::to(URL::build('/panel/store/products'));
            }

            $source_product = new Product($_GET['product']);
            if (!$source_product->exists() || $source_product->data()->deleted) {
                Redirect::to(URL::build('/panel/store/products'));
            }

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
                        $category = DB::getInstance()->query('SELECT id FROM nl2_store_categories WHERE id = ? AND deleted = 0', [Input::get('category')])->results();
                        if (!count($category)) {
                            $errors[] = $store_language->get('admin', 'invalid_category');
                        }

                        if (!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0.00 || $_POST['price'] > 20000000 || !preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])) {
                            $errors[] = $store_language->get('admin', 'invalid_price');
                        }

                        if (in_array(Input::get('payment_type'), [2, 3]) && Input::get('durability_period') == 'never' && Input::get('durability_interval') > 0) {
                            $errors[] = $store_language->get('admin', 'invalid_durability_period');
                        }

                        if (in_array(Input::get('payment_type'), [2, 3]) && in_array(Input::get('durability_period'), ['min', 'hour'])) {
                            $errors[] = $store_language->get('admin', 'invalid_durability_period_short_time');
                        }

                        if (!count($errors)) {
                            $source_data = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE id = ?', [$source_product->data()->id])->first();
                            $product_data = get_object_vars($source_data);
                            unset($product_data['id']);

                            $last_order = DB::getInstance()->query('SELECT `order` FROM nl2_store_products ORDER BY `order` DESC LIMIT 1')->first();
                            $product_data['name'] = Input::get('name');
                            $product_data['description'] = Input::get('description');
                            $product_data['category_id'] = $category[0]->id;
                            $product_data['price_cents'] = Store::toCents(Input::get('price'));
                            $product_data['hidden'] = isset($_POST['hidden']) && $_POST['hidden'] == 'on' ? 1 : 0;
                            $product_data['disabled'] = isset($_POST['disabled']) && $_POST['disabled'] == 'on' ? 1 : 0;
                            $product_data['order'] = ($last_order->order ?? 0) + 1;
                            $product_data['payment_type'] = Input::get('payment_type');
                            $product_data['deleted'] = 0;

                            $selected_connection_ids = isset($_POST['connections']) && is_array($_POST['connections'])
                                ? array_map('intval', $_POST['connections'])
                                : [];
                            $selected_field_ids = isset($_POST['fields']) && is_array($_POST['fields'])
                                ? array_map('intval', $_POST['fields'])
                                : [];

                            $valid_connection_ids = [];
                            foreach (DB::getInstance()->query('SELECT id FROM nl2_store_connections')->results() as $connection) {
                                $valid_connection_ids[] = (int) $connection->id;
                            }
                            $selected_connection_ids = array_values(array_intersect($selected_connection_ids, $valid_connection_ids));

                            $valid_field_ids = [];
                            foreach (DB::getInstance()->query('SELECT id FROM nl2_store_fields WHERE deleted = 0')->results() as $field) {
                                $valid_field_ids[] = (int) $field->id;
                            }
                            $selected_field_ids = array_values(array_intersect($selected_field_ids, $valid_field_ids));

                            if (isset($_POST['durability_period']) && $_POST['durability_period'] != 'never') {
                                $product_data['durability'] = json_encode([
                                    'interval' => $_POST['durability_interval'] ?? 1,
                                    'period' => $_POST['durability_period']
                                ]);
                            } else {
                                $product_data['durability'] = null;
                            }

                            $new_product_id = DB::getInstance()->transaction(static function (DB $db) use ($product_data, $source_product, $selected_connection_ids, $selected_field_ids) {
                                if (!$db->insert('store_products', $product_data)) {
                                    throw new RuntimeException('Unable to clone product');
                                }

                                $new_product_id = (int) $db->lastId();
                                EventHandler::executeEvent(new ProductClonedEvent($new_product_id, $source_product->data()->id));

                                $db->query('DELETE FROM nl2_store_products_connections WHERE product_id = ? AND action_id IS NULL', [$new_product_id]);
                                foreach ($selected_connection_ids as $connection_id) {
                                    if (!$db->insert('store_products_connections', [
                                        'product_id' => $new_product_id,
                                        'connection_id' => $connection_id
                                    ])) {
                                        throw new RuntimeException('Unable to apply cloned product connections');
                                    }
                                }

                                $db->query('DELETE FROM nl2_store_products_fields WHERE product_id = ?', [$new_product_id]);
                                foreach ($selected_field_ids as $field_id) {
                                    if (!$db->insert('store_products_fields', [
                                        'product_id' => $new_product_id,
                                        'field_id' => $field_id
                                    ])) {
                                        throw new RuntimeException('Unable to apply cloned product fields');
                                    }
                                }

                                return $new_product_id;
                            });

                            if ($new_product_id) {
                                Session::flash('products_success', $store_language->get('admin', 'product_cloned_successfully'));
                                Redirect::to(URL::build('/panel/store/product/', 'product=' . $new_product_id));
                            }

                            $errors[] = $store_language->get('admin', 'unable_to_clone_product');
                        }
                    } else {
                        $errors = $validation->errors();
                    }
                } else {
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }

            $selected_connections = Input::exists() && isset($_POST['connections']) && is_array($_POST['connections'])
                ? $_POST['connections']
                : array_keys($source_product->getConnections());
            $connections_array = [];
            foreach (DB::getInstance()->query('SELECT * FROM nl2_store_connections')->results() as $connection) {
                $connections_array[] = [
                    'id' => Output::getClean($connection->id),
                    'name' => Output::getClean($connection->name),
                    'selected' => in_array($connection->id, $selected_connections)
                ];
            }

            $selected_fields = Input::exists() && isset($_POST['fields']) && is_array($_POST['fields'])
                ? $_POST['fields']
                : array_keys($source_product->getFields());
            $fields_array = [];
            foreach (DB::getInstance()->query('SELECT * FROM nl2_store_fields WHERE deleted = 0')->results() as $field) {
                $fields_array[] = [
                    'id' => Output::getClean($field->id),
                    'identifier' => Output::getClean($field->identifier),
                    'selected' => in_array($field->id, $selected_fields)
                ];
            }

            $durability_json = Input::exists()
                ? []
                : (json_decode($source_product->data()->durability, true) ?? []);
            $durability = [
                'interval' => Input::exists() ? Output::getClean(Input::get('durability_interval')) : ($durability_json['interval'] ?? 1),
                'period' => Input::exists() ? Output::getClean(Input::get('durability_period')) : ($durability_json['period'] ?? 'never')
            ];

            $template->getEngine()->addVariables([
                'PRODUCT_TITLE' => $store_language->get('admin', 'cloning_product_x', ['product' => Output::getClean($source_product->data()->name)]),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/products/'),
                'PRODUCT_NAME' => $store_language->get('admin', 'product_name'),
                'PRODUCT_NAME_VALUE' => Input::exists() ? Output::getClean(Input::get('name')) : $store_language->get('admin', 'product_copy_name', ['product' => Output::getClean($source_product->data()->name)]),
                'PRODUCT_DESCRIPTION' => $store_language->get('admin', 'product_description'),
                'PRODUCT_DESCRIPTION_VALUE' => Input::exists() ? Output::getClean(Input::get('description')) : Output::getPurified(Output::getDecoded($source_product->data()->description ?? '')),
                'PRICE' => $store_language->get('admin', 'price'),
                'PRODUCT_PRICE_VALUE' => Input::exists() ? Output::getClean(Input::get('price')) : Store::fromCents($source_product->data()->price_cents),
                'CATEGORY' => $store_language->get('admin', 'category'),
                'CATEGORY_LIST' => $store->getAllCategories(),
                'PRODUCT_CATEGORY_VALUE' => Input::exists() ? Output::getClean(Input::get('category')) : $source_product->data()->category_id,
                'CONNECTIONS' => $store_language->get('admin', 'service_connections'),
                'CONNECTIONS_LIST' => $connections_array,
                'FIELDS' => $store_language->get('admin', 'fields'),
                'FIELDS_LIST' => $fields_array,
                'CURRENCY' => Output::getClean(Store::getCurrency()),
                'DURABILITY' => $durability,
                'REMOVE_AFTER_EXPIRE' => $store_language->get('admin', 'remove_after_expire'),
                'RECURRING_PAYMENT' => $store_language->get('admin', 'recurring_payment'),
                'RECURRING_PAYMENT_VALUE' => Input::exists() ? Output::getClean(Input::get('payment_type')) : $source_product->data()->payment_type,
                'CHARGE_CUSTOMER_ONCE' => $store_language->get('admin', 'charge_customer_once'),
                'CHARGE_RECURRING_SUBSCRIPTION' => $store_language->get('admin', 'charge_recurring_subscription'),
                'ONE_OFF_AND_RECURRING' => $store_language->get('admin', 'one_off_and_recurring'),
                'HIDE_PRODUCT' => $store_language->get('admin', 'hide_product_from_store'),
                'HIDE_PRODUCT_VALUE' => Input::exists() ? (isset($_POST['hidden']) ? 1 : 0) : $source_product->data()->hidden,
                'DISABLE_PRODUCT' => $store_language->get('admin', 'disable_product'),
                'DISABLE_PRODUCT_VALUE' => Input::exists() ? (isset($_POST['disabled']) ? 1 : 0) : $source_product->data()->disabled,
            ]);

            $template->assets()->include([
                AssetTree::TINYMCE,
            ]);
            $template->addJSScript(Input::createTinyEditor($language, 'inputDescription', null, false, true));

            $template_file = 'store/product_new';
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
    $template->getEngine()->addVariables([
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ]);

if (isset($errors) && count($errors))
    $template->getEngine()->addVariables([
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ]);

$template->getEngine()->addVariables([
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
$template->displayTemplate($template_file);
