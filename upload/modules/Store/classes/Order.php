<?php
/**
 * Order class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.2.0
 * @license MIT
 */
class Order {

    private $_db,
            $_data;

    /**
     * @var ItemList Lists of all items for this order.
     */
    private ItemList $_items;

    /**
     * @var Amount The amount to charge.
     */
    private Amount $_amount;

    /**
     * @var bool Whenever this order is a subscription during checkout flow.
     */
    private bool $_subscription = false;

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
    public function data(): object {
        return $this->_data;
    }

    /**
     * Get the items list for this order.
     *
     * @return ItemList Lists of all items for this order.
     */
    public function items(): ItemList {
        return $this->_items ??= (function (): ItemList {
            $items = new ItemList();
            $amount = 0;

            $products_query = $this->_db->query('SELECT nl2_store_products.*, nl2_store_orders_products.quantity, nl2_store_orders_products.id AS item_id, amount_cents FROM nl2_store_orders_products INNER JOIN nl2_store_products ON nl2_store_products.id=product_id WHERE order_id = ?', [$this->data()->id]);
            if ($products_query->count()) {
                $products_query = $products_query->results();

                foreach ($products_query as $data) {
                    $product = new Product(null, null, $data);

                    $fields = [];
                    $fields_query = $this->_db->query('SELECT field_id, identifier, value FROM nl2_store_orders_products_fields INNER JOIN nl2_store_fields ON field_id=nl2_store_fields.id WHERE order_id = ? AND product_id = ?', [$this->data()->id, $product->data()->id])->results();
                    foreach ($fields_query as $field) {
                        $fields[$field->identifier] = [
                            'field_id' => $field->field_id,
                            'identifier' => Output::getClean($field->identifier),
                            'value' => Output::getClean($field->value)
                        ];
                    }

                    $item = new Item($data->item_id, $product, $data->quantity, $fields);
                    $amount += $data->amount_cents * $data->quantity;

                    $items->addItem($item);
                }
            }

            $this->_amount = new Amount();
            $this->_amount->setTotalCents($amount);
            $this->_amount->setCurrency(Store::getCurrency());

            return $items;
        })();
    }

    /**
     * Register the order to database.
     *
     * @param ?User $user The NamelessMC user buying the product.
     * @param Customer $from_customer The customer buying the product.
     * @param Customer $to_customer The customer who is receiving the product.
     * @param ItemList $items The list of items along with custom fields for product
     */
    public function create(?User $user, Customer $from_customer, Customer $to_customer, ItemList $items, ?Coupon $coupon = null): void {
        // Referrals Integration
        $referral_id = null;
        if (Util::isModuleEnabled('Referrals')) {
            if (Session::exists('referral_id')) {
                // Get referral id by session
                $referral_id = Session::get('referral_id');
            } else {
                // Get referral id by registered user
                $referral = new Referral($from_customer->data()->user_id, 'reg_user_id');
                if ($referral->exists()) {
                    $referral_id = $referral->data()->id;
                }
            }
        }

        $this->_db->insert('store_orders', [
            'user_id' => $user != null ? $user->exists() ? $user->data()->id : null : null,
            'from_customer_id' => $from_customer->data()->id,
            'to_customer_id' => $to_customer->data()->id,
            'created' => date('U'),
            'ip' => HttpUtils::getRemoteAddress(),
            'coupon_id' => $coupon != null ? $coupon->data()->id : null,
            'referral_id' => $referral_id
        ]);
        $last_id = $this->_db->lastId();

        // Register products and fields to order
        $this->_items = $items;
        foreach ($items->getItems() as $item) {
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
                    'field_id' => $field['field_id'],
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
        if (!isset($this->_amount)) {
            $this->items();
        }

        return $this->_amount;
    }

    /**
     * Check whether a session cart still describes this unpaid order.
     */
    public function matchesCart(
        ItemList $items,
        ?Coupon $coupon,
        Customer $customer,
        Customer $recipient
    ): bool {
        if (!$this->exists()) {
            return false;
        }

        if (
            (int) $this->data()->from_customer_id !== (int) $customer->data()->id
            || (int) $this->data()->to_customer_id !== (int) $recipient->data()->id
            || (int) ($this->data()->coupon_id ?? 0) !== (int) ($coupon?->data()->id ?? 0)
        ) {
            return false;
        }

        $stored_items = $this->_db->query(
            'SELECT product_id, quantity, amount_cents FROM nl2_store_orders_products WHERE order_id = ? ORDER BY product_id, id',
            [(int) $this->data()->id]
        )->results();
        $cart_items = $items->getItems();

        if (count($stored_items) !== count($cart_items)) {
            return false;
        }

        $cart_by_product = [];
        foreach ($cart_items as $item) {
            $cart_by_product[(int) $item->getProduct()->data()->id] = $item;
        }

        foreach ($stored_items as $stored_item) {
            $product_id = (int) $stored_item->product_id;
            if (!isset($cart_by_product[$product_id])) {
                return false;
            }

            $cart_item = $cart_by_product[$product_id];
            if (
                (int) $stored_item->quantity !== (int) $cart_item->getQuantity()
                || (int) $stored_item->amount_cents !== (int) $cart_item->getSingleQuantityPrice()
            ) {
                return false;
            }
        }

        return true;
    }

    /** A completed, refunded or reversed order must never be reused by checkout. */
    public function isReusable(): bool {
        if (!$this->exists()) {
            return false;
        }

        $final_payment = $this->_db->query(
            'SELECT id FROM nl2_store_payments WHERE order_id = ? AND status_id IN (1, 2, 3) LIMIT 1',
            [(int) $this->data()->id]
        );
        if ($final_payment->count()) {
            return false;
        }

        return !$this->_db->query(
            'SELECT id FROM nl2_store_subscriptions WHERE order_id = ? LIMIT 1',
            [(int) $this->data()->id]
        )->count();
    }

    /**
     * Description of all the product names.
     *
     * @return string Description of all the product names.
     */
    public function getDescription(): string {
        $product_names = '';
        foreach ($this->items()->getItems() as $item) {
            $product_names .= $item->getProduct()->data()->name . ', ';
        }
        $product_names = rtrim($product_names, ', ');

        return $product_names;
    }

    public function setSubscriptionMode(bool $value): void {
        $this->_subscription = $value;
    }

    public function isSubscriptionMode(): bool {
        return $this->_subscription;
    }
}
