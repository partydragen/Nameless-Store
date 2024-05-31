<?php

class ActionTask {

    public const PENDING = 0;
    public const COMPLETED = 1;
    public const FAILED = 3;

    /**
     * @var object|null The action data. Basically just the row from `nl2_store_pending_actions` where the action ID is the key.
     */
    private $_data;

    public function __construct(string $value = null, string $field = 'id') {
        if ($value) {
            $data = $this->_db->get('store_pending_actions', [$field, '=', $value]);
            if ($data->count()) {
                $this->_data = $data->first();
            }
        }
    }

    public function create(string $command, Action $action, Order $order, Item $item, Payment $payment, array $extra = []) {
        DB::getInstance()->insert('store_pending_actions', array_merge([
            'order_id' => $payment->data()->order_id,
            'action_id' => $action->data()->id,
            'product_id' => $item->getProduct()->data()->id,
            'customer_id' => $order->data()->to_customer_id,
            'type' => $action->data()->type,
            'command' => $command,
            'require_online' => $action->data()->require_online,
            'order' => $action->data()->order
        ], $extra));
    }

    /**
     * Update an action data in the database.
     *
     * @param array $fields Column names and values to update.
     */
    public function update(array $fields = []) {
        if (!$this->_db->update('store_pending_actions', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating task');
        }
    }

    /**
     * Does this action exist?
     *
     * @return bool Whether the action exists (has data) or not.
     */
    public function exists(): bool {
        return (!empty($this->_data));
    }

    /**
     * Get the action data.
     *
     * @return object This action data.
     */
    public function data() {
        return $this->_data;
    }
}