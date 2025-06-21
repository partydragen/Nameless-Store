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

    private $_item_id;
    private $_product;
    private $_quantity;
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
     * Get the item id.
     *
     * @return int
     */
    public function getId() {
        return $this->_item_id;
    }

    /**
     * Get the product for this item.
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
     * Item cost after any discounts in cents for a single quantity.
     *
     * @param Customer|null $recipient The customer object to calculate the price for.
     * @return int
     */
    public function getSingleQuantityPrice(Customer $recipient = null) {
        return ($this->getSubtotalPrice() - $this->getTotalDiscounts($recipient)) / $this->getQuantity();
    }

    /**
     * Item cost after any discounts in cents.
     *
     * @param Customer|null $recipient The customer object to calculate the price for.
     * @return int
     */
    public function getTotalPrice(Customer $recipient = null) {
        return $this->getSubtotalPrice() - $this->getTotalDiscounts($recipient);
    }

    /**
     * Item cost before any discounts in cents.
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
     * Item discounts.
     *
     * @param Customer|null $recipient The customer object to calculate the price for.
     * @return int
     */
    public function getTotalDiscounts(Customer $recipient = null) {
        $subtotal = $this->getSubtotalPrice();

        // Get the final price from the Product class, passing the recipient
        $final_unit_price = $this->_product->getRealPriceCents($recipient);

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