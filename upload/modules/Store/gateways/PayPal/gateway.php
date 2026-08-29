<?php
/**
 * PayPal_Business_Gateway class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
namespace Store\Gateways\PayPal;

use SupportSubscriptions;
use SupportRefunds;
use Customer;
use GatewayBase;
use GatewayRefundResult;
use Payment;
use Store;
use Subscription;
use TemplateBase;

class PayPal_Gateway extends GatewayBase implements SupportSubscriptions, SupportRefunds {
    use ApiClient;
    use OrderProcessor;
    use SubscriptionManager;
    use PlanManager;
    use WebhookManager;

    public function __construct() {
        $name = 'PayPal';
        $author = '<a href="https://partydragen.com" target="_blank" rel="nofollow noopener">Partydragen</a> and my <a href="https://partydragen.com/supporters/" target="_blank">Sponsors</a>';
        $gateway_version = '1.9.3';
        $store_version = '1.9.3';
        $settings = ROOT_PATH . '/modules/Store/gateways/PayPal/gateway_settings/settings.php';

        parent::__construct($name, $author, $gateway_version, $store_version, $settings);
    }

    public function onCheckoutPageLoad(TemplateBase $template, Customer $customer): void {
        // Not necessary
    }

    public function chargePayment(Subscription $subscription): bool {
        // Not necessary for this gateway
        return false;
    }

    public function refundPayment(Payment $payment, int $amount_cents, string $reason): ?GatewayRefundResult {
        $access_token = $this->getAccessToken();
        if (!$access_token || $this->getErrors()) {
            return null;
        }

        if (!$payment->data()->transaction) {
            $this->logError('Missing PayPal capture ID for payment ' . $payment->data()->id);
            $this->addError(Store::getLanguage()->get('admin', 'payment_refund_failed'));
            return null;
        }

        $response = $this->makeApiRequest(
            '/v2/payments/captures/' . rawurlencode($payment->data()->transaction) . '/refund',
            'POST',
            $access_token,
            [
                'amount' => [
                    'value' => Store::fromCents($amount_cents),
                    'currency_code' => strtoupper($payment->data()->currency)
                ],
                'note_to_payer' => mb_substr($reason, 0, 255)
            ]
        );

        if (
            !in_array($response['http_code'] ?? 0, [200, 201], true)
            || empty($response['id'])
            || ($response['status'] ?? '') === 'FAILED'
        ) {
            $this->logError('PayPal rejected refund for payment ' . $payment->data()->id . ': ' . json_encode($response));
            $this->addError(Store::getLanguage()->get('admin', 'payment_refund_failed'));
            return null;
        }

        return new GatewayRefundResult($response['id'], ($response['status'] ?? '') === 'COMPLETED');
    }
}

$gateway = new PayPal_Gateway();
