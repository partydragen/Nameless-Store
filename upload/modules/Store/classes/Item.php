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
    private $_item_id;

    /**
     * @var Product The product for this item.
     */
    private $_product;

    /**
     * @var int Number of a particular item.
     */
    private $_quantity;

    /**
     * @var array The custom fields for this item.
     */
    private $_fields;

    public function __construct($item_id, Product $product = null, $quantity = null, $fields = []) {
        $this->_item_id = $item_id;

        if ($product != null) {
            $this->_product = $product;
            $this->_quantity = $quantity;
            $this->_fields = $fields;
        } else {
            $item_query = DB::getInstance()->query('SELECT nl2_store_products.*, nl2_store_orders_products.quantity, nl2_store_orders_products.id AS item_id FROM nl2_store_orders_products INNER JOIN nl2_store_products ON nl2_store_products.id=product_id WHERE nl2_store_orders_products.id = ?', [$item_id]);
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
    public function getId() {
        return $this->_item_id;
    }

    /**
     * Get the item id.
     *
     * @return Product
     */
    public function getProduct() {
        return $this->_product;
    }

    /**
     * Number of a particular item.
     *
     * @return int
     */
    public function getQuantity() {
        return $this->_quantity;
    }

    /**
     * Item cost after any discounts in cents for a single quantity. (e.g., 100 cents is $1.00, a zero-decimal currency)
     *
     * @return int
     */
    public function getSingleQuantityPrice(User $user = null) {
        return ($this->getSubtotalPrice() - $this->getTotalDiscounts($user)) / $this->getQuantity();
    }

    /**
     * Item cost after any discounts in cents. (e.g., 100 cents to charge $1.00, a zero-decimal currency)
     *
     * @return int
     */
    public function getTotalPrice(User $user = null) {
        return $this->getSubtotalPrice() - $this->getTotalDiscounts($user);
    }

    /**
     * Item cost before any discounts in cents. (e.g., 100 cents to charge $1.00, a zero-decimal currency)
     *
     * @return int
     */
    public function getSubtotalPrice() {
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
    public function getTotalDiscounts(User $user = null) {
        $subtotal = $this->getSubtotalPrice();
        $final_unit_price = $this->_product->getRealPriceCents($user);
        $final_total_price = $final_unit_price * $this->getQuantity();
        return max(0, $subtotal - $final_total_price);
    }

    /**
     * Get a fields.
     *
     * @return array
     */
    public function getFields() {
        return $this->_fields;
    }

    /**
     * Get a field by identifier.
     *
     * @param string $identifier The field identifier.
     *
     * @return array
     */
    public function getField($identifier) {
        foreach ($this->_fields as $field) {
            if ($field['identifier'] == $identifier) {
                return $field;
            }
        }

        return null;
    }

    // Get item description
    public function getDescription() {
        return $this->_product->data()->description;
    }
}