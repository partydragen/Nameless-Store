<?php
class CreatePaymentEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'store/payments/create';
        $this->_module = 'Store';
        $this->_description = 'Create payment to order';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api): void {
        $api->validateParams($_POST, ['order', 'amount', 'currency']);

        $order = new Order($_POST['order']);
        if (!$order->exists()) {
            $api->throwError('store:cannot_find_order');
        }

        $payment = new Payment();
        $payment->handlePaymentEvent(Payment::COMPLETED, [
            'order_id' => $order->data()->id,
            'gateway_id' => $_POST['gateway_id'] ?? 0,
            'amount_cents' => Store::toCents($_POST['amount']),
            'transaction' => $_POST['transaction'] ?? null,
            'currency' => $_POST['currency']
        ]);

        $api->returnArray(['id' => $payment->data()->id]);
    }
}