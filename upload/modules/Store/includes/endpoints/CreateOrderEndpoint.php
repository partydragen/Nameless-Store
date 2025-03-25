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

        $items = new ItemList();
        foreach ($_POST['products'] as $item) {
            $product = new Product($item['id']);
            if ($product->exists()) {
                $items->addItem(new Item(
                    0,
                    $product,
                    1,
                    $item['fields'] ?? []
                ));
            }
        }

        if (!count($items->getItems())) {
            $api->throwError('store:cannot_find_products');
        }

        $order = new Order();
        $order->create($user, $customer, $recipient, $items);

        $api->returnArray(['id' => $order->data()->id]);
    }

    private function transformUser(Nameless2API $api, string $value) {
        return Endpoints::getAllTransformers()['user']['transformer']($api, $value);
    }
}