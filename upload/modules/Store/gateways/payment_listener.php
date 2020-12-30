<?php
$data = $queries->getWhere('store_settings', array('name', '=', 'store_hook_key'));

if(!count($data))
    die('Error 1');
else
    $data = $data[0]->value;

if(isset($_GET['key']) && $_GET['key'] == $data){
    require_once(ROOT_PATH . '/modules/Store/gateways/paypal/paypal.php');
    require_once(ROOT_PATH . '/modules/Store/classes/Store.php');
    $store = new Store();
    
    if(!count($gateway_data)) {
        die('Error 2');
    }
    $gateway_data = $gateway_data[0];
    
    $bodyReceived = file_get_contents('php://input');
    $headers = getallheaders();
    $headers = array_change_key_case($headers, CASE_UPPER);
    $signatureVerification = new \PayPal\Api\VerifyWebhookSignature();
    $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
    $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
    $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL']);
    $signatureVerification->setWebhookId($gateway_data->hook_key);
    $signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG']);
    $signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']);
    $signatureVerification->setRequestBody($bodyReceived);

    try {
        $response = json_decode($bodyReceived);     
        if(isset($response->event_type)) {

            
            file_put_contents(ROOT_PATH . '/api_'.$response->event_type.'_'.date('U').'.txt', $bodyReceived);

            $id = 'Unknown!';
            // Handle event
            switch($response->event_type){
                case 'PAYMENT.SALE.COMPLETED':
                    if(isset($response->resource->parent_payment)){
                        // Single payment
                        $transaction = $queries->getWhere('store_payments', array('payment_id', '=', $response->resource->parent_payment));
                        $transaction = $transaction[0];
                        if(count($transaction)) {
                            $queries->update('store_payments', $transaction->id, array(
                                'transaction' => $response->resource->id,
                                'status_id' => 1,
                                'last_updated' => date('U'),
                                'amount' => Output::getClean($response->resource->amount->total),
                                'currency' => Output::getClean($response->resource->amount->currency),
                            ));
                            
                            $store->addPendingCommands($transaction->player_id, $transaction->id, 1);
                        }
                        
                    } else if(isset($response->resource->billing_agreement_id)){
                        // Agreement payment
                        
                        // Save payment to database
                        $queries->create('store_payments', array(
                            'agreement_id' => Output::getClean($response->resource->billing_agreement_id),
                            'transaction' => Output::getClean($response->resource->id),
                            'amount' => Output::getClean($response->resource->amount->total),
                            'currency' => Output::getClean($response->resource->amount->currency),
                            'payment_method' => 'paypal',
                            'status_id' => 1,
                            'created' => date('U'),
                            'last_updated' => date('U')
                        ));
                        
                        $agreement = $queries->getWhere('store_agreements', array('agreement_id', '=', $response->resource->billing_agreement_id));
                        if(count($agreement)) {
                            $agreement = $agreement[0];
                            
                            $packages = json_decode($agreement->packages, true);
                        }
                        
                    } else {
                        /// Unknown payment
                        throw new Exception('Unknown payment type');
                        die('Unknown payment type');
                    }
                    
                    break;
                case 'PAYMENT.SALE.REFUNDED':
                    $payment = $queries->getWhere('store_payments', array('transaction', '=', $response->resource->id));
                    $payment = $payment[0];
                    if(count($payment)) {
                        DB::getInstance()->createQuery('UPDATE `nl2_store_payments` SET status = ?, updated = ? WHERE transaction = ?', array(
                            2,
                            date('U'),
                            $response->resource->id
                        ));
                        
                        $store->deletePendingCommands($payment->id);
                        $store->addPendingCommands($payment->player_id, $payment->id, 2);
                    }
                    
                    break;
                case 'PAYMENT.SALE.REVERSED':
                    $payment = $queries->getWhere('store_payments', array('transaction', '=', $response->resource->id));
                    $payment = $payment[0];
                    if(count($payment)) {        
                        DB::getInstance()->createQuery('UPDATE `nl2_store_payments` SET status = ?, updated = ? WHERE transaction = ?', array(
                            3,
                            date('U'),
                            $response->resource->id
                        ));
                        
                        $store->deletePendingCommands($payment->id);
                        $store->addPendingCommands($payment->player_id, $payment->id, 3);
                    }
                    
                    break;
                case 'PAYMENT.SALE.DENIED':
                    $payment = $queries->getWhere('store_payments', array('transaction', '=', $response->resource->id));
                    $payment = $payment[0];
                    if(count($payment)) {
                        DB::getInstance()->createQuery('UPDATE `nl2_store_payments` SET status = ?, updated = ? WHERE transaction = ?', array(
                            4,
                            date('U'),
                            $response->resource->id
                        ));
                    }
                    
                    break;
                case 'BILLING.SUBSCRIPTION.CREATED':
                    $id = $response->resource->id;
                    
                    break;
                case 'BILLING.SUBSCRIPTION.CANCELLED':
                    $id = $response->resource->id;
                    
                    DB::getInstance()->createQuery('UPDATE `nl2_store_agreements` SET status = ?, updated = ? WHERE agreement_id = ?', array(
                        2,
                        date('U'),
                        $id
                    ));
                    
                    break;
                case 'BILLING.SUBSCRIPTION.SUSPENDED':
                    $id = $response->resource->id;
                    
                    DB::getInstance()->createQuery('UPDATE `nl2_store_agreements` SET status = ?, updated = ? WHERE agreement_id = ?', array(
                        3,
                        date('U'),
                        $id
                    ));
                    
                    break;
                case 'BILLING.SUBSCRIPTION.RE-ACTIVATED':
                    $id = $response->resource->id;
                    
                    DB::getInstance()->createQuery('UPDATE `nl2_store_agreements` SET status = ?, updated = ? WHERE agreement_id = ?', array(
                        1,
                        date('U'),
                        $id
                    ));
                            
                    break;
                case 'BILLING.PLAN.CREATED':
                    $id = $response->resource->id;
                    break;
                case 'BILLING.PLAN.UPDATED':
                    $id = $response->resource->id;
                    break;
                default:
                    // Error
                    ErrorHandler::logCustomError('[PayPal] Unknown event type ' . Output::getClean($response->event_type));
                    break;
            }
            
            HookHandler::executeEvent('paypal_hook', array(
                'event' => 'paypal_hook',
                'username' => 'Success',
                'content' => 'Success',
                'content_full' => $response->event_type,
                'title' => 'Success',
                'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/suggestions/view')
            ));
            
        } else {
            // Event type not set!!!!
            file_put_contents(ROOT_PATH . '/api_no_event_'.date('U').'.txt', $bodyReceived);
        }
            
    } catch(\PayPal\Exception\PayPalInvalidCredentialException $e){
        // Error verifying webhook
        ErrorHandler::logCustomError('[PayPal] ' . $e->errorMessage());
        HookHandler::executeEvent('paypal_hook', array(
            'event' => 'paypal_hook',
            'webhook' => 'https://discordapp.com/api/webhooks/767420207882567720/KoksbsCA0jECaIM8S9zehPUcSc_ec2aXbp_Ic8Vcyzed99yi5YrKloDZWuZv57MnF1Rb',
            'username' => 'success',
            'content' => 'success',
            'content_full' => $e->getMessage(),
            'title' => 'Error 3',
            'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/suggestions/view')
        ));
    } catch(Exception $e){
        ErrorHandler::logCustomError('[PayPal] ' . $e->getMessage());
        HookHandler::executeEvent('paypal_hook', array(
            'event' => 'paypal_hook',
            'webhook' => 'https://discordapp.com/api/webhooks/767420207882567720/KoksbsCA0jECaIM8S9zehPUcSc_ec2aXbp_Ic8Vcyzed99yi5YrKloDZWuZv57MnF1Rb',
            'username' => 'success',
            'content' => 'success',
            'content_full' => $e->getMessage(),
            'title' => 'Error 4',
            'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/suggestions/view')
        ));
    }
}
die();
