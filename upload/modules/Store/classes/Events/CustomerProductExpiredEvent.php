<?php
class CustomerProductExpiredEvent extends AbstractEvent implements HasWebhookParams, DiscordDispatchable {
    public Payment $payment;
    public Order $order;
    public Customer $customer;
    public Customer $recipient;
    public Product $product;

    public function __construct(Payment $payment, Order $order, Customer $customer, Customer $recipient, Product $product) {
        $this->payment = $payment;
        $this->order = $order;
        $this->customer = $customer;
        $this->recipient = $recipient;
        $this->product = $product;
    }

    public static function name(): string {
        return 'customerProductExpired';
    }

    public static function description(): string {
        return (new Language(ROOT_PATH . '/modules/Store/language'))->get('admin', 'customer_product_expired');
    }

    public function webhookParams(): array {
        return [
            'id' => $this->payment->data()->id,
            'order_id' => $this->payment->data()->order_id,
            'payment_id' => $this->payment->data()->id,
            'product' => [
                'id' => $this->product->data()->id,
                'name' => $this->product->data()->name
            ],
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
        $username = $this->order->recipient()->getUsername();

        return DiscordWebhookBuilder::make()
            ->setUsername($username)
            ->addEmbed(function (DiscordEmbed $embed) use ($username) {
                return $embed
                    ->setDescription($username . ' product ' . $this->product->data()->name . ' just expired!');
            });
    }
}