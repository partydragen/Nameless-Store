<?php
/**
 * Item class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.2.0
 * @license MIT
 */
class Item {

    /**
     * @var int Get the item id.
     */
    private int $_item_id;

    /**
     * @var Product The product for this item.
     */
    private Product $_product;

    /**
     * @var int Number of a particular item.
     */
    private int $_quantity;

    /**
     * @var ?array The custom fields for this item.
     */
    private ?array $_fields;

    public function __construct(int $item_id, Product $product = null, int $quantity = null, array $fields = null) {
        $this->_item_id = $item_id;

        if ($product != null) {
            $this->_product = $product;
            $this->_quantity = $quantity;
            $this->_fields = $fields;
        } else {
            $item_query = $this->_db->query('SELECT nl2_store_products.*, nl2_store_orders_products.quantity, nl2_store_orders_products.id AS item_id FROM nl2_store_orders_products INNER JOIN nl2_store_products ON nl2_store_products.id=product_id WHERE nl2_store_orders_products.id = ?', [$item_id]);
            if ($item_query->count()) {
                $item_query = $item_query->first();

                $this->_product = new Product(null, null, $item_query);
                $this->_quantity = $item_query->quantity;
            }
        }
    }

    /**
     * Get the product for this item.
     *
     * @return int
     */
    public function getId(): int {
        return $this->_item_id;
    }

    /**
     * Get the item id.
     *
     * @return Product
     */
    public function getProduct(): Product {
        return $this->_product;
    }

    /**
     * Number of a particular item.
     *
     * @return int
     */
    public function getQuantity(): int {
        return $this->_quantity;
    }

    /**
     * Item cost after any discounts in cents for a single quantity. (e.g., 100 cents is $1.00, a zero-decimal currency)
     *
     * @return int
     */
    public function getSingleQuantityPrice(): int {
        return ($this->getSubtotalPrice() - $this->getTotalDiscounts()) / $this->getQuantity();
    }

    /**
     * Item cost after any discounts in cents. (e.g., 100 cents to charge $1.00, a zero-decimal currency)
     *
     * @return int
     */
    public function getTotalPrice(): int {
        return $this->getSubtotalPrice() - $this->getTotalDiscounts();
    }

    /**
     * Item cost before any discounts in cents. (e.g., 100 cents to charge $1.00, a zero-decimal currency)
     *
     * @return int
     */
    public function getSubtotalPrice(): int {
        $field = $this->getField('price');
        if ($field) {
            $price = Store::toCents($field['value']);
        } else {
            $price = $this->_product->data()->price_cents;
        }

        foreach ($this->_fields as $field) {
            if (isset($field['value_price'])) {
                $price += $field['value_price'];
            }
        }

        return $price * $this->getQuantity();
    }

    /**
     * Item discounts. (e.g., 100 cents to charge $1.00, a zero-decimal currency)
     *
     * @return int
     */
    public function getTotalDiscounts(): int {
        return $this->_product->data()->sale_active == 1 ? $this->_product->data()->sale_discount_cents * $this->getQuantity() : 0;
    }

    /**
     * Get a fields.
     *
     * @return array
     */
    public function getFields(): array {
        return $this->_fields;
    }

    public function getFields(): array {
        return $this->_fields ??= (function (): array {
            $fields_query = $this->_db->query('SELECT identifier, value FROM nl2_store_orders_products_fields INNER JOIN nl2_store_fields ON field_id=nl2_store_fields.id WHERE order_id = ? AND product_id = ?', [$this->data()->id, $product_id])->results();
            foreach ($fields_query as $field) {
                $fields[$field->identifier] = [
                    'identifier' => Output::getClean($field->identifier),
                    'value' => Output::getClean($field->value)
                ];
            }

            return $items;
        })();
    }

    /**
     * Get a field by identifier.
     *
     * @param string $identifier The field identifier.
     *
     * @return array
     */
    public function getField(string $identifier): ?array {
        foreach ($this->_fields as $field) {
            if ($field['identifier'] == $identifier) {
                return $field;
            }
        }

        return null;
    }

    // Get item description
    public function getDescription(): string {
        return $this->_product->data()->description;
    }
}