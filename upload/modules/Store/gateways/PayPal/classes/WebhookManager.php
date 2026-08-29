<?php
/**
 * WebhookManager
 * Handles PayPal webhook events and managing webhooks
 *
 * @package Modules\Store
 */
namespace Store\Gateways\PayPal;

use DB;
use EventHandler;
use Order;
use Payment;
use Store;
use StoreConfig;
use Subscription;
use SubscriptionCreatedEvent;

trait WebhookManager {

    public function createWebhook(string $access_token): bool {
        $key = md5(uniqid());
        $webhook_data = [
            'url' => $this->getListenerURL("key=$key"),
            'event_types' => [
                ['name' => 'CHECKOUT.ORDER.APPROVED'],
                ['name' => 'PAYMENT.CAPTURE.COMPLETED'],
                ['name' => 'PAYMENT.CAPTURE.REFUNDED'],
                ['name' => 'PAYMENT.REFUND.PENDING'],
                ['name' => 'PAYMENT.REFUND.FAILED'],
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
                'paypal.key' => $key,
                'paypal.hook_key' => $webhook_response['id']
            ]);

            return true;
        }

        $this->logError('Failed to create webhook: ' . json_encode($webhook_response));
        $this->addError('PayPal integration incorrectly configured!');
        return false;
    }

    public function updateWebhook(): bool {
        $hook_key = StoreConfig::get('paypal.hook_key');
        if (!$hook_key) {
            $this->logError('No webhook ID found to update');
            return false;
        }

        $access_token = $this->getAccessToken();
        if ($this->getErrors()) {
            $this->logError('Failed to get access token for webhook update');
            return false;
        }

        $key = StoreConfig::get('paypal.key') ?: md5(uniqid());
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
                    ['name' => 'PAYMENT.REFUND.PENDING'],
                    ['name' => 'PAYMENT.REFUND.FAILED'],
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
            if ($key !== StoreConfig::get('paypal.key')) {
                StoreConfig::set('paypal.key', $key);
            }

            return true;
        }

        $this->logError('Failed to update webhook: ' . json_encode($response));
        return false;
    }

    public function handleListener(): void {
        header('Content-Type: application/json; charset=UTF-8');

        // Validate webhook key
        if (!isset($_GET['key']) || $_GET['key'] !== StoreConfig::get('paypal.key')) {
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
                // Capture from the webhook as well as the browser return so an
                // approved order still completes if the customer closes PayPal.
                $paypal_order_id = $response['resource']['id'] ?? null;
                if ($paypal_order_id) {
                    $capture_response = $this->makeApiRequest(
                        "/v2/checkout/orders/{$paypal_order_id}/capture",
                        'POST',
                        $access_token,
                        []
                    );

                    if (
                        ($capture_response['status'] ?? '') === 'COMPLETED'
                        && !$this->completeCapturedOrder($capture_response)
                    ) {
                        http_response_code(500);
                    }
                }
                break;

            case 'PAYMENT.CAPTURE.REFUNDED':
                $resource = $response['resource'];
                $refund_query = DB::getInstance()->query(
                    'SELECT refunds.payment_id FROM nl2_store_payment_refunds refunds '
                    . 'INNER JOIN nl2_store_payments payments ON payments.id = refunds.payment_id '
                    . 'WHERE refunds.gateway_refund_id = ? AND payments.gateway_id = ?',
                    [$resource['id'], $this->getId()]
                );
                $payment = $refund_query->count()
                    ? new Payment($refund_query->first()->payment_id)
                    : new Payment();

                if (!$payment->exists()) {
                    foreach ($resource['links'] ?? [] as $link) {
                        if (($link['rel'] ?? '') === 'up' && preg_match('~/captures/([^/?]+)~', $link['href'], $match)) {
                            $payment = new Payment($match[1], 'transaction');
                            break;
                        }
                    }
                }

                if ($payment->exists()) {
                    try {
                        $payment->recordRefund(
                            new \GatewayRefundResult($resource['id'], true),
                            Store::toCents($resource['amount']['value']),
                            $resource['note_to_payer'] ?? ''
                        );
                    } catch (\Throwable $e) {
                        $this->logError('Unable to record completed PayPal refund '
                            . $resource['id'] . ': ' . $e->getMessage());
                        http_response_code(500);
                    }
                } else {
                    $this->logError('Could not handle refund event for invalid payment ' . $resource['id']);
                }
                break;

            case 'PAYMENT.REFUND.FAILED':
                $refund = DB::getInstance()->query(
                    'SELECT refunds.payment_id FROM nl2_store_payment_refunds refunds INNER JOIN nl2_store_payments payments ON payments.id = refunds.payment_id WHERE refunds.gateway_refund_id = ? AND payments.gateway_id = ?',
                    [$response['resource']['id'], $this->getId()]
                );
                if ($refund->count()) {
                    $payment = new Payment($refund->first()->payment_id);
                    $payment->failRefund(
                        $response['resource']['id'],
                        $response['resource']['status_details']['reason'] ?? 'PayPal refund failed'
                    );
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
                        http_response_code(400);
                        $this->logError('Could not handle payment for invalid subscription ' . $response['resource']['billing_agreement_id']);
                    }
                }
                break;

            case 'PAYMENT.CAPTURE.COMPLETED':
                $resource = $response['resource'];
                $provider_order_id = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
                $store_order_id = $resource['invoice_id'] ?? $resource['custom_id'] ?? null;

                $payment = $provider_order_id
                    ? new Payment($provider_order_id, 'payment_id')
                    : new Payment();
                if (!$payment->exists() && is_numeric($store_order_id)) {
                    $payment = Payment::findPendingForOrder((int) $store_order_id, $this->getId());
                }

                if ($payment->exists()) {
                    $payment_data = [
                        'transaction' => $resource['id'],
                        'amount_cents' => Store::toCents($resource['amount']['value']),
                        'currency' => $resource['amount']['currency_code'],
                        'fee_cents' => isset($resource['seller_receivable_breakdown']['paypal_fee']['value'])
                            ? Store::toCents($resource['seller_receivable_breakdown']['paypal_fee']['value'])
                            : null
                    ];
                    if ($provider_order_id) {
                        $payment_data['payment_id'] = $provider_order_id;
                    }
                    $payment->handlePaymentEvent(Payment::COMPLETED, $payment_data);
                } else {
                    http_response_code(400);
                    $this->logError('Could not handle completed capture for unknown PayPal order');
                }
                break;

            default:
                $this->logError('Unknown event type ' . $response['event_type']);
                break;
        }
    }
}
