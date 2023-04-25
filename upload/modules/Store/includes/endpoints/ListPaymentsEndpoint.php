<?php
class ListPaymentsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'store/payments';
        $this->_module = 'Store';
        $this->_description = 'List all payments';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api): void {
        $query = 'SELECT p.*, o.from_customer_id, o.to_customer_id FROM nl2_store_payments AS p LEFT JOIN nl2_store_orders AS o ON order_id=o.id';
        $where = ' WHERE p.id <> 0';
        $order = ' ORDER BY `created` DESC';
        $limit = '';
        $params = [];

        if (isset($_GET['order'])) {
            $where .= ' AND order_id = ?';
            array_push($params, $_GET['order']);
        }

        if (isset($_GET['gateway'])) {
            $where .= ' AND gateway_id = ?';
            array_push($params, $_GET['gateway']);
        }

        if (isset($_GET['status'])) {
            $where .= ' AND status_id = ?';
            array_push($params, $_GET['status']);
        }

        if (isset($_GET['customer'])) {
            $where .= ' AND from_customer_id = ?';
            array_push($params, $_GET['customer']);
        }

        if (isset($_GET['recipient'])) {
            $where .= ' AND to_customer_id = ?';
            array_push($params, $_GET['recipient']);
        }

        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
            $limit .= ' LIMIT '. $_GET['limit'];
        }

        // Ensure the user exists
        $payments_query = $api->getDb()->query($query . $where . $order . $limit, $params)->results();

        $payments_list = [];
        foreach ($payments_query as $payment) {
            $customer = new Customer(null, $payment->from_customer_id);
            $recipient = new Customer(null, $payment->to_customer_id);

            $products = [];
            $products_query = $api->getDb()->query('SELECT product_id, name FROM nl2_store_orders_products LEFT JOIN nl2_store_products ON product_id=nl2_store_products.id WHERE order_id = ?', [$payment->order_id])->results();
            foreach ($products_query as $product) {
                $products[] = [
                    'id' => (int)$product->product_id,
                    'name' => $product->name
                ];
            }

            $payments_list[] = [
                'id' => (int)$payment->id,
                'order_id' => (int)$payment->order_id,
                'gateway_id' => (int)$payment->gateway_id,
                'transaction' => $payment->transaction,
                'amount' => Store::fromCents($payment->amount_cents), // Deprecated
                'amount_cents' => (int) $payment->amount_cents ?? 0,
                'currency' => $payment->currency,
                'fee' => Store::fromCents($payment->fee_cents ?? 0), // Deprecated
                'fee_cents' => (int) $payment->fee_cents ?? 0,
                'status_id' => (int)$payment->status_id,
                'created' => (int)$payment->created,
                'last_updated' => (int)$payment->last_updated,
                'customer' => [
                    'customer_id' => (int)$payment->from_customer_id,
                    'user_id' => $customer->exists() ? (int)$customer->data()->user_id : null,
                    'username' => $customer->exists() ? $customer->data()->username : null,
                    'identifier' => $customer->exists() ? $customer->data()->identifier : null
                ],
                'recipient' => [
                    'customer_id' => (int)$payment->to_customer_id,
                    'user_id' => $recipient->exists() ? (int)$recipient->data()->user_id : null,
                    'username' => $recipient->exists() ? $recipient->data()->username : null,
                    'identifier' => $recipient->exists() ? $recipient->data()->identifier : null
                ],
                'products' => $products
            ];
        }
 
        $api->returnArray(['payments' => $payments_list]);
    }
}
