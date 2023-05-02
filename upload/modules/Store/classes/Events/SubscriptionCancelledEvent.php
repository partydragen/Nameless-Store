<?php
class SubscriptionCancelledEvent extends AbstractEvent implements HasWebhookParams, DiscordDispatchable {
    public Subscription $subscription;

    public function __construct(Subscription $subscription) {
        $this->subscription = $subscription;
    }

    public static function name(): string {
        return 'subscriptionCancelledEvent';
    }

    public static function description(): string {
        return (new Language(ROOT_PATH . '/modules/Store/language'))->get('admin', 'subscription_cancelled');
    }

    public function webhookParams(): array {
        return [
            'id' => $this->subscription->data()->id
        ];
    }

    public function toDiscordWebhook(): DiscordWebhookBuilder {
        $store_language = new Language(ROOT_PATH . '/modules/Store/language', LANGUAGE);

        return DiscordWebhookBuilder::make()
            ->setUsername('None')
            ->addEmbed(function (DiscordEmbed $embed) use ($store_language) {
                return $embed
                    ->setDescription($store_language->get('general', 'subscription_cancelled'));
            });
    }
}