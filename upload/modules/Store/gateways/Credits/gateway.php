<?php
/**
 * Credits_Gateway class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
class Credits_Gateway extends GatewayBase implements SupportSubscriptions {

    public function __construct() {
        $name = 'Store Credits';
        $author = '<a href="https://partydragen.com" target="_blank" rel="nofollow noopener">Partydragen</a> and my <a href="https://partydragen.com/supporters/" target="_blank">Sponsors</a>';
        $gateway_version = '1.8.2';
        $store_version = '1.8.2';
        $settings = ROOT_PATH . '/modules/Store/gateways/Credits/gateway_settings/settings.php';

        parent::__construct($name, $author, $gateway_version, $store_version, $settings);
    }

    public function onCheckoutPageLoad(TemplateBase $template, Customer $customer): void {
        if (!$customer->exists()) {
            $this->setEnabled(false);
            return;
        }

        $this->setDisplayname(
            Store::getLanguage()->get('general', 'pay_with_credits', [
                'currency_symbol' => Store::getCurrencySymbol(),
                'currency' => Store::getCurrency(),
                'credits' => $customer->getCredits()
            ])
        );
    }

    public function processOrder(Order $order): void {
        $customer = $order->customer();
        $amount_to_pay = $order->getAmount()->getTotalCents();

        if ($customer->exists() && $customer->data()->cents >= $amount_to_pay) {
            if (!$order->isSubscriptionMode()) {
                // Single payment
                $transaction_id = $customer->removeCents($amount_to_pay, 'Order_payment');

                $payment = new Payment();
                $payment->handlePaymentEvent(Payment::COMPLETED, [
                    'order_id' => $order->data()->id,
                    'gateway_id' => $this->getId(),
                    'amount_cents' => $amount_to_pay,
                    'transaction' => $transaction_id,
                    'currency' => Store::getCurrency()
                ]);
            } else {
                // Payment subscription
                $item = $order->items()->getItems()[0];
                $duration_json = json_decode($item->getProduct()->data()->durability, true) ?? [];

                $subscription = new Subscription();
                $subscription->create([
                    'order_id' => $order->data()->id,
                    'gateway_id' => $this->getId(),
                    'customer_id' => $order->customer()->data()->id,
                    'agreement_id' => $order->data()->id,
                    'status_id' => Subscription::ACTIVE,
                    'amount_cents' => $amount_to_pay,
                    'currency' => Store::getCurrency(),
                    'frequency' => strtoupper($duration_json['period'] ?? 'month',),
                    'frequency_interval' => $duration_json['interval'] ?? 1,
                    'verified' => 1,
                    'payer_id' => $order->customer()->data()->id,
                    'next_billing_date' => date('U'),
                    'created' => date('U'),
                    'updated' => date('U')
                ]);

                $subscription->chargePayment();
            }

            ShoppingCart::getInstance()->clear();
            Redirect::to(URL::build(Store::getStorePath() . '/checkout/', 'do=complete'));
        } else {
            $this->addError(Store::getLanguage()->get('general', 'not_enough_credits'));
        }
    }

    public function handleReturn(): bool {
        return false;
    }

    public function handleListener(): void {

    }

    public function createSubscription(): void {

    }

    public function cancelSubscription(Subscription $subscription): bool {
        $subscription->cancelled();

        return true;
    }

    public function syncSubscription(Subscription $subscription): bool {
        return false;
    }

    public function chargePayment(Subscription $subscription): bool {
        $customer = new Customer(null, $subscription->data()->customer_id);
        $amount_to_pay = $subscription->data()->amount_cents;

        if ($customer->exists() && $customer->data()->cents >= $amount_to_pay) {
            // Successfully renewal
            $transaction_id = $customer->removeCents($amount_to_pay, 'Order_payment');

            $payment = new Payment();
            $payment->handlePaymentEvent(Payment::COMPLETED, [
                'order_id' => $subscription->data()->order_id,
                'gateway_id' => $this->getId(),
                'subscription_id' => $subscription->data()->id,
                'amount_cents' => $amount_to_pay,
                'transaction' => $transaction_id,
                'currency' => $subscription->data()->currency
            ]);

            $subscription->update([
                'last_payment_date' => date('U'),
                'next_billing_date' => strtotime($subscription->data()->frequency_interval . ' ' . $subscription->data()->frequency),
                'failed_attempts' => 0
            ]);

            return true;
        }

        $failed_attempts = $subscription->data()->failed_attempts + 1;
        $subscription->update([
            'failed_attempts' => $failed_attempts
        ]);

        // Cancel subscription after 3 failed charge attempts
        if ($failed_attempts >= 3) {
            $subscription->cancelled();
        }

        return false;
    }
}

$gateway = new Credits_Gateway();