<?php
/**
 * PayPal_Business_Gateway class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
class PayPal_Business_Gateway extends GatewayBase {

    public function __construct() {
        $name = 'PayPalBusiness';
        $author = '<a href="https://partydragen.com/" target="_blank" rel="nofollow noopener">Partydragen</a>';
        $gateway_version = '1.6.2';
        $store_version = '1.6.2';
        $settings = ROOT_PATH . '/modules/Store/gateways/PayPalBusiness/gateway_settings/settings.php';

        parent::__construct($name, $author, $gateway_version, $store_version, $settings);
    }

    public function onCheckoutPageLoad(TemplateBase $template, Customer $customer): void {
        // Not necessary
    }

    public function processOrder(Order $order): void {
        $apiContext = $this->getApiContext();
        if (count($this->getErrors())) {
            return;
        }

        $payer = new \PayPal\Api\Payer();
        $payer->setPaymentMethod('paypal');

        $amount = new \PayPal\Api\Amount();
        $amount->setTotal(Store::fromCents($order->getAmount()->getTotalCents()));
        $amount->setCurrency($order->getAmount()->getCurrency());

        $transaction = new \PayPal\Api\Transaction();
        $transaction->setAmount($amount);
        $transaction->setDescription($order->getDescription());
        $transaction->setInvoiceNumber($order->data()->id);

        $redirectUrls = new \PayPal\Api\RedirectUrls();
        $redirectUrls->setReturnUrl(rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPalBusiness&do=success'))
            ->setCancelUrl(rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPalBusiness&do=cancel'));

        $payment = new \PayPal\Api\Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setTransactions([$transaction])
            ->setRedirectUrls($redirectUrls);

        try {
            $payment->create($apiContext);

            Redirect::to($payment->getApprovalLink());
        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
            ErrorHandler::logCustomError($ex->getData());
        }
    }

    public function handleReturn(): bool {
        if (isset($_GET['do']) && $_GET['do'] == 'success') {
            if (!isset($_GET['paymentId']) && !isset($_GET['token'])) {
                ErrorHandler::logCustomError('Unknown payment id');
                $this->addError('There was a error processing this order');
                return false;
            }

            if (isset($_GET['paymentId'])) {
                // Single payment successfully made
                $apiContext = $this->getApiContext();
                if (count($this->getErrors())) {
                    return false;
                }

                $paymentId = $_GET['paymentId'];
                $payment = \PayPal\Api\Payment::get($paymentId, $apiContext);
                
                $execution = new \PayPal\Api\PaymentExecution();
                $execution->setPayerId($_GET['PayerID']);

                try {
                    $result = $payment->execute($execution, $apiContext);
                    $payment = \PayPal\Api\Payment::get($paymentId, $apiContext);
                } catch (Exception $e) {
                    ErrorHandler::logCustomError('Message: ' . $e->getMessage());
                    $this->addError('There was a error processing this order');
                    return false;
                }
                
                $transactions = $payment->getTransactions();
                $related_resources = $transactions[0]->getRelatedResources();
                $sale = $related_resources[0]->getSale();
                
                $store_payment = new Payment($payment->getId(), 'payment_id');
                if (!$store_payment->exists()) {
                    // Register pending payment
                    $payment_id = $store_payment->create([
                        'order_id' => $transactions[0]->invoice_number,
                        'gateway_id' => $this->getId(),
                        'payment_id' => $payment->getId(),
                        'transaction' => $sale->getId(),
                        'amount_cents' => Store::toCents($transactions[0]->getAmount()->total),
                        'currency' => $transactions[0]->getAmount()->currency,
                        'fee_cents' => $sale->getTransactionFee() ? Store::toCents($sale->getTransactionFee()->getValue()) : null,
                        'created' => date('U'),
                        'last_updated' => date('U')
                    ]);
                }

                return true;
            } else if (isset($_GET['token'])) {
                // Agreement successfully made
                $token = $_GET['token'];
                $agreement = new \PayPal\Api\Agreement();

                try {
                    $agreement->execute($token, $apiContext);
                } catch (Exception $ex) {
                    ErrorHandler::logCustomError('Message: Failed to get activate: ' . $ex);
                    $this->addError('There was a error processing this order');
                    return false;
                }

                $agreement = \PayPal\Api\Agreement::get($agreement->getId(), $apiContext);
                $payer = $agreement->getPayer();

                $last_payment_date = 0;
                if ($agreement->getAgreementDetails()->getLastPaymentDate() != null) {
                    $last_payment_date = date("U", strtotime($agreement->getAgreementDetails()->getLastPaymentDate()));
                }

                $next_billing_date = 0;
                if ($agreement->getAgreementDetails()->getNextBillingDate() != null) {
                    $next_billing_date = date("U", strtotime($agreement->getAgreementDetails()->getNextBillingDate()));
                }

                // Save agreement to database
                DB::getInstance()->insert('store_agreements', [
                    'user_id' => ($user->isLoggedIn() ? $user->data()->id : null),
                    'player_id' => $player_id,
                    'agreement_id' => $agreement->id,
                    'status_id' => 1,
                    'email' => $payer->getPayerInfo()->email,
                    'payment_method' => $this->getId(),
                    'verified' => $payer->status == 'verified' ? 1 : 0,
                    'payer_id' => $payer->getPayerInfo()->payer_id,
                    'last_payment_date' => $last_payment_date,
                    'next_billing_date' => $next_billing_date,
                    'created' => date('U'),
                    'updated' => date('U')
                ]);

                return true;
            }
        }

        return false;
    }

    public function handleListener(): void {
        if (isset($_GET['key']) && $_GET['key'] == StoreConfig::get('paypal_business/key')) {
            $this->getApiContext();
            
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
                        file_put_contents(ROOT_PATH . '/cache/paypal_logs/'. $this->getName() . '_' .$response->event_type.'_'.date('U').'.txt', $bodyReceived);
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
                                        'transaction' => $response->resource->id,
                                        'amount_cents' => Store::toCents($response->resource->amount->total),
                                        'currency' => $response->resource->amount->currency,
                                        'fee_cents' => Store::toCents($response->resource->transaction_fee->value ?? 0)
                                    ];

                                } else {
                                    // Register new payment
                                    $data = [
                                        'order_id' => $response->resource->invoice_number,
                                        'payment_id' => $response->id,
                                        'gateway_id' => $this->getId(),
                                        'transaction' => $response->resource->id,
                                        'amount_cents' => Store::toCents($response->resource->amount->total),
                                        'currency' => $response->resource->amount->currency,
                                        'fee_cents' => Store::toCents($response->resource->transaction_fee->value ?? 0)
                                    ];
                                }

                                $payment->handlePaymentEvent(Payment::COMPLETED, $data);
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
                                $payment->handlePaymentEvent(Payment::REFUNDED);
                            }

                            break;
                        case 'PAYMENT.SALE.REVERSED':
                            // Payment reversed
                            $payment = new Payment($response->resource->id, 'transaction');
                            if ($payment->exists()) {
                                // Payment exists 
                                $payment->handlePaymentEvent(Payment::REVERSED);
                            }

                            break;
                        case 'PAYMENT.SALE.DENIED':
                            // Payment denied
                            $payment = new Payment($response->resource->id, 'transaction');
                            if ($payment->exists()) {
                                // Payment exists 
                                $payment->handlePaymentEvent(Payment::DENIED);
                            }

                            break;
                        case 'BILLING.SUBSCRIPTION.CREATED':
                            $id = $response->resource->id;

                            break;
                        case 'BILLING.SUBSCRIPTION.CANCELLED':
                            $id = $response->resource->id;

                            DB::getInstance()->query('UPDATE `nl2_store_agreements` SET status = ?, updated = ? WHERE agreement_id = ?', [
                                2,
                                date('U'),
                                $id
                            ]);

                            break;
                        case 'BILLING.SUBSCRIPTION.SUSPENDED':
                            $id = $response->resource->id;

                            DB::getInstance()->query('UPDATE `nl2_store_agreements` SET status = ?, updated = ? WHERE agreement_id = ?', [
                                3,
                                date('U'),
                                $id
                            ]);

                            break;
                        case 'BILLING.SUBSCRIPTION.RE-ACTIVATED':
                            $id = $response->resource->id;

                            DB::getInstance()->query('UPDATE `nl2_store_agreements` SET status = ?, updated = ? WHERE agreement_id = ?', [
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
                            ErrorHandler::logCustomError('[PayPal] Unknown event type ' . $response->event_type);
                            break;
                    }
                } else {
                    // Save response to log
                    if (is_dir(ROOT_PATH . '/cache/paypal_logs/')) {
                        file_put_contents(ROOT_PATH . '/cache/paypal_logs/' . $this->getName() . '_no_event_'.date('U').'.txt', $bodyReceived);
                    }
                }

            } catch (\PayPal\Exception\PayPalInvalidCredentialException $e) {
                // Error verifying webhook
                ErrorHandler::logCustomError('[PayPal] ' . $e->errorMessage());
            } catch (Exception $e) {
                ErrorHandler::logCustomError('[PayPal] ' . $e->getMessage());
            }
        }
    }

    private function getApiContext() {
        $client_id = StoreConfig::get('paypal_business/client_id');
        $client_secret = StoreConfig::get('paypal_business/client_secret');

        if ($client_id && $client_secret) {
            try {
                require_once(ROOT_PATH . '/modules/Store/gateways/PayPalBusiness/autoload.php');
                $apiContext = new \PayPal\Rest\ApiContext(
                    new \PayPal\Auth\OAuthTokenCredential(
                        $client_id,
                        $client_secret
                    )
                );

                $apiContext->setConfig(
                    [
                        'log.LogEnabled' => true,
                        'log.FileName' => ROOT_PATH . '/cache/logs/PayPal.log',
                        'log.LogLevel' => 'FINE',
                        'mode' => 'live',
                    ]
                );

                $hook_key = StoreConfig::get('paypal_business/hook_key');
                if (!$hook_key) {
                    $key = md5(uniqid());

                    // Create API webhook
                    $webhook = new \PayPal\Api\Webhook();
                    $webhookEventTypes = [];

                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.COMPLETED"}');
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.DENIED"}');
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.REFUNDED"}');
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.REVERSED"}');
                    
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.CREATED"}');
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.CANCELLED"}');
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.SUSPENDED"}');
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.RE-ACTIVATED"}');
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.UPDATED"}');
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.EXPIRED"}');
                    
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.PLAN.CREATED"}');
                    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.PLAN.UPDATED"}');

                    $webhook->setUrl(rtrim(URL::getSelfURL(), '/') . URL::build('/store/listener', 'gateway=PayPalBusiness&key=' . $key));
                    $webhook->setEventTypes($webhookEventTypes);
                    $output = $webhook->create($apiContext);
                    $id = $output->getId();
                    
                    StoreConfig::set(['paypal_business/key' => $key, 'paypal_business/hook_key' => $id]);
                }
                
                return $apiContext;
            } catch (Exception $e) {
                ErrorHandler::logCustomError($e->getData());
                $this->addError('PayPal integration incorrectly configured!');
            }
        } else {
            $this->addError('Administration have not completed the configuration of this gateway!');
        }
    }
}

$gateway = new PayPal_Business_Gateway();