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
if (!$user->handlePanelPageLoad('staffcp.store.payments')) {
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
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (isset($_GET['customer'])) {
    // Get payments for user
    $payments = DB::getInstance()->query('SELECT nl2_store_payments.*, order_id, nl2_store_orders.user_id, to_customer_id FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id LEFT JOIN nl2_store_customers ON to_customer_id=nl2_store_customers.id WHERE nl2_store_customers.username = ? ORDER BY created DESC', [$_GET['customer']]);

    if ($payments->count()) {
        $payments = $payments->results();

        // Recipient
        if ($payments[0]->to_customer_id) {
            $recipient = new Customer(null, $payments[0]->to_customer_id, 'id');
        } else {
            $recipient = new Customer(null, $payments[0]->user_id, 'user_id');
        }

        if ($recipient->exists() && $recipient->getUser()->exists()) {
            $recipient_user = $recipient->getUser();
            $username = $recipient->getUsername();
            $avatar = $recipient_user->getAvatar();
            $style = $recipient_user->getGroupClass();
            $identifier = Output::getClean($recipient->getIdentifier());
            $link = URL::build('/panel/users/store/', 'user=' . $recipient_user->data()->id);
        } else {
            $username = $recipient->getUsername();
            $avatar = AvatarSource::getAvatarFromUUID(Output::getClean($recipient->getIdentifier()));
            $style = '';
            $identifier = Output::getClean($recipient->getIdentifier());
            $link = URL::build('/panel/store/payments/', 'customer=' . $username);
        }

        $template_payments = [];

        foreach ($payments as $paymentQuery) {
            $payment = new Payment($paymentQuery->id);

            $template_payments[] = [
                'user_link' => $link,
                'user_style' => $style,
                'user_avatar' => $avatar,
                'username' => $username,
                'user_uuid' => $identifier,
                'status_id' => $paymentQuery->status_id,
                'status' => $payment->getStatusHtml(),
                'currency' => Output::getPurified($paymentQuery->currency),
                'amount' => Output::getClean($paymentQuery->amount),
                'date' => date(DATE_FORMAT, $paymentQuery->created),
                'link' => URL::build('/panel/store/payments', 'payment=' . Output::getClean($paymentQuery->id))
            ];
        }

        $smarty->assign([
            'VIEW' => $store_language->get('admin', 'view'),
            'USER_PAYMENTS' => $template_payments
        ]);

        if (!defined('TEMPLATE_STORE_SUPPORT')) {
            $template->assets()->include([
                AssetTree::DATATABLES
            ]);

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

    $smarty->assign([
        'VIEWING_PAYMENTS_FOR_USER' => $store_language->get('admin', 'viewing_payments_for_user_x', ['user' => Output::getClean($_GET['customer'])]),
        'BACK' => $language->get('general', 'back'),
        'BACK_LINK' => URL::build('/panel/store/payments')
    ]);

    $template_file = 'store/payments_user.tpl';

} else if (isset($_GET['payment'])) {
    // View payment
    $payment = new Payment($_GET['payment']);
    if (!$payment->exists()) {
        Redirect::to(URL::build('/panel/store/payments'));
    }

    // Handle input
    if (Input::exists()) {
        $errors = [];

        if (Token::check(Input::get('token'))) {
            if (Input::get('action') == 'delete_payment') {
                // Delete payment only if payment is manual
                if ($payment->data()->gateway_id == 0) {
                    $payment->delete();

                    Session::flash('store_payment_success', $store_language->get('admin', 'payment_deleted_successfully'));
                    Redirect::to(URL::build('/panel/store/payments'));
                }
            } else if (Input::get('action') == 'delete_command') {
                // Delete pending command
            }
        } else {
            // Invalid token
            $errors[] = $language->get('general', 'invalid_token');
        }
    }

    $order = $payment->getOrder();

    // Recipient
    $recipient = $order->recipient();
    if ($recipient->exists() && $recipient->getUser()->exists()) {
        $recipient_user = $recipient->getUser();
        $username = $recipient->getUsername();
        $avatar = $recipient_user->getAvatar();
        $style = $recipient_user->getGroupClass();
        $uuid = Output::getClean($recipient->getIdentifier());
        $link = URL::build('/panel/users/store/', 'user=' . $recipient_user->data()->id);
    } else {
        $username = $recipient->getUsername();
        $avatar = AvatarSource::getAvatarFromUUID(Output::getClean($recipient->getIdentifier()));
        $style = '';
        $uuid = Output::getClean($recipient->getIdentifier());
        $link = URL::build('/panel/store/payments/', 'customer=' . $username);
    }

    // Get Products
    $products_list = [];
    foreach ($order->getProducts() as $product) {
        $fields_array = [];
        $fields = DB::getInstance()->query('SELECT identifier, value FROM nl2_store_orders_products_fields INNER JOIN nl2_store_fields ON field_id=nl2_store_fields.id WHERE order_id = ? AND product_id = ?', [$payment->data()->order_id, $product->data()->id])->results();
        foreach ($fields as $field) {
            $fields_array[] = [
                'identifier' => Output::getClean($field->identifier),
                'value' => Output::getClean($field->value)
            ];
        }

        $products_list[] = [
            'id' => Output::getClean($product->data()->id),
            'name' => Output::getClean($product->data()->name),
            'fields' => $fields_array
        ];
    }

    $pending_commands = DB::getInstance()->query('SELECT * FROM nl2_store_pending_actions INNER JOIN nl2_store_connections ON connection_id=nl2_store_connections.id WHERE order_id = ? AND status = 0', [$payment->data()->order_id])->results();
    $pending_commands_array = [];
    foreach ($pending_commands as $command) {
        $pending_commands_array[] = [
            'command' => Output::getClean($command->command),
            'connection_name' => Output::getClean($command->name)
        ];
    }

    $processed_commands = DB::getInstance()->query('SELECT * FROM nl2_store_pending_actions LEFT JOIN nl2_store_connections ON connection_id=nl2_store_connections.id WHERE order_id = ? AND status = 1', [$payment->data()->order_id])->results();
    $processed_commands_array = [];
    foreach ($processed_commands as $command) {
        $processed_commands_array[] = [
            'command' => Output::getClean($command->command),
            'connection_name' => Output::getClean($command->name ?? 'Unknown')
        ];
    }

    // Allow manual payment deletion
    if ($payment->data()->gateway_id == 0) {
        $smarty->assign([
            'DELETE_PAYMENT' => $language->get('admin', 'delete'),
            'CONFIRM_DELETE_PAYMENT' => $store_language->get('admin', 'confirm_payment_deletion'),
        ]);
    }

    $smarty->assign([
        'VIEWING_PAYMENT' => $store_language->get('admin', 'viewing_payment', ['payment' => Output::getClean($payment->data()->transaction)]),
        'BACK' => $language->get('general', 'back'),
        'BACK_LINK' => URL::build('/panel/store/payments'),
        'IGN' => $store_language->get('admin', 'ign'),
        'IGN_VALUE' => $username,
        'USER_LINK' => $link,
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
        'DATE_VALUE' => date(DATE_FORMAT, $payment->data()->created),
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
    ]);

    $template_file = 'store/payments_view.tpl';

} else if (isset($_GET['action'])) {
    if ($_GET['action'] == 'create') {
        // Create payment
        if (Input::exists()) {
            $errors = [];

            if (Token::check()) {
                $to_validation = [
                    'username' => [
                        Validate::REQUIRED => true
                    ]
                ];

                // Valid, continue with validation
                $validation = Validate::check($_POST, $to_validation);
                if ($validation->passed()) {

                    if ($store->isPlayerSystemEnabled()) {
                        // Attempt to load recipient
                        $recipient = new Customer();
                        if (!$recipient->login(Output::getClean(Input::get('username')), false)) {
                            $errors[] = $language->get('user', 'invalid_mcname');
                        }

                        $target_user = new User(Output::getClean(Input::get('username')), 'username');
                    } else {
                        // User required
                        $target_user = new User(Output::getClean(Input::get('username')), 'username');
                        if (!$target_user->exists()) {
                            $errors[] = $store_language->get('admin', 'user_dont_exist');
                        }

                        $recipient = new Customer($target_user);
                    }

                    $items = [];
                    $selected_products = $_POST['products'];
                    foreach ($selected_products as $item) {
                        $items[$item] = [
                            'id' => $item,
                            'quantity' => 1
                        ];
                    }

                    if (!count($errors) && count($items)) {
                        // Register order
                        $order = new Order();
                        $order->create($target_user, $recipient, $recipient, $items);

                        // Register payment
                        $payment = new Payment();
                        $payment->handlePaymentEvent('COMPLETED', [
                            'order_id' => $order->data()->id,
                            'gateway_id' => 0,
                            'amount' => 0,
                            'currency' => Output::getClean($configuration->get('store', 'currency')),
                            'fee' => 0
                        ]);

                        Session::flash('store_payment_success', $store_language->get('admin', 'payment_created_successfully'));
                        Redirect::to(URL::build('/panel/store/payments/', 'payment=' . $payment->data()->id));
                    }
                } else {
                    $errors = $validation->errors();
                }
            } else {
                // Invalid token
                $errors[] = $language->get('general', 'invalid_token');
            }
        }
    
        $smarty->assign([
            'CREATE_PAYMENT' => $store_language->get('admin', 'create_payment'),
            'BACK' => $language->get('general', 'back'),
            'BACK_LINK' => URL::build('/panel/store/payments')
        ]);

        // Products to choose
        $products = $store->getProducts();
        
        if (count($products)) {
            $template_products = [];

            foreach ($products as $product) {
                $template_products[] = [
                    'id' => Output::getClean($product->data()->id),
                    'name' => Output::getClean($product->data()->name)
                ];
            }

            $smarty->assign([
                'USERNAME' => $store->isPlayerSystemEnabled() ? $store_language->get('admin', 'ign') : $language->get('user', 'username'),
                'PRODUCTS' => $store_language->get('general', 'products') . ' ' . $store_language->get('admin', 'select_multiple_with_ctrl'),
                'PRODUCTS_LIST' => $template_products
            ]);

        } else
            $smarty->assign('NO_PRODUCTS', $store_language->get('general', 'no_products'));

        $template_file = 'store/payments_new.tpl';
    }
} else {
    $payments = $store->getAllPayments();

    if (count($payments)) {
        $template_payments = [];

        foreach ($payments as $paymentQuery) {
            $payment = new Payment($paymentQuery->id);

            // Recipient
            if ($paymentQuery->to_customer_id) {
                $recipient = new Customer(null, $paymentQuery->to_customer_id, 'id');
            } else {
                $recipient = new Customer(null, $paymentQuery->user_id, 'user_id');
            }

            if ($recipient->exists() && $recipient->getUser()->exists()) {
                $recipient_user = $recipient->getUser();
                $username = $recipient->getUsername();
                $avatar = $recipient_user->getAvatar();
                $style = $recipient_user->getGroupClass();
                $identifier = Output::getClean($recipient->getIdentifier());
                $link = URL::build('/panel/users/store/', 'user=' . $recipient_user->data()->id);
            } else {
                $username = $recipient->getUsername();
                $avatar = AvatarSource::getAvatarFromUUID(Output::getClean($recipient->getIdentifier()));
                $style = '';
                $identifier = Output::getClean($recipient->getIdentifier());
                $link = URL::build('/panel/store/payments/', 'customer=' . $username);
            }

            $template_payments[] = [
                'user_link' =>  $link,
                'user_style' => $style,
                'user_avatar' => $avatar,
                'username' => $username,
                'uuid' => $identifier,
                'status_id' => $paymentQuery->status_id,
                'status' => $payment->getStatusHtml(),
                'currency_symbol' => '$',
                'amount' => Output::getClean($paymentQuery->amount),
                'date' => date(DATE_FORMAT, $paymentQuery->created),
                'date_unix' => Output::getClean($paymentQuery->created),
                'link' => URL::build('/panel/store/payments/', 'payment=' . Output::getClean($paymentQuery->id))
            ];
        }

        $smarty->assign([
            'VIEW' => $store_language->get('admin', 'view'),
            'ALL_PAYMENTS' => $template_payments
        ]);

        if (!defined('TEMPLATE_STORE_SUPPORT')) {
            $template->assets()->include([
                AssetTree::DATATABLES
            ]);

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

    $smarty->assign([
        'CREATE_PAYMENT' => $store_language->get('admin', 'create_payment'),
        'CREATE_PAYMENT_LINK' => URL::build('/panel/store/payments/', 'action=create')
    ]);

    $template_file = 'store/payments.tpl';

}

$smarty->assign([
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
]);

if (Session::exists('store_payment_success')) {
    $success = Session::flash('store_payment_success');
}

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

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);