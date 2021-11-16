<?php
/*
 *  Made by Partydragen
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
            
            echo '<pre>', print_r($_POST), '</pre>';
            echo '<pre>', print_r($payment), '</pre>';
            
            $transactions = $payment->getTransactions();
            $related_resources = $transactions[0]->getRelatedResources();
            $sale = $related_resources[0]->getSale();
            
            $store_payment = new Payment($payment->getId(), 'payment_id');
            if(!$store_payment->exists()) {
                // Register pending payment
                $payment_id = $store_payment->create(array(
                    'order_id' => Output::getClean($transactions[0]->invoice_number),
                    'gateway_id' => $gateway->getId(),
                    'payment_id' => Output::getClean($payment->getId()),
                    'transaction' => $sale->getId(),
                    'amount' => Output::getClean($transactions[0]->getAmount()->total),
                    'currency' => Output::getClean($transactions[0]->getAmount()->currency),
                    'fee' => $sale->getTransactionFee() ? $sale->getTransactionFee()->getValue() : null,
                    'created' => date('U'),
                    'last_updated' => date('U')
                ));
            }

            $shopping_cart->clear();

            //Redirect::to(URL::build($store_url . '/checkout/', 'do=complete'));
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

            
            $shopping_cart->clear();
            Redirect::to(URL::build($store_url . '/checkout/', 'do=complete'));
            die();
        }
        Redirect::to(URL::build($store_url));
        die();
    } else {
        // Invalid
        //Redirect::to(URL::build($store_url . '/checkout/', 'do=cancel'));
        echo '<pre>', print_r($_POST), '</pre>';
        die();
    }

} else {
    // Build product names string
    $product_names = '';
    foreach($order->getProducts() as $product) {
        $product_names .= $product->name . ', ';
    }
    $product_names = rtrim($product_names, ', ');
    
    $currency = Output::getClean($configuration->get('store', 'currency'));

                    
    $payer = new \PayPal\Api\Payer();
    $payer->setPaymentMethod('paypal');

    $amount = new \PayPal\Api\Amount();
    $amount->setTotal($shopping_cart->getTotalPrice());
    $amount->setCurrency($currency);

    $transaction = new \PayPal\Api\Transaction();
    $transaction->setAmount($amount);
    $transaction->setDescription($product_names);
    $transaction->setInvoiceNumber($order->data()->id);

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