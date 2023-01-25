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
     * @var array<int, Product> The list of products.
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
            foreach ($products_query as $item) {
                $product = new Product(null, null, $item);

                $renderProductEvent = EventHandler::executeEvent('renderStoreProduct', [
                    'product' => $product,
                    'name' => $product->data()->name,
                    'content' => $product->data()->description,
                    'image' => (isset($product->data()->image) && !is_null($product->data()->image) ? (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/' . 'uploads/store/' . Output::getClean(Output::getDecoded($product->data()->image))) : null),
                    'link' => URL::build($store_url . '/checkout', 'add=' . Output::getClean($product->data()->id)),
                    'hidden' => false,
                ]);

                $this->_products[$item->id] = $product;
            }

            // Remove items if they're invalid, disabled or deleted
            foreach ($this->_items as $item) {
                if (!array_key_exists($item['id'], $this->_products) || $item['quantity'] <= 0) {
                    $this->remove($item['id']);
                }
            }
        }
    }

    // Add product to shopping cart
    public function add(int $product_id, int $quantity = 1, array $fields = []): void {
        $shopping_cart = (isset($_SESSION['shopping_cart']) ? $_SESSION['shopping_cart'] : []);

        $shopping_cart[$product_id] = [
            'id' => $product_id,
            'quantity' => $quantity,
            'fields' => $fields
        ];

        $_SESSION['shopping_cart'] = $shopping_cart;
    }

    // Remove product from shopping cart
    public function remove(int $product_id): void {
        unset($_SESSION['shopping_cart'][$product_id]);
        unset($this->_items[$product_id]);
    }

    // Clear the shopping cart
    public function clear(): void {
        unset($_SESSION['shopping_cart']);
    }

    // Get the items from the shopping cart
    public function getItems(): array {
        return $this->_items;
    }

    // Get the products from the shopping cart
    public function getProducts(): array {
        return $this->_products ? $this->_products : [];
    }

    // Get total price to pay in cents
    public function getTotalPriceCents(): int {
        $price = 0;

        foreach ($this->getProducts() as $product) {
            $price += $product->getRealPriceCents() * $this->_items[$product->data()->id]['quantity'];
        }

        return $price;
    }

    // Get total price to pay in cents
    public function getTotalCents(): int {
        $price = 0;

        foreach ($this->getProducts() as $product) {
            $price += $product->data()->price_cents * $this->_items[$product->data()->id]['quantity'];
        }

        return $price;
    }

    // Get total real price in cents
    public function getTotalRealPriceCents(): int {
        $price = 0;

        foreach ($this->getProducts() as $product) {
            $price += $product->getRealPriceCents() * $this->_items[$product->data()->id]['quantity'];
        }

        return $price;
    }

    // Get total discount in cents
    public function getTotalDiscountCents(): int {
        $discount = 0;

        foreach ($this->getProducts() as $product) {
            $discount += $product->data()->sale_discount_cents * $this->_items[$product->data()->id]['quantity'];
        }

        return $discount;
    }
}