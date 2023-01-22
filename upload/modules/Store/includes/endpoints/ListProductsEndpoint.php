<?php
class ListProductsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'store/products';
        $this->_module = 'Store';
        $this->_description = 'List all products or in a category';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api): void {
        $query = 'SELECT * FROM nl2_store_products';
        $where = ' WHERE deleted = 0';
        $order = ' ORDER BY `order` ASC';
        $limit = '';
        $params = [];

        if (isset($_GET['product'])) {
            $where .= ' AND id = ?';
            array_push($params, $_GET['product']);
        }

        if (isset($_GET['category_id'])) {
            $where .= ' AND category_id = ?';
            array_push($params, $_GET['category_id']);
        }

        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
            $limit .= ' LIMIT '. $_GET['limit'];
        }

        // Ensure the user exists
        $products_query = $api->getDb()->query($query . $where . $order . $limit, $params)->results();

        $products_array = [];
        foreach ($products_query as $item) {
            $product = new Product(null, null, $item);

            $global_limit_json = json_decode($product->data()->global_limit, true) ?? [];
            $user_limit_json = json_decode($product->data()->user_limit, true) ?? [];

            $required_products_list = [];
            $required_products = json_decode($product->data()->required_products, true) ?? [];
            foreach ($required_products as $item) {
                $required_products_list[] = (int) $item;
            }

            $required_groups_list = [];
            $required_groups = json_decode($product->data()->required_groups, true) ?? [];
            foreach ($required_groups as $item) {
                $required_groups_list[] = (int) $item;
            }

            $required_integrations_list = [];
            $required_integrations = json_decode($product->data()->required_integrations, true) ?? [];
            foreach ($required_integrations as $item) {
                $required_integrations_list[] = (int) $item;
            }

            $product_array = [
                'id' => (int) $product->data()->id,
                'category_id' => (int) $product->data()->category_id,
                'name' => $product->data()->name,
                'price' => (double) Store::fromCents($product->data()->price_cents),
                'price_cents' => (int) $product->data()->price_cents,
                'hidden' => (bool) $product->data()->hidden,
                'disabled' => (bool) $product->data()->disabled,
                'global_limit' => [
                    'limit' => (int) $global_limit_json['limit'] ?? 0,
                    'interval' => (int) $global_limit_json['interval'] ?? 1,
                    'period' => $global_limit_json['period'] ?? 'no_period'
                ],
                'user_limit' => [
                    'limit' => (int) $user_limit_json['limit'] ?? 0,
                    'interval' => (int) $user_limit_json['interval'] ?? 1,
                    'period' => $user_limit_json['period'] ?? 'no_period'
                ],
                'required_products' => $required_products_list,
                'required_groups' => $required_groups_list,
                'required_integrations' => $required_integrations_list
            ];

            // Show description if description param is true
            if (isset($_GET['description']) && $_GET['description'] == 'true') {
                $product_array['description'] = $product->data()->description;
            }

            // List all fields if actions param is true
            if (isset($_GET['fields']) && $_GET['fields'] == 'true') {
                $fields = [];
                foreach ($product->getFields() as $field) {
                    $fields[] = [
                        'id' => (int) $field->id,
                        'identifier' => $field->identifier,
                        'type' => $field->type,
                        'required' => (bool) $field->required,
                        'min' => (int) $field->min,
                        'max' => (int) $field->max,
                        'regex' => $field->regex,
                        'default_value' => $field->default_value
                    ];
                }
                $product_array['fields'] = $fields;
            }

            // List all actions if actions param is true
            if (isset($_GET['actions']) && $_GET['actions'] == 'true') {
                $actions = [];
                foreach ($product->getActions() as $action) {
                    $actions[] = [
                        'id' => (int) $action->data()->id,
                        'type' => (int) $action->data()->type,
                        'service_id' => (int) $action->data()->service_id,
                        'command' => $action->data()->command,
                        'require_online' => (bool) $action->data()->require_online,
                        'own_connections' => (bool) $action->data()->own_connections,
                    ];
                }
                $product_array['actions'] = $actions;
            }

            $products_array[] = $product_array;
        }
 
        $api->returnArray(['products' => $products_array]);
    }
}
