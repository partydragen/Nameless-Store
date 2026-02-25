<?php
/**
 * Stripe_Gateway class
 *
 * @package Modules\Store
 * @author Supercrafter100
 * @version 2.0.3
 * @license MIT
 */
class Stripe_Gateway extends GatewayBase implements SupportSubscriptions {

    public function __construct() {
        $name = 'Stripe';
        $author = '<a href="https://github.com/supercrafter100/" target="_blank" rel="nofollow noopener">Supercrafter100</a> and <a href="https://partydragen.com" target="_blank" rel="nofollow noopener">Partydragen</a>';
        $gateway_version = '1.9.0';
        $store_version = '1.9.0';
        $settings = ROOT_PATH . '/modules/Store/gateways/Stripe/gateway_settings/settings.php';

        parent::__construct($name, $author, $gateway_version, $store_version, $settings);
    }

    public function onCheckoutPageLoad(TemplateBase $template, Customer $customer): void {
        // Not necessary
    }

    public function processOrder(Order $order): void {
        $stripe = $this->getApiContext();
        if (count($this->getErrors())) {
            return;
        }

        $currency = $order->getAmount()->getCurrency();
        $successRedirect = $this->getReturnURL();
        $cancelRedirect = $this->getCancelURL();

        $products = [];
        if (!$order->isSubscriptionMode()) {
            // Single payment
            foreach ($order->items()->getItems() as $item) {
                $products[] = [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $item->getProduct()->data()->name,
                        ],
                        'unit_amount' => $item->getSingleQuantityPrice(),
                    ],
                    'quantity' => $item->getQuantity()
                ];
            }

            try {
                $json = [
                    'mode' => 'payment',
                    'line_items' => $products,
                    'success_url' => $successRedirect,
                    'cancel_url' => $cancelRedirect,

                    'payment_intent_data' => [
                        'metadata' => [
                            'order_id' => $order->data()->id
                        ],
                    ]
                ];

                $session = $stripe->checkout->sessions->create($json);
                Redirect::to($session->url);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->logError($e->getMessage());
                $this->addError('Somethings went wrong, Please contact administration!');
            }

            return;
        }

        // Payment subscription
        foreach ($order->items()->getItems() as $item) {
            $product = $item->getProduct();
            $duration_json = json_decode($product->data()->durability, true) ?? [];

            if (!in_array($duration_json['period'], ['day', 'week', 'month', 'year']) || $duration_json['interval'] < 1) {
                $this->addError('Invalid durability for product ' . $product->data()->name);
                return;
            }

            $products[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $product->data()->name,
                    ],
                    'unit_amount' => $item->getSingleQuantityPrice(),
                    'recurring' => [
                        'interval' => $duration_json['period'],
                        'interval_count' => $duration_json['interval']
                    ]
                ],
                'quantity' => $item->getQuantity()
            ];
        }

        try {
            $json = [
                'mode' => 'subscription',
                'line_items' => $products,
                'success_url' => $successRedirect,
                'cancel_url' => $cancelRedirect,
                'metadata' => [
                    'order_id' => $order->data()->id
                ],
                'subscription_data' => [
                    'metadata' => [
                        'order_id' => $order->data()->id
                    ],
                ]
            ];

            $session = $stripe->checkout->sessions->create($json);
            Redirect::to($session->url);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->logError($e->getMessage());
            $this->addError('Somethings went wrong, Please contact administration!');
        }
    }

    public function handleReturn(): bool {
        if (isset($_GET['do']) && $_GET['do'] == 'success') {
            return true;
        }

        return false;
    }

    public function handleListener(): void {
        $this->getApiContext();

        $webhook_secret = StoreConfig::get('stripe.hook_key');
        if (!$webhook_secret) {
            http_response_code(500);
            $this->logError('No webhook secret found. Is it set up?');
            return;
        }

        $bodyReceived = file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $response = json_decode($bodyReceived);

        // Log webhook response
        $this->logWebhookResponse($bodyReceived, $response->type);

        try {
            $event = \Stripe\Webhook::constructEvent(
                $bodyReceived, $sig_header, $webhook_secret
            );
        } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            $this->logError($e->getMessage());
            return;
        }

        switch($event->type) {
            case 'payment_intent.succeeded':
               $data = $event->data->object;
               if (isset($data->metadata->order_id)) {
                   $payment = new Payment($data->charges->data[0]->payment_intent, 'payment_id');
                   $payment->handlePaymentEvent(Payment::COMPLETED, [
                       'order_id' => $data->metadata->order_id,
                       'gateway_id' => $this->getId(),
                       'payment_id' => $data->charges->data[0]->payment_intent,
                       'transaction' => $data->charges->data[0]->id,
                       'amount_cents' => $data->amount_received,
                       'currency' => strtoupper($data->currency),
                   ]);
               }
               break;

            case 'charge.refunded':
                $data = $event->data->object;
                $payment = new Payment($data->payment_intent, 'payment_id');
                if ($payment->exists()) {
                    $payment->handlePaymentEvent(Payment::REFUNDED);
                }
                break;

            case 'charge.failed':
                $data = $event->data->object;
                $payment = new Payment($data->payment_intent, 'payment_id');
                if ($payment->exists()) {
                    $payment->handlePaymentEvent(Payment::DENIED);
                }
                break;

            case 'charge.dispute.closed':
                $data = $event->data->object;
                if ($data->status === "lost") {
                    $payment = new Payment($data->charge, 'transaction');
                    if ($payment->exists()) {
                        $payment->handlePaymentEvent(Payment::REVERSED);
                    }
                }
                break;

            case 'customer.subscription.created':
                // Subscription created
                $data = $event->data->object;

                // Get order
                $order = new Order($data->metadata->order_id);

                $subscription = new Subscription($data->id, 'agreement_id');
                if (!$subscription->exists()) {
                    // Save agreement to database
                    $subscription = new Subscription();
                    $subscription->create([
                        'order_id' => $data->metadata->order_id,
                        'gateway_id' => $this->getId(),
                        'customer_id' => $order->customer()->data()->id,
                        'agreement_id' => $data->id,
                        'status_id' => $this->subscriptionStatus($data->status),
                        'amount_cents' => $data->plan->amount,
                        'currency' => strtoupper($data->plan->currency),
                        'frequency' => strtoupper($data->plan->interval),
                        'frequency_interval' => $data->plan->interval_count,
                        'payer_id' => $data->customer,
                        'next_billing_date' => $data->current_period_end ?? date('U'),
                        'created' => date('U'),
                        'updated' => date('U')
                    ]);
                }
                break;

            case 'customer.subscription.updated':
                // Subscription updated
                $data = $event->data->object;
                $subscription = new Subscription($data->id, 'agreement_id');
                if ($subscription->exists()) {
                   $subscription->update([
                       'status_id' => $this->subscriptionStatus($data->status),
                       'next_billing_date' => $data->current_period_end,
                       'updated' => date('U')
                   ]);
                } else {
                    http_response_code(503);
                    $this->logError('Received subscription updated event for unknown subscription');
                }
                break;

            case 'customer.subscription.deleted':
                // Subscription deleted
                $data = $event->data->object;
                $subscription = new Subscription($data->id, 'agreement_id');
                if ($subscription->exists()) {
                    $subscription->cancelled();
                }
                break;

            case 'invoice.paid':
                $data = $event->data->object;
                if (isset($data->subscription, $data->subscription_details)) {
                    $subscription = new Subscription($data->subscription, 'agreement_id');
                    if ($subscription->exists()) {
                        $payment = new Payment($data->payment_intent, 'payment_id');
                        if (!$payment->exists()) {
                            // Register new payment from subscription
                            $data = [
                                'order_id' => $data->subscription_details->metadata->order_id,
                                'payment_id' => $data->payment_intent,
                                'gateway_id' => $this->getId(),
                                'subscription_id' => $subscription->data()->id,
                                'transaction' => $data->charge,
                                'amount_cents' => $data->total,
                                'currency' => strtoupper($data->currency)
                            ];

                            $payment->handlePaymentEvent(Payment::COMPLETED, $data);
                        }

                        $subscription->update([
                            'last_payment_date' => date('U')
                        ]);
                    } else {
                        http_response_code(503);
                        $this->logError('Received invoice paid event for unknown subscription');
                    }
                }
                break;
        }
    }

    private function getApiContext(): ?\Stripe\StripeClient {
        $secret_key = StoreConfig::get('stripe.secret_key');

        if ($secret_key) {
            try {
                require_once(ROOT_PATH . '/modules/Store/gateways/Stripe/vendor/autoload.php');
                $stripe = new \Stripe\StripeClient($secret_key);

                $hook_key = StoreConfig::get('stripe.hook_key');
                if (!$hook_key) {
                    $webhook = $stripe->webhookEndpoints->create([
                        'url' => $this->getListenerURL(),
                        'enabled_events' => [
                            'payment_intent.succeeded',
                            'charge.refunded',
                            'charge.failed',
                            'charge.dispute.closed',
                            'customer.subscription.created',
                            'customer.subscription.updated',
                            'customer.subscription.deleted',
                            'customer.subscription.paused',
                            'customer.subscription.resumed',
                            'invoice.paid'
                        ]
                    ]);

                    if ($webhook->secret == null || empty($webhook->secret)) {
                        $this->logError('Could not generate webhook secret');
                        $this->addError('Somethings went wrong, Please contact administration!');
                        return null;
                    }

                    StoreConfig::setMultiple([
                        'stripe.hook_id' => $webhook->id,
                        'stripe.hook_key' => $webhook->secret
                    ]);
                } else {
                    $this->updateWebhook($stripe);
                }

                return $stripe;
            } catch (Exception $e) {
                $this->logError($e->getMessage());
                $this->addError('Stripe integration incorrectly configured!');
            }
        } else {
            $this->addError('Administration has not completed the configuration of this gateway!');
        }

        return null;
    }

    public function createSubscription(): void {

    }

    public function cancelSubscription(Subscription $subscription): bool {
        $stripe = $this->getApiContext();
        if (count($this->getErrors())) {
            return false;
        }

        try {
            $stripe->subscriptions->cancel($subscription->data()->agreement_id);
            return true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }

    public function syncSubscription(Subscription $subscription): bool {
        $stripe = $this->getApiContext();
        if (count($this->getErrors())) {
            return false;
        }
        $data = $stripe->subscriptions->retrieve($subscription->data()->agreement_id);

        $subscription->update([
            'status_id' => $this->subscriptionStatus($data->status),
            'next_billing_date' => $data->current_period_end,
        ]);

        return true;
    }

    public function chargePayment(Subscription $subscription): bool {
        return false;
    }

    public function subscriptionStatus(string $status): int {
        switch($status) {
            case 'incomplete':
                $status_id = Subscription::PENDING;
                break;
            case 'active':
            case 'past_due':
            case 'unpaid':
            case 'trialing':
                $status_id = Subscription::ACTIVE;
                break;
            case 'incomplete_expired':
            case 'canceled':
                $status_id = Subscription::CANCELLED;
                break;
            default:
                $status_id = Subscription::UNKNOWN;
                break;
        }

        return $status_id;
    }

    public function updateWebhook(Stripe\StripeClient $stripe) {
        $hook_id = StoreConfig::get('stripe.hook_id');
        $hook_key = StoreConfig::get('stripe.hook_key');
        if ($hook_key && !$hook_id) {
            // Update existing webhooks
            $last_id = null;
            foreach ($stripe->webhookEndpoints->all() as $webhook) {
                if (str_contains($webhook->url, 'gateway=Stripe')) {
                    $webhook = $stripe->webhookEndpoints->update($webhook->id, [
                        'enabled_events' => [
                            'payment_intent.succeeded',
                            'charge.refunded',
                            'charge.failed',
                            'charge.dispute.closed',
                            'customer.subscription.created',
                            'customer.subscription.updated',
                            'customer.subscription.deleted',
                            'customer.subscription.paused',
                            'customer.subscription.resumed',
                            'invoice.paid'
                        ]
                    ]);

                    $last_id = $webhook->id;
                }
            }

            if ($last_id) {
                StoreConfig::set('stripe.hook_id', $last_id);
            }
        }
    }
}

$gateway = new Stripe_Gateway();
