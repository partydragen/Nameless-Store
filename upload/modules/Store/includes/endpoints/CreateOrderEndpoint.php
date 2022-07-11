<?php
class CreateOrderEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'store/order/create';
        $this->_module = 'Store';
        $this->_description = 'Create order';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api): void {
        $api->validateParams($_POST, ['customer', 'recipient', 'products']);

        $user != null;
        if (isset($_POST['user'])) {
            $user = $this::transformUser($api, $_POST['user']);
        }

        $customer = new Customer(null, $_POST['customer']);
        if (!$customer->exists()) {
            $api->throwError('store:cannot_find_customer');
        }

        $recipient = new Customer(null, $_POST['recipient']);
        if (!$recipient->exists()) {
            $api->throwError('store:cannot_find_customer');
        }

        $order = new Order();
        $order->create($user, $customer, $recipient, $_POST['products']);

        $api->returnArray(['id' => $order->data()->id]);
    }

    private function transformUser(Nameless2API $api, string $value) {
        return Endpoints::getAllTransformers()['user']['transformer']($api, $value);
    }
}