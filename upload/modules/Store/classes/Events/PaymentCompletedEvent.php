<?php
class PaymentCompletedEvent extends AbstractEvent implements HasWebhookParams, DiscordDispatchable {
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
        return 'paymentCompleted';
    }

    public static function description(): string {
        return (new Language(ROOT_PATH . '/modules/Store/language'))->get('admin', 'payment_completed');
    }

    public function webhookParams(): array {
        $products_list = [];
        foreach ($this->order->getProducts() as $product) {
            $products_list[] = [
                'id' => $product->data()->id,
                'name' => $product->data()->name,
                'fields' => $this->order->getProductFields($product->data()->id)
            ];
        }

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
            ],
            'products' => $products_list
        ];
    }

    public function toDiscordWebhook(): DiscordWebhookBuilder {
        $customer_username = $this->order->customer()->getUsername();
        $recipient_username = $this->order->recipient()->getUsername();

        $placeholders['{username}'] = $recipient_username;
        $placeholders['{customerUsername}'] = $customer_username;
        $placeholders['{recipientUsername}'] = $recipient_username;
        $placeholders['{products}'] = $this->order->getDescription();
        $placeholders['{amount}'] = Store::fromCents($this->payment->data()->amount_cents);
        $placeholders['{currency}'] = $this->payment->data()->currency;
        $placeholders['{gateway}'] = $this->payment->getGateway() != null ? $this->payment->getGateway()->getName() : 'Unknown';

        $discord_message = Settings::get('discord_message', 'New payment from {username} who bought the following products {products}', 'Store');
        $discord_message = str_replace(array_keys($placeholders), array_values($placeholders), $discord_message);

        return DiscordWebhookBuilder::make()
            ->setUsername($recipient_username)
            ->addEmbed(function (DiscordEmbed $embed) use ($discord_message) {
                return $embed
                    ->setDescription($discord_message);
            });
    }
}