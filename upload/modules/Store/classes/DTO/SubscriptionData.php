<?php

class SubscriptionData {

    public int $id;
    public int $order_id;
    public int $gateway_id;
    public int $status_id;
    public int $created;
    public int $last_updated;

    public function __construct(object $row) {
        $this->id = $row->id;
        $this->order_id = $row->order_id;
        $this->gateway_id = $row->gateway_id;
        $this->status_id = $row->status_id;
        $this->created = $row->created;
        $this->last_updated = $row->last_updated;
    }

}