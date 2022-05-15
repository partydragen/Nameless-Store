<?php
/**
 * Order class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.0-pr12
 * @license MIT
 */
class Order {

    private $_db,
            $_data;

    /**
     * @var Product[] The list of products.
     */
    private array $_products;

    /**
     * @var Amount The amount to charge.
     */
    private Amount $_amount;

    // Constructor
    public function __construct(?string $value = null, string $field = 'id') {
        $this->_db = DB::getInstance();

        if ($value != null) {
            $data = $this->_db->get('store_orders', [$field, '=', $value]);
            if ($data->count()) {
                $this->_data = $data->first();
            }
        }
    }

    /**
     * Does this payment exist?
     *
     * @return bool Whether the order exists (has data) or not.
     */
    public function exists(): bool {
        return (!empty($this->_data));
    }

    /**
     * @return object This order's data.
     */
    public function data() {
        return $this->_data;
    }

    /**
     * Get the products for this order.
     *
     * @return Product[] The products for this order.
     */
    public function getProducts(): array {
        return $this->_products ??= (function (): array {
            $products = [];

            $products_query = $this->_db->query('SELECT nl2_store_products.* FROM nl2_store_orders_products INNER JOIN nl2_store_products ON nl2_store_products.id=product_id WHERE order_id = ?', [$this->data()->id]);
            if ($products_query->count()) {
                $products_query = $products_query->results();

                foreach ($products_query as $data) {
                    $products[$data->id] = new Product(null, null, $data);
                }
            }

            return $products;
        })();
    }

    public function create($user, $player, $items) {
        $this->_db->insert('store_orders', [
            'user_id' => $user->data() ? $user->data()->id : null,
            'player_id' => $player->data() ? $player->data()->id : null,
            'created' => date('U'),
            'ip' => $user->getIP(),
        ]);
        $last_id = $this->_db->lastId();

        // Register products and fields to order
        foreach ($items as $item) {
            $this->_db->insert('store_orders_products', [
                'order_id' => $last_id,
                'product_id' => $item['id']
            ]);

            if (isset($item['fields']) && count($item['fields'])) {
                foreach ($item['fields'] as $field) {
                    $this->_db->insert('store_orders_products_fields', [
                        'order_id' => $last_id,
                        'product_id' => $item['id'],
                        'field_id' => $field['id'],
                        'value' => $field['value']
                    ]);
                }
            }
        }

        // Load order
        $data = $this->_db->get('store_orders', ['id', '=', $last_id]);
        if ($data->count()) {
            $this->_data = $data->first();
        }
    }

    /**
     * Set the amount to charge.
     *
     * @param amount $amount
     */
    public function setAmount(Amount $amount): void {
        $this->_amount = $amount;
    }

    /**
     * Get the charge amount for this order.
     *
     * @return Amount
     */
    public function getAmount(): Amount {
        return $this->_amount;
    }

    /**
     * Description of all the product names.
     *
     * @return string Description of all the product names.
     */
    public function getDescription(): string {
        $product_names = '';
        foreach ($this->getProducts() as $product) {
            $product_names .= $product->data()->name . ', ';
        }
        $product_names = rtrim($product_names, ', ');

        return $product_names;
    }
}