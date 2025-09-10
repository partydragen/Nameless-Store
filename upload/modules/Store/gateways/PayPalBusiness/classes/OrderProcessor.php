<?php
/**
 * OrderProcessor
 * Handles single payment order processing and return
 *
 * @package Modules\Store
 */
namespace Store\Gateways\PayPalBusiness;

use Order;
use Payment;
use Redirect;
use Store;
use Subscription;

trait OrderProcessor {

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
                    'brand_name' => SITE_NAME,
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING',
                    'return_url' => $this->getReturnURL(),
                    'cancel_url' => $this->getCancelURL()
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
            $this->processSubscription($order, $access_token);
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
                    $order_id = $_SESSION['shopping_cart']['order_id'] ?? $response['custom_id'];
                    if ($order_id == null || !is_numeric($order_id)) {
                        $this->logError('Invalid order id');
                        $this->addError('Invalid order id');
                        return false;
                    }

                    $subscription = new Subscription($subscription_id, 'agreement_id');
                    if (!$subscription->exists()) {
                        $order = new Order($order_id);
                        $plan = $this->makeApiRequest("/v1/billing/plans/{$response['plan_id']}", 'GET', $access_token);
                        $billing_cycles = $plan['billing_cycles'][0];

                        $subscription->create([
                            'order_id' => $order->data()->id,
                            'gateway_id' => $this->getId(),
                            'customer_id' => $order->customer()->data()->id,
                            'agreement_id' => $subscription_id,
                            'status_id' => Subscription::ACTIVE,
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
                    } else {
                        $subscription->update([
                            'status_id' => Subscription::ACTIVE,
                            'next_billing_date' => isset($response['billing_info']['next_billing_time']) ? date('U', strtotime($response['billing_info']['next_billing_time'])) : 0,
                        ]);
                    }

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
}