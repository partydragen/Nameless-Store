<?php

class ExpireCustomerProductTask extends Task {

    public function run(): string {
        $data = $this->getData();

        $payment = new Payment($data['payment_id']);
        if ($payment->exists()) {
            $order = $payment->getOrder();

            $item = new Item($data['order_item_id']);
            $payment->deletePendingActions($item->getProduct()->data()->id);
            $payment->executeActions(Action::EXPIRE, $item);

            EventHandler::executeEvent(new CustomerProductExpiredEvent(
                $payment,
                $order,
                $order->customer(),
                $order->recipient(),
                $item
            ));

            $this->setOutput(['success' => true]);
        }

        return Task::STATUS_COMPLETED;
    }

    /**
     * Schedule this task
     *
     * @param Order $order
     * @param Item $item
     * @param Payment $payment
     *
     * @return void
     */
    public static function schedule(Order $order, Item $item, Payment $payment): void {
        $hasBeenScheduled = DB::getInstance()->query('SELECT COUNT(*) c FROM nl2_queue WHERE `task` = \'ExpireCustomerProductTask\' AND entity_id = ?', [$item->getId()])->first()->c;

        if (!$hasBeenScheduled) {
            $product = $item->getProduct();

            $duration_json = json_decode($product->data()->durability, true) ?? [];
            $time = strtotime('+' . $duration_json['interval'] . ' ' . $duration_json['period']);
            $success = Queue::schedule((new ExpireCustomerProductTask())->fromNew(
                Module::getIdFromName('Store'),
                'Expire Customer Product',
                [
                    'order_id' => $order->data()->id,
                    'order_item_id' => $item->getId(),
                    'product_id' => $product->data()->id,
                    'payment_id' => $payment->data()->id
                ],
                $time,
                'order_item',
                $item->getId()
            ));

            $task_id = $success ? DB::getInstance()->lastId() : null;
            DB::getInstance()->query('UPDATE nl2_store_orders_products SET expire = ?, task_id = ? WHERE id = ?', [$time, $task_id, $item->getId()]);
        }
    }
}