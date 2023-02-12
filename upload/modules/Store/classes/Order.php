<?php
/**
 * Order class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
class Order {

    private $_db,
            $_data;

    /**
     * @var Item[] The list of products.
     */
    private array $_items;

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

    // Set products to keep current rendered data and to avoid to requery
    public function setProducts(array $products) {
        $this->_products = $products;
    }

    /**
     * Get the products for this order.
     *
     * @return Item[] The products for this order.
     */
    public function getItems(): array {
        return $this->_items;
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

    /**
     * Get the product fields.
     *
     * @return array The list of field values
     */
    public function getProductFields(int $product_id): array {
        $fields = [];
        $fields_query = $this->_db->query('SELECT identifier, value FROM nl2_store_orders_products_fields INNER JOIN nl2_store_fields ON field_id=nl2_store_fields.id WHERE order_id = ? AND product_id = ?', [$this->data()->id, $product_id])->results();
        foreach ($fields_query as $field) {
            $fields[$field->identifier] = [
                'identifier' => Output::getClean($field->identifier),
                'value' => Output::getClean($field->value)
            ];
        }

        return $fields;
    }

    /**
     * Register the order to database.
     *
     * @param ?User $user The NamelessMC user buying the product.
     * @param Customer $from_customer The customer buying the product.
     * @param Customer $to_customer The customer who is receiving the product.
     * @param array<int, Item> $items The list of items along with custom fields for product
     */
    public function create(?User $user, Customer $from_customer, Customer $to_customer, array $items, ?Coupon $coupon = null): void {
        $this->_db->insert('store_orders', [
            'user_id' => $user != null ? $user->exists() ? $user->data()->id : null : null,
            'from_customer_id' => $from_customer->data()->id,
            'to_customer_id' => $to_customer->data()->id,
            'created' => date('U'),
            'ip' => HttpUtils::getRemoteAddress(),
            'coupon_id' => $coupon != null ? $coupon->data()->id : null
        ]);
        $last_id = $this->_db->lastId();

        // Register products and fields to order
        $this->_items = $items;
        foreach ($items as $item) {
            $this->_db->insert('store_orders_products', [
                'order_id' => $last_id,
                'product_id' => $item->getProduct()->data()->id,
                'quantity' => $item->getQuantity(),
                'amount_cents' => $item->getSingleQuantityPrice()
            ]);

            foreach ($item->getFields() as $field) {
                $this->_db->insert('store_orders_products_fields', [
                    'order_id' => $last_id,
                    'product_id' => $item->getProduct()->data()->id,
                    'field_id' => $field['id'],
                    'value' => $field['value']
                ]);
            }
        }

        // Load order
        $data = $this->_db->get('store_orders', ['id', '=', $last_id]);
        if ($data->count()) {
            $this->_data = $data->first();
        }
    }

    public function customer(): Customer {
        if ($this->data()->from_customer_id) {
            return new Customer(null, $this->data()->from_customer_id, 'id');
        } else {
            return new Customer(null, $this->data()->user_id, 'user_id');
        }
    }

    public function recipient(): Customer {
        if ($this->data()->to_customer_id) {
            return new Customer(null, $this->data()->to_customer_id, 'id');
        } else {
            return new Customer(null, $this->data()->user_id, 'user_id');
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