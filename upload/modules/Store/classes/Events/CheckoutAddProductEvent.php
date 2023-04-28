<?php
class CheckoutAddProductEvent extends AbstractEvent {

    use Cancellable;

    public User $user;
    public Product $product;
    public Customer $customer;
    public Customer $recipient;
    public array $fields;

    public function __construct(User $user, Product $product, Customer $customer, Customer $recipient, array $fields) {
        $this->user = $user;
        $this->product = $product;
        $this->customer = $customer;
        $this->recipient = $recipient;
        $this->fields = $fields;
    }

    public static function name(): string {
        return 'storeCheckoutAddProduct';
    }

    public static function description(): string {
        return 'storeCheckoutAddProduct';
    }

    public static function internal(): bool {
        return true;
    }

    public static function return(): bool {
        return true;
    }
}