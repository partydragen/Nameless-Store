<?php

class HandleSubscriptionsTask extends Task {

    public function run(): string {
        $subscriptions_log = [];
        $subscriptions = DB::getInstance()->query('SELECT * FROM nl2_store_subscriptions WHERE next_billing_date < ? AND expired = 0', [date('U')]);
        foreach ($subscriptions->results() as $sub) {
            $subscription = new Subscription(null, null, $sub);

            switch ($subscription->data()->status_id) {
                case Subscription::ACTIVE:
                    // Active Subscription
                    try {
                        $success = $subscription->chargePayment();

                        $subscriptions_log['charge_attempt'][$subscription->data()->id] = $success;
                    } catch (Exception $e) {
                        $subscriptions_log['charge_failure'][$subscription->data()->id] = $e;
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

                            EventHandler::executeEvent(new CustomerProductExpiredEvent(
                                $payment,
                                $order,
                                $order->customer(),
                                $order->recipient(),
                                $item
                            ));
                        }
                    }

                    $subscription->update([
                        'expired' => 1,
                        'updated' => date('U')
                    ]);

                    $subscriptions_log['expired'][$subscription->data()->id] = true;
                    break;
            }
        }

        $this->setOutput($subscriptions_log);
        $this->reschedule();
        return Task::STATUS_COMPLETED;
    }

    private function reschedule() {
        $hasBeenScheduled = DB::getInstance()->query('SELECT COUNT(*) c FROM nl2_queue WHERE `task` = \'HandleSubscriptionsTask\' AND `status` = \'ready\'')->first()->c;

        if (!$hasBeenScheduled) {
            Queue::schedule((new HandleSubscriptionsTask())->fromNew(
                Module::getIdFromName('Store'),
                'Handle Subscriptions',
                [],
                Date::next()->getTimestamp()
            ));
        }
    }

    public static function schedule() {
        (new self())->reschedule();
    }
}