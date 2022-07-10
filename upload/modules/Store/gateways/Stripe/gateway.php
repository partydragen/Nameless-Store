<?php
/**
 * Stripe_Gateway class
 *
 * @package Modules\Store
 * @author Supercrafter100
 * @version 2.0.0-pre13
 * @license MIT
 */
class Stripe_Gateway extends GatewayBase {

    public function __construct()
    {
        $name = 'Stripe';
        $settings = ROOT_PATH . '/modules/Store/gateways/Stripe/gateway_settings/settings.php';

        parent::__construct($name, $settings);
    }

    public function processOrder(Order $order): void
    {
        $this->getApiContext();
        if (count($this->getErrors())) {
            return;
        }

        $currency = $order->getAmount()->getCurrency();
        $successRedirect = rtrim(Util::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=Stripe&do=success');
        $cancelRedirect = rtrim(Util::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=Stripe&do=cancel');

        $products = [];
        foreach ($order->getProducts() as $product) {
            $products[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $product->data()->name,
                    ],
                    'unit_amount' => $product->data()->price * 100,
                ],
                'quantity' => 1
            ];
        }

        try {
            $json = [
                'mode' => 'payment',
                'line_items' => $products,
                'success_url' => $successRedirect,
                'cancel_url' => $cancelRedirect,

                'payment_intent_data' => [
                    'metadata'=> [
                        'order_id' => $order->data()->id
                    ],
                ]
            ];

            $session = \Stripe\Checkout\Session::create($json);
            Redirect::to($session->url);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            ErrorHandler::logCustomError($e->getMessage());
        }
    }

    public function handleReturn(): bool
    {
        // TODO: Implement handleReturn() method.
        return true;
    }

    public function handleListener(): void
    {
        $this->getApiContext();

        $webhook_secret = StoreConfig::get('stripe/hook_key');
        if (!$webhook_secret) {
            ErrorHandler::logCustomError('No webhook secret found. Is it set up?');
            return;
        }

        $bodyReceived = file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        $response = json_decode($bodyReceived);

        if (is_dir(ROOT_PATH . '/cache/stripe_logs/')) {
            file_put_contents(ROOT_PATH . '/cache/stripe_logs/' . $this->getName() . '_' . $response->type . "_" . date('U') . '.txt', $bodyReceived);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $bodyReceived, $sig_header, $webhook_secret
            );
        } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            ErrorHandler::logCustomError($e->getMessage());
            return;
        }

        switch($event->type) {
            case 'payment_intent.succeeded':
               $data = $event->data->object;
               if (isset($data->metadata->order_id)) {
                   $payment = new Payment($data->charges->data[0]->payment_intent, 'payment_id');
                   $payment->handlePaymentEvent('COMPLETED', [
                       'order_id' => $data->metadata->order_id,
                       'gateway_id' => $this->getId(),
                       'payment_id' => $data->charges->data[0]->payment_intent,
                       'transaction' => $data->charges->data[0]->id,
                       'amount' => $data->amount_received / 100,
                       'currency' => $data->currency,
                       'fee' => 0,
                   ]);
               }
               break;

            case 'charge.refunded':
                $data = $event->data->object;
                $payment = new Payment($data->payment_intent, 'payment_id');
                if ($payment->exists()) {
                    $payment->handlePaymentEvent('REFUNDED');
                }
                break;

            case 'charge.failed':
                $data = $event->data->object;
                $payment = new Payment($data->payment_intent, 'payment_id');
                if ($payment->exists()) {
                    $payment->handlePaymentEvent('DENIED');
                }
                break;

            case 'charge.dispute.closed':
                $data = $event->data->object;
                if ($data->status === "lost") {
                    $payment = new Payment($data->charge, 'transaction');
                    if ($payment->exists()) {
                        $payment->handlePaymentEvent('REVERSED');
                    }
                }
                break;
        }
    }

    private function getApiContext() {
        $secret_key = StoreConfig::get('stripe/secret_key');

        if ($secret_key) {
            try {
                require_once(ROOT_PATH . '/modules/Store/gateways/Stripe/vendor/autoload.php');
                \Stripe\Stripe::setApiKey($secret_key);

                $hook_key = StoreConfig::get('stripe/hook_key');
                if (!$hook_key) {
                    $stripe = new \Stripe\StripeClient($secret_key);
                    $webhook = $stripe->webhookEndpoints->create([
                        'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/store/listener', 'gateway=Stripe'),
                        'enabled_events' => ['payment_intent.succeeded', 'charge.refunded', 'charge.failed', 'charge.dispute.closed']
                    ]);

                    StoreConfig::set([
                        'stripe/hook_key' => $webhook->secret
                    ]);
                }
            } catch (Exception $e) {
                ErrorHandler::logCustomError($e->getMessage());
                $this->addError('Stripe integration incorrectly configured!');
            }
        } else {
            $this->addError('Administration has not completed the configuration of this gateway!');
        }
    }
}

$gateway = new Stripe_Gateway();