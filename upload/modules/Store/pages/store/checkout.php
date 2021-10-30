<?php
/*
 *	Made by Partydragen
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
require_once(ROOT_PATH . '/modules/Store/classes/Store.php');
require_once(ROOT_PATH . '/modules/Store/classes/Player.php');
require_once(ROOT_PATH . '/modules/Store/classes/Gateways.php');
require_once(ROOT_PATH . '/modules/Store/classes/GatewayBase.php');
require_once(ROOT_PATH . '/modules/Store/classes/ShoppingCart.php');
$store = new Store($cache, $store_language);
$player = new Player();
$gateways = new Gateways();
$shopping_cart = new ShoppingCart();

$store_url = $store->getStoreURL();

if(Input::exists()){
	if(Token::check(Input::get('token'))){
		if(Input::get('type') == 'store_logout') {
			// Logout the store player
			$player->logout();
		}
	}
}

if(isset($_GET['do'])){
	if($_GET['do'] == 'complete'){
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
                require_once(ROOT_PATH . '/modules/Store/classes/Gateways.php');
                require_once(ROOT_PATH . '/modules/Store/classes/GatewayBase.php');
                require_once(ROOT_PATH . '/modules/Store/classes/StoreConfig.php');
                require_once(ROOT_PATH . '/modules/Store/config.php');

                // Load Store config
                if (isset($store_conf) && is_array($store_conf)) {
                    $GLOBALS['store_config'] = $store_conf;
                }

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
                'displayname' => Output::getClean('PayPal'),
                'name' => Output::getClean($gateway->getName())
            );
        }
    }
	
	$smarty->assign(array(
		'TOKEN' => Token::get(),
		'TOTAL_PRICE' => $shopping_cart->getTotalPrice(),
		'CHECKOUT' => $store_language->get('general', 'checkout'),
		'SUMMARY' => $store_language->get('general', 'summary'),
		'PURCHASE' => $store_language->get('general', 'purchase'),
        'PAYMENT_METHODS' => $payment_methods,
        'SHOPPING_CART_LIST' => $shopping_cart_list,
        'PROCESS_URL' => URL::build($store_url . '/process/'),
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
    'CURRENCY' => 'USD',
    'CURRENCY_SYMBOL' => '$',
    'TOKEN' => Token::get()
));

if($player->isLoggedIn()) {
	$smarty->assign(array(
		'STORE_PLAYER' => $player->getUsername()
	));
}

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