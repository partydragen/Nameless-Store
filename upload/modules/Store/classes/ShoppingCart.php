<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - ShoppingCart class
 */

class ShoppingCart {

    /**
     * @var array The list of items.
     */
    private array $_items = [];

    /**
     * @var array<int, Object> The list of products.
     */
    private array $_products = [];

    // Constructor
    public function __construct() {
        $this->_items = (isset($_SESSION['shopping_cart']) ? $_SESSION['shopping_cart'] : []);

        if (count($this->_items)) {
            $products_ids = '(';
            foreach ($this->_items as $product) {
                $products_ids .= (int) $product['id'] . ',';
            }
            $products_ids = rtrim($products_ids, ',');
            $products_ids .= ')';

            // Get products
            $products_query = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE id in '.$products_ids.' AND disabled = 0 AND deleted = 0 ')->results();
            foreach ($products_query as $product) {
                $this->_products[$product->id] = $product;
            }

            // Remove items if they're invalid, disabled or deleted
            foreach ($this->_items as $item) {
                if (!array_key_exists($item['id'], $this->_products)) {
                    $this->remove($item['id']);
                }
            }
        }
    }

    // Add product to shopping cart
    public function add($product_id, $quantity = 1, $fields = []) {
        $shopping_cart = (isset($_SESSION['shopping_cart']) ? $_SESSION['shopping_cart'] : []);

        $shopping_cart[$product_id] = [
            'id' => $product_id,
            'quantity' => $quantity,
            'fields' => $fields
        ];

        $_SESSION['shopping_cart'] = $shopping_cart;
    }

    // Remove product from shopping cart
    public function remove($product_id) {
        unset($_SESSION['shopping_cart'][$product_id]);
        unset($this->_items[$product_id]);
    }

    // Clear the shopping cart
    public function clear() {
        unset($_SESSION['shopping_cart']);
    }

    // Get the items from the shopping cart
    public function getItems() {
        return $this->_items;
    }

    // Get the products from the shopping cart
    public function getProducts() {
        return $this->_products ? $this->_products : [];
    }

    // Get total price to pay
    public function getTotalPrice() {
        $price = 0;

        foreach ($this->_products as $product) {
            $price += $product->price;
        }

        return $price;
    }
}