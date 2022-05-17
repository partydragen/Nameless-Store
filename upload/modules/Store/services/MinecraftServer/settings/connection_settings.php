<?php
if ($_GET['action'] == 'new') {
    // Create new connection
    if (Input::exists()) {
        $errors = [];

        if (Token::check(Input::get('token'))) {
            // Update product
            $validate = new Validate();
            $validation = $validate->check($_POST, [
                'name' => [
                    Validate::REQUIRED => true,
                    Validate::MIN => 1,
                    Validate::MAX => 64
                ]
            ])->messages([
                'name' => [
                    Validate::REQUIRED => $store_language->get('admin', 'name_required'),
                    Validate::MIN => str_replace('{min}', '1', $store_language->get('admin', 'name_minimum_x')),
                    Validate::MAX => str_replace('{max}', '64', $store_language->get('admin', 'name_maximum_x'))
                ]
            ]);

            if ($validation->passed()) {
                $queries->create('store_connections', [
                    'name' => Output::getClean(Input::get('name')),
                    'service_id' => $service->getId()
                ]);

                Session::flash('connections_success', $store_language->get('admin', 'connection_updated_successfully'));
                Redirect::to(URL::build('/panel/store/connections'));
                die();
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
        'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/MinecraftServer/settings/connection_settings.tpl'
    ]);
} else {
    // Editing connection
    if (Input::exists()) {
        $errors = [];

        if (Token::check(Input::get('token'))) {
            // Update product
            $validate = new Validate();
            $validation = $validate->check($_POST, [
                'name' => [
                    Validate::REQUIRED => true,
                    Validate::MIN => 1,
                    Validate::MAX => 64
                ]
            ])->messages([
                'name' => [
                    Validate::REQUIRED => $store_language->get('admin', 'name_required'),
                    Validate::MIN => str_replace('{min}', '1', $store_language->get('admin', 'name_minimum_x')),
                    Validate::MAX => str_replace('{max}', '64', $store_language->get('admin', 'name_maximum_x'))
                ]
            ]);

            if ($validation->passed()) {
                $queries->update('store_connections', $connection->id, [
                    'name' => Output::getClean(Input::get('name'))
                ]);

                Session::flash('connections_success', $store_language->get('admin', 'connection_updated_successfully'));
                Redirect::to(URL::build('/panel/store/connections'));
                die();
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
        'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/MinecraftServer/settings/connection_settings.tpl'
    ]);

}