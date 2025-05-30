<?php
/**
 * Minecraft_Community_Gateway class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
class Minecraft_Community_Gateway extends GatewayBase {

    public function __construct() {
        $name = 'Minecraft-Community';
        $author = '<a href="https://mccommunity.net/" target="_blank" rel="nofollow noopener">Minecraft Community</a>';
        $gateway_version = '1.0.0';
        $store_version = '1.8.3';
        $settings = ROOT_PATH . '/modules/Store/gateways/Minecraft-Community/gateway_settings/settings.php';

        parent::__construct($name, $author, $gateway_version, $store_version, $settings);
    }

    public function onCheckoutPageLoad(TemplateBase $template, Customer $customer): void {
        // Not necessary
    }

    public function processOrder(Order $order): void {
        $this->getApiContext();
        if (count($this->getErrors())) {
            return;
        }

        $currency = $order->getAmount()->getCurrency();
        $successRedirect = rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=Minecraft-Community&do=success');
        $cancelRedirect = rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=Minecraft-Community&do=cancel');

        $result = HttpClient::post('https://mccommunity.net/index.php?route=/api/v2/oauth2/store/order/create', json_encode([
            'client_id' => Settings::get('client_id', null, 'Minecraft Community'),
            'client_secret' => Settings::get('client_secret', null, 'Minecraft Community'),
            'order_id' => $order->data()->id,
            'currency' => $currency,
            'price' => Store::fromCents($order->getAmount()->getTotalCents()),
            'description' => $order->getDescription(),
            'success_url' => $successRedirect,
            'cancel_url' => $cancelRedirect
        ]));

        if (!$result->hasError()) {
            $result = $result->json();

            Redirect::to($result->url);
        } else {
            ErrorHandler::logCustomError($result->getError());
            $this->addError('Minecraft Community gateway incorrectly configured!');
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

        $bodyReceived = file_get_contents('php://input');
        $response = json_decode($bodyReceived);

        // Log webhook response
        $this->logWebhookResponse($bodyReceived, $response->type);

        if (!isset($response->client_id, $response->client_secret)) {
            return;
        }

        if ($response->client_id != Settings::get('client_id', null, 'Minecraft Community') || $response->client_secret != Settings::get('client_secret', null, 'Minecraft Community')) {
            return;
        }

        switch($response->event) {
            case 'paymentCompleted':
                $payment = new Payment($response->id, 'payment_id');

                // Get order from field
                $order_id = null;
                foreach ($response->products as $product) {
                    $order_id = $product->fields->order_id->value;
                }

                $payment->handlePaymentEvent(Payment::COMPLETED, [
                    'order_id' => $order_id,
                    'gateway_id' => $this->getId(),
                    'payment_id' => $response->id,
                    'transaction' => $response->transaction,
                    'amount_cents' => $response->amount_cents,
                    'currency' => $response->currency
                ]);

                break;

            case 'paymentRefunded':
                $payment = new Payment($response->id, 'payment_id');
                if ($payment->exists()) {
                    $payment->handlePaymentEvent(Payment::REFUNDED);
                }
                break;

            case 'paymentDenied':
                $payment = new Payment($response->id, 'payment_id');
                if ($payment->exists()) {
                    $payment->handlePaymentEvent(Payment::DENIED);
                }
                break;

            case 'paymentReversed':
                $payment = new Payment($response->id, 'payment_id');
                if ($payment->exists()) {
                    $payment->handlePaymentEvent(Payment::REVERSED);
                }
                break;
        }
    }

    private function getApiContext() {
        $client_id = Settings::get('client_id', null, 'Minecraft Community');
        $client_secret = Settings::get('client_secret', null, 'Minecraft Community');

        if ($client_id && $client_secret) {
            return;
        } else {
            $this->addError('Administration has not completed the configuration of this gateway!');
        }
    }
}

$gateway = new Minecraft_Community_Gateway();
