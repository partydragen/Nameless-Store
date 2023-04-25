<?php
class PaymentInfoEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'store/payment';
        $this->_module = 'Store';
        $this->_description = 'Payment info';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api): void {
        $api->validateParams($_GET, ['id']);

        // Get payment
        $payment = new Payment($_GET['id'], 'id');
        if (!$payment->exists()) {
            $api->throwError(StoreApiErrors::ERROR_PAYMENT_NOT_FOUND);
        }

        $order = $payment->getOrder();

        $products = [];
        foreach ($order->getProducts() as $product) {
            $fields_array = [];
            $fields = DB::getInstance()->query('SELECT identifier, value FROM nl2_store_orders_products_fields INNER JOIN nl2_store_fields ON field_id=nl2_store_fields.id WHERE order_id = ? AND product_id = ?', [$payment->data()->order_id, $product->data()->id])->results();
            foreach ($fields as $field) {
                $fields_array[] = [
                    'identifier' => Output::getClean($field->identifier),
                    'value' => Output::getClean($field->value)
                ];
            }

            $products[] = [
                'id' => (int)$product->data()->id,
                'name' => $product->data()->name,
                'quantity' => 1,
                'fields' => $fields_array
            ];
        }

        $customer = $order->customer();
        $recipient = $order->recipient();

        $return = [
            'id' => $payment->data()->id,
            'order_id' => $payment->data()->order_id,
            'gateway_id' => $payment->data()->gateway_id,
            'transaction' => $payment->data()->transaction,
            'amount' => Store::fromCents($payment->data()->amount_cents ?? 0), // Deprecated
            'amount_cents' => $payment->data()->amount_cents ?? 0,
            'currency' => $payment->data()->currency,
            'fee' => Store::fromCents($payment->data()->fee_cents ?? 0), // Deprecated
            'fee_cents' => $payment->data()->fee_cents ?? 0,
            'status_id' => $payment->data()->status_id,
            'created' => $payment->data()->created,
            'last_updated' => $payment->data()->last_updated,
            'customer' => [
                'customer_id' => $payment->data()->from_customer_id,
                'user_id' => $customer->exists() ? $customer->data()->user_id ?? 0 : 0,
                'username' => $customer->getUsername(),
                'identifier' => $customer->getIdentifier(),
            ],
            'recipient' => [
                'customer_id' => $payment->data()->to_customer_id,
                'user_id' => $recipient->exists() ? $recipient->data()->user_id ?? 0 : 0,
                'username' => $recipient->getUsername(),
                'identifier' => $recipient->getIdentifier(),
            ],
            'products' => $products
        ];

        $api->returnArray($return);
    }
}