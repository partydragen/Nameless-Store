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
}

$product = new Product($_GET['product']);
if (!$product->exists()) {
    Redirect::to(URL::build('/panel/store/products'));
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_products');
$page_title = $store_language->get('general', 'products');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

$store = new Store($cache, $store_language);

$services = Services::getInstance();

if (!isset($_GET['action'])) {
    // Edit product
    if (Input::exists()) {
        $errors = [];

        if (Token::check(Input::get('token'))) {
            if (Input::get('type') == 'settings') {
            // Update product
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
                    $product->update([
                        'name' => Input::get('name'),
                        'description' => Input::get('description'),
                        'category_id' => $category[0]->id,
                        'price_cents' => Store::toCents(Input::get('price')),
                        'hidden' => $hidden,
                        'disabled' => $disabled,
                        'durability' => $durability
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
                    $image = new Bulletproof\Image($_FILES);

                    $image->setSize(1000, 2 * 1048576)
                        ->setMime(['jpeg', 'png', 'gif'])
                        ->setDimension(2000, 2000)
                        ->setLocation(ROOT_PATH . '/uploads/store', 0777);

                    if ($image['product_image']) {
                        $upload = $image->upload();

                        if ($upload) {
                            $product->update([
                                'image' => $image->getName() . '.' . $image->getMime()
                            ]);

                            Session::flash('products_success', $store_language->get('admin', 'image_updated_successfully'));
                            Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
                        } else {
                            $errors[] = $store_language->get('admin', 'unable_to_upload_image', ['error' => Output::getClean($image->getError())]);
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
    $actions_array = [];
    foreach ($product->getActions() as $action) {
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
            case 4:
                $type = 'Renewal';
            break;
            case 5:
                $type = 'Expire';
            break;
        }

        $actions_array[] = [
            'id' => Output::getClean($action->data()->id),
            'command' => Output::getClean($action->data()->command),
            'type' => $type,
            'service' => $action->getService()->getName(),
            'requirePlayer' => ($action->data()->require_online ? 'Yes' : 'No'),
            'edit_link' => URL::build('/panel/store/product', 'action=edit_action&product=' . $product->data()->id . '&aid=' . $action->data()->id),
            'delete_link' => URL::build('/panel/store/product', 'action=delete_action&product=' . $product->data()->id . '&aid=' . $action->data()->id),
        ];
    }

    // Remove from customer after (Expire)
    $durability_json = json_decode($product->data()->durability, true) ?? [];
    $durability = [
        'interval' => $durability_json['interval'] ?? 1,
        'period' => $durability_json['period'] ?? 'never'
    ];

    $smarty->assign([
        'PRODUCT_TITLE' => $store_language->get('admin', 'editing_product_x', ['product' => Output::getClean($product->data()->name)]),
        'ID' => Output::getClean($product->data()->id),
        'BACK' => $language->get('general', 'back'),
        'BACK_LINK' => URL::build('/panel/store/products/'),
        'PRODUCT_NAME' => $store_language->get('admin', 'product_name'),
        'PRODUCT_NAME_VALUE' => Output::getClean($product->data()->name),
        'PRODUCT_DESCRIPTION' => $store_language->get('admin', 'product_description'),
        'PRODUCT_DESCRIPTION_VALUE' => Output::getPurified(Output::getDecoded($product->data()->description)),
        'PRICE' => $store_language->get('general', 'price'),
        'PRODUCT_PRICE_VALUE' => Store::fromCents($product->data()->price_cents),
        'PRODUCT_CATEGORY_VALUE' => Output::getClean($product->data()->category_id),
        'CATEGORY' => $store_language->get('admin', 'category'),
        'CATEGORY_LIST' => $store->getAllCategories(),
        'CONNECTIONS' => $store_language->get('admin', 'service_connections'),
        'CONNECTIONS_LIST' => $connections_array,
        'FIELDS' => $store_language->get('admin', 'fields'),
        'FIELDS_LIST' => $fields_array,
        'NEW_ACTION' => $store_language->get('admin', 'new_action'),
        'NEW_ACTION_LINK' => URL::build('/panel/store/product/' , 'action=new_action&product=' . $product->data()->id),
        'ACTION_LIST' => $actions_array,
        'CURRENCY' => Output::getClean(Store::getCurrency()),
        'DURABILITY' => $durability,
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

    $template->assets()->include([
        AssetTree::TINYMCE,
    ]);

    $template->addJSScript(Input::createTinyEditor($language, 'inputDescription', null, false, true));

    $template_file = 'store/product.tpl';
} else {
    switch ($_GET['action']) {
        case 'delete';
            // Delete product
            $product->delete();
            Session::flash('products_success', $store_language->get('admin', 'product_deleted_successfully'));

            Redirect::to(URL::build('/panel/store/products'));
        break;
        case 'new_action';
            // New action for product
            if (!isset($_GET['service'])) {
                // Select service type
                $services_list = [];
                foreach ($services->getAll() as $service) {
                    $services_list[] = [
                        'id' => Output::getClean($service->getId()),
                        'name' => Output::getClean($service->getName()),
                        'description' => Output::getClean($service->getDescription()),
                        'select_link' => URL::build('/panel/store/product/' , 'action=new_action&product=' . $product->data()->id . '&service=' . $service->getId()),
                    ];
                }
                
                $smarty->assign([
                    'ACTION_TITLE' => $store_language->get('admin', 'new_action_for_x', ['product' => Output::getClean($product->data()->name)]),
                    'BACK' => $language->get('general', 'back'),
                    'BACK_LINK' => URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
                    'SERVICES_LIST' => $services_list
                ]);
                
                $template_file = 'store/products_action_type.tpl';
            } else {
                if (!is_numeric($_GET['service'])) {
                    Redirect::to(URL::build('/panel/store/products'));
                }

                $service = $services->get($_GET['service']);
                if ($service == null) {
                    Redirect::to(URL::build('/panel/store/products'));
                }

                $action = new Action($service);

                $fields = new Fields();
                if (file_exists($service->getActionSettings())) {
                    $securityPolicy->secure_dir = [ROOT_PATH . '/modules/Store', ROOT_PATH . '/custom/panel_templates'];
                    require_once($service->getActionSettings());
                }

                $service->onActionSettingsPageLoad($template, $fields);

                $smarty->assign([
                    'ACTION_TITLE' => $store_language->get('admin', 'new_action_for_x', ['product' => Output::getClean($product->data()->name)]),
                    'BACK' => $language->get('general', 'back'),
                    'BACK_LINK' => URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
                    'FIELDS' => $fields->getAll()
                ]);
                
                $template_file = 'store/products_action_form.tpl';
            }
        break;
        case 'edit_action';
            // Editing action for product
            if (!isset($_GET['aid']) || !is_numeric($_GET['aid'])) {
                Redirect::to(URL::build('/panel/store/products'));
            }

            $action = $product->getAction($_GET['aid']);
            if ($action == null) {
                Redirect::to(URL::build('/panel/store/products'));
            }
            $service = $action->getService();

            $fields = new Fields();
            if (file_exists($service->getActionSettings())) {
                $securityPolicy->secure_dir = [ROOT_PATH . '/modules/Store', ROOT_PATH . '/custom/panel_templates'];
                require_once($service->getActionSettings());
            }

            $action->getService()->onActionSettingsPageLoad($template, $fields);

            $smarty->assign([
                'ACTION_TITLE' => $store_language->get('admin', 'editing_action_for_x', ['product' => Output::getClean($product->data()->name)]),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
                'FIELDS' => $fields->getAll()
            ]);

            $template_file = 'store/products_action_form.tpl';
        break;
        case 'delete_action';
            // Delete product
            if (!isset($_GET['aid']) || !is_numeric($_GET['aid'])) {
                Redirect::to(URL::build('/panel/store/products'));
            }

            $action = $product->getAction($_GET['aid']);
            if ($action != null) {
                $action->delete();
                Session::flash('products_success', $store_language->get('admin', 'action_deleted_successfully'));
            }

            Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
        break;
        case 'actions';
            // Get product actions
            $actions_array = [];
            foreach ($product->getActions() as $action) {
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
                    case 4:
                        $type = 'Renewal';
                    break;
                    case 5:
                        $type = 'Expire';
                    break;
                }

                $actions_array[] = [
                    'id' => Output::getClean($action->data()->id),
                    'command' => Output::getClean($action->data()->command),
                    'type' => $type,
                    'service' => $action->getService()->getName(),
                    'requirePlayer' => ($action->data()->require_online ? 'Yes' : 'No'),
                    'edit_link' => URL::build('/panel/store/product', 'action=edit_action&product=' . $product->data()->id . '&aid=' . $action->data()->id),
                    'delete_link' => URL::build('/panel/store/product', 'action=delete_action&product=' . $product->data()->id . '&aid=' . $action->data()->id),
                ];
            }
            $smarty->assign([
                'PRODUCT_TITLE' => $store_language->get('admin', 'editing_product_x', ['product' => Output::getClean($product->data()->name)]),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
                'NEW_ACTION' => $store_language->get('admin', 'new_action'),
                'NEW_ACTION_LINK' => URL::build('/panel/store/product/' , 'action=new_action&product=' . $product->data()->id),
                'ACTION_LIST' => $actions_array,
            ]);
            
            $template_file = 'store/product_actions.tpl';
        break;
        case 'limits_requirements';
            // Limits and requirements
            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    $global_limit = [
                        'limit' => $_POST['global_limit'] ?? 0,
                        'interval' => $_POST['global_limit_interval'] ?? 1,
                        'period' => $_POST['global_limit_period'] ?? 'no_period'
                    ];

                    $user_limit = [
                        'limit' => $_POST['user_limit'] ?? 0,
                        'interval' => $_POST['user_limit_interval'] ?? 1,
                        'period' => $_POST['user_limit_period'] ?? 'no_period'
                    ];

                    $player_age = [
                        'interval' => $_POST['player_age_interval'] ?? 0,
                        'period' => $_POST['player_age_period'] ?? 'hour'
                    ];

                    $player_playtime = [
                        'playtime' => $_POST['player_playtime'] ?? 0,
                        'interval' => $_POST['player_playtime_interval'] ?? 1,
                        'period' => $_POST['player_playtime_period'] ?? 'all_time'
                    ];

                    $required_products = $_POST['required_products'];
                    $required_groups = $_POST['required_groups'];
                    $required_integrations = $_POST['required_integrations'];
                    $allowed_gateways = $_POST['allowed_gateways'];

                    if (isset($_POST['require_one_product']) && $_POST['require_one_product'] == 'on') $require_one_product = 1;
                    else $require_one_product = 0;

                    $product->update([
                        'global_limit' => json_encode($global_limit),
                        'user_limit' => json_encode($user_limit),
                        'min_player_age' => json_encode($player_age),
                        'min_player_playtime' => json_encode($player_playtime),
                        'required_products' => json_encode(isset($required_products) && is_array($required_products) ? $required_products : []),
                        'require_one_product' => $require_one_product,
                        'required_groups' => json_encode(isset($required_groups) && is_array($required_groups) ? $required_groups : []),
                        'required_integrations' =>  json_encode(isset($required_integrations) && is_array($required_integrations) ? $required_integrations : []),
                        'allowed_gateways' => json_encode(isset($allowed_gateways) && is_array($allowed_gateways) ? $allowed_gateways : []),
                    ]);

                    Session::flash('products_success', $store_language->get('admin', 'product_updated_successfully'));
                    Redirect::to(URL::build('/panel/store/product/' , 'product=' . $product->data()->id . '&action=limits_requirements'));
                } else {
                    // Invalid token
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }

            $global_limit_json = json_decode($product->data()->global_limit, true) ?? [];
            $global_limit = [
                'limit' => $global_limit_json['limit'] ?? 0,
                'interval' => $global_limit_json['interval'] ?? 1,
                'period' => $global_limit_json['period'] ?? 'no_period'
            ];

            $user_limit_json = json_decode($product->data()->user_limit, true) ?? [];
            $user_limit = [
                'limit' => $user_limit_json['limit'] ?? 0,
                'interval' => $user_limit_json['interval'] ?? 1,
                'period' => $user_limit_json['period'] ?? 'no_period'
            ];

            $products_list = [];
            $selected_products = json_decode($product->data()->required_products, true) ?? [];
            $products = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE id <> ? AND deleted = 0', [$product->data()->id])->results();
            foreach ($products as $item) {
                $products_list[] = [
                    'id' => $item->id,
                    'name' => Output::getClean($item->name),
                    'selected' => in_array($item->id, $selected_products)
                ];
            }

            $groups_list = [];
            $selected_groups = json_decode($product->data()->required_groups, true) ?? [];
            $groups = DB::getInstance()->query('SELECT * FROM nl2_groups')->results();
            foreach ($groups as $item) {
                $groups_list[] = [
                    'id' => $item->id,
                    'name' => Output::getClean($item->name),
                    'selected' => in_array($item->id, $selected_groups)
                ];
            }

            $integrations_list = [];
            $selected_integrations = json_decode($product->data()->required_integrations, true) ?? [];
            foreach (Integrations::getInstance()->getEnabledIntegrations() as $item) {
                $integrations_list[] = [
                    'id' => $item->data()->id,
                    'name' => Output::getClean($item->getName()),
                    'selected' => in_array($item->data()->id, $selected_integrations)
                ];
            }

            $allowed_gateways_list = [];
            $selected_gateways = json_decode($product->data()->allowed_gateways, true) ?? [];
            foreach (Gateways::getInstance()->getAll() as $gateway) {
                if ($gateway->isEnabled()) {
                    $allowed_gateways_list[] = [
                        'id' => $gateway->getId(),
                        'name' => Output::getClean($gateway->getName()),
                        'selected' => in_array($gateway->getId(), $selected_gateways)
                    ];
                }
            }

            $player_age_json = json_decode($product->data()->min_player_age, true) ?? [];
            $player_age = [
                'interval' => $player_age_json['interval'] ?? 0,
                'period' => $player_age_json['period'] ?? 'hour'
            ];

            $player_playtime_json = json_decode($product->data()->min_player_playtime, true) ?? [];
            $player_playtime = [
                'playtime' => $player_playtime_json['playtime'] ?? 0,
                'interval' => $player_playtime_json['interval'] ?? 1,
                'period' => $player_playtime_json['period'] ?? 'all_time'
            ];

            $smarty->assign([
                'PRODUCT_TITLE' => $store_language->get('admin', 'editing_product_x', ['product' => Output::getClean($product->data()->name)]),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
                'GLOBAL_LIMIT_VALUE' => $global_limit,
                'USER_LIMIT_VALUE' => $user_limit,
                'PRODUCTS_LIST' => $products_list,
                'REQUIRE_ONE_PRODUCT_VALUE' => $product->data()->require_one_product,
                'GROUPS_LIST' => $groups_list,
                'INTEGRATIONS_LIST' => $integrations_list,
                'ALLOWED_GATEWAYS_LIST' => $allowed_gateways_list,
                'MCSTATISTICS_ENABLED' => Util::isModuleEnabled('MCStatistics'),
                'PLAYER_AGE_VALUE' => $player_age,
                'PLAYER_PLAYTIME_VALUE' => $player_playtime,
            ]);
            
            $template_file = 'store/product_limits_requirements.tpl';
        break;
        case 'remove_image';
            // Remove image from product
            $product->update([
                'image' => null
            ]);
            Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
        break;
        default:
            Redirect::to(URL::build('/panel/store/products'));
        break;
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
    'PRODUCTS' => $store_language->get('general', 'products'),
    'GENERAL_SETTINGS' => $language->get('admin', 'general_settings'),
    'GENERAL_SETTINGS_LINK' => URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
    'ACTIONS' => $store_language->get('admin', 'actions'),
    'ACTIONS_LINK' => URL::build('/panel/store/product/' , 'product=' . $product->data()->id . '&action=actions'),
    'LIMITS_AND_REQUIREMENTS' => $store_language->get('admin', 'limits_and_requirements'),
    'LIMITS_AND_REQUIREMENTS_LINK' => URL::build('/panel/store/product/' , 'product=' . $product->data()->id . '&action=limits_requirements')
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);