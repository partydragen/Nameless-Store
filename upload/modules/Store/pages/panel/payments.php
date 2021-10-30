<?php
/*
 *	Made by Partydragen
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
require_once(ROOT_PATH . '/modules/Store/classes/Store.php');

$store = new Store($cache, $store_language);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

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

if(isset($_GET['player'])){
	// Get payments for user
	$payments = DB::getInstance()->query('SELECT nl2_store_payments.*, uuid, username FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id LEFT JOIN nl2_store_players ON player_id=nl2_store_players.id WHERE nl2_store_players.username = ? ORDER BY created DESC', array($_GET['player']));

	if($payments->count()){
		$payments = $payments->results();
    
        $payment_user = new User(str_replace('-', '', $payments[0]->uuid), 'uuid');
        if($payment_user->data()){
            $avatar = $payment_user->getAvatar();
            $style = $payment_user->getGroupClass();
        } else {
            $avatar = Util::getAvatarFromUUID(Output::getClean($payments[0]->uuid));
            $style = '';
        }

		$template_payments = array();

		foreach($payments as $payment){
			$template_payments[] = array(
				'user_link' => URL::build('/panel/store/payments/', 'player=' . Output::getClean($payment->username)),
				'user_style' => $style,
				'user_avatar' => $avatar,
				'username' => Output::getClean($payment->username),
				'user_uuid' => Output::getClean($payment->uuid),
				'currency' => Output::getPurified($payment->currency),
				'amount' => Output::getClean($payment->amount),
				'date' => date('d M Y, H:i', $payment->created),
				'link' => URL::build('/panel/store/payments', 'payment=' . Output::getClean($payment->id))
			);
		}

		$smarty->assign(array(
			'USER' => $store_language->get('admin', 'user'),
			'AMOUNT' => $store_language->get('admin', 'amount'),
			'DATE' => $store_language->get('admin', 'date'),
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
						order: [[ 2, "desc" ]],
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
	$payment = DB::getInstance()->query('SELECT nl2_store_payments.*, uuid, username FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id LEFT JOIN nl2_store_players ON player_id=nl2_store_players.id WHERE nl2_store_payments.id = ?', array($_GET['payment']))->results();
	if(count($payment))
		$payment = $payment[0];
	else {
		Redirect::to(URL::build('/panel/store/payments'));
		die();
	}
    
    $payment_user = new User(str_replace('-', '', $payment->uuid), 'uuid');
	if($payment_user->data()){
		$avatar = $payment_user->getAvatar();
		$style = $payment_user->getGroupClass();
	} else {
		$avatar = Util::getAvatarFromUUID(Output::getClean($payment->uuid));
		$style = '';
	}
            
    switch($payment->status_id) {
        case 0;
            $status = '<span class="badge badge-warning">Pending</span>';
        break;
        case 1;
            $status = '<span class="badge badge-success">Complete</span>';
        break;
        case 2;
            $status = '<span class="badge badge-primary">Refunded</span>';
        break;
        case 3;
            $status = '<span class="badge badge-info">Changeback</span>';
        break;
        default:
            $status = '<span class="badge badge-danger">Unknown</span>';
        break;
    }
   
    $pending_commands = DB::getInstance()->query('SELECT * FROM nl2_store_pending_commands WHERE payment_id = ? AND status = 0', array($payment->id))->results();
	$pending_commands_array = array();
    foreach($pending_commands as $command){
		$pending_commands_array[] = array(
            'command' => Output::getClean($command->command)
        );
    }
    
    $processed_commands = DB::getInstance()->query('SELECT * FROM nl2_store_pending_commands WHERE payment_id = ? AND status = 1', array($payment->id))->results();
	$processed_commands_array = array();
    foreach($processed_commands as $command){
		$processed_commands_array[] = array(
            'command' => Output::getClean($command->command)
        );
	}
    
	$smarty->assign(array(
		'VIEWING_PAYMENT' => str_replace('{x}', Output::getClean($payment->transaction), $store_language->get('admin', 'viewing_payment')),
		'BACK' => $language->get('general', 'back'),
		'BACK_LINK' => URL::build('/panel/store/payments'),
		'IGN' => $store_language->get('admin', 'ign'),
		'IGN_VALUE' => Output::getClean($payment->username),
		'USER_LINK' => URL::build('/panel/store/payments/', 'player=' . Output::getClean($payment->username)),
		'AVATAR' => $avatar,
		'STYLE' => $style,
        'TRANSACTION' => $store_language->get('admin', 'transaction'),
		'TRANSACTION_VALUE' => Output::getClean($payment->transaction),
        'PAYMENT_METHOD' => $store_language->get('admin', 'payment_method'),
		'PAYMENT_METHOD_VALUE' => Output::getClean($payment->payment_method),
        'STATUS' => $store_language->get('admin', 'status'),
		'STATUS_VALUE' => $status,
		'UUID' => $store_language->get('admin', 'uuid'),
		'UUID_VALUE' => Output::getClean($payment->uuid),
		'PRICE' => $store_language->get('general', 'price'),
		'PRICE_VALUE' => Output::getClean($payment->amount),
		'CURRENCY_SYMBOL' => Output::getClean('$'),
		'CURRENCY_ISO' => Output::getClean($payment->currency),
		'DATE' => $store_language->get('admin', 'date'),
		'DATE_VALUE' => date('d M Y, H:i', $payment->created),
		'PENDING_COMMANDS' => $store_language->get('admin', 'pending_commands'),
        'PROCESSED_COMMANDS' => $store_language->get('admin', 'processed_commands'),
		'NO_PENDING_COMMANDS' => $store_language->get('admin', 'no_pending_commands'),
        'NO_PROCESSED_COMMANDS' => $store_language->get('admin', 'no_processed_commands'),
		'PENDING_COMMANDS_LIST' => $pending_commands_array,
        'PROCESSED_COMMANDS_LIST' => $processed_commands_array,
	));

	$template_file = 'store/payments_view.tpl';

} else if(isset($_GET['action'])){
	if($_GET['action'] == 'create'){
        die();
		// New payment
		$smarty->assign(array(
			'NEW_PAYMENT' => $store_language->get('admin', 'new_payment'),
			'CANCEL' => $language->get('general', 'cancel'),
			'CANCEL_LINK' => URL::build('/panel/store/payments'),
			'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
			'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
			'YES' => $language->get('general', 'yes'),
			'NO' => $language->get('general', 'no')
		));

		if(isset($_GET['step'])){

		} else {
			// Choose product
			$products = $queries->orderAll('store_products', '`order`', 'ASC');

			if(count($products)){
				$template_products = array();

				foreach($products as $product){
					$template_products[] = array(
						'id' => Output::getClean($product->id),
						'name' => Output::getClean($product->name)
					);
				}

				$smarty->assign(array(
					'IGN' => $store_language->get('admin', 'ign'),
					'PRODUCT' => $store_language->get('general', 'product'),
					'PRODUCTS' => $template_products
				));

			} else
				$smarty->assign('NO_PRODUCTS', $store_language->get('general', 'no_products'));

			$template_file = 'store/payments_new_step_1.tpl';
		}
	}

} else {
	$payments = $store->getAllPayments();

	if(count($payments)){
		$template_payments = array();

		foreach($payments as $payment){
            $payment_user = new User(str_replace('-', '', $payment->uuid), 'uuid');
			if($payment_user->data()){
				$avatar = $payment_user->getAvatar();
				$style = $payment_user->getGroupClass();
			} else {
				$avatar = Util::getAvatarFromUUID(Output::getClean($payment->uuid));
				$style = '';
			}
            
            switch($payment->status_id) {
                case 0;
                    $status = '<span class="badge badge-warning">Pending</span>';
                break;
                case 1;
                    $status = '<span class="badge badge-success">Complete</span>';
                break;
                case 2;
                    $status = '<span class="badge badge-primary">Refunded</span>';
                break;
                case 3;
                    $status = '<span class="badge badge-info">Changeback</span>';
                break;
                default:
                    $status = '<span class="badge badge-danger">Unknown</span>';
                break;
            }

			$template_payments[] = array(
				'user_link' => 	URL::build('/panel/store/payments/', 'player=' . Output::getClean($payment->username)),
				'user_style' => $style,
				'user_avatar' => $avatar,
				'username' => Output::getClean($payment->username),
				'uuid' => Output::getClean($payment->uuid),
                'status_id' => $payment->status_id,
                'status' => $status,
				'currency_symbol' => '$',
				'amount' => Output::getClean($payment->amount),
				'date' => date('d M Y, H:i', $payment->created),
				'date_unix' => Output::getClean($payment->created),
				'link' => URL::build('/panel/store/payments/', 'payment=' . Output::getClean($payment->id))
			);
		}

		$smarty->assign(array(
			'USER' => $store_language->get('admin', 'user'),
			'AMOUNT' => $store_language->get('admin', 'amount'),
            'STATUS' => $store_language->get('admin', 'status'),
			'DATE' => $store_language->get('admin', 'date'),
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
		'NEW_PAYMENT' => $store_language->get('admin', 'new_payment'),
		'NEW_PAYMENT_LINK' => URL::build('/panel/store/payments/', 'action=create')
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
	'PAYMENTS' => $store_language->get('admin', 'payments')
));

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);