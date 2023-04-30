<?php
if ($_GET['action'] == 'new_action') {
    // Creating new action
    if (Input::exists()) {
        $errors = [];

        if (Token::check(Input::get('token'))) {
            // New Action
            $validation = Validate::check($_POST, [
                'command' => [
                    Validate::REQUIRED => true,
                    Validate::MIN => 1,
                    Validate::MAX => 500
                ]
           ])->messages([
                'command' => [
                    Validate::REQUIRED => $store_language->get('admin', 'command_required'),
                    Validate::MIN => $store_language->get('admin', 'command_min'),
                    Validate::MAX => $store_language->get('admin', 'command_max')
                ]
            ]);

            if ($validation->passed()) {
                $trigger = Input::get('trigger');
                if (!in_array($trigger, [1,2,3,4,5])) {
                    $errors[] = 'Invalid Trigger';
                }

                if (!count($errors)) {
                    // Get last order
                    $last_order = DB::getInstance()->query('SELECT id FROM nl2_store_products_actions WHERE product_id = ? ORDER BY `order` DESC LIMIT 1', [$product->id])->results();
                    if (count($last_order)) $last_order = $last_order[0]->order;
                    else $last_order = 0;

                    $selected_connections = (isset($_POST['connections']) && is_array($_POST['connections']) ? $_POST['connections'] : []);

                    // Save to database
                    DB::getInstance()->insert('store_products_actions', [
                        'product_id' => $product->data()->id,
                        'type' => $trigger,
                        'service_id' => $service->getId(),
                        'command' => Input::get('command'),
                        'require_online' => 0,
                        'order' => $last_order + 1,
                        'own_connections' => (in_array(0, $selected_connections) ? 0 : 1)
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
                    Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
                }
            } else {
                $errors = $validation->errors();
            }
        } else {
            // Invalid token
            $errors[] = $language->get('general', 'invalid_token');
        }
    }

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

    $smarty->assign([
        'TRIGGER_VALUE' => ((isset($_POST['trigger'])) ? Output::getClean($_POST['trigger']) : 1),
        'REQUIRE_PLAYER_VALUE' => ((isset($_POST['requirePlayer'])) ? Output::getClean($_POST['requirePlayer']) : 1),
        'COMMAND_VALUE' => ((isset($_POST['command']) && $_POST['command']) ? Output::getClean($_POST['command']) : ''),
        'SERVICE_CONNECTIONS' => $store_language->get('admin', 'service_connections'),
        'CONNECTIONS_LIST' => $connections_array,
        'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/RCON/settings/action_settings.tpl'
    ]);

} else {
    // Editing action
    if (Input::exists()) {
        $errors = [];

        if (Token::check(Input::get('token'))) {
            $validation = Validate::check($_POST, [
                'command' => [
                    Validate::REQUIRED => true,
                    Validate::MIN => 1,
                    Validate::MAX => 500
                ]
            ])->messages([
                'command' => [
                    Validate::REQUIRED => $store_language->get('admin', 'command_required'),
                    Validate::MIN => $store_language->get('admin', 'command_min'),
                    Validate::MAX => $store_language->get('admin', 'command_max')
                ]
            ]);

            if ($validation->passed()) {
                $trigger = Input::get('trigger');
                if (!in_array($trigger, [1,2,3,4,5])) {
                    $errors[] = 'Invalid Trigger';
                }

                if (!count($errors)) {
                    $selected_connections = (isset($_POST['connections']) && is_array($_POST['connections']) ? $_POST['connections'] : []);

                    // Save to database
                    $action->update([
                        'type' => $trigger,
                        'command' => Input::get('command'),
                        'require_online' => 0,
                        'own_connections' => (in_array(0, $selected_connections) ? 0 : 1)
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
                    Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
                }
            } else {
                $errors = $validation->errors();
            }
        } else {
            // Invalid token
            $errors[] = $language->get('general', 'invalid_token');
        }
    }

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

    $smarty->assign([
        'TRIGGER_VALUE' => Output::getClean($action->data()->type),
        'REQUIRE_PLAYER_VALUE' => Output::getClean($action->data()->require_online),
        'COMMAND_VALUE' => Output::getClean($action->data()->command),
        'SERVICE_CONNECTIONS' => $store_language->get('admin', 'service_connections'),
        'CONNECTIONS_LIST' => $connections_array,
        'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/RCON/settings/action_settings.tpl'
    ]);
}