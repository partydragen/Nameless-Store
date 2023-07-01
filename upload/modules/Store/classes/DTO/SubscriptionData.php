<?php

class SubscriptionData {

    public int $id;
    public int $order_id;
    public int $gateway_id;
    public int $customer_id;
    public int $status_id;
    public int $amount_cents;
    public string $currency;
    public string $frequency;
    public int $frequency_interval;
    public int $created;
    public int $updated;

    public function __construct(object $row) {
        $this->id = $row->id;
        $this->order_id = $row->order_id;
        $this->gateway_id = $row->gateway_id;
        $this->customer_id = $row->customer_id;
        $this->status_id = $row->status_id;
        $this->amount_cents = $row->amount_cents;
        $this->currency = $row->currency;
        $this->frequency = $row->frequency;
        $this->frequency_interval = $row->frequency_interval;
        $this->created = $row->created;
        $this->updated = $row->updated;
    }

}