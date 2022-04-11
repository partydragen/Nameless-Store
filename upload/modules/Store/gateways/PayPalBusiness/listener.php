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

if (isset($_GET['key']) && $_GET['key'] == StoreConfig::get('paypal_business/key')) {
    require_once(ROOT_PATH . '/modules/Store/gateways/PayPalBusiness/paypal.php');

    $bodyReceived = file_get_contents('php://input');
    $headers = getallheaders();
    $headers = array_change_key_case($headers, CASE_UPPER);
    $signatureVerification = new \PayPal\Api\VerifyWebhookSignature();
    $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
    $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
    $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL']);
    $signatureVerification->setWebhookId(StoreConfig::get('paypal_business/hook_key'));
    $signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG']);
    $signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']);
    $signatureVerification->setRequestBody($bodyReceived);
    
    try {
        $response = json_decode($bodyReceived);     
        if (isset($response->event_type)) {
            
            // Save response to log
            if (is_dir(ROOT_PATH . '/cache/paypal_logs/')) {
                file_put_contents(ROOT_PATH . '/cache/paypal_logs/'. $gateway->getName() . '_' .$response->event_type.'_'.date('U').'.txt', $bodyReceived);
            }
            
            // Handle event
            switch ($response->event_type) {
                case 'PAYMENT.SALE.COMPLETED':
                    if (isset($response->resource->parent_payment)) {
                        // Single payment
                        
                        $payment = new Payment($response->resource->parent_payment, 'payment_id');
                        if ($payment->exists()) {
                            // Payment exists
                            $data = [
                                'transaction' => Output::getClean($response->resource->id),
                                'amount' => Output::getClean($response->resource->amount->total),
                                'currency' => Output::getClean($response->resource->amount->currency),
                                'fee' => Output::getClean($response->resource->transaction_fee->value)
                            ];

                        } else {
                            // Register new payment
                            $data = [
                                'order_id' => Output::getClean($response->resource->invoice_number),
                                'payment_id' => Output::getClean($response->id),
                                'gateway_id' => $gateway->getId(),
                                'transaction' => Output::getClean($response->resource->id),
                                'amount' => Output::getClean($response->resource->amount->total),
                                'currency' => Output::getClean($response->resource->amount->currency),
                                'fee' => Output::getClean($response->resource->transaction_fee->value)
                            ];
                        }
                        
                        $payment->handlePaymentEvent('COMPLETED', $data);
                    } else if (isset($response->resource->billing_agreement_id)) {
                        // Agreement payment

                    } else {
                        /// Unknown payment
                        throw new Exception('Unknown payment type');
                        die('Unknown payment type');
                    }
                    
                    break;
                case 'PAYMENT.SALE.REFUNDED':
                    // Payment refunded
                    $payment = new Payment($response->resource->sale_id, 'transaction');
                    if ($payment->exists()) {
                        // Payment exists 
                        $payment->handlePaymentEvent('REFUNDED', []);
                    }
                    
                    break;
                case 'PAYMENT.SALE.REVERSED':
                    // Payment reversed
                    $payment = new Payment($response->resource->id, 'transaction');
                    if ($payment->exists()) {
                        // Payment exists 
                        $payment->handlePaymentEvent('REVERSED', []);
                    }
                    
                    break;
                case 'PAYMENT.SALE.DENIED':
                    // Payment denied
                    $payment = new Payment($response->resource->id, 'transaction');
                    if ($payment->exists()) {
                        // Payment exists 
                        $payment->handlePaymentEvent('DENIED', []);
                    }
                    
                    break;
                case 'BILLING.SUBSCRIPTION.CREATED':
                    $id = $response->resource->id;
                    
                    break;
                case 'BILLING.SUBSCRIPTION.CANCELLED':
                    $id = $response->resource->id;
                    
                    DB::getInstance()->createQuery('UPDATE `nl2_store_agreements` SET status = ?, updated = ? WHERE agreement_id = ?', [
                        2,
                        date('U'),
                        $id
                    ]);
                    
                    break;
                case 'BILLING.SUBSCRIPTION.SUSPENDED':
                    $id = $response->resource->id;
                    
                    DB::getInstance()->createQuery('UPDATE `nl2_store_agreements` SET status = ?, updated = ? WHERE agreement_id = ?', [
                        3,
                        date('U'),
                        $id
                    ]);
                    
                    break;
                case 'BILLING.SUBSCRIPTION.RE-ACTIVATED':
                    $id = $response->resource->id;
                    
                    DB::getInstance()->createQuery('UPDATE `nl2_store_agreements` SET status = ?, updated = ? WHERE agreement_id = ?', [
                        1,
                        date('U'),
                        $id
                    ]);
                            
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
        } else {
            // Save response to log
            if (is_dir(ROOT_PATH . '/cache/paypal_logs/')) {
                file_put_contents(ROOT_PATH . '/cache/paypal_logs/' . $gateway->getName() . '_no_event_'.date('U').'.txt', $bodyReceived);
            }
        }
            
    } catch (\PayPal\Exception\PayPalInvalidCredentialException $e) {
        // Error verifying webhook
        ErrorHandler::logCustomError('[PayPal] ' . $e->errorMessage());
    } catch (Exception $e) {
        ErrorHandler::logCustomError('[PayPal] ' . $e->getMessage());
    }
}
die();
