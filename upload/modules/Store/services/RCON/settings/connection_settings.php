<?php
if ($_GET['action'] == 'new') {
    // Create new connection
    if (Input::exists()) {
        $errors = [];

        if (Token::check(Input::get('token'))) {
            // Update product
            $validation = Validate::check($_POST, [
                'name' => [
                    Validate::REQUIRED => true,
                    Validate::MIN => 1,
                    Validate::MAX => 64
                ],
                'rcon_address' => [
                    Validate::REQUIRED => true,
                ],
                'rcon_port' => [
                    Validate::REQUIRED => true,
                ],
                'rcon_password' => [
                    Validate::REQUIRED => true,
                ]
            ])->messages([
                'name' => [
                    Validate::REQUIRED => $store_language->get('admin', 'name_required'),
                    Validate::MIN => str_replace('{min}', '1', $store_language->get('admin', 'name_minimum_x')),
                    Validate::MAX => str_replace('{max}', '64', $store_language->get('admin', 'name_maximum_x'))
                ]
            ]);

            if ($validation->passed()) {
                $data = [
                    'address' => Output::getClean(Input::get('rcon_address')),
                    'port' => (isset($_POST['rcon_port']) && !empty($_POST['rcon_port']) && is_numeric($_POST['rcon_port'])) ? $_POST['rcon_port'] : '',
                    'password' => Input::get('rcon_password'),
                    'timeout' => 1
                ];

                DB::getInstance()->insert('store_connections', [
                    'name' => Output::getClean(Input::get('name')),
                    'service_id' => $service->getId(),
                    'data' => json_encode($data)
                ]);

                Session::flash('connections_success', $store_language->get('admin', 'connection_updated_successfully'));
                Redirect::to(URL::build('/panel/store/connections'));
            } else {
                // Errors
                $errors = $validation->errors();
            }
        } else {
            // Invalid token
            $errors[] = $language->get('general', 'invalid_token');
        }
    }

    $smarty->assign([
        'NAME' => $language->get('admin', 'name'),
        'NAME_VALUE' => Output::getClean($connection->name),
        'RCON_HOST_VALUE' => ((isset($_POST['rcon_address']) && $_POST['rcon_address']) ? Output::getClean($_POST['rcon_address']) : ''),
        'RCON_PORT_VALUE' => ((isset($_POST['rcon_port']) && $_POST['rcon_port']) ? Output::getClean($_POST['rcon_port']) : ''),
        'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/RCON/settings/connection_settings.tpl'
    ]);
} else {
    // Editing connection
    if (Input::exists()) {
        $errors = [];

        if (Token::check(Input::get('token'))) {
            // Update product
            $validation = Validate::check($_POST, [
                'name' => [
                    Validate::REQUIRED => true,
                    Validate::MIN => 1,
                    Validate::MAX => 64
                ],
                'rcon_address' => [
                    Validate::REQUIRED => true,
                ],
                'rcon_port' => [
                    Validate::REQUIRED => true,
                ]
            ])->messages([
                'name' => [
                    Validate::REQUIRED => $store_language->get('admin', 'name_required'),
                    Validate::MIN => str_replace('{min}', '1', $store_language->get('admin', 'name_minimum_x')),
                    Validate::MAX => str_replace('{max}', '64', $store_language->get('admin', 'name_maximum_x'))
                ]
            ]);

            if ($validation->passed()) {
                if (isset($_POST['rcon_password']) && !empty($_POST['rcon_password'])) {
                    $password = $_POST['rcon_password'];
                } else {
                    $data = json_decode($connection->data);

                    if (isset($data->password) && !empty($data->password)) {
                        $password = $data->password;
                    } else {
                        $password = '';
                    }
                }

                $data = [
                    'address' => Output::getClean(Input::get('rcon_address')),
                    'port' => (isset($_POST['rcon_port']) && !empty($_POST['rcon_port']) && is_numeric($_POST['rcon_port'])) ? $_POST['rcon_port'] : '',
                    'password' => $password,
                    'timeout' => 1
                ];

                DB::getInstance()->update('store_connections', $connection->id, [
                    'name' => Output::getClean(Input::get('name')),
                    'data' => json_encode($data)
                ]);

                Session::flash('connections_success', $store_language->get('admin', 'connection_updated_successfully'));
            } else {
                // Errors
                $errors = $validation->errors();
            }
        } else {
            // Invalid token
            $errors[] = $language->get('general', 'invalid_token');
        }
    }

    $data = json_decode($connection->data);

    $smarty->assign([
        'NAME' => $language->get('admin', 'name'),
        'NAME_VALUE' => Output::getClean($connection->name),
        'RCON_HOST_VALUE' => Output::getClean($data->address),
        'RCON_PORT_VALUE' => Output::getClean($data->port),
        'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/RCON/settings/connection_settings.tpl'
    ]);

}