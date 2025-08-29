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

    private $api_url = 'https://api-m.sandbox.paypal.com'; // Use 'https://api-m.sandbox.paypal.com' for sandbox

    public function __construct() {
        $name = 'PayPalBusiness';
        $author = '<a href="https://partydragen.com" target="_blank" rel="nofollow noopener">Partydragen</a> and my <a href="https://partydragen.com/supporters/" target="_blank">Sponsors</a>';
        $gateway_version = '2.0.0';
        $store_version = '1.8.3';
        $settings = ROOT_PATH . '/modules/Store/gateways/PayPalBusiness/gateway_settings/settings.php';

        parent::__construct($name, $author, $gateway_version, $store_version, $settings);
    }

    public function onCheckoutPageLoad(TemplateBase $template, Customer $customer): void {
        // Not necessary
    }

    public function processOrder(Order $order): void {
        $access_token = $this->getAccessToken();
        if (count($this->getErrors())) {
            return;
        }

        if (!$order->isSubscriptionMode()) {
            // Single payment using Orders API
            $order_data = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $order->getAmount()->getCurrency(),
                            'value' => Store::fromCents($order->getAmount()->getTotalCents())
                        ],
                        'description' => $order->getDescription(),
                        'invoice_id' => $order->data()->id,
                        'custom_id' => $order->data()->id
                    ]
                ],
                'application_context' => [
                    'return_url' => rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPalBusiness&do=success'),
                    'cancel_url' => rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPalBusiness&do=cancel')
                ]
            ];

            $response = $this->makeApiRequest('/v2/checkout/orders', 'POST', $access_token, $order_data);
            if (isset($response['id'])) {
                foreach ($response['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        Redirect::to($link['href']);
                    }
                }
            } else {
                $this->logError(json_encode($response));
                $this->addError('Failed to create PayPal order');
            }
        } else {
            // Subscription payment using Subscriptions API
            $product = null;
            foreach ($order->items()->getItems() as $item) {
                $product = $item->getProduct();
                break;
            }

            $plan_id = $this->getPlan($product);
            if (!$plan_id) {
                $this->addError('Failed to retrieve or create subscription plan');
                return;
            }

            $subscription_data = [
                'plan_id' => $plan_id,
                'application_context' => [
                    'return_url' => rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPalBusiness&do=success'),
                    'cancel_url' => rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPalBusiness&do=cancel')
                ]
            ];

            $response = $this->makeApiRequest('/v1/billing/subscriptions', 'POST', $access_token, $subscription_data);
            if (isset($response['id'])) {
                foreach ($response['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        Redirect::to($link['href']);
                    }
                }
            } else {
                $this->logError(json_encode($response));
                $this->addError('Failed to create PayPal subscription');
            }
        }
    }

    public function handleReturn(): bool {
        if (isset($_GET['do']) && $_GET['do'] == 'success') {
            if (!isset($_GET['token'])) {
                $this->logError('Unknown order or subscription ID');
                $this->addError('There was an error processing this order');
                return false;
            }

            $access_token = $this->getAccessToken();
            if (count($this->getErrors())) {
                return false;
            }

            if (!isset($_GET['subscription_id'])) {
                // Single payment
                $order_id = $_GET['token'];
                $response = $this->makeApiRequest("/v2/checkout/orders/{$order_id}/capture", 'POST', $access_token, ['custom_id' => $order_id]);

                if (isset($response['status']) && $response['status'] === 'COMPLETED') {
                    $purchase_unit = $response['purchase_units'][0];
                    $capture = $purchase_unit['payments']['captures'][0];

                    $store_payment = new Payment($response['id'], 'payment_id');
                    if (!$store_payment->exists()) {
                        $store_payment->create([
                            'order_id' => $capture['invoice_id'],
                            'gateway_id' => $this->getId(),
                            'payment_id' => $response['id'],
                            'transaction' => $capture['id'],
                            'amount_cents' => Store::toCents($capture['amount']['value']),
                            'currency' => $capture['amount']['currency_code'],
                            'fee_cents' => isset($capture['seller_receivable_breakdown']['paypal_fee']['value']) ? Store::toCents($capture['seller_receivable_breakdown']['paypal_fee']['value']) : null,
                            'created' => date('U'),
                            'last_updated' => date('U')
                        ]);
                    }
                    return true;
                } else {
                    $this->logError(json_encode($response));
                    $this->addError('There was an error capturing the payment');
                    return false;
                }
            } else {
                // Subscription
                $subscription_id = $_GET['subscription_id'];
                $response = $this->makeApiRequest("/v1/billing/subscriptions/{$subscription_id}", 'GET', $access_token);

                if (isset($response['status']) && $response['status'] === 'ACTIVE') {
                    $order_id = $_SESSION['shopping_cart']['order_id'];
                    if ($order_id == null || !is_numeric($order_id)) {
                        $this->logError('Invalid order id');
                        $this->addError('Invalid order id');
                        return false;
                    }

                    $order = new Order($order_id);
                    $plan = $this->makeApiRequest("/v1/billing/plans/{$response['plan_id']}", 'GET', $access_token);
                    $billing_cycles = $plan['billing_cycles'][0];

                    DB::getInstance()->insert('store_subscriptions', [
                        'order_id' => $order->data()->id,
                        'gateway_id' => $this->getId(),
                        'customer_id' => $order->customer()->data()->id,
                        'agreement_id' => $subscription_id,
                        'status_id' => Subscription::PENDING,
                        'amount_cents' => Store::toCents($billing_cycles['pricing_scheme']['fixed_price']['value']),
                        'currency' => $billing_cycles['pricing_scheme']['fixed_price']['currency_code'],
                        'frequency' => strtolower($billing_cycles['frequency']['interval_unit']),
                        'frequency_interval' => $billing_cycles['frequency']['interval_count'],
                        'email' => $response['subscriber']['email_address'] ?? null,
                        'verified' => 1,
                        'payer_id' => $response['subscriber']['payer_id'] ?? null,
                        'last_payment_date' => null,
                        'next_billing_date' => isset($response['billing_info']['next_billing_time']) ? date('U', strtotime($response['billing_info']['next_billing_time'])) : 0,
                        'created' => date('U'),
                        'updated' => date('U')
                    ]);

                    return true;
                } else {
                    $this->logError(json_encode($response));
                    $this->addError('There was an error activating the subscription');
                    return false;
                }
            }
        }

        return false;
    }

    public function handleListener(): void {
        if (isset($_GET['key']) && $_GET['key'] == StoreConfig::get('paypal_business.key')) {
            $access_token = $this->getAccessToken();
            if (count($this->getErrors())) {
                http_response_code(500);
                return;
            }

            $body_received = file_get_contents('php://input');
            $headers = getallheaders();
            $headers = array_change_key_case($headers, CASE_UPPER);

            $verify_data = [
                'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? '',
                'cert_url' => $headers['PAYPAL-CERT-URL'] ?? '',
                'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
                'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
                'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
                'webhook_id' => StoreConfig::get('paypal_business.hook_key'),
                'webhook_event' => json_decode($body_received, true)
            ];

            $verify_response = $this->makeApiRequest('/v1/notifications/verify-webhook-signature', 'POST', $access_token, $verify_data);
            if ($verify_response['verification_status'] !== 'SUCCESS') {
                http_response_code(400);
                $this->logError('Webhook signature verification failed: ' . json_encode($verify_response));
                return;
            }

            $response = json_decode($body_received, true);
            if (isset($response['event_type'])) {
                $this->logWebhookResponse($body_received, $response['event_type']);

                switch ($response['event_type']) {
                    case 'CHECKOUT.ORDER.APPROVED':
                        // Handle single payment completion
                        if (isset($response['resource']['purchase_units'])) {
                            $payment = new Payment($response['resource']['id'], 'payment_id');
                            if ($payment->exists()) {
                                $data = [
                                    'transaction' => $response['resource']['purchase_units'][0]['payments']['captures'][0]['id'],
                                    'amount_cents' => Store::toCents($response['resource']['purchase_units'][0]['amount']['value']),
                                    'currency' => $response['resource']['purchase_units'][0]['amount']['currency_code'],
                                    'fee_cents' => isset($response['resource']['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value']) ? Store::toCents($response['resource']['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value']) : 0
                                ];
                                $payment->handlePaymentEvent(Payment::COMPLETED, $data);
                            }
                        }
                        break;

                    case 'PAYMENT.CAPTURE.REFUNDED':
                        $payment = new Payment($response['resource']['id'], 'transaction');
                        if ($payment->exists()) {
                            $payment->handlePaymentEvent(Payment::REFUNDED);
                        }
                        break;

                    case 'PAYMENT.CAPTURE.REVERSED':
                        $payment = new Payment($response['resource']['id'], 'transaction');
                        if ($payment->exists()) {
                            $payment->handlePaymentEvent(Payment::REVERSED);
                        }
                        break;

                    case 'PAYMENT.CAPTURE.DENIED':
                        $payment = new Payment($response['resource']['id'], 'transaction');
                        if ($payment->exists()) {
                            $payment->handlePaymentEvent(Payment::DENIED);
                        }
                        break;

                    case 'BILLING.SUBSCRIPTION.ACTIVATED':
                        $subscription = new Subscription($response['resource']['id'], 'agreement_id');
                        if ($subscription->exists()) {
                            $subscription->update([
                                'status_id' => Subscription::ACTIVE
                            ]);
                            EventHandler::executeEvent(new SubscriptionCreatedEvent($subscription));
                        }
                        break;

                    case 'BILLING.SUBSCRIPTION.CANCELLED':
                        $subscription = new Subscription($response['resource']['id'], 'agreement_id');
                        if ($subscription->exists()) {
                            $subscription->cancelled();
                        }
                        break;

                    case 'BILLING.SUBSCRIPTION.SUSPENDED':
                        $subscription = new Subscription($response['resource']['id'], 'agreement_id');
                        if ($subscription->exists()) {
                            $subscription->update([
                                'status_id' => Subscription::PAUSED,
                                'updated' => date('U')
                            ]);
                        }
                        break;

                    case 'BILLING.SUBSCRIPTION.RE-ACTIVATED':
                        $subscription = new Subscription($response['resource']['id'], 'agreement_id');
                        if ($subscription->exists()) {
                            $subscription->update([
                                'status_id' => Subscription::ACTIVE,
                                'updated' => date('U')
                            ]);
                        }
                        break;

                    case 'PAYMENT.SALE.COMPLETED':
                        if (isset($response['resource']['billing_agreement_id'])) {
                            $subscription = new Subscription($response['resource']['billing_agreement_id'], 'agreement_id');
                            if ($subscription->exists()) {
                                $payment = new Payment($response['resource']['id'], 'transaction');
                                if (!$payment->exists()) {
                                    $data = [
                                        'order_id' => $subscription->data()->order_id,
                                        'payment_id' => $response['id'],
                                        'gateway_id' => $this->getId(),
                                        'subscription_id' => $subscription->data()->id,
                                        'transaction' => $response['resource']['id'],
                                        'amount_cents' => Store::toCents($response['resource']['amount']['total']),
                                        'currency' => $response['resource']['amount']['currency'],
                                        'fee_cents' => isset($response['resource']['transaction_fee']['value']) ? Store::toCents($response['resource']['transaction_fee']['value']) : 0
                                    ];
                                    $payment->handlePaymentEvent(Payment::COMPLETED, $data);
                                    $subscription->sync();
                                }
                            } else {
                                $this->logError('Could not handle payment for invalid subscription ' . $response['resource']['billing_agreement_id']);
                            }
                        }
                        break;

                    default:
                        $this->logError('Unknown event type ' . $response['event_type']);
                        break;
                }
            } else {
                $this->logWebhookResponse($body_received, 'unknown');
            }
        } else {
            http_response_code(400);
            $this->logError('Missing or invalid webhook key');
        }
    }

    private function getAccessToken(): ?string {
        $client_id = StoreConfig::get('paypal_business.client_id');
        $client_secret = StoreConfig::get('paypal_business.client_secret');

        if (!$client_id || !$client_secret) {
            $this->logError('Client ID and Client Secret not set up');
            $this->addError('Administration have not completed the configuration of this gateway!');
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->api_url}/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, "{$client_id}:{$client_secret}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: en_US']);

        $response = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && isset($response['access_token'])) {
            $hook_key = StoreConfig::get('paypal_business.hook_key');
            if (!$hook_key) {
                $key = md5(uniqid());
                $webhook_data = [
                    'url' => rtrim(URL::getSelfURL(), '/') . URL::build('/store/listener', 'gateway=PayPalBusiness&key=' . $key),
                    'event_types' => [
                        ['name' => 'CHECKOUT.ORDER.APPROVED'],
                        ['name' => 'PAYMENT.CAPTURE.REFUNDED'],
                        ['name' => 'PAYMENT.CAPTURE.REVERSED'],
                        ['name' => 'PAYMENT.CAPTURE.DENIED'],
                        ['name' => 'BILLING.SUBSCRIPTION.ACTIVATED'],
                        ['name' => 'BILLING.SUBSCRIPTION.CANCELLED'],
                        ['name' => 'BILLING.SUBSCRIPTION.SUSPENDED'],
                        ['name' => 'BILLING.SUBSCRIPTION.RE-ACTIVATED'],
                        ['name' => 'PAYMENT.SALE.COMPLETED']
                    ]
                ];

                $webhook_response = $this->makeApiRequest('/v1/notifications/webhooks', 'POST', $response['access_token'], $webhook_data);
                if (isset($webhook_response['id'])) {
                    StoreConfig::setMultiple([
                        'paypal_business.key' => $key,
                        'paypal_business.hook_key' => $webhook_response['id']
                    ]);
                } else {
                    $this->logError('Failed to create webhook: ' . json_encode($webhook_response));
                    $this->addError('PayPal integration incorrectly configured!');
                    return null;
                }
            }
            return $response['access_token'];
        }

        $this->logError('Failed to obtain access token: ' . json_encode($response));
        $this->addError('PayPal integration incorrectly configured!');
        return null;
    }

    private function makeApiRequest(string $endpoint, string $method, string $access_token, array $data = []): array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->api_url}{$endpoint}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = json_decode(curl_exec($ch), true) ?: [];
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 400) {
            $this->logError("API request failed ($endpoint): " . json_encode($response));
        }

        $response["http_code"] = $http_code;
        return $response;
    }

    public function createSubscription(): void {
        // Not necessary for this gateway
    }

    public function cancelSubscription(Subscription $subscription): bool {
        $access_token = $this->getAccessToken();
        if (count($this->getErrors())) {
            return false;
        }

        $response = $this->makeApiRequest("/v1/billing/subscriptions/{$subscription->data()->agreement_id}/cancel", 'POST', $access_token, [
            'reason' => 'Cancelled the agreement'
        ]);

        if (isset($response['http_code']) && $response['http_code'] === 204) {
            return true;
        }

        $this->logError('Failed to cancel subscription: ' . json_encode($response));
        return false;
    }

    public function syncSubscription(Subscription $subscription): bool {
        $access_token = $this->getAccessToken();
        if (count($this->getErrors())) {
            return false;
        }

        $response = $this->makeApiRequest("/v1/billing/subscriptions/{$subscription->data()->agreement_id}", 'GET', $access_token);
        if (!isset($response['status'])) {
            $this->logError('Failed to sync subscription: ' . json_encode($response));
            return false;
        }

        $last_payment_date = $subscription->data()->last_payment_date;
        if (isset($response['billing_info']['last_payment']['time'])) {
            $last_payment_date = date('U', strtotime($response['billing_info']['last_payment']['time']));
        }

        $next_billing_date = $subscription->data()->next_billing_date;
        if (isset($response['billing_info']['next_billing_time'])) {
            $next_billing_date = date('U', strtotime($response['billing_info']['next_billing_time']));
        }

        $subscription->update([
            'last_payment_date' => $last_payment_date,
            'next_billing_date' => $next_billing_date,
            'status_id' => $this->subscriptionStatus($response['status'])
        ]);

        return true;
    }

    public function chargePayment(Subscription $subscription): bool {
        // Not necessary for this gateway
        return false;
    }

    public function getPlan(Product $product): ?string {
        $plan_query = DB::getInstance()->query('SELECT * FROM nl2_store_products_meta WHERE product_id = ? AND name = ?', [$product->data()->id, 'paypal_plan_id']);
        if ($plan_query->count()) {
            return $plan_query->first()->value;
        }

        $plan_id = $this->createPlan($product);
        if ($plan_id) {
            DB::getInstance()->insert('store_products_meta', [
                'product_id' => $product->data()->id,
                'name' => 'paypal_plan_id',
                'value' => $plan_id
            ]);
            return $plan_id;
        }

        return null;
    }

    public function createPlan(Product $product): ?string {
        $access_token = $this->getAccessToken();
        if (count($this->getErrors())) {
            return null;
        }

        // Get or create PayPal product
        $paypal_product_id = $this->getPayPalProductId($product);
        if (!$paypal_product_id) {
            $this->logError('Failed to retrieve or create PayPal product');
            return null;
        }

        $duration_json = json_decode($product->data()->durability, true) ?? [];

        $plan_data = [
            'product_id' => $paypal_product_id,
            'name' => 'Payment Order',
            'description' => $product->data()->name,
            'status' => 'ACTIVE',
            'billing_cycles' => [
                [
                    'frequency' => [
                        'interval_unit' => strtoupper($duration_json['period'] ?? 'MONTH'),
                        'interval_count' => $duration_json['interval'] ?? 1
                    ],
                    'tenure_type' => 'REGULAR',
                    'sequence' => 1,
                    'total_cycles' => 0,
                    'pricing_scheme' => [
                        'fixed_price' => [
                            'value' => Store::fromCents($product->data()->price_cents),
                            'currency_code' => Store::getCurrency()
                        ]
                    ]
                ]
            ],
            'payment_preferences' => [
                'auto_bill_outstanding' => true,
                'payment_failure_threshold' => 0
            ]
        ];

        $response = $this->makeApiRequest('/v1/billing/plans', 'POST', $access_token, $plan_data);
        if (isset($response['id'])) {
            return $response['id'];
        }

        $this->logError('Failed to create plan: ' . json_encode($response));
        return null;
    }

    private function getPayPalProductId(Product $product): ?string {
        // Check if a PayPal product_id exists in the database
        $product_query = DB::getInstance()->query('SELECT * FROM nl2_store_products_meta WHERE product_id = ? AND name = ?', [$product->data()->id, 'paypal_product_id']);
        if ($product_query->count()) {
            return $product_query->first()->value;
        }

        // Create a new product in PayPal
        $access_token = $this->getAccessToken();
        if (count($this->getErrors())) {
            return null;
        }

        $product_data = [
            'name' => $product->data()->name,
            'description' => $product->data()->name,
            'type' => 'SERVICE',
            'category' => 'SOFTWARE'
        ];

        $response = $this->makeApiRequest('/v1/catalogs/products', 'POST', $access_token, $product_data);
        if (isset($response['id'])) {
            $paypal_product_id = $response['id'];
            // Store the product_id in the database
            DB::getInstance()->insert('store_products_meta', [
                'product_id' => $product->data()->id,
                'name' => 'paypal_product_id',
                'value' => $paypal_product_id
            ]);
            return $paypal_product_id;
        }

        $this->logError('Failed to create PayPal product: ' . json_encode($response));
        return null;
    }

    public function subscriptionStatus(string $status): int {
        switch (strtoupper($status)) {
            case 'ACTIVE':
                return Subscription::ACTIVE;
            case 'CANCELLED':
                return Subscription::CANCELLED;
            case 'SUSPENDED':
                return Subscription::PAUSED;
            default:
                return Subscription::UNKNOWN;
        }
    }
}

$gateway = new PayPal_Business_Gateway();