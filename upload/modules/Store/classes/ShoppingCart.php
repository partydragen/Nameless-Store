<?php
/**
 * ShoppingCart class used during checkout flow.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.2.0
 * @license MIT
 */
class ShoppingCart extends Instanceable {

    /**
     * @var ItemList The list of items.
     */
    private ItemList $_items;

    /**
     * @var ?Order Current order.
     */
    private ?Order $_order = null;

    /**
     * @var ?Coupon Coupon code.
     */
    private ?Coupon $_coupon = null;

    /**
     * @var Customer Customer (The paying customer).
     */
    private Customer $_customer;

    /**
     * @var Customer Recipient customer (Recipient that receive the products).
     */
    private Customer $_recipient;

    /**
     * @var bool Shopping cart subscription mode.
     */
    private bool $_subscription_mode = false;

    // Constructor
    public function __construct() {
        // Load customer
        $this->_customer = new Customer(new User());
        if (Settings::get('player_login', '0', 'Store')) {
            // Customer will need to enter minecraft username to buy the products for
            $this->_recipient = new Customer();
        } else {
            // Customer will buy the products for them self
            $this->_recipient = $this->_customer;
        }

        // Load shopping cart
        $this->_items = new ItemList();
        if (!Session::exists('shopping_cart')) {
            return;
        }
        $shopping_cart = Session::get('shopping_cart');

        // Get current mode
        if (isset($shopping_cart['subscription_mode'])) {
            $this->_subscription_mode = $_SESSION['shopping_cart']['subscription_mode'];
        }

        // Get items mode
        $items = $shopping_cart['items'] ?? [];
        if (count($items)) {
            // Get active coupon
            if (isset($shopping_cart['coupon_id'])) {
                $coupon = new Coupon($shopping_cart['coupon_id']);
                if ($coupon->exists()) {
                    $this->_coupon = $coupon;
                }
            }

            // Get products
            $payment_type = $this->isSubscriptionMode() ? '2,3' : '1,3';
            $products_ids = implode(',', array_keys($items));
            $products_query = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE id in ('.$products_ids.') AND disabled = 0 AND deleted = 0 AND payment_type IN ('.$payment_type.')')->results();
            foreach ($products_query as $item) {
                $product = new Product(null, null, $item);

                EventHandler::executeEvent('renderStoreProduct', [
                    'product' => $product,
                    'name' => $product->data()->name,
                    'content' => $product->data()->description,
                    'image' => (isset($product->data()->image) && !is_null($product->data()->image) ? (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/' . 'uploads/store/' . Output::getClean(Output::getDecoded($product->data()->image))) : null),
                    'link' => URL::build(Store::getStorePath() . '/checkout', 'add=' . Output::getClean($product->data()->id)),
                    'hidden' => false,
                    'shopping_cart' => $this
                ]);

                // Add item to item list
                $item = $items[$product->data()->id];
                $this->_items->addItem(new Item(
                    0,
                    $product,
                    $item['quantity'],
                    $item['fields']
                ));
            }
        }
    }

    // Add product to shopping cart
    public function add(int $product_id, int $quantity = 1, array $fields = []): void {
        $shopping_cart = (isset($_SESSION['shopping_cart']) ? $_SESSION['shopping_cart'] : []);

        if ($this->_subscription_mode) {
            // Only allow 1 item in subscription mode
            $shopping_cart['items'] = [];
        }

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
    }

    // Clear the shopping cart
    public function clear(): void {
        unset($_SESSION['shopping_cart']);
    }

    // Get the item list from the shopping cart
    public function items(): ItemList {
        return $this->_items;
    }

    // Set order for this shopping cart
    public function setOrder(?Order $order) {
        $this->_order = $order;

        if ($order != null) {
            $_SESSION['shopping_cart']['order_id'] = $order->data()->id;
        } else {
            unset($_SESSION['shopping_cart']['order_id']);
        }
    }

    // Get current active order.
    public function getOrder(): ?Order {
        return $this->_order;
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

    // Set shopping cart subscription mode
    public function setSubscriptionMode(bool $subscription_mode) {
        if ($this->_subscription_mode != $subscription_mode) {
            $subscription_mode = false;
            $this->_subscription_mode = $subscription_mode;

            $_SESSION['shopping_cart']['subscription_mode'] = $subscription_mode;
            $_SESSION['shopping_cart']['items'] = [];
        }
    }

    // Get current shopping cart subscription mode
    public function isSubscriptionMode(): bool {
        return $this->_subscription_mode;
    }

    // Get active coupon code
    public function getCoupon(): ?Coupon {
        return $this->_coupon;
    }

    // Get the paying customer.
    public function getCustomer(): Customer {
        return $this->_customer;
    }

    // Recipient that receive the products.
    public function getRecipient(): Customer {
        return $this->_recipient;
    }

    // Get total price to pay in cents
    public function getTotalCents(): int {
        $price = 0;

        foreach ($this->items()->getItems() as $item) {
            $price += $item->getSubtotalPrice();
        }

        return $price;
    }

    // Get total real price in cents
    public function getTotalRealPriceCents(): int {
        $price = 0;

        foreach ($this->items()->getItems() as $item) {
            $price += $item->getTotalPrice();
        }

        return $price;
    }

    // Get total discount in cents
    public function getTotalDiscountCents(): int {
        $discount = 0;

        foreach ($this->items()->getItems() as $item) {
            $discount += $item->getTotalDiscounts();
        }

        return $discount;
    }
}