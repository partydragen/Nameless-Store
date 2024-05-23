<?php

class ParseActionCommandEvent extends AbstractEvent {

    public string $command;
    public Action $action;
    public Order $order;
    public Item $item;
    public Payment $payment;

    public function __construct(string $command, Action $action, Order $order, Item $item, Payment $payment) {
        $this->command = $command;
        $this->action = $action;
        $this->order = $order;
        $this->item = $item;
        $this->payment = $payment;
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
}