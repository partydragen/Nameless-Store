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

if (!isset($_GET['product']) || !is_numeric($_GET['product'])) {
    Redirect::to(URL::build('/panel/store/products'));
    die();
}

$product = new Product($_GET['product']);
if (!$product->exists()) {
    Redirect::to(URL::build('/panel/store/products'));
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_products');
$page_title = $store_language->get('general', 'products');
require_once(ROOT_PATH . '/core/templates/backend_init.php');
require_once(ROOT_PATH . '/modules/Store/classes/Store.php');

$store = new Store($cache, $store_language);

if (!isset($_GET['action'])) {
    // Edit product
    if (Input::exists()) {
        $errors = [];

        if (Token::check(Input::get('token'))) {
            if (Input::get('type') == 'settings') {
            // Update product
            $validate = new Validate();
            $validation = $validate->check($_POST, [
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
                    Validate::MIN => str_replace('{min}', '1', $store_language->get('admin', 'name_minimum_x')),
                    Validate::MAX => str_replace('{max}', '128', $store_language->get('admin', 'name_maximum_x'))
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
                if (!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0.00 || $_POST['price'] > 1000 || !preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])) {
                    $errors[] = $store_language->get('admin', 'invalid_price');
                } else {
                    $price = number_format($_POST['price'], 2, '.', '');
                }

                // insert into database if there is no errors
                if (!count($errors)) {
                    // Hide category?
                    if (isset($_POST['hidden']) && $_POST['hidden'] == 'on') $hidden = 1;
                    else $hidden = 0;
                            
                    // Disable category?
                    if (isset($_POST['disabled']) && $_POST['disabled'] == 'on') $disabled = 1;
                    else $disabled = 0;

                    // Save to database
                    $product->update([
                        'name' => Output::getClean(Input::get('name')),
                        'description' => Output::getClean(Input::get('description')),
                        'category_id' => $category[0]->id,
                        'price' => $price,
                        'hidden' => $hidden,
                        'disabled' => $disabled
                    ]);

                    $selected_connections = isset($_POST['connections']) && is_array($_POST['connections']) ? $_POST['connections'] : [];

                    // Check for new connections to give product which they dont already have
                    foreach ($selected_connections as $connection) {
                        if (!array_key_exists($connection, $product->getConnections())) {
                            $product->addConnection($connection);
                        }
                    }

                    // Check for connections they had, but werent in the $_POST connections
                    foreach ($product->getConnections() as $connection) {
                        if (!in_array($connection->id, $selected_connections)) {
                            $product->removeConnection($connection->id);
                        }
                    }
                            
                    $selected_fields = isset($_POST['fields']) && is_array($_POST['fields']) ? $_POST['fields'] : [];
                            
                    // Check for new fields to give product which they dont already have
                    foreach ($selected_fields as $field) {
                        if (!array_key_exists($field, $product->getFields())) {
                            $product->addField($field);
                        }
                    }

                    // Check for fields they had, but werent in the $_POST fields
                    foreach ($product->getFields() as $field) {
                        if (!in_array($field->id, $selected_fields)) {
                            $product->removeField($field->id);
                        }
                    }
                                
                    Session::flash('products_success', $store_language->get('admin', 'product_updated_successfully'));
                    Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
                    die();
                }
            } else {
                $errors = $validation->errors();
            }
            } else if (Input::get('type') == 'image') {
                // Product image
                if (!is_dir(ROOT_PATH . '/uploads/store')) {
                    try {
                        mkdir(ROOT_PATH . '/uploads/store');
                    } catch (Exception $e) {
                        $errors[] = $store_language->get('admin', 'unable_to_create_image_directory');
                    }
                }

                if (!count($errors)) {
                    require(ROOT_PATH . '/core/includes/bulletproof/bulletproof.php');

                    $image = new Bulletproof\Image($_FILES);

                    $image->setSize(1000, 2 * 1048576)
                        ->setMime(['jpeg', 'png', 'gif'])
                        ->setDimension(2000, 2000)
                        ->setLocation(ROOT_PATH . '/uploads/store', 0777);

                    if ($image['product_image']) {
                        $upload = $image->upload();

                        if ($upload) {
                            $product->update([
                                'image' => Output::getClean($image->getName() . '.' . $image->getMime())
                            ]);

                            Session::flash('products_success', $store_language->get('admin', 'image_updated_successfully'));
                            Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
                            die();
                        } else {
                            $errors[] = str_replace('{x}', Output::getClean($image->getError()), $store_language->get('admin', 'unable_to_upload_image'));
                        }
                    }
                }
            }
        } else {
            // Invalid token
            $errors[] = $language->get('general', 'invalid_token');
        }
    }
            
    // Connections
    $connections_array = [];
    $selected_connections = $product->getConnections();

    $connections = DB::getInstance()->query('SELECT * FROM nl2_store_connections')->results();
    foreach ($connections as $connection) {
        $connections_array[] = [
            'id' => Output::getClean($connection->id),
            'name' => Output::getClean($connection->name),
            'selected' => (array_key_exists($connection->id, $selected_connections))
        ];
    }
            
    // Fields
    $fields_array = [];
    $selected_fields = $product->getFields();

    $fields = DB::getInstance()->query('SELECT * FROM nl2_store_fields WHERE deleted = 0')->results();
    foreach ($fields as $field) {
        $fields_array[] = [
            'id' => Output::getClean($field->id),
            'identifier' => Output::getClean($field->identifier),
            'selected' => (array_key_exists($field->id, $selected_fields))
        ];
    }

    // Get product actions
    $actions = $product->getActions();

    $actions_array = [];
    foreach ($actions as $action) {
        $type = 'Unknown';
        switch ($action->data()->type) {
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

        $actions_array[] = [
            'id' => Output::getClean($action->data()->id),
            'command' => Output::getClean($action->data()->command),
            'type' => $type,
            'requirePlayer' => ($action->data()->require_online ? 'Yes' : 'No'),
            'edit_link' => URL::build('/panel/store/product', 'action=edit_action&product=' . $product->data()->id . '&aid=' . $action->data()->id),
            'delete_link' => URL::build('/panel/store/product', 'action=delete_action&product=' . $product->data()->id . '&aid=' . $action->data()->id),
        ];
    }

    $smarty->assign([
        'PRODUCT_TITLE' => str_replace('{x}', Output::getClean($product->data()->name), $store_language->get('admin', 'editing_product_x')),
        'ID' => Output::getClean($product->data()->id),
        'BACK' => $language->get('general', 'back'),
        'BACK_LINK' => URL::build('/panel/store/products/'),
        'PRODUCT_NAME' => $store_language->get('admin', 'product_name'),
        'PRODUCT_NAME_VALUE' => Output::getClean($product->data()->name),
        'PRODUCT_DESCRIPTION' => $store_language->get('admin', 'product_description'),
        'PRODUCT_DESCRIPTION_VALUE' => Output::getPurified(Output::getDecoded($product->data()->description)),
        'PRICE' => $store_language->get('general', 'price'),
        'PRODUCT_PRICE_VALUE' => Output::getClean($product->data()->price),
        'PRODUCT_CATEGORY_VALUE' => Output::getClean($product->data()->category_id),
        'CATEGORY' => $store_language->get('admin', 'category'),
        'CATEGORY_LIST' => $store->getAllCategories(),
        'CONNECTIONS' => $store_language->get('admin', 'connections') . ' ' . $store_language->get('admin', 'select_multiple_with_ctrl'),
        'CONNECTIONS_LIST' => $connections_array,
        'FIELDS' => $store_language->get('admin', 'fields') . ' ' . $store_language->get('admin', 'select_multiple_with_ctrl'),
        'FIELDS_LIST' => $fields_array,
        'ACTIONS' => $store_language->get('admin', 'actions'),
        'NEW_ACTION' => $store_language->get('admin', 'new_action'),
        'NEW_ACTION_LINK' => URL::build('/panel/store/product/' , 'action=new_action&product=' . $product->data()->id),
        'ACTION_LIST' => $actions_array,
        'CURRENCY' => Output::getClean($configuration->get('store', 'currency')),
        'HIDE_PRODUCT' => $store_language->get('admin', 'hide_product_from_store'),
        'HIDE_PRODUCT_VALUE' => $product->data()->hidden,
        'DISABLE_PRODUCT' => $store_language->get('admin', 'disable_product'),
        'DISABLE_PRODUCT_VALUE' => $product->data()->disabled,
        'PRODUCT_IMAGE' => $store_language->get('admin', 'product_image'),
        'PRODUCT_IMAGE_VALUE' => (!is_null($product->data()->image) ? ((defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/store/' . Output::getClean($product->data()->image)) : null),
        'UPLOAD_NEW_IMAGE' => $store_language->get('admin', 'upload_new_image'),
        'BROWSE' => $language->get('general', 'browse'),
        'REMOVE' => $language->get('general', 'remove'),
        'REMOVE_IMAGE_LINK' => URL::build('/panel/store/product/' , 'action=remove_image&product=' . $product->data()->id),
    ]);

    $template->addJSFiles([
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => [],
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => []
    ]);

    $template->addJSScript(Input::createEditor('inputDescription'));

    $template_file = 'store/products_form.tpl';
} else {
    switch ($_GET['action']) {
        case 'delete';
            // Delete product
            $product->delete();
            Session::flash('products_success', $store_language->get('admin', 'product_deleted_successfully'));

            Redirect::to(URL::build('/panel/store/products'));
            die();
        break;
        case 'new_action';
            // New action for product
            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    // New Action
                    $validate = new Validate();
                    $validation = $validate->check($_POST, [
                        'command' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 1,
                            Validate::MAX => 500
                        ]
                    ])->messages([
                        'command' => [
                            Validate::REQUIRED => $store_language->get('admin', 'command_required'),
                            Validate::MIN => $store_language->get('admin', 'command_min'),
                            Validate::MAX => $store_language->get('admin', 'command_max')
                        ]
                    ]);
                        
                    if ($validation->passed()) {
                        $trigger = Input::get('trigger');
                        if (!in_array($trigger, [1,2,3])) {
                            $errors[] = 'Invalid Trigger';
                        }
                            
                        $require_player = Input::get('requirePlayer');
                        if (!in_array($require_player, [0,1])) {
                            $errors[] = 'Invalid requirePlayer';
                        }
                            
                        if (!count($errors)) {
                            // Get last order
                            $last_order = DB::getInstance()->query('SELECT id FROM nl2_store_products_actions WHERE product_id = ? ORDER BY `order` DESC LIMIT 1', [$product->id])->results();
                            if (count($last_order)) $last_order = $last_order[0]->order;
                            else $last_order = 0;
                            
                            $selected_connections = (isset($_POST['connections']) && is_array($_POST['connections']) ? $_POST['connections'] : []);
                            
                            // Save to database
                            $queries->create('store_products_actions', [
                                'product_id' => $product->data()->id,
                                'type' => $trigger,
                                'command' => Output::getClean(Input::get('command')),
                                'require_online' => $require_player,
                                'order' => $last_order + 1,
                                'own_connections' => (in_array(0, $selected_connections) ? 0 : 1)
                            ]);
                            $lastId = $queries->getLastId();
                            
                            // Handle selected connections if its use own connection list
                            if (!in_array(0, $selected_connections)) {
                                $action = new Action($lastId); 
                                foreach ($selected_connections as $connection) {
                                    $action->addConnection($connection);
                                }
                            }
                            
                            Session::flash('products_success', $store_language->get('admin', 'action_created_successfully'));
                            Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
                            die();
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
            $connections = DB::getInstance()->query('SELECT * FROM nl2_store_connections')->results();
            $connections_array[] = [
                'id' => 0,
                'name' => 'Execute on all connections selected on product',
                'selected' => ((isset($_POST['connections']) && is_array($_POST['connections'])) ? in_array(0, $_POST['connections']) : true)
            ];
            foreach ($connections as $connection) {
                $connections_array[] = [
                    'id' => Output::getClean($connection->id),
                    'name' => Output::getClean($connection->name),
                    'selected' => ((isset($_POST['connections']) && is_array($_POST['connections'])) ? in_array($connection->id, $_POST['connections']) : false)
                ];
            }
            
            $smarty->assign([
                'ACTION_TITLE' => str_replace('{x}', Output::getClean($product->data()->name), $store_language->get('admin', 'new_action_for_x')),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
                'TRIGGER_VALUE' => ((isset($_POST['trigger'])) ? Output::getClean($_POST['trigger']) : 1),
                'REQUIRE_PLAYER_VALUE' => ((isset($_POST['requirePlayer'])) ? Output::getClean($_POST['requirePlayer']) : 1),
                'COMMAND_VALUE' => ((isset($_POST['command']) && $_POST['command']) ? Output::getClean($_POST['command']) : ''),
                'CONNECTIONS' => $store_language->get('admin', 'connections') . ' ' . $store_language->get('admin', 'select_multiple_with_ctrl'),
                'CONNECTIONS_LIST' => $connections_array
            ]);
            
            $template_file = 'store/products_action_form.tpl';
        break;
        case 'edit_action';
            // Editing action for product
            if (!isset($_GET['aid']) || !is_numeric($_GET['aid'])) {
                Redirect::to(URL::build('/panel/store/products'));
                die();
            }
            
            $action = new Action($_GET['aid']);
            if (!$action->exists()) {
                Redirect::to(URL::build('/panel/store/products'));
                die();
            }
            
            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    // Edit Action
                    $validate = new Validate();
                    $validation = $validate->check($_POST, [
                        'command' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 1,
                            Validate::MAX => 500
                        ]
                    ])->messages([
                        'command' => [
                            Validate::REQUIRED => $store_language->get('admin', 'command_required'),
                            Validate::MIN => $store_language->get('admin', 'command_min'),
                            Validate::MAX => $store_language->get('admin', 'command_max')
                        ]
                    ]);
                        
                    if ($validation->passed()) {
                        $trigger = Input::get('trigger');
                        if (!in_array($trigger, [1,2,3])) {
                            $errors[] = 'Invalid Trigger';
                        }
                            
                        $require_player = Input::get('requirePlayer');
                        if (!in_array($require_player, [0,1])) {
                            $errors[] = 'Invalid requirePlayer';
                        }
                        
                        if (!count($errors)) {
                            $selected_connections = (isset($_POST['connections']) && is_array($_POST['connections']) ? $_POST['connections'] : []);
                            
                            // Save to database
                            $action->update([
                                'type' => $trigger,
                                'command' => Output::getClean(Input::get('command')),
                                'require_online' => $require_player,
                                'own_connections' => (in_array(0, $selected_connections) ? 0 : 1)
                            ]);
                            
                            // Handle selected connections if its use own connection list
                            if (!in_array(0, $selected_connections)) {
                                // Check for new connections to give action which they dont already have
                                foreach ($selected_connections as $connection) {
                                    if (!array_key_exists($connection, $action->getConnections())) {
                                        $action->addConnection($connection);
                                    }
                                }

                                // Check for connections they had, but werent in the $_POST connections
                                foreach ($action->getConnections() as $connection) {
                                    if (!in_array($connection->id, $selected_connections)) {
                                        $action->removeConnection($connection->id);
                                    }
                                }
                            }
                            
                            Session::flash('products_success', $store_language->get('admin', 'action_updated_successfully'));
                            Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
                            die();
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
            $selected_connections = ($action->data()->own_connections ? $action->getConnections() : []);

            $connections = DB::getInstance()->query('SELECT * FROM nl2_store_connections')->results();
            $connections_array[] = [
                'id' => 0,
                'name' => 'Execute on all connections selected on product',
                'selected' => !$action->data()->own_connections
            ];
            foreach ($connections as $connection) {
                $connections_array[] = [
                    'id' => Output::getClean($connection->id),
                    'name' => Output::getClean($connection->name),
                    'selected' => (array_key_exists($connection->id, $selected_connections))
                ];
            }
            
            $smarty->assign([
                'ACTION_TITLE' => str_replace('{x}', Output::getClean($product->data()->name), $store_language->get('admin', 'editing_action_for_x')),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
                'TRIGGER_VALUE' => Output::getClean($action->data()->type),
                'REQUIRE_PLAYER_VALUE' => Output::getClean($action->data()->require_online),
                'COMMAND_VALUE' => Output::getClean($action->data()->command),
                'CONNECTIONS' => $store_language->get('admin', 'connections') . ' ' . $store_language->get('admin', 'select_multiple_with_ctrl'),
                'CONNECTIONS_LIST' => $connections_array
            ]);
        
            $template_file = 'store/products_action_form.tpl';
        break;
        case 'delete_action';
            // Delete product
            if (!isset($_GET['aid']) || !is_numeric($_GET['aid'])) {
                Redirect::to(URL::build('/panel/store/products'));
                die();
            }
            
            $action = new Action($_GET['aid']);
            if ($action->exists()) {
                $action->delete();
                Session::flash('products_success', $store_language->get('admin', 'action_deleted_successfully'));
            } 
            
            Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
            die();
        break;
        case 'remove_image';
            // Remove image from product
            $product->update([
                'image' => null
            ]);
            
            Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
            die();
        break;
        default:
            Redirect::to(URL::build('/panel/store/products'));
            die();
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $mod_nav], $widgets);

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