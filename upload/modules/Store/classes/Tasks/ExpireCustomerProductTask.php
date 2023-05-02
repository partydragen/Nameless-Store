<?php

class ExpireCustomerProductTask extends Task {

    public function run(): string {
        $data = $this->getData();

        $payment = new Payment($data['payment_id']);
        if ($payment->exists()) {
            $product = new Product($data['product_id']);
            $order = $payment->getOrder();

            $payment->deletePendingActions($product->data()->id);
            $payment->executeActions(Action::EXPIRE, $product);

            EventHandler::executeEvent(new CustomerProductExpiredEvent(
                $payment,
                $order,
                $order->customer(),
                $order->recipient(),
                $product
            ));

            $this->setOutput(['success' => true]);
        }

        return Task::STATUS_COMPLETED;
    }

    /**
     * Schedule this task
     *
     * @param Order $order
     * @param Product $product
     * @param Payment $payment
     *
     * @return void
     */
    public static function schedule(Order $order, Product $product, Payment $payment): void {
        $hasBeenScheduled = DB::getInstance()->query('SELECT COUNT(*) c FROM nl2_queue WHERE `task` = \'ExpireCustomerProductTask\' AND entity_id = ?', [$item_id])->first()->c;

        if (!$hasBeenScheduled) {
            $durability = json_decode($product->data()->durability, true) ?? [];
            $item = DB::getInstance()->query('SELECT id FROM nl2_store_orders_products WHERE order_id = ? AND product_id = ?', [$order->data()->id, $product->data()->id])->first();

            $time = strtotime('+' . $durability['interval'] . ' ' . $durability['period']);
            $success = Queue::schedule((new ExpireCustomerProductTask())->fromNew(
                Module::getIdFromName('Store'),
                'Expire Customer Product',
                [
                    'order_id' => $order->data()->id,
                    'order_item_id' => $item->id,
                    'product_id' => $product->data()->id,
                    'payment_id' => $payment->data()->id
                ],
                $time,
                'order_item',
                $item->id
            ));

            $task_id = $success ? DB::getInstance()->lastId() : null;
            DB::getInstance()->query('UPDATE nl2_store_orders_products SET expire = ?, task_id = ? WHERE id = ?', [$time, $task_id, $item->id]);
        }
    }
}