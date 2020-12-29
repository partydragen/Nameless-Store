<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com
 *
 *  Store page - checkout view
 */

// Always define page name
define('PAGE', 'store');
$page_title = $store_language->get('general', 'store');

require_once(ROOT_PATH . '/core/templates/frontend_init.php');
require_once(ROOT_PATH . '/modules/Store/gateways/paypal/paypal.php');

// Get variables from cache
$cache->setCache('store_settings');
if($cache->isCached('store_url')){
	$store_url = Output::getClean(rtrim($cache->retrieve('store_url'), '/'));
} else {
	$store_url = '/store';
}

if(isset($_GET['do'])){
	if($_GET['do'] == 'success'){
		if(!isset($_GET['paymentId']) && !isset($_GET['token'])){
			die('Invalid id!');
		}
		
		$player_id = 0;
		$store_player = $_SESSION['store_player'];
		$player = DB::getInstance()->query('SELECT * FROM nl2_store_players WHERE uuid = ?', array($store_player['uuid']))->results();
		if(count($player)) {
			$queries->update('store_players', $player[0]->id, array(
				'username' => $store_player['username'],
				'uuid' => $store_player['uuid']
			));
			
			$player_id = $player[0]->id;
		} else {
			$queries->create('store_players', array(
				'username' => $store_player['username'],
				'uuid' => $store_player['uuid']
			));
			
			$player_id = DB::getInstance()->lastId();
		}
			
		if(isset($_GET['paymentId'])) {
			// Single payment successfully made
			$paymentId = $_GET['paymentId'];
			$payment = \PayPal\Api\Payment::get($paymentId, $apiContext);

			$execution = new \PayPal\Api\PaymentExecution();
			$execution->setPayerId($_GET['PayerID']);

			try {
				$result = $payment->execute($execution, $apiContext);
				$payment = \PayPal\Api\Payment::get($paymentId, $apiContext);
			} catch(Exception $e){
				ErrorHandler::logCustomError('Message: ' . $e->getMessage());
				die('Unknown error');
			}

			// Save agreement to database
			$queries->create('store_payments', array(
				'user_id' => ($user->isLoggedIn() ? $user->data()->id : null),
				'player_id' => $player_id,
				'payment_id' => $payment->getId(),
				'payment_method' => 1,
				'created' => date('U'),
				'last_updated' => date('U')
			));
            
            $payment_id = $queries->getLastId();
            foreach($_SESSION['packages'] as $package_id) {
                $queries->create('store_payments_packages', array(
                    'payment_id' => $payment_id,
                    'package_id' => $package_id
                ));
            }
			
			unset($_SESSION['packages']);
			Redirect::to(URL::build($store_url . '/checkout/', 'do=complete'));
			die();
		} else if(isset($_GET['token'])) {
			// Agreement successfully made
			$token = $_GET['token'];
			$agreement = new \PayPal\Api\Agreement();

			try {
				$agreement->execute($token, $apiContext);
			} catch (Exception $ex) {
				echo "Failed to get activate";
				var_dump($ex);
				exit();
			}
			
			$agreement = \PayPal\Api\Agreement::get($agreement->getId(), $apiContext);
			$payer = $agreement->getPayer();
			
			$last_payment_date = 0;
			if($agreement->getAgreementDetails()->getLastPaymentDate() != null) {
				$last_payment_date = date("U", strtotime($agreement->getAgreementDetails()->getLastPaymentDate()));
			}
			
			$next_billing_date = 0;
			if($agreement->getAgreementDetails()->getNextBillingDate() != null) {
				$next_billing_date = date("U", strtotime($agreement->getAgreementDetails()->getNextBillingDate()));
			}

			// Save agreement to database
			$queries->create('store_agreements', array(
				'user_id' => ($user->isLoggedIn() ? $user->data()->id : null),
				'player_id' => $player_id,
				'agreement_id' => Output::getClean($agreement->id),
				'status_id' => 1,
				'email' => Output::getClean($payer->getPayerInfo()->email),
				'payment_method' => 1,
				'verified' => (Output::getClean($payer->status) == 'verified' ? 1 : 0),
				'payer_id' => Output::getClean($payer->getPayerInfo()->payer_id),
				'last_payment_date' => $last_payment_date,
				'next_billing_date' => $next_billing_date,
				'created' => date('U'),
				'updated' => date('U')
			));
            
            $agreement_id = $queries->getLastId();
            foreach($_SESSION['packages'] as $package_id) {
                $queries->create('store_payments', array(
                    'agreement_id' => $agreement_id,
                    'package_id' => $package_id
                ));
            }
			
			unset($_SESSION['packages']);
			Redirect::to(URL::build($store_url . '/checkout/', 'do=complete'));
			die();
		}
		Redirect::to(URL::build($store_url));
		die();
		
	} else if($_GET['do'] == 'complete'){
		$template_file = 'store/checkout_complete.tpl';
	} else {
		// Invalid
		Redirect::to(URL::build($store_url));
		die();
	}
} else {
	if(isset($_GET['add'])) {
		if(!is_numeric($_GET['add'])){
			die('Invalid package');
		}
		
		$_SESSION['packages'] = array($_GET['add']);
		Redirect::to(URL::build($store_url . '/checkout/'));
		die();
	}

	if(isset($_GET['remove'])) {
		if(!is_numeric($_GET['remove'])){
			die('Invalid package');
		}
		
		unset($_SESSION['packages']);
		Redirect::to(URL::build($store_url . '/checkout/'));
		die();
	}

	if(!isset($_SESSION['packages'])) {
		Redirect::to(URL::build($store_url));
		die();
	}
	
	$packages_ids = '(';
	foreach($_SESSION['packages'] as $package) {
		$packages_ids .= (int) $package . ',';
	}
	$packages_ids = rtrim($packages_ids, ',');
	$packages_ids .= ')';
	
	// Get packages
	$packages = DB::getInstance()->query('SELECT * FROM nl2_store_packages WHERE id in '.$packages_ids.' AND deleted = 0 ')->results();
	$packages = $packages[0];
	if(!count($packages)) {
		Redirect::to(URL::build($store_url));
		die();
	}
	
	if(Input::exists()){
		if(Token::check(Input::get('token'))){
			if(Input::get('type') == 'single') {
				// Create a normal payment
				if(!in_array($packages->payment_type, array(1, 3))) {
					Redirect::to(URL::build($store_url));
					die();
				}
				
				$payer = new \PayPal\Api\Payer();
				$payer->setPaymentMethod('paypal');

				$amount = new \PayPal\Api\Amount();
				$amount->setTotal($packages->price);
				$amount->setCurrency('USD');

				$transaction = new \PayPal\Api\Transaction();
				$transaction->setAmount($amount);
				$transaction->setDescription($packages->name);

				$redirectUrls = new \PayPal\Api\RedirectUrls();
				$redirectUrls->setReturnUrl(rtrim(Util::getSelfURL(), '/') . URL::build('/store/checkout/', 'do=success'))
							->setCancelUrl(rtrim(Util::getSelfURL(), '/') . URL::build('/store/checkout/', 'do=cancel'));

				$payment = new \PayPal\Api\Payment();
				$payment->setIntent('sale')
					->setPayer($payer)
					->setTransactions(array($transaction))
					->setRedirectUrls($redirectUrls);

				try {
					$payment->create($apiContext);

					Redirect::to($payment->getApprovalLink());
					die();

				} catch (\PayPal\Exception\PayPalConnectionException $ex) {
					ErrorHandler::logCustomError($ex->getData());
					$error = 'error_while_purchasing';
				}
			} else if(Input::get('type') == 'agreement') {
				// Create a subscription
				if(!in_array($packages->payment_type, array(2, 3))) {
					Redirect::to(URL::build($store_url));
					die();
				}
				
				$agreement = new \PayPal\Api\Agreement();

				$agreement->setName($packages->name)
					->setDescription($packages->name)
					->setStartDate(date("c", strtotime("+2 mins")));

				$plan = new \PayPal\Api\Plan();

				$plan->setId($packages->plan_id);
				$agreement->setPlan($plan);

				$payer = new \PayPal\Api\Payer();
				$payer->setPaymentMethod('paypal');
				$agreement->setPayer($payer);

				try {
					$agreement = $agreement->create($apiContext);

					$approvalUrl = $agreement->getApprovalLink();

				} catch (Exception $ex) {
					echo "Failed to get activate";
					var_dump($ex);
					exit();
				}

				header("Location:" . $approvalUrl);
				exit();
			} else if(Input::get('type') == 'store_logout') {
				// Logout the store player
				unset($_SESSION['store_player']);
			}
		} else
			$error = 'invalid_token';
	}
	
	$smarty->assign(array(
		'TOKEN' => Token::get(),
		'PACKAGE' => $packages->name,
		'TOTAL_PRICE' => $packages->price,
		'CHECKOUT' => $store_language->get('general', 'checkout'),
		'SUMMARY' => $store_language->get('general', 'summary'),
		'PURCHASE' => $store_language->get('general', 'purchase')
	));
	
	$template_file = 'store/checkout.tpl';
}

// Check if store player is required and isset
if(!isset($_SESSION['store_player'])) {
	Redirect::to(URL::build($store_url));
	die();
}

$smarty->assign(array(
	'STORE' => $store_language->get('general', 'store')
));

if(isset($_SESSION['store_player'])) {
	$smarty->assign(array(
		'STORE_PLAYER' => Output::getClean($_SESSION['store_player']['username'])
	));
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets, $template);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

$smarty->assign('WIDGETS', $widgets->getWidgets());

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate($template_file, $smarty);