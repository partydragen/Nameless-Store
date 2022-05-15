<?php
/**
 * The action class for the product actions.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.0-pr12
 * @license MIT
 */
class Action {

    private DB $_db;

    /**
     * @var object|null The action data. Basically just the row from `nl2_store_products_actions` where the action ID is the key.
     */
    private $_data;

    /**
     * @var array The list of connections.
     */
    private array $_connections;

    /**
     * @var ServiceBase The service this action belong to.
     */
    private ServiceBase $_service;

    public function __construct(ServiceBase $service, ?string $value = null, ?string $field = 'id', $query_data = null) {
        $this->_db = DB::getInstance();
        $this->_service = $service;

        if (!$query_data && $value) {
            $data = $this->_db->get('store_products_actions', [$field, '=', $value]);
            if ($data->count()) {
                $this->_data = $data->first();
            }
        } else if ($query_data) {
            // Load data from existing query.
            $this->_data = $query_data;
        }
    }

    public function getService(): ServiceBase {
        return $this->_service;
    }

    /**
     * Update a action data in the database.
     *
     * @param array $fields Column names and values to update.
     */
    public function update(array $fields = []) {
        if (!$this->_db->update('store_products_actions', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating action');
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

    /**
     * Get the action connections.
     *
     * @return array Their connections.
     */
    public function getConnections(): array {
        return $this->_connections ??= (function (): array {
            $this->_connections = [];

            $connections_query = $this->_db->query('SELECT nl2_store_connections.* FROM nl2_store_products_connections INNER JOIN nl2_store_connections ON connection_id = nl2_store_connections.id WHERE action_id = ?', [$this->data()->id]);
            if ($connections_query->count()) {
                $connections_query = $connections_query->results();
                foreach ($connections_query as $item) {
                    $this->_connections[$item->id] = $item;
                }
            }

            return $this->_connections;
        })();
    }

    /**
     * Add a connection to this action.
     *
     * @return bool True on success, false if action already have it.
     */
    public function addConnection(int $connection_id): bool {
        if (array_key_exists($connection_id, $this->getConnections())) {
            return false;
        }

        $this->_db->createQuery('INSERT INTO `nl2_store_products_connections` (`product_id`, `action_id`, `connection_id`) VALUES (?, ?, ?)',
            [
                $this->data()->product_id,
                $this->data()->id,
                $connection_id
            ]
        );

        return true;
    }

    /**
     * Remove a connection to this action.
     *
     * @return bool Returns false if they did not have this connection
     */
    public function removeConnection(int $connection_id): bool {
        if (!array_key_exists($connection_id, $this->getConnections())) {
            return false;
        }

        $this->_db->createQuery('DELETE FROM `nl2_store_products_connections` WHERE `action_id` = ? AND `connection_id` = ?',
            [
                $this->data()->id,
                $connection_id
            ]
        );

        return true;
    }

    /**
     * Execute actions for product and make placeholders
     */
    public function execute(Order $order, Product $product, Payment $payment) {
        $placeholders = [];

        $custom_fields = $this->_db->query('SELECT identifier, value FROM nl2_store_orders_products_fields INNER JOIN nl2_store_fields ON field_id=nl2_store_fields.id WHERE order_id = ? AND product_id = ?', [$this->data()->order_id, $product->data()->id])->results();
        foreach ($custom_fields as $field) {
            $placeholders['{'.$field->identifier.'}'] = Output::getClean($field->value);
        }

        $placeholders['{userId}'] = $order->data()->user_id ?? 0;
        $placeholders['{productId}'] = $product->data()->id;
        $placeholders['{productPrice}'] = $product->data()->price;
        $placeholders['{productName}'] = $product->data()->name;
        $placeholders['{transaction}'] = $payment->data()->transaction;
        $placeholders['{amount}'] = $payment->data()->amount;
        $placeholders['{currency}'] = $payment->data()->currency;
        $placeholders['{orderId}'] = $payment->data()->order_id;
        $placeholders['{time}'] = date('H:i', $this->data()->created);
        $placeholders['{date}'] = date('d M Y', $this->data()->created);

        $this->_service->executeAction($this, $order, $product, $payment, $placeholders);
    }

    public function delete() {
        if ($this->exists()) {
            $this->_db->createQuery('DELETE FROM `nl2_store_products_actions` WHERE `id` = ?', [$this->data()->id]);
            $this->_db->createQuery('DELETE FROM `nl2_store_products_connections` WHERE `action_id` = ?', [$this->data()->id]);
            $this->_db->createQuery('DELETE FROM `nl2_store_pending_actions` WHERE `action_id` = ?', [$this->data()->id]);

            return true;
        }

        return false;
    }
}