<?php
/**
 * SubscriptionProcessor
 * Handles subscription creation, cancellation, and synchronization
 *
 * @package Modules\Store
 */
namespace Store\Gateways\PayPal;

use Order;
use Redirect;
use Store;
use Subscription;

trait SubscriptionManager {
    public function processSubscription(Order $order, string $access_token): void {
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

        $duration_json = json_decode($product->data()->durability, true) ?? [];
        $subscription_data = [
            'custom_id' => $order->data()->id,
            'plan_id' => $plan_id,
            'plan' => [
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
                                'value' => Store::fromCents($order->getAmount()->getTotalCents()),
                                'currency_code' => $order->getAmount()->getCurrency()
                            ]
                        ]
                    ]
                ],
            ],
            'application_context' => [
                'brand_name' => SITE_NAME,
                'user_action' => 'SUBSCRIBE_NOW',
                'shipping_preference' => 'NO_SHIPPING',
                'return_url' => $this->getReturnURL(),
                'cancel_url' => $this->getCancelURL()
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