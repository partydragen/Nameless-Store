<?php
/**
 * Product class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
class Product {

    private DB $_db;

    /**
     * @var ProductData|null The product data. Basically just the row from `nl2_store_products` where the product ID is the key.
     */
    private ?ProductData $_data;

    /**
     * @var array The list of connections for this product.
     */
    private array $_connections;

    /**
     * @var array The list of fields for this product.
     */
    private array $_fields;

    /**
     * @var Action[] The list of actions for this product.
     */
    private array $_actions;

    public function __construct(?string $value = null, ?string $field = 'id', $query_data = null) {
        $this->_db = DB::getInstance();

        if (!$query_data && $value) {
            $data = $this->_db->get('store_products', [$field, '=', $value]);
            if ($data->count()) {
                $this->_data = new ProductData($data->first());
            }
        } else if ($query_data) {
            // Load data from existing query.
            $this->_data = new ProductData($query_data);
        }
    }

    /**
     * Update a product data in the database.
     *
     * @param array $fields Column names and values to update.
     */
    public function update(array $fields = []): void {
        if (!$this->_db->update('store_products', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating product');
        }
    }

    /**
     * Does this product exist?
     *
     * @return bool Whether the product exists (has data) or not.
     */
    public function exists(): bool {
        return (!empty($this->_data));
    }

    /**
     * Get the product data.
     *
     * @return ProductData This product data.
     */
    public function data(): ?ProductData {
        return $this->_data;
    }

    /**
     * Get the product connections.
     *
     * @param int $service_id Service id.
     *
     * @return array Their connections.
     */
    public function getConnections(int $service_id = null): array {
        $this->_connections ??= (function (): array {
            $this->_connections = [];

            $connections_query = $this->_db->query('SELECT nl2_store_connections.* FROM nl2_store_products_connections INNER JOIN nl2_store_connections ON connection_id = nl2_store_connections.id WHERE product_id = ? AND action_id IS NULL', [$this->data()->id]);
            if ($connections_query->count()) {
                $connections_query = $connections_query->results();
                foreach ($connections_query as $item) {
                    $this->_connections[$item->id] = $item;
                }
            }

            return $this->_connections;
        })();

        if ($service_id) {
            $connections = [];
            foreach ($this->_connections as $connection) {
                if ($connection->service_id == $service_id) {
                    $connections[$connection->id] = $connection;
                }
            }

            return $connections;
        }

        return $this->_connections;
    }

    /**
     * Add a connection to this product.
     *
     * @return bool True on success, false if product already have it.
     */
    public function addConnection(int $connection_id): bool {
        if (array_key_exists($connection_id, $this->getConnections())) {
            return false;
        }

        $this->_db->query('INSERT INTO `nl2_store_products_connections` (`product_id`, `connection_id`) VALUES (?, ?)',
            [
                $this->data()->id,
                $connection_id
            ]
        );

        return true;
    }

    /**
     * Remove a connection to this product.
     *
     * @return bool Returns false if they did not have this connection
     */
    public function removeConnection(int $connection_id): bool {
        if (!array_key_exists($connection_id, $this->getConnections())) {
            return false;
        }

        $this->_db->query('DELETE FROM `nl2_store_products_connections` WHERE `product_id` = ? AND `connection_id` = ? AND action_id IS NULL',
            [
                $this->data()->id,
                $connection_id
            ]
        );

        return true;
    }

    /**
     * Get the product fields.
     *
     * @return array Their fields.
     */
    public function getFields(): array {
        return $this->_fields ??= (function (): array {
            $this->_fields = [];

            $fields_query = $this->_db->query('SELECT nl2_store_fields.* FROM nl2_store_products_fields INNER JOIN nl2_store_fields ON field_id = nl2_store_fields.id WHERE product_id = ? AND deleted = 0 ORDER BY `order`', [$this->data()->id]);
            if ($fields_query->count()) {
                $fields_query = $fields_query->results();
                foreach ($fields_query as $field) {
                    $this->_fields[$field->id] = $field;
                }
            }

            return $this->_fields;
        })();
    }

    /**
     * Add a field to this product.
     *
     * @return bool True on success, false if product already have it.
     */
    public function addField(int $field_id): bool {
        if (array_key_exists($field_id, $this->getFields())) {
            return false;
        }

        $this->_db->query('INSERT INTO `nl2_store_products_fields` (`product_id`, `field_id`) VALUES (?, ?)',
            [
                $this->data()->id,
                $field_id
            ]
        );

        return true;
    }

    /**
     * Remove a field to this product.
     *
     * @return bool Returns false if they did not have this field
     */
    public function removeField(int $field_id): bool {
        if (!array_key_exists($field_id, $this->getFields())) {
            return false;
        }

        $this->_db->query('DELETE FROM `nl2_store_products_fields` WHERE `product_id` = ? AND `field_id` = ?',
            [
                $this->data()->id,
                $field_id
            ]
        );

        return true;
    }

    /**
     * Get the product actions.
     *
     * @param int $type Trigger type like Purchase/Refund/Changeback etc
     *
     * @return Action Actions for this product.
     */
    public function getActions(int $type = null): array {
        $this->_actions ??= (function (): array {
            $this->_actions = [];

            $actions_query = $this->_db->query('SELECT * FROM nl2_store_products_actions WHERE product_id = ? ORDER BY `order` ASC', [$this->data()->id]);
            if ($actions_query->count()) {
                $actions_query = $actions_query->results();
                
                $services = Services::getInstance();
                foreach ($actions_query as $data) {
                    $service = $services->get($data->service_id);
                    if ($service == null) {
                        continue;
                    }

                    $action = new Action($service, null, null, $data);

                    $this->_actions[$action->data()->id] = $action;
                }
            }
            
            return $this->_actions;
        })();

        if ($type) {
            $return = [];
            foreach ($this->_actions as $action) {
                if ($action->data()->type == $type) {
                    $return[$action->data()->id] = $action;
                }
            }

            return $return;
        }

        return $this->_actions;
    }

    /**
     * Get product action by id.
     *
     * @param int $id Action id
     *
     * @return Action|null Action by id otherwise null.
     */
    public function getAction(int $id): ?Action {
        return $this->getActions()[$id] ?? null;
    }

    /**
     * Get the required user integrations that this product require.
     *
     * @return array List of required integrations.
     */
    public function getRequiredIntegrations(): array {
        $required_integrations_list = [];

        $integrations = Integrations::getInstance();
        if (!Settings::get('player_login', '0', 'Store')) {
            foreach ($this->getActions() as $action) {
                if ($action->getService()->getId() == 2) {
                    $integration = $integrations->getIntegration('Minecraft');
                    if ($integration != null) {
                        $required_integrations_list[$integration->data()->id] = $integration;
                    }
                }
            }
        }

        $enabled_integrations = $integrations->getEnabledIntegrations();
        $required_integrations = json_decode($this->data()->required_integrations, true) ?? [];
        foreach ($required_integrations as $item) {
            foreach ($enabled_integrations as $integration) {
                if ($integration->data()->id == $item) {
                    $required_integrations_list[$integration->data()->id] = $integration;
                }
            }
        }

        return $required_integrations_list;
    }

    public function getRealPriceCents(): int {
        return $this->data()->sale_active == 1 ? $this->data()->price_cents - $this->data()->sale_discount_cents : $this->data()->price_cents;
    }

    public function delete() {
        if ($this->exists()) {
            $this->update([
                'deleted' => date('U')
            ]);
            
            $this->_db->query('DELETE FROM `nl2_store_pending_actions` WHERE `product_id` = ?', [$this->data()->id]);
            
            return true;
        }

        return false;
    }
}