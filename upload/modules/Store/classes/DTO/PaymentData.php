<?php

class PaymentData {

    public int $id;
    public int $order_id;
    public int $gateway_id;
    public ?string $payment_id;
    public ?string $agreement_id;
    public ?string $transaction;
    public ?int $amount_cents;
    public ?string $currency;
    public ?int $fee_cents;
    public int $status_id;
    public int $created;
    public int $last_updated;

    public function __construct(object $row) {
        $this->id = $row->id;
        $this->order_id = $row->order_id;
        $this->gateway_id = $row->gateway_id;
        $this->payment_id = $row->payment_id;
        $this->agreement_id = $row->agreement_id;
        $this->transaction = $row->transaction;
        $this->amount_cents = $row->amount_cents;
        $this->currency = $row->currency;
        $this->fee_cents = $row->fee_cents;
        $this->status_id = $row->status_id;
        $this->created = $row->created;
        $this->last_updated = $row->last_updated;
    }

}