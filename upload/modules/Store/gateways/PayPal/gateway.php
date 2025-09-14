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
use Customer;
use GatewayBase;
use Subscription;
use TemplateBase;

class PayPal_Gateway extends GatewayBase implements SupportSubscriptions {
    use ApiClient;
    use OrderProcessor;
    use SubscriptionManager;
    use PlanManager;
    use WebhookManager;

    public function __construct() {
        $name = 'PayPal';
        $author = '<a href="https://partydragen.com" target="_blank" rel="nofollow noopener">Partydragen</a> and my <a href="https://partydragen.com/supporters/" target="_blank">Sponsors</a>';
        $gateway_version = '1.9.0';
        $store_version = '1.9.0';
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
}

$gateway = new PayPal_Gateway();