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

if(!$configuration->get('store', 'allow_guests')) {
    if(!$user->isLoggedIn()) {
        Redirect::to(URL::build('/login/'));
        die();
    }
}

require_once(ROOT_PATH . '/core/templates/frontend_init.php');
require_once(ROOT_PATH . '/modules/Store/core/frontend_init.php');

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
} else {
    // Add item to shopping cart
    if(isset($_GET['add'])) {
        if(!is_numeric($_GET['add'])){
            die('Invalid product');
        }
        $shopping_cart->add($_GET['add']);
        
        Redirect::to(URL::build($store_url . '/checkout/'));
        die();
    }

    // Remove item from shopping cart
    if(isset($_GET['remove'])) {
        if(!is_numeric($_GET['remove'])){
            die('Invalid product');
        }
        $shopping_cart->remove($_GET['remove']);
        
        Redirect::to(URL::build($store_url . '/checkout/'));
        die();
    }

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
                $order->create($user, $player, $shopping_cart->getItems());

                $gateway = $gateways->get($_POST['payment_method']);
                if($gateway) {
                    // Load gateway process
                    require_once(ROOT_PATH . '/modules/Store/gateways/'.$gateway->getName().'/process.php');
                } else {
                    die('Invalid gateway');
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
    
    // Load shopping list
    $shopping_cart_list = array();
    foreach($shopping_cart->getProducts() as $product) {
        $shopping_cart_list[] = array(
            'name' => Output::getClean($product->name),
            'quantity' => 1,
            'price' => Output::getClean($product->price),
            'remove_link' => URL::build($store_url . '/checkout/', 'remove=' . $product->id),
        );
    }
    
    // Load available gateways
    $payment_methods = array();
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
if(!$player->isLoggedIn()) {
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