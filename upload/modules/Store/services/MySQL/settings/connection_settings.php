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
                'db_address' => [
                    Validate::REQUIRED => true,
                ],
                'db_port' => [
                    Validate::REQUIRED => true,
                ],
                'db_username' => [
                    Validate::REQUIRED => true,
                ],
                'db_name' => [
                    Validate::REQUIRED => true,
                ],
                'db_password' => [
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
                    'address' => Input::get('db_address'),
                    'port' => (isset($_POST['db_port']) && !empty($_POST['db_port']) && is_numeric($_POST['db_port'])) ? $_POST['db_port'] : 3306,
                    'database' => Input::get('db_name'),
                    'username' => Input::get('db_username'),
                    'password' => Input::get('db_password'),
                ];

                DB::getInstance()->insert('store_connections', [
                    'name' => Input::get('name'),
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
        'DB_HOST_VALUE' => ((isset($_POST['db_address']) && $_POST['db_address']) ? Output::getClean($_POST['db_address']) : ''),
        'DB_PORT_VALUE' => ((isset($_POST['db_port']) && $_POST['db_port']) ? Output::getClean($_POST['db_port']) : '3306'),
        'DB_DATABASE_VALUE' => ((isset($_POST['db_name']) && $_POST['db_name']) ? Output::getClean($_POST['db_name']) : ''),
        'DB_USERNAME_VALUE' => ((isset($_POST['db_username']) && $_POST['db_username']) ? Output::getClean($_POST['db_username']) : ''),
        'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/MySQL/settings/connection_settings.tpl'
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
                'db_address' => [
                    Validate::REQUIRED => true,
                ],
                'db_port' => [
                    Validate::REQUIRED => true,
                ],
                'db_username' => [
                    Validate::REQUIRED => true,
                ],
                'db_name' => [
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
                if (isset($_POST['db_password']) && !empty($_POST['db_password'])) {
                    $password = $_POST['db_password'];
                } else {
                    $data = json_decode($connection->data);

                    if (isset($data->password) && !empty($data->password)) {
                        $password = $data->password;
                    } else {
                        $password = '';
                    }
                }

                $data = [
                    'address' => Output::getClean(Input::get('db_address')),
                    'port' => (isset($_POST['db_port']) && !empty($_POST['db_port']) && is_numeric($_POST['db_port'])) ? $_POST['db_port'] : 3306,
                    'database' => Input::get('db_name'),
                    'username' => Input::get('db_username'),
                    'password' => $password,
                ];

                DB::getInstance()->update('store_connections', $connection->id, [
                    'name' => Input::get('name'),
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
        'DB_HOST_VALUE' => Output::getClean($data->address),
        'DB_PORT_VALUE' => Output::getClean($data->port),
        'DB_DATABASE_VALUE' => Output::getClean($data->database),
        'DB_USERNAME_VALUE' => Output::getClean($data->username),
        'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/MySQL/settings/connection_settings.tpl'
    ]);

}