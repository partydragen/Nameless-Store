<?php

interface SupportSubscriptions {
    public function createSubscription(): void;
    public function cancelSubscription(Subscription $subscription): bool;
    public function chargePayment(Subscription $subscription): bool;
}