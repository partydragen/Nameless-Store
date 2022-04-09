<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Checkout page
 */

// Always define page name
define('PAGE', 'store');
$page_title = $store_language->get('general', 'store');

require_once(ROOT_PATH . '/core/templates/frontend_init.php');
require_once(ROOT_PATH . '/modules/Store/core/frontend_init.php');

if(!$store->isPlayerSystemEnabled() || !$configuration->get('store', 'allow_guests')) {
    if(!$user->isLoggedIn()) {
        Redirect::to(URL::build('/login/'));
        die();
    }
}

$gateways = new Gateways();

$store_url = $store->getStoreURL();

if(isset($_GET['do'])){
    if($_GET['do'] == 'complete'){
        // Checkout complete page
        $checkout_complete_content = $queries->getWhere('store_settings', array('name', '=', 'checkout_complete_content'));
        $smarty->assign('CHECKOUT_COMPLETE_CONTENT', Output::getPurified(Output::getDecoded($checkout_complete_content[0]->value)));
        
        $template_file = 'store/checkout_complete.tpl';
    } else {
        // Invalid
        Redirect::to(URL::build($store_url . '/checkout/'));
        die();
    }
} else if(isset($_GET['add'])) {
    // Add item to shopping cart
    if(!is_numeric($_GET['add'])){
        die('Invalid product');
    }
    
    $product = new Product($_GET['add']);
    if(!$product->exists()) {
        die('Invalid product');
    }
    
    $fields = $product->getFields();
    if(count($fields)) {
        $force_continue = true;
        
        // Any fields to fill?
        $product_fields = array();
        foreach($fields as $field) {
            $options = explode(',', Output::getClean($field->options));
            
            // Is value forced loaded?
            $forced = isset($_GET[$field->identifier]);
            
            $product_fields[] = array(
                'id' => Output::getClean($field->id),
                'identifier' => Output::getClean($field->identifier),
                'value' => $forced ? Output::getClean($_GET[$field->identifier]) : (isset($_POST[$field->id]) && !is_array($_POST[$field->id]) ? Output::getClean(Input::get($field->id)) : ''),
                'description' => Output::getClean($field->description),
                'type' => Output::getClean($field->type),
                'required' => Output::getClean($field->required),
                'options' => $options,
                'forced' => $forced
            );
            
            // Continue to next step if all fields are force loaded
            if(!$forced)
                $force_continue = false;
        }
        
        // Continue to next step if all fields are force loaded
        if($force_continue) {
            $shopping_cart->add($_GET['add'], 1, $product_fields);
            Redirect::to(URL::build($store_url . '/checkout/'));
            die();
        }
        
        // Deal with any input
        if (Input::exists()) {
            $errors = array();
            
            if (Token::check()) {
                // Validation
                $validate = new Validate();
                $to_validate = array();
                
                foreach($fields as $field){
                    $field_validation = array();
                    
                    if($field->required == 1 /*&& $field->type != 9*/) {
                        $field_validation['required'] = true;
                    }
                    
                    if($field->min != 0) {
                        $field_validation['min'] = $field->min;
                    }
                    
                    if($field->max != 0) {
                        $field_validation['max'] = $field->max;
                    }
                    
                    if(count($field_validation)) {
                        $to_validate[$field->id] = $field_validation;
                    }
                }
                
                // Modify post validation
                $validate_post = array();
                foreach($_POST as $key => $item){
                    $validate_post[$key] = !is_array($item) ? $item : true ;
                }
                
                $validation = $validate->check($validate_post, $to_validate);
                if($validation->passed()){
                    // Validation passed
                    
                    $product_fields = array();
                    foreach($fields as $field) {
                        // Post value exists?
                        if(!isset($_POST[$field->id]))
                            continue;
                        
                        $item = $_POST[$field->id];
                        $value = (!is_array($item) ? $item : implode(', ', $item));
                            
                        $product_fields[] = array(
                            'id' => Output::getClean($field->id),
                            'identifier' => Output::getClean($field->identifier),
                            'value' => $value,
                            'description' => Output::getClean($field->description),
                            'type' => Output::getClean($field->type)
                        );
                    }
                    
                    $shopping_cart->add($_GET['add'], 1, $product_fields);
                    Redirect::to(URL::build($store_url . '/checkout/'));
                    die();
                } else {
                    // Validation errors
                    foreach($validation->errors() as $item){
                        // Get field name
                        $id = explode(' ', $item);
                        $id = $id[0];

                        $fielderror = $queries->getWhere('store_fields', array('id', '=', $id));
                        if (count($fielderror)) {
                            $fielderror = $fielderror[0];

                            if(strpos($item, 'is required') !== false){
                                $errors[] = str_replace('{x}', Output::getClean($fielderror->name), $language->get('user', 'field_is_required'));
                            } else if(strpos($item, 'minimum') !== false){
                                $errors[] = str_replace(array('{x}', '{y}'), array(Output::getClean($fielderror->name), $fielderror->min), $store_language->get('general', 'x_field_minimum_y'));
                            } else if(strpos($item, 'maximum') !== false){
                                $errors[] = str_replace(array('{x}', '{y}'), array(Output::getClean($fielderror->name), $fielderror->max), $store_language->get('general', 'x_field_maximum_y'));
                            }
                        }
                    }
                }
            } else {
                // Invalid token
                $errors[] = $language->get('general', 'invalid_token');
            }
        }
        
        $smarty->assign(array(
            'PRODUCT_NAME' => Output::getClean($product->data()->name),
            'PRODUCT_FIELDS' => $product_fields,
            'CONTINUE' => $store_language->get('general', 'continue'),
            'TOKEN' => Token::get()
        ));
        
        $template_file = 'store/checkout_add.tpl';

    } else {
        // No fields to fill, continue to next step
        $shopping_cart->add($_GET['add']);
        Redirect::to(URL::build($store_url . '/checkout/'));
        die();
    }
    
} else if(isset($_GET['remove'])) {
    if(!is_numeric($_GET['remove'])){
        die('Invalid product');
    }
    $shopping_cart->remove($_GET['remove']);
        
    Redirect::to(URL::build($store_url . '/checkout/'));
    die();
    
} else {
    // Make sure the shopping cart is not empty
    if(!count($shopping_cart->getItems())) {
        Redirect::to(URL::build($store_url));
        die();
    }
    
    // Deal with any input
    if (Input::exists()) {
        $errors = array();
        
        if (Token::check()) {
            $validate = new Validate();
            
            $to_validation = array(
                'payment_method' => array(
                    Validate::REQUIRED => true
                ),
                't_and_c' => array(
                    Validate::REQUIRED => true,
                    Validate::AGREE => true
                )
            );
            
            // Valid, continue with validation
            $validation = $validate->check($_POST, $to_validation); // Execute validation
            if ($validation->passed()) {
                require_once(ROOT_PATH . '/modules/Store/config.php');

                // Load Store config
                if (isset($store_conf) && is_array($store_conf)) {
                    $GLOBALS['store_config'] = $store_conf;
                }

                // Register order
                $order = new Order();

                // Complete order if there is nothing to pay
                $amount_to_pay = $shopping_cart->getTotalPrice();
                if ($amount_to_pay == 0) {
                    $order->create($user, $player, $shopping_cart->getItems());

                    $payment = new Payment();
                    $payment->handlePaymentEvent('COMPLETED', array(
                        'order_id' => $order->data()->id,
                        'gateway_id' => 0,
                        'amount' => 0,
                        'currency' => Output::getClean($configuration->get('store', 'currency')),
                        'fee' => 0
                    ));

                    $shopping_cart->clear();
                    Redirect::to(URL::build($store_url . '/checkout/', 'do=complete'));
                    die();
                }

                $payment_method = $_POST['payment_method'];
                if($payment_method != 'Credits') {
                    $gateway = $gateways->get($payment_method);
                    if($gateway) {
                        // Load gateway process
                        $order->create($user, $player, $shopping_cart->getItems());
                        
                        require_once(ROOT_PATH . '/modules/Store/gateways/'.$gateway->getName().'/process.php');
                    } else {
                        $errors[] = 'Invalid Gateway';
                    }
                } else {
                    // User is paying with credits
                    if ($customer->exists() && $customer->getCredits() >= $amount_to_pay) {
                        $customer->removeCredits($amount_to_pay);

                        $order->create($user, $player, $shopping_cart->getItems());

                        $payment = new Payment();
                        $payment->handlePaymentEvent('COMPLETED', array(
                            'order_id' => $order->data()->id,
                            'gateway_id' => 0,
                            'amount' => $amount_to_pay,
                            'currency' => 'Credits',
                            'fee' => 0
                        ));

                        $shopping_cart->clear();
                        Redirect::to(URL::build($store_url . '/checkout/', 'do=complete'));
                        die();
                    } else {
                        $errors[] = 'You don\'t have enough credits to complete this order!';
                    }
                }
            } else {
                // Errors
                foreach ($validation->errors() as $validation_error) {
                    if (strpos($validation_error, 'is required') !== false) {
                        // x is required
                        if (strpos($validation_error, 'payment_method') !== false) {
                            $errors[] = 'Please choose your desired payment option.';
                        } else if (strpos($validation_error, 't_and_c') !== false) {
                            $errors[] = 'You must accept the terms and conditions';
                        }
                    }
                }
            }
        } else {
            // Invalid token
            $errors[] = $language->get('general', 'invalid_token');
        }
    }

    $currency = Output::getClean($configuration->get('store', 'currency'));
    $currency_symbol = Output::getClean($configuration->get('store', 'currency_symbol'));

    // Load shopping list
    $shopping_cart_list = array();
    foreach($shopping_cart->getProducts() as $product) {
        $shopping_cart_list[] = array(
            'name' => Output::getClean($product->name),
            'quantity' => 1,
            'price' => Output::getClean($product->price),
            'fields' => $shopping_cart->getItems()[$product->id]['fields'],
            'remove_link' => URL::build($store_url . '/checkout/', 'remove=' . $product->id),
        );
    }

    // Get user credits if user is logged in
    $credits = 0;
    if ($customer->exists()) {
        $credits = $customer->getCredits();
    }

    // Load available gateways
    $payment_methods = array();
    if ($credits > 0) {
        $payment_methods[] = array(
            'displayname' => 'Pay with your credit balance of '.$currency_symbol . $credits . ' ' . $currency,
            'name' => 'Credits'
        );
    }
    
    foreach($gateways->getAll() as $gateway) {
        if($gateway->isEnabled()) {
            $payment_methods[] = array(
                'displayname' => Output::getClean($gateway->getDisplayname()),
                'name' => Output::getClean($gateway->getName())
            );
        }
    }
    
    $smarty->assign(array(
        'TOKEN' => Token::get(),
        'CHECKOUT' => $store_language->get('general', 'checkout'),
        'SHOPPING_CART' => $store_language->get('general', 'shopping_cart'),
        'NAME' => $store_language->get('general', 'name'),
        'OPTIONS' => $store_language->get('general', 'options'),
        'QUANTITY' => $store_language->get('general', 'quantity'),
        'PRICE' => $store_language->get('general', 'price'),
        'TOTAL_PRICE' => $store_language->get('general', 'total_price'),
        'TOTAL_PRICE_VALUE' => $shopping_cart->getTotalPrice(),
        'PAYMENT_METHOD' => $store_language->get('general', 'payment_method'),
        'PURCHASE' => $store_language->get('general', 'purchase'),
        'AGREE_T_AND_C_PURCHASE' => str_replace('{x}', URL::build('/terms'), $store_language->get('general', 'agree_t_and_c_purchase')),
        'PAYMENT_METHODS' => $payment_methods,
        'SHOPPING_CART_LIST' => $shopping_cart_list
    ));
    
    $template_file = 'store/checkout.tpl';
}

// Check if store player is required and isset
if($store->isPlayerSystemEnabled() && !$player->isLoggedIn()) {
    Redirect::to(URL::build($store_url));
    die();
}

$smarty->assign(array(
    'STORE' => $store_language->get('general', 'store'),
    'CATEGORIES' => $store->getNavbarMenu(false),
    'TOKEN' => Token::get()
));

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets, $template);

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
    
$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

$smarty->assign('WIDGETS_LEFT', $widgets->getWidgets('left'));
$smarty->assign('WIDGETS_RIGHT', $widgets->getWidgets('right'));

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate($template_file, $smarty);