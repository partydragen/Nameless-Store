<?php
/**
 * WebhookManager
 * Handles PayPal webhook events and managing webhooks
 *
 * @package Modules\Store
 */
namespace Store\Gateways\PayPalBusiness;

use DB;
use EventHandler;
use Order;
use Payment;
use Store;
use StoreConfig;
use Subscription;
use SubscriptionCreatedEvent;

trait WebhookManager {

    public function createWebhook(): bool {
        $access_token = $this->getAccessToken();
        if ($this->getErrors()) {
            $this->logError('Failed to get access token for webhook update');
            return false;
        }

        $key = md5(uniqid());
        $webhook_data = [
            'url' => $this->getListenerURL("key=$key"),
            'event_types' => [
                ['name' => 'CHECKOUT.ORDER.APPROVED'],
                ['name' => 'PAYMENT.CAPTURE.COMPLETED'],
                ['name' => 'PAYMENT.CAPTURE.REFUNDED'],
                ['name' => 'PAYMENT.CAPTURE.REVERSED'],
                ['name' => 'PAYMENT.CAPTURE.DENIED'],
                ['name' => 'BILLING.SUBSCRIPTION.CREATED'],
                ['name' => 'BILLING.SUBSCRIPTION.ACTIVATED'],
                ['name' => 'BILLING.SUBSCRIPTION.CANCELLED'],
                ['name' => 'BILLING.SUBSCRIPTION.SUSPENDED'],
                ['name' => 'BILLING.SUBSCRIPTION.RE-ACTIVATED'],
                ['name' => 'PAYMENT.SALE.COMPLETED']
            ]
        ];

        $webhook_response = $this->makeApiRequest('/v1/notifications/webhooks', 'POST', $access_token, $webhook_data);
        if (isset($webhook_response['id'])) {
            StoreConfig::setMultiple([
                'paypal_business.key' => $key,
                'paypal_business.hook_key' => $webhook_response['id']
            ]);

            return true;
        }

        $this->logError('Failed to create webhook: ' . json_encode($webhook_response));
        $this->addError('PayPal integration incorrectly configured!');
        return false;
    }

    public function updateWebhook(): bool {
        $hook_key = StoreConfig::get('paypal_business.hook_key');
        if (!$hook_key) {
            $this->logError('No webhook ID found to update');
            return false;
        }

        $access_token = $this->getAccessToken();
        if ($this->getErrors()) {
            $this->logError('Failed to get access token for webhook update');
            return false;
        }

        $key = StoreConfig::get('paypal_business.key') ?: md5(uniqid());
        $webhook_data = [
            [
                'op' => 'replace',
                'path' => '/url',
                'value' => $this->getListenerURL("key=$key"),
            ],
            [
                'op' => 'replace',
                'path' => '/event_types',
                'value' => [
                    ['name' => 'CHECKOUT.ORDER.APPROVED'],
                    ['name' => 'PAYMENT.CAPTURE.COMPLETED'],
                    ['name' => 'PAYMENT.CAPTURE.REFUNDED'],
                    ['name' => 'PAYMENT.CAPTURE.REVERSED'],
                    ['name' => 'PAYMENT.CAPTURE.DENIED'],
                    ['name' => 'BILLING.SUBSCRIPTION.CREATED'],
                    ['name' => 'BILLING.SUBSCRIPTION.ACTIVATED'],
                    ['name' => 'BILLING.SUBSCRIPTION.CANCELLED'],
                    ['name' => 'BILLING.SUBSCRIPTION.SUSPENDED'],
                    ['name' => 'BILLING.SUBSCRIPTION.RE-ACTIVATED'],
                    ['name' => 'PAYMENT.SALE.COMPLETED']
                ]
            ]
        ];

        $response = $this->makeApiRequest("/v1/notifications/webhooks/{$hook_key}", 'PATCH', $access_token, $webhook_data);
        if (isset($response['id']) && $response['id'] === $hook_key) {
            // Update key if it changed
            if ($key !== StoreConfig::get('paypal_business.key')) {
                StoreConfig::set('paypal_business.key', $key);
            }

            return true;
        }

        $this->logError('Failed to update webhook: ' . json_encode($response));
        return false;
    }

    public function handleListener(): void {
        header('Content-Type: application/json; charset=UTF-8');

        // Validate webhook key
        if (!isset($_GET['key']) || $_GET['key'] !== StoreConfig::get('paypal_business.key')) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid webhook key']);
            $this->logError('Missing or invalid webhook key: ' . ($_GET['key'] ?? 'not provided'));
            return;
        }

        // Fetch Access token
        $access_token = $this->getAccessToken();
        if (count($this->getErrors())) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get access token']);
            $this->logError('Failed to get access token for webhook verification');
            return;
        }

        $body_received = file_get_contents('php://input');

        $signature = new VerifyWebhookSignature();
        $signature->setRequestBody($body_received);

        $verify_response = $this->makeApiRequest('/v1/notifications/verify-webhook-signature', 'POST', $access_token, $signature->toJSON());
        if ($verify_response['verification_status'] !== 'SUCCESS') {
            http_response_code(400);
            echo json_encode(['error' => 'Webhook signature verification failed', 'details' => $verify_response]);
            $this->logError('Webhook signature verification failed: ' . json_encode($verify_response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return;
        }

        $response = json_decode($body_received, true);
        if (!isset($response['event_type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid webhook event']);
            $this->logWebhookResponse($body_received, 'unknown');
            return;
        }

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
                    } else {
                        $this->logError('Could not handle order approved for invalid payment ' . $response['resource']['id']);
                    }
                }
                break;

            case 'PAYMENT.CAPTURE.REFUNDED':
                $payment = new Payment($response['resource']['id'], 'transaction');
                if ($payment->exists()) {
                    $payment->handlePaymentEvent(Payment::REFUNDED);
                } else {
                    $this->logError('Could not handle refund event for invalid payment ' . $response['resource']['id']);
                }
                break;

            case 'PAYMENT.CAPTURE.REVERSED':
                $payment = new Payment($response['resource']['id'], 'transaction');
                if ($payment->exists()) {
                    $payment->handlePaymentEvent(Payment::REVERSED);
                } else {
                    $this->logError('Could not handle reversed event for invalid payment ' . $response['resource']['id']);
                }
                break;

            case 'PAYMENT.CAPTURE.DENIED':
                $payment = new Payment($response['resource']['id'], 'transaction');
                if ($payment->exists()) {
                    $payment->handlePaymentEvent(Payment::DENIED);
                } else {
                    $this->logError('Could not handle denied event for invalid payment ' . $response['resource']['id']);
                }
                break;

            case 'BILLING.SUBSCRIPTION.CREATED':
                $subscription = new Subscription($response['resource']['id'], 'agreement_id');
                if (!$subscription->exists()) {
                    $order = new Order($response['resource']['custom_id']);
                    if ($order->exists()) {
                        $billing_cycles = $response['resource']['plan']['billing_cycles'][0];

                        DB::getInstance()->insert('store_subscriptions', [
                            'order_id' => $order->data()->id,
                            'gateway_id' => $this->getId(),
                            'customer_id' => $order->customer()->data()->id,
                            'agreement_id' => $response['resource']['id'],
                            'status_id' => -1,
                            'amount_cents' => Store::toCents($billing_cycles['pricing_scheme']['fixed_price']['value']),
                            'currency' => $billing_cycles['pricing_scheme']['fixed_price']['currency_code'],
                            'frequency' => strtolower($billing_cycles['frequency']['interval_unit']),
                            'frequency_interval' => $billing_cycles['frequency']['interval_count'],
                            'email' => $response['resource']['subscriber']['email_address'] ?? null,
                            'verified' => 1,
                            'payer_id' => $response['resource']['subscriber']['payer_id'] ?? null,
                            'last_payment_date' => null,
                            'next_billing_date' => 0,
                            'created' => date('U'),
                            'updated' => date('U')
                        ]);
                    }
                }
                break;

            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                $subscription = new Subscription($response['resource']['id'], 'agreement_id');
                if ($subscription->exists()) {
                    $billing_cycles = $response['resource']['plan']['billing_cycles'][0];

                    $subscription->update([
                        'status_id' => Subscription::ACTIVE,
                        'amount_cents' => Store::toCents($billing_cycles['pricing_scheme']['fixed_price']['value']),
                        'currency' => $billing_cycles['pricing_scheme']['fixed_price']['currency_code'],
                        'frequency' => strtolower($billing_cycles['frequency']['interval_unit']),
                        'frequency_interval' => $billing_cycles['frequency']['interval_count'],
                        'email' => $response['resource']['subscriber']['email_address'] ?? $subscription->data()->email,
                        'payer_id' => $response['resource']['subscriber']['payer_id'] ?? $subscription->data()->payer_id,
                    ]);

                    EventHandler::executeEvent(new SubscriptionCreatedEvent($subscription));
                }
                break;

            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $subscription = new Subscription($response['resource']['id'], 'agreement_id');
                if ($subscription->exists()) {
                    $subscription->cancelled();
                } else {
                    $this->logError('Could not handle cancelled event for invalid subscription ' . $response['resource']['id']);
                }
                break;

            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                $subscription = new Subscription($response['resource']['id'], 'agreement_id');
                if ($subscription->exists()) {
                    $subscription->update([
                        'status_id' => Subscription::PAUSED,
                        'updated' => date('U')
                    ]);
                } else {
                    $this->logError('Could not handle suspended event for invalid subscription ' . $response['resource']['id']);
                }
                break;

            case 'BILLING.SUBSCRIPTION.RE-ACTIVATED':
                $subscription = new Subscription($response['resource']['id'], 'agreement_id');
                if ($subscription->exists()) {
                    $subscription->update([
                        'status_id' => Subscription::ACTIVE,
                        'updated' => date('U')
                    ]);
                } else {
                    $this->logError('Could not handle re-activated event for invalid subscription ' . $response['resource']['id']);
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

            case 'PAYMENT.CAPTURE.COMPLETED':
                // Not necessary atm
                break;

            default:
                $this->logError('Unknown event type ' . $response['event_type']);
                break;
        }
    }
}