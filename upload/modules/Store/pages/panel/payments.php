<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel payments page
 */

// Can the user view the StaffCP?
if(!$user->handlePanelPageLoad('staffcp.store.payments')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_payments');
$page_title = $store_language->get('admin', 'payments');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

$store = new Store($cache, $store_language);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

if(isset($_GET['player'])){
    // Get payments for user
    $payments = DB::getInstance()->query('SELECT nl2_store_payments.*, uuid, username, order_id, user_id, player_id FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id LEFT JOIN nl2_store_players ON player_id=nl2_store_players.id WHERE nl2_store_players.username = ? ORDER BY created DESC', array($_GET['player']));

    if($payments->count()){
        $payments = $payments->results();
        
        if($payments[0]->player_id != null) {
            // Use ingame user data
            $payment_user = new User(str_replace('-', '', $payments[0]->uuid), 'uuid');
            if($payment_user->data()){
                $username = Output::getClean($payments[0]->username);
                $avatar = $payment_user->getAvatar();
                $style = $payment_user->getGroupClass();
            } else {
                $username = Output::getClean($payments[0]->username);
                $avatar = Util::getAvatarFromUUID(Output::getClean($payments[0]->uuid));
                $style = '';
            }
        } else if($payments[0]->user_id != null) {
            // Use User data
            $payment_user = new User($payments[0]->user_id);
                
            $username = $payment_user->getDisplayname(true);
            $avatar = $payment_user->getAvatar();
            $style = $payment_user->getGroupClass();
        }

        $template_payments = array();

        foreach($payments as $paymentQuery){
            $payment = new Payment($paymentQuery->id);
            
            $template_payments[] = array(
                'user_link' => URL::build('/panel/store/payments/', 'player=' . Output::getClean($paymentQuery->username)),
                'user_style' => $style,
                'user_avatar' => $avatar,
                'username' => $username,
                'user_uuid' => Output::getClean($paymentQuery->uuid),
                'status_id' => $paymentQuery->status_id,
                'status' => $payment->getStatusHtml(),
                'currency' => Output::getPurified($paymentQuery->currency),
                'amount' => Output::getClean($paymentQuery->amount),
                'date' => date('d M Y, H:i', $paymentQuery->created),
                'link' => URL::build('/panel/store/payments', 'payment=' . Output::getClean($paymentQuery->id))
            );
        }

        $smarty->assign(array(
            'VIEW' => $store_language->get('admin', 'view'),
            'USER_PAYMENTS' => $template_payments
        ));

        if(!defined('TEMPLATE_STORE_SUPPORT')){
            $template->addCSSFiles(array(
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/custom/panel_templates/Default/assets/css/dataTables.bootstrap4.min.css' => array()
            ));

            $template->addJSFiles(array(
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/dataTables/jquery.dataTables.min.js' => array(),
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/custom/panel_templates/Default/assets/js/dataTables.bootstrap4.min.js' => array()
            ));

            $template->addJSScript('
                $(document).ready(function() {
                    $(\'.dataTables-payments\').dataTable({
                        responsive: true,
                        order: [[ 3, "desc" ]],
                        language: {
                            "lengthMenu": "' . $language->get('table', 'display_records_per_page') . '",
                            "zeroRecords": "' . $language->get('table', 'nothing_found') . '",
                            "info": "' . $language->get('table', 'page_x_of_y') . '",
                            "infoEmpty": "' . $language->get('table', 'no_records') . '",
                            "infoFiltered": "' . $language->get('table', 'filtered') . '",
                            "search": "' . $language->get('general', 'search') . '",
                            "paginate": {
                                "next": "' . $language->get('general', 'next') . '",
                                "previous": "' . $language->get('general', 'previous') . '"
                            }
                        }
                    });
                });
            ');
        }

    } else
        $smarty->assign('NO_PAYMENTS', $store_language->get('admin', 'no_payments_for_user'));

    $smarty->assign(array(
        'VIEWING_PAYMENTS_FOR_USER' => str_replace('{x}', Output::getClean($_GET['player']), $store_language->get('admin', 'viewing_payments_for_user_x')),
        'BACK' => $language->get('general', 'back'),
        'BACK_LINK' => URL::build('/panel/store/payments')
    ));

    $template_file = 'store/payments_user.tpl';

} else if(isset($_GET['payment'])){
    // View payment
    $payment = new Payment($_GET['payment']);
    if (!$payment->exists()) {
        Redirect::to(URL::build('/panel/store/payments'));
        die();
    }
    
    // Handle input
    if(Input::exists()){
        $errors = array();
        if(Token::check(Input::get('token'))){
            if(Input::get('action') == 'delete_payment') {
                // Delete payment only if payment is manual
                if($payment->data()->gateway_id == 0) {
                    $payment->delete();
                    
                    Session::flash('store_payment_success', $store_language->get('admin', 'payment_deleted_successfully'));
                    Redirect::to(URL::build('/panel/store/payments'));
                    die();
                }
            } else if(Input::get('action') == 'delete_command') {
                // Delete pending command
            }
        } else {
            // Invalid token
            $errors[] = $language->get('general', 'invalid_token');
        }
    }
    
    $order = $payment->getOrder();
    
    // Get user details
    if($order->data()->player_id != null) {
        $player = new Player($order->data()->player_id);
        
        $payment_user = new User(str_replace('-', '', $player->getUUID()), 'uuid');
        if($payment_user->data()){
            $username = Output::getClean($player->getUsername());
            $avatar = $payment_user->getAvatar();
            $style = $payment_user->getGroupClass();
            $uuid = Output::getClean($player->getUUID());
        } else {
            $username = Output::getClean($player->getUsername());
            $avatar = Util::getAvatarFromUUID(Output::getClean($player->getUUID()));
            $style = '';
            $uuid = Output::getClean($player->getUUID());
        }
    } else if($order->data()->user_id != null) {
        // Custumer paid while being logged in
        $payment_user = new User($order->data()->user_id);
                
        $username = $payment_user->getDisplayname(true);
        $avatar = $payment_user->getAvatar();
        $style = $payment_user->getGroupClass();
        $uuid = Output::getClean($payment_user->data()->uuid);
    }

    // Get Products
    $products_list = array();
    foreach($order->getProducts() as $product) {
        $fields_array = array();
        $fields = DB::getInstance()->query('SELECT identifier, value FROM nl2_store_orders_products_fields INNER JOIN nl2_store_fields ON field_id=nl2_store_fields.id WHERE order_id = ? AND product_id = ?', array($payment->data()->order_id, $product->id))->results();
        foreach($fields as $field) {
            $fields_array[] = array(
                'identifier' => Output::getClean($field->identifier),
                'value' => Output::getClean($field->value)
            );
        }
        
        $products_list[] = array(
            'id' => Output::getClean($product->id),
            'name' => Output::getClean($product->name),
            'fields' => $fields_array
        );
    }
   
    $pending_commands = DB::getInstance()->query('SELECT * FROM nl2_store_pending_actions INNER JOIN nl2_store_connections ON connection_id=nl2_store_connections.id WHERE order_id = ? AND status = 0', array($payment->data()->order_id))->results();
    $pending_commands_array = array();
    foreach($pending_commands as $command){
        $pending_commands_array[] = array(
            'command' => Output::getClean($command->command),
            'connection_name' => Output::getClean($command->name)
        );
    }
    
    $processed_commands = DB::getInstance()->query('SELECT * FROM nl2_store_pending_actions INNER JOIN nl2_store_connections ON connection_id=nl2_store_connections.id WHERE order_id = ? AND status = 1', array($payment->data()->order_id))->results();
    $processed_commands_array = array();
    foreach($processed_commands as $command){
        $processed_commands_array[] = array(
            'command' => Output::getClean($command->command),
            'connection_name' => Output::getClean($command->name)
        );
    }
    
    // Allow manual payment deletion
    if($payment->data()->gateway_id == 0) {
        $smarty->assign(array(
            'DELETE_PAYMENT' => $language->get('admin', 'delete'),
            'CONFIRM_DELETE_PAYMENT' => $store_language->get('admin', 'confirm_payment_deletion'),
        ));
    }
    
    $smarty->assign(array(
        'VIEWING_PAYMENT' => str_replace('{x}', Output::getClean($payment->data()->transaction), $store_language->get('admin', 'viewing_payment')),
        'BACK' => $language->get('general', 'back'),
        'BACK_LINK' => URL::build('/panel/store/payments'),
        'IGN' => $store_language->get('admin', 'ign'),
        'IGN_VALUE' => $username,
        'USER_LINK' => URL::build('/panel/store/payments/', 'player=' . $username),
        'AVATAR' => $avatar,
        'STYLE' => $style,
        'TRANSACTION' => $store_language->get('admin', 'transaction'),
        'TRANSACTION_VALUE' => Output::getClean($payment->data()->transaction),
        'PAYMENT_METHOD' => $store_language->get('admin', 'payment_method'),
        'PAYMENT_METHOD_VALUE' => Output::getClean($payment->data()->gateway_id),
        'STATUS' => $store_language->get('admin', 'status'),
        'STATUS_VALUE' => $payment->getStatusHtml(),
        'UUID' => $store_language->get('admin', 'uuid'),
        'UUID_VALUE' => $uuid,
        'PRICE' => $store_language->get('general', 'price'),
        'PRICE_VALUE' => Output::getClean($payment->data()->amount),
        'CURRENCY_SYMBOL' => Output::getClean('$'),
        'CURRENCY_ISO' => Output::getClean($payment->data()->currency),
        'DATE_VALUE' => date('d M Y, H:i', $payment->data()->created),
        'PRODUCTS' => $store_language->get('admin', 'products'),
        'PRODUCTS_LIST' => $products_list,
        'DETAILS' => $store_language->get('admin', 'details'),
        'CONNECTION' => $store_language->get('admin', 'connection'),
        'COMMAND' => $store_language->get('admin', 'command'),
        'PENDING_COMMANDS' => $store_language->get('admin', 'pending_commands'),
        'PROCESSED_COMMANDS' => $store_language->get('admin', 'processed_commands'),
        'NO_PENDING_COMMANDS' => $store_language->get('admin', 'no_pending_commands'),
        'NO_PROCESSED_COMMANDS' => $store_language->get('admin', 'no_processed_commands'),
        'PENDING_COMMANDS_LIST' => $pending_commands_array,
        'PROCESSED_COMMANDS_LIST' => $processed_commands_array,
        'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
        'YES' => $language->get('general', 'yes'),
        'NO' => $language->get('general', 'no')
    ));

    $template_file = 'store/payments_view.tpl';

} else if(isset($_GET['action'])){
    if($_GET['action'] == 'create'){
        // Create payment
        if (Input::exists()) {
            $errors = array();
            
            if (Token::check()) {
                $validate = new Validate();
                
                $to_validation = array(
                    'username' => array(
                        Validate::REQUIRED => true
                    )
                );
                
                // Valid, continue with validation
                $validation = $validate->check($_POST, $to_validation);
                if ($validation->passed()) {
                    
                    $player = new Player();
                    if($store->isPlayerSystemEnabled()) {
                        // Attempt to load player
                        $player = new Player();
                        if(!$player->login(Output::getClean(Input::get('username')), false)) {
                            $errors[] = $language->get('user', 'invalid_mcname');
                        }
                        
                        $target_user = new User(Output::getClean(Input::get('username')), 'username');
                    } else {
                        // User required
                        $target_user = new User(Output::getClean(Input::get('username')), 'username');
                        if(!$target_user->exists()) {
                            $errors[] = $store_language->get('admin', 'user_dont_exist');
                        }
                    }
                    
                    $items = array();
                    $selected_products = $_POST['products'];
                    foreach($selected_products as $item) {
                        $items[$item] = array(
                            'id' => $item,
                            'quantity' => 1
                        );
                    }

                    if(!count($errors) && count($items)) {
                        // Register order
                        $order = new Order();
                        $order->create($target_user, $player, $items);
                        
                        // Register payment
                        $payment = new Payment();
                        $payment->handlePaymentEvent('COMPLETED', array(
                            'order_id' => $order->data()->id,
                            'gateway_id' => 0,
                            'amount' => 0,
                            'currency' => Output::getClean($configuration->get('store', 'currency')),
                            'fee' => 0
                        ));
                        
                        Session::flash('store_payment_success', $store_language->get('admin', 'payment_created_successfully'));
                        Redirect::to(URL::build('/panel/store/payments/', 'payment=' . $payment->data()->id));
                        die();
                    }
                }
            } else {
                // Invalid token
                $errors[] = $language->get('general', 'invalid_token');
            }
        }
    
        $smarty->assign(array(
            'CREATE_PAYMENT' => $store_language->get('admin', 'create_payment'),
            'BACK' => $language->get('general', 'back'),
            'BACK_LINK' => URL::build('/panel/store/payments')
        ));

        // Products to choose
        $products = $store->getProducts();
        
        if(count($products)){
            $template_products = array();

            foreach($products as $product){
                $template_products[] = array(
                    'id' => Output::getClean($product->data()->id),
                    'name' => Output::getClean($product->data()->name)
                );
            }

            $smarty->assign(array(
                'USERNAME' => $store->isPlayerSystemEnabled() ? $store_language->get('admin', 'ign') : $language->get('user', 'username'),
                'PRODUCTS' => $store_language->get('general', 'products') . ' ' . $store_language->get('admin', 'select_multiple_with_ctrl'),
                'PRODUCTS_LIST' => $template_products
            ));

        } else
            $smarty->assign('NO_PRODUCTS', $store_language->get('general', 'no_products'));

        $template_file = 'store/payments_new.tpl';
    }
} else {
    $payments = $store->getAllPayments();

    if(count($payments)){
        $template_payments = array();

        foreach($payments as $paymentQuery){
            $payment = new Payment($paymentQuery->id);
            
            if($paymentQuery->player_id != null) {
                // Custumer paid as a guest, attempt to load user by uuid
                $payment_user = new User(str_replace('-', '', $paymentQuery->uuid), 'uuid');
                if($payment_user->data()){
                    $username = Output::getClean($paymentQuery->username);
                    $avatar = $payment_user->getAvatar();
                    $style = $payment_user->getGroupClass();
                } else {
                    $username = Output::getClean($paymentQuery->username);
                    $avatar = Util::getAvatarFromUUID(Output::getClean($paymentQuery->uuid));
                    $style = '';
                }
            } else if($paymentQuery->user_id != null) {
                // Custumer paid while being logged in
                $payment_user = new User($paymentQuery->user_id);
                
                $username = $payment_user->getDisplayname(true);
                $avatar = $payment_user->getAvatar();
                $style = $payment_user->getGroupClass();
            }

            $template_payments[] = array(
                'user_link' =>  URL::build('/panel/store/payments/', 'player=' . $username),
                'user_style' => $style,
                'user_avatar' => $avatar,
                'username' => $username,
                'uuid' => Output::getClean($paymentQuery->uuid),
                'status_id' => $paymentQuery->status_id,
                'status' => $payment->getStatusHtml(),
                'currency_symbol' => '$',
                'amount' => Output::getClean($paymentQuery->amount),
                'date' => date('d M Y, H:i', $paymentQuery->created),
                'date_unix' => Output::getClean($paymentQuery->created),
                'link' => URL::build('/panel/store/payments/', 'payment=' . Output::getClean($paymentQuery->id))
            );
        }

        $smarty->assign(array(
            'VIEW' => $store_language->get('admin', 'view'),
            'ALL_PAYMENTS' => $template_payments
        ));

        if(!defined('TEMPLATE_STORE_SUPPORT')){
            $template->addCSSFiles(array(
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/custom/panel_templates/Default/assets/css/dataTables.bootstrap4.min.css' => array()
            ));

            $template->addJSFiles(array(
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/dataTables/jquery.dataTables.min.js' => array(),
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/custom/panel_templates/Default/assets/js/dataTables.bootstrap4.min.js' => array()
            ));

            $template->addJSScript('
                $(document).ready(function() {
                    $(\'.dataTables-payments\').dataTable({
                        responsive: true,
                        order: [[ 3, "desc" ]],
                        language: {
                            "lengthMenu": "' . $language->get('table', 'display_records_per_page') . '",
                            "zeroRecords": "' . $language->get('table', 'nothing_found') . '",
                            "info": "' . $language->get('table', 'page_x_of_y') . '",
                            "infoEmpty": "' . $language->get('table', 'no_records') . '",
                            "infoFiltered": "' . $language->get('table', 'filtered') . '",
                            "search": "' . $language->get('general', 'search') . '",
                            "paginate": {
                                "next": "' . $language->get('general', 'next') . '",
                                "previous": "' . $language->get('general', 'previous') . '"
                            }
                        }
                    });
                });
            ');
        }

    } else
        $smarty->assign('NO_PAYMENTS', $store_language->get('admin', 'no_payments'));

    $smarty->assign(array(
        'CREATE_PAYMENT' => $store_language->get('admin', 'create_payment'),
        'CREATE_PAYMENT_LINK' => URL::build('/panel/store/payments/', 'action=create')
    ));

    $template_file = 'store/payments.tpl';

}

$smarty->assign(array(
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('admin', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'STORE' => $store_language->get('general', 'store'),
    'PAYMENTS' => $store_language->get('admin', 'payments'),
    'USER' => $store_language->get('admin', 'user'),
    'AMOUNT' => $store_language->get('admin', 'amount'),
    'STATUS' => $store_language->get('admin', 'status'),
    'DATE' => $store_language->get('admin', 'date'),
));

if(Session::exists('store_payment_success')){
    $success = Session::flash('store_payment_success');
}

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

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);