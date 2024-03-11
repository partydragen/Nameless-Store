<?php

class HandleSubscriptionsTask extends Task {

    public function run(): string {
        $subscriptions = DB::getInstance()->query('SELECT * FROM nl2_store_subscriptions WHERE next_billing_date < ? AND expired = 0', [date('U')]);
        foreach ($subscriptions->results() as $sub) {
            $subscription = new Subscription(null, null, $sub);

            switch ($subscription->data()->status_id) {
                case Subscription::ACTIVE:
                    // Active Subscription
                    try {
                        $subscription->chargePayment();
                    } catch (Exception $e) {
                        
                    }
                    break;

                case Subscription::CANCELLED:
                    // Cancelled Subscription
                    $payment = new Payment($subscription->data()->id, 'subscription_id');
                    if ($payment->exists()) {

                        $order = $payment->getOrder();
                        foreach ($order->items()->getItems() as $item) {
                            $payment->deletePendingActions($item->getProduct()->data()->id);
                            $payment->executeActions(Action::EXPIRE, $item);
                        }
                    }

                    $subscription->update([
                        'expired' => 1,
                        'updated' => date('U')
                    ]);
                    break;
            }
        }

        $this->reschedule();
        return Task::STATUS_COMPLETED;
    }

    private function reschedule() {
        Queue::schedule((new HandleSubscriptionsTask())->fromNew(
            Module::getIdFromName('Store'),
            'Handle Subscriptions',
            [],
            Date::next()->getTimestamp()
        ));
    }

    public static function schedule() {
        (new self())->reschedule();
    }
}