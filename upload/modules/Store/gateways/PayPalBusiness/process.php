<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module
 */

require_once(ROOT_PATH . '/modules/Store/gateways/PayPalBusiness/paypal.php');

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

			// Save payment to database
			$queries->create('store_payments', array(
				'user_id' => ($user->isLoggedIn() ? $user->data()->id : null),
				'player_id' => $player_id,
				'payment_id' => $payment->getId(),
				'payment_method' => $gateway->getId(),
				'created' => date('U'),
				'last_updated' => date('U')
			));
            
            $payment_id = $queries->getLastId();
            foreach($shopping_cart->getItems() as $item) {
                $queries->create('store_payments_packages', array(
                    'payment_id' => $payment_id,
                    'package_id' => $item['id']
                ));
            }
			
			$shopping_cart->clear();
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
				'payment_method' => $gateway->getId(),
				'verified' => (Output::getClean($payer->status) == 'verified' ? 1 : 0),
				'payer_id' => Output::getClean($payer->getPayerInfo()->payer_id),
				'last_payment_date' => $last_payment_date,
				'next_billing_date' => $next_billing_date,
				'created' => date('U'),
				'updated' => date('U')
			));
            
            $agreement_id = $queries->getLastId();
            foreach($shopping_cart->getItems() as $item) {
                $queries->create('store_payments', array(
                    'agreement_id' => $agreement_id,
                    'package_id' => $item['id']
                ));
            }
			
			$shopping_cart->clear();
			Redirect::to(URL::build($store_url . '/checkout/', 'do=complete'));
			die();
		}
		Redirect::to(URL::build($store_url));
		die();
	} else {
		// Invalid
		Redirect::to(URL::build($store_url . '/checkout/', 'do=cancel'));
		die();
	}

} else {
    // Build package names string
    $packages_names = '';
    foreach($shopping_cart->getPackages() as $package) {
        $packages_names .= $package->name . ', ';
    }
    $packages_names = rtrim($packages_names, ', ');

                    
    $payer = new \PayPal\Api\Payer();
    $payer->setPaymentMethod('paypal');

    $amount = new \PayPal\Api\Amount();
    $amount->setTotal($shopping_cart->getTotalPrice());
    $amount->setCurrency('USD');

    $transaction = new \PayPal\Api\Transaction();
    $transaction->setAmount($amount);
    $transaction->setDescription($packages_names);

    $redirectUrls = new \PayPal\Api\RedirectUrls();
    $redirectUrls->setReturnUrl(rtrim(Util::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPalBusiness&do=success'))
        ->setCancelUrl(rtrim(Util::getSelfURL(), '/') . URL::build($store_url . '/checkout/', 'gateway=PayPalBusiness&do=cancel'));

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
    }
}