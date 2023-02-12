<?php
/**
 * Item class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
class Item {

    /**
     * @var Product The product for this item.
     */
    private Product $_product;

    /**
     * @var int Number of a particular item.
     */
    private int $_quantity;

    /**
     * @var array The custom fields for this item.
     */
    private array $_fields = [];

    public function __construct(Product $product, int $quantity, array $fields) {
        $this->_product = $product;
        $this->_quantity = $quantity;
        $this->_fields = $fields;
    }

    /**
     * Get the product for this item.
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