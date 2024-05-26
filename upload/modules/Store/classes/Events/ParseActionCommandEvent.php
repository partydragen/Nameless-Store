<?php

class ParseActionCommandEvent extends AbstractEvent {

    public string $command;
    public Action $action;
    public Order $order;
    public Item $item;
    public Payment $payment;
    public array $placeholders;

    public function __construct(string $command, Action $action, Order $order, Item $item, Payment $payment, array $placeholders) {
        $this->command = $command;
        $this->action = $action;
        $this->order = $order;
        $this->item = $item;
        $this->payment = $payment;
        $this->placeholders = $placeholders;
    }

    public static function name(): string {
        return 'parseActionCommand';
    }

    public static function description(): string {
        return 'Parse Action Command';
    }

    public static function internal(): bool {
        return true;
    }

    public static function return(): bool {
        return true;
    }

    public function getPlaceholder(string $placeholder) {
        if (array_key_exists($placeholder, $this->placeholders)) {
            return $this->placeholders[$placeholder];
        }

        return null;
    }
}