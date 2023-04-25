<?php
class PaymentPendingEvent extends AbstractEvent implements HasWebhookParams, DiscordDispatchable {
    public Payment $payment;
    public Order $order;
    public Customer $customer;
    public Customer $recipient;

    public function __construct(Payment $payment, Order $order, Customer $customer, Customer $recipient) {
        $this->payment = $payment;
        $this->order = $order;
        $this->customer = $customer;
        $this->recipient = $recipient;
    }

    public static function name(): string {
        return 'paymentPending';
    }

    public static function description(): string {
        return (new Language(ROOT_PATH . '/modules/Store/language'))->get('admin', 'payment_pending');
    }

    public function webhookParams(): array {
        return [
            'id' => $this->payment->data()->id,
            'order_id' => $this->payment->data()->order_id,
            'gateway_id' => $this->payment->data()->gateway_id,
            'transaction' => $this->payment->data()->transaction,
            'amount' => Store::fromCents($this->payment->data()->amount_cents ?? 0),
            'amount_cents' => $this->payment->data()->amount_cents ?? 0,
            'currency' => $this->payment->data()->currency,
            'fee' => Store::fromCents($this->payment->data()->fee_cents ?? 0),
            'fee_cents' => $this->payment->data()->fee_cents ?? 0,
            'status_id' => $this->payment->data()->status_id,
            'created' => $this->payment->data()->created,
            'last_updated' => $this->payment->data()->last_updated,
            'customer' => [
                'customer_id' => $this->customer->data()->id,
                'user_id' => $this->customer->exists() ? $this->customer->data()->user_id ?? 0 : 0,
                'username' => $this->customer->getUsername(),
                'identifier' => $this->customer->getIdentifier(),
            ],
            'recipient' => [
                'customer_id' => $this->recipient->data()->id,
                'user_id' => $this->recipient->exists() ? $this->recipient->data()->user_id ?? 0 : 0,
                'username' => $this->recipient->getUsername(),
                'identifier' => $this->recipient->getIdentifier(),
            ]
        ];
    }

    public function toDiscordWebhook(): DiscordWebhookBuilder {
        $store_language = new Language(ROOT_PATH . '/modules/Store/language', LANGUAGE);
        $username = $this->order->recipient()->getUsername();

        return DiscordWebhookBuilder::make()
            ->setUsername($username)
            ->addEmbed(function (DiscordEmbed $embed) use ($store_language, $username) {
                return $embed
                    ->setDescription($store_language->get('general', 'pending_payment_text', ['user' => $username]));
            });
    }
}