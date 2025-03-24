<?php

if (Input::exists()) {
    $errors = [];

    if (Token::check(Input::get('token'))) {
        // New Action
        $validation = Validate::check($_POST, [
            'command' => [
                Validate::REQUIRED => true,
                Validate::MIN => 1,
                Validate::MAX => 500
            ],
            'trigger' => [
                Validate::REQUIRED => true,
                Validate::IN => [1,2,3,4,5],
            ]
        ])->messages([
            'command' => [
                Validate::REQUIRED => $store_language->get('admin', 'command_required'),
                Validate::MIN => $store_language->get('admin', 'command_min'),
                Validate::MAX => $store_language->get('admin', 'command_max')
            ],
            'trigger' => [
                Validate::IN => 'Invalid Trigger'
            ]
        ]);

        if ($validation->passed()) {
            $selected_connections = (isset($_POST['connections']) && is_array($_POST['connections']) ? $_POST['connections'] : []);

            // Run for each quantity?
            if (isset($_POST['each_quantity']) && $_POST['each_quantity'] == 'on') $each_quantity = 1;
            else $each_quantity = 0;

            // Run for each product?
            if (isset($_POST['each_product']) && $_POST['each_product'] == 'on') $each_product = 1;
            else $each_product = 0;

            if (!$action->exists()) {
                // Create new action
                $last_order = DB::getInstance()->query('SELECT `order` FROM nl2_store_products_actions ORDER BY `order` DESC LIMIT 1')->results();
                if (count($last_order)) $last_order = $last_order[0]->order;
                else $last_order = 0;

                // Save to database
                DB::getInstance()->insert('store_products_actions', [
                    'product_id' => $product != null ? $product->data()->id : null,
                    'type' => Input::get('trigger'),
                    'service_id' => $service->getId(),
                    'command' => Input::get('command'),
                    'require_online' => 0,
                    'order' => $last_order + 1,
                    'own_connections' => (in_array(0, $selected_connections) ? 0 : 1),
                    'each_quantity' => $each_quantity,
                    'each_product' => $each_product,
                ]);
                $lastId = DB::getInstance()->lastId();

                // Handle selected connections if its use own connection list
                if (!in_array(0, $selected_connections)) {
                    $action = new Action($service, $lastId);
                    foreach ($selected_connections as $connection) {
                        $action->addConnection($connection);
                    }
                }

                Session::flash('products_success', $store_language->get('admin', 'action_created_successfully'));
            } else {
                // Update existing action
                $action->update([
                    'type' => Input::get('trigger'),
                    'command' => Input::get('command'),
                    'require_online' => 0,
                    'own_connections' => (in_array(0, $selected_connections) ? 0 : 1),
                    'each_quantity' => $each_quantity,
                    'each_product' => $each_product,
                ]);

                // Handle selected connections if its use own connection list
                if (!in_array(0, $selected_connections)) {
                    // Check for new connections to give action which they dont already have
                    foreach ($selected_connections as $connection) {
                        if (!array_key_exists($connection, $action->getConnections())) {
                            $action->addConnection($connection);
                        }
                    }

                    // Check for connections they had, but werent in the $_POST connections
                    foreach ($action->getConnections() as $connection) {
                        if (!in_array($connection->id, $selected_connections)) {
                            $action->removeConnection($connection->id);
                        }
                    }
                }

                Session::flash('products_success', $store_language->get('admin', 'action_updated_successfully'));
            }

            // Redirect to right page
            if ($product != null) {
                Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
            } else {
                Redirect::to(URL::build('/panel/store/actions/'));
            }
        } else {
            $errors = $validation->errors();
        }
    } else {
        // Invalid token
        $errors[] = $language->get('general', 'invalid_token');
    }
}

if (!$action->exists()) {
    // Connections
    $connections = DB::getInstance()->query('SELECT * FROM nl2_store_connections WHERE service_id = ?', [$service->getId()])->results();
    $connections_array[] = [
        'id' => 0,
        'name' => 'Execute on all RCON connections selected on product',
        'selected' => ((isset($_POST['connections']) && is_array($_POST['connections'])) ? in_array(0, $_POST['connections']) : true)
    ];
    foreach ($connections as $connection) {
        $connections_array[] = [
            'id' => Output::getClean($connection->id),
            'name' => Output::getClean($connection->name),
            'selected' => ((isset($_POST['connections']) && is_array($_POST['connections'])) ? in_array($connection->id, $_POST['connections']) : false)
        ];
    }

    $template->getEngine()->addVariables([
        'TRIGGER_VALUE' => ((isset($_POST['trigger'])) ? Output::getClean($_POST['trigger']) : 1),
        'REQUIRE_PLAYER_VALUE' => ((isset($_POST['requirePlayer'])) ? Output::getClean($_POST['requirePlayer']) : 1),
        'COMMAND_VALUE' => ((isset($_POST['command']) && $_POST['command']) ? Output::getClean($_POST['command']) : ''),
        'CONNECTIONS_LIST' => $connections_array,
        'EACH_QUANTITY_VALUE' => 1,
        'EACH_PRODUCT_VALUE' => 1,
    ]);

} else {
    // Connections
    $connections_array = [];
    $selected_connections = ($action->data()->own_connections ? $action->getConnections() : []);

    $connections = DB::getInstance()->query('SELECT * FROM nl2_store_connections WHERE service_id = ?', [$service->getId()])->results();
    $connections_array[] = [
        'id' => 0,
        'name' => 'Execute on all RCON connections selected on product',
        'selected' => !$action->data()->own_connections
    ];
    foreach ($connections as $connection) {
        $connections_array[] = [
            'id' => Output::getClean($connection->id),
            'name' => Output::getClean($connection->name),
            'selected' => (array_key_exists($connection->id, $selected_connections))
        ];
    }

    $template->getEngine()->addVariables([
        'TRIGGER_VALUE' => Output::getClean($action->data()->type),
        'REQUIRE_PLAYER_VALUE' => Output::getClean($action->data()->require_online),
        'COMMAND_VALUE' => Output::getClean($action->data()->command),
        'CONNECTIONS_LIST' => $connections_array,
        'EACH_QUANTITY_VALUE' => $action->data()->each_quantity,
        'EACH_PRODUCT_VALUE' => $action->data()->each_product,
    ]);
}

$template->getEngine()->addVariables([
    'SERVICE_CONNECTIONS' => $store_language->get('admin', 'service_connections'),
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/RCON/settings/action_settings.tpl'
]);