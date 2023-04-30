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
     * @var array<int, Item> The list of items.
     */
    private array $_items = [];

    /**
     * @var array<int, Product> The list of products.
     */
    private array $_products = [];

    /**
     * @var Coupon Coupon code
     */
    private ?Coupon $_coupon = null;

    // Constructor
    public function __construct() {
        $shopping_cart = $_SESSION['shopping_cart'] ?? [];
        $items = $_SESSION['shopping_cart']['items'] ?? [];

        if (count($items)) {
            // Get active coupon
            if (isset($_SESSION['shopping_cart']['coupon_id'])) {
                $coupon = new Coupon($_SESSION['shopping_cart']['coupon_id']);
                if ($coupon->exists()) {
                    $this->_coupon = $coupon;
                }
            }

            // Get products
            $products_ids = implode(',', array_keys($items));
            $products_query = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE id in ('.$products_ids.') AND disabled = 0 AND deleted = 0 ')->results();
            foreach ($products_query as $item) {
                $product = new Product(null, null, $item);

                EventHandler::executeEvent('renderStoreProduct', [
                    'product' => $product,
                    'name' => $product->data()->name,
                    'content' => $product->data()->description,
                    'image' => (isset($product->data()->image) && !is_null($product->data()->image) ? (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/' . 'uploads/store/' . Output::getClean(Output::getDecoded($product->data()->image))) : null),
                    'link' => URL::build($store_url . '/checkout', 'add=' . Output::getClean($product->data()->id)),
                    'hidden' => false,
                    'shopping_cart' => $this
                ]);

                $item = $items[$product->data()->id];
                $this->_items[$product->data()->id] = new Item($product, $item['quantity'], $item['fields']);
                $this->_products[$product->data()->id] = $product;
            }
        }
    }

    // Add product to shopping cart
    public function add(int $product_id, int $quantity = 1, array $fields = []): void {
        $shopping_cart = (isset($_SESSION['shopping_cart']) ? $_SESSION['shopping_cart'] : []);

        $shopping_cart['items'][$product_id] = [
            'id' => $product_id,
            'quantity' => $quantity,
            'fields' => $fields
        ];

        $_SESSION['shopping_cart'] = $shopping_cart;
    }

    // Remove product from shopping cart
    public function remove(int $product_id): void {
        unset($_SESSION['shopping_cart']['items'][$product_id]);
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
        return $this->_products;
    }

    // Set coupon for this shopping cart
    public function setCoupon(?Coupon $coupon) {
        $this->_coupon = $coupon;

        if ($coupon != null) {
            $_SESSION['shopping_cart']['coupon_id'] = $coupon->data()->id;
        } else {
            unset($_SESSION['shopping_cart']['coupon_id']);
        }
    }

    // Get active coupon code
    public function getCoupon(): ?Coupon {
        return $this->_coupon;
    }

    // Get total price to pay in cents
    public function getTotalCents(): int {
        $price = 0;

        foreach ($this->getItems() as $item) {
            $price += $item->getSubtotalPrice();
        }

        return $price;
    }

    // Get total real price in cents
    public function getTotalRealPriceCents(): int {
        $price = 0;

        foreach ($this->getItems() as $item) {
            $price += $item->getTotalPrice();
        }

        return $price;
    }

    // Get total discount in cents
    public function getTotalDiscountCents(): int {
        $discount = 0;

        foreach ($this->getItems() as $item) {
            $discount += $item->getTotalDiscounts();
        }

        return $discount;
    }
}