<?php
/**
 * PayPal_Business_Gateway class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
class PayPal_Business_Gateway extends GatewayBase implements SupportSubscriptions {

    public function __construct() {
        $name = 'PayPalBusiness';
        $author = '<a href="https://partydragen.com/" target="_blank" rel="nofollow noopener">Partydragen</a>';
        $gateway_version = '1.6.1';
        $store_version = '1.6.1';
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

        if (!$order->isSubscriptionMode()) {
            // Single payment
            $payer = new \PayPal\Api\Payer();
            $payer->setPaymentMethod('paypal');

            $amount = new \PayPal\Api\Amount();
            $amount->setTotal(Store::fromCents($order->getAmount()->getTotalCents()));
            $amount->setCurrency($order->getAmount()->getCurrency());

            $transaction = new \PayPal\Api\Transaction();
            $transaction->setAmount($amount);
            $transaction->setDescription($order->getDescription());
            $transaction->setInvoiceNumber($order->data()->id);
            $transaction->setPurchaseOrder($order->data()->id);
            $transaction->setCustom($order->data()->id);

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
        } else {
            // Payment subscription
            $product = null;
            foreach ($order->getProducts() as $item) {
                $product = $item;
                break;
            }

            $agreement = new \PayPal\Api\Agreement();
            $agreement->setName($product->data()->name)
                ->setDescription($product->data()->name)
                ->setStartDate(date("c", strtotime("+5 mins")));


            $agreement->setPlan($this->getPlan($product));

            $payer = new \PayPal\Api\Payer();
            $payer->setPaymentMethod('paypal');
            $agreement->setPayer($payer);

            try {
                $agreement = $agreement->create($apiContext);

                $approvalUrl = $agreement->getApprovalLink();

            } catch (Exception $ex) {
                echo "Failed to get activate";
                exit();
            }

            header("Location:" . $approvalUrl);
            exit();
        }
    }

    public function handleReturn(): bool {
        if (isset($_GET['do']) && $_GET['do'] == 'success') {
            if (!isset($_GET['paymentId']) && !isset($_GET['token'])) {
                ErrorHandler::logCustomError('Unknown payment id');
                $this->addError('There was a error processing this order');
                return false;
            }

            $apiContext = $this->getApiContext();
            if (count($this->getErrors())) {
                return false;
            }

            if (isset($_GET['paymentId'])) {
                // Single payment successfully made
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
                    $store_payment->create([
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

                $payment_definitions = $agreement->getPlan()->getPaymentDefinitions();
                $payment_definitions = $payment_definitions[0];

                $order_id = $_SESSION['shopping_cart']['order_id'];
                if ($order_id == null || !is_numeric($order_id)) {
                    die('INVALID ORDER ID');
                }

                // Get order
                $order = new Order($order_id);

                // Save agreement to database
                DB::getInstance()->insert('store_subscriptions', [
                    'order_id' => $order->data()->id,
                    'gateway_id' => $this->getId(),
                    'customer_id' => $order->customer()->data()->id,
                    'agreement_id' => $agreement->id,
                    'status_id' => 0,
                    'amount_cents' => Store::toCents($payment_definitions->getAmount()->getValue()),
                    'currency' => $payment_definitions->getAmount()->getCurrency(),
                    'frequency' => $payment_definitions->getFrequency(),
                    'frequency_interval' => $payment_definitions->getFrequencyInterval(),
                    'email' => $payer->getPayerInfo()->email,
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
        if (isset($_GET['key']) && $_GET['key'] == StoreConfig::get('paypal_business.key')) {
            $this->getApiContext();
            
            $bodyReceived = file_get_contents('php://input');
            $headers = getallheaders();
            $headers = array_change_key_case($headers, CASE_UPPER);
            $signatureVerification = new \PayPal\Api\VerifyWebhookSignature();
            $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
            $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
            $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL']);
            $signatureVerification->setWebhookId(StoreConfig::get('paypal_business.hook_key'));
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
                                // Subscription payment
                                $subscription = new Subscription($response->resource->billing_agreement_id, 'agreement_id');
                                if ($subscription->exists()) {
                                    $payment = new Payment($response->resource->parent_payment, 'payment_id');
                                    if (!$payment->exists()) {
                                        // Register new payment from subscription
                                        $data = [
                                            'order_id' => $subscription->data()->order_id,
                                            'payment_id' => $response->id,
                                            'gateway_id' => $this->getId(),
                                            'subscription_id' => $subscription->data()->id,
                                            'transaction' => $response->resource->id,
                                            'amount_cents' => Store::toCents($response->resource->amount->total),
                                            'currency' => $response->resource->amount->currency,
                                            'fee_cents' => Store::toCents($response->resource->transaction_fee->value ?? 0)
                                        ];

                                        $payment->handlePaymentEvent(Payment::COMPLETED, $data);

                                        $subscription->sync();
                                    }
                                }
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
                            // Subscription created
                            $subscription = new Subscription($response->resource->id, 'agreement_id');
                            if ($subscription->exists()) {
                                $subscription->update([
                                    'status_id' => 1
                                ]);

                                EventHandler::executeEvent(new SubscriptionCreatedEvent($subscription));
                            }

                            break;
                        case 'BILLING.SUBSCRIPTION.CANCELLED':
                            // Subscription cancelled
                            $subscription = new Subscription($response->resource->id, 'agreement_id');
                            if ($subscription->exists()) {
                                $subscription->cancelled();
                            }

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
        $client_id = StoreConfig::get('paypal_business.client_id');
        $client_secret = StoreConfig::get('paypal_business.client_secret');

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
                        'mode' => 'sandbox',
                    ]
                );

                $hook_key = StoreConfig::get('paypal_business.hook_key');
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

                    StoreConfig::set('paypal_business', [
                        'key' => $key,
                        'hook_key' => $id
                    ]);
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

    public function createSubscription(): void {
        // TODO: Implement createSubscription() method.
    }

    public function cancelSubscription(Subscription $subscription): bool {
        $apiContext = $this->getApiContext();
        if (count($this->getErrors())) {
            return false;
        }

        $agreementStateDescriptor = new PayPal\Api\AgreementStateDescriptor();
        $agreementStateDescriptor->setNote("Cancelled the agreement");

        $agreement = PayPal\Api\Agreement::get($subscription->data()->agreement_id, $apiContext);
        $agreement->cancel($agreementStateDescriptor, $apiContext);
        return true;
    }

    public function syncSubscription(Subscription $subscription): bool {
        $apiContext = $this->getApiContext();
        if (count($this->getErrors())) {
            return false;
        }

        $agreement = PayPal\Api\Agreement::get($subscription->data()->agreement_id, $apiContext);

        $last_payment_date = $subscription->data()->last_payment_date;
        if ($agreement->getAgreementDetails()->getLastPaymentDate() != null) {
            $last_payment_date = date("U", strtotime($agreement->getAgreementDetails()->getLastPaymentDate()));
        }

        $next_billing_date = $subscription->data()->next_billing_date;
        if ($agreement->getAgreementDetails()->getNextBillingDate() != null) {
            $next_billing_date = date("U", strtotime($agreement->getAgreementDetails()->getNextBillingDate()));
        }

        $agreement->getState();

        $subscription->update([
            'last_payment_date' => $last_payment_date,
            'next_billing_date' => $next_billing_date,
        ]);

        return true;
    }

    public function chargePayment(Subscription $subscription): bool {
        // TODO: Implement chargePayment() method.

        return false;
    }

    public function getPlan(Product $product): PayPal\Api\Plan {
        $plan_query = DB::getInstance()->query('SELECT * FROM nl2_store_products_meta WHERE product_id = ? AND name = ?', [$product->data()->id, 'paypal_plan_id']);
        if ($plan_query->count()) {
            // Use existing plan
            $plan = new PayPal\Api\Plan();
            $plan->setId($plan_query->first()->value);
        } else {
            // Create plan
            $plan = $this->createPlan($product);
            $plan = $this->updatePlan($plan);

            DB::getInstance()->insert('store_products_meta', [
                'product_id' => $product->data()->id,
                'name' => 'paypal_plan_id',
                'value' => $plan->getId()
            ]);
        }

        return $plan;
    }

    public function createPlan(Product $product): PayPal\Api\Plan {
        $durability_json = json_decode($product->data()->durability, true) ?? [];

        $plan = new PayPal\Api\Plan();
        $plan->setName('Payment Order')
            ->setDescription($product->data()->name)
            ->setType('INFINITE');

        $paymentDefinition = new PayPal\Api\PaymentDefinition();
        $paymentDefinition->setName('Regular Payments')
            ->setType('REGULAR')
            ->setFrequency($durability_json['period'] ?? 'month')
            ->setFrequencyInterval($durability_json['interval'] ?? 1)
            ->setAmount(new PayPal\Api\Currency(array('value' => Store::fromCents($product->data()->price_cents), 'currency' => Store::getCurrency())));

        $merchantPreferences = new PayPal\Api\MerchantPreferences();
        $merchantPreferences->setReturnUrl(rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPalBusiness&do=success'))
            ->setCancelUrl(rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPalBusiness&do=cancel'))
            //->setAutoBillAmount("yes")
            ->setInitialFailAmountAction("CONTINUE")
            ->setMaxFailAttempts("0");

        $plan->setPaymentDefinitions(array($paymentDefinition));
        $plan->setMerchantPreferences($merchantPreferences);

        return $plan->create($this->getApiContext());
    }

    public function updatePlan(PayPal\Api\Plan $plan): PayPal\Api\Plan {
        $patch = new PayPal\Api\Patch();

        $value = new PayPal\Common\PayPalModel('{
	       "state":"ACTIVE"
	     }');

        $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);
        $patchRequest = new PayPal\Api\PatchRequest();
        $patchRequest->addPatch($patch);

        $new_plan = new \PayPal\Api\Plan();
        $new_plan->setId($plan->getId());
        $new_plan->update($patchRequest, $this->getApiContext());

        return $new_plan;
    }
}

$gateway = new PayPal_Business_Gateway();