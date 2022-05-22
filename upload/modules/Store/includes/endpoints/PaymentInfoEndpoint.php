<?php
class PaymentInfoEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'payment';
        $this->_module = 'Store';
        $this->_description = 'Payment info';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api): void {
        $api->validateParams($_GET, ['id']);

        // Get payment
        $payment = new Payment($_GET['id'], 'id');
        if (!$payment->exists()) {
            $api->throwError(251, 'Payment not found');
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
                'id' => $product->data()->id,
                'name' => $product->data()->name,
                'quantity' => 1,
                'fields' => $fields_array
            ];
        }

        $customer = $order->customer();
        $customer_array = [
            'user_id' => $customer->exists() ? $customer->data()->user_id ?? 0 : 0,
            'username' => $customer->getUsername(),
            'identifier' => $customer->getIdentifier(),
        ];

        $recipient = $order->recipient();
        $recipient_array = [
            'user_id' => $recipient->exists() ? $recipient->data()->user_id ?? 0 : 0,
            'username' => $recipient->getUsername(),
            'identifier' => $recipient->getIdentifier(),
        ];

        $return = [
            'id' => $payment->data()->id,
            'order_id' => $payment->data()->order_id,
            'gateway_id' => $payment->data()->gateway_id,
            'transaction' => $payment->data()->transaction,
            'amount' => $payment->data()->amount,
            'currency' => $payment->data()->currency,
            'fee' => $payment->data()->fee,
            'status_id' => $payment->data()->status_id,
            'created' => $payment->data()->created,
            'last_updated' => $payment->data()->last_updated,
            'customer' => $customer_array,
            'recipient' => $recipient_array,
            'products' => $products
        ];

        $api->returnArray($return);
    }
}