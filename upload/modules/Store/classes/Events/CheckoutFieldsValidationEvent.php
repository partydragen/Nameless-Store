<?php
class CheckoutFieldsValidationEvent extends AbstractEvent {

    use Cancellable;

    public User $user;
    public Product $product;
    public Customer $customer;
    public Customer $recipient;
    public array $fields;
    public Validate $validation;

    public function __construct(User $user, Product $product, Customer $customer, Customer $recipient, array $fields, Validate $validation) {
        $this->user = $user;
        $this->product = $product;
        $this->customer = $customer;
        $this->recipient = $recipient;
        $this->fields = $fields;
        $this->validation = $validation;
    }

    public static function name(): string {
        return 'storeCheckoutFieldsValidation';
    }

    public static function description(): string {
        return 'storeCheckoutFieldsValidation';
    }

    public static function internal(): bool {
        return true;
    }

    public static function return(): bool {
        return true;
    }
}