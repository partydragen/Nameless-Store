<?php
class SubscriptionSuspendedEvent extends AbstractEvent implements HasWebhookParams, DiscordDispatchable {
    public Subscription $subscription;

    public function __construct(Subscription $subscription) {
        $this->subscription = $subscription;
    }

    public static function name(): string {
        return 'subscriptionSuspendedEvent';
    }

    public static function description(): string {
        return 'Store » ' . (new Language(ROOT_PATH . '/modules/Store/language'))->get('admin', 'subscription_suspended');
    }

    public function webhookParams(): array {
        $customer = new Customer(null, $this->subscription->data()->customer_id);

        return [
            'id' => $this->subscription->data()->id,
            'gateway_id' => $this->subscription->data()->gateway_id,
            'status_id' => $this->subscription->data()->status_id,
            'amount_cents' => $this->subscription->data()->amount_cents ?? 0,
            'currency' => $this->subscription->data()->currency,
            'frequency' => $this->subscription->data()->frequency,
            'frequency_interval' => $this->subscription->data()->frequency_interval,
            'customer' => [
                'customer_id' => $customer->data()->id,
                'user_id' => $customer->exists() ? $customer->data()->user_id ?? 0 : 0,
                'username' => $customer->getUsername(),
                'identifier' => $customer->getIdentifier(),
            ],
            'order' => [
                'id' => $this->subscription->data()->order_id,
            ]
        ];
    }

    public function toDiscordWebhook(): DiscordWebhookBuilder {
        $store_language = new Language(ROOT_PATH . '/modules/Store/language', LANGUAGE);
        $customer = new Customer(null, $this->subscription->data()->customer_id);

        return DiscordWebhookBuilder::make()
            ->setUsername($customer->getUsername())
            ->addEmbed(function (DiscordEmbed $embed) use ($store_language, $customer) {
                return $embed
                    ->setDescription($store_language->get('general', 'subscription_suspended', [
                        'username' => $customer->getUsername(),
                        'gateway' => $this->subscription->getGateway() != null ? $this->subscription->getGateway()->getName() : 'Unknown'
                    ]));
            });
    }
}