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
                ]
            ])->messages([
                'name' => [
                    Validate::REQUIRED => $store_language->get('admin', 'name_required'),
                    Validate::MIN => $store_language->get('admin', 'name_minimum_x', ['min' => '1']),
                    Validate::MAX => $store_language->get('admin', 'name_maximum_x', ['max' => '64'])
                ]
            ]);

            if ($validation->passed()) {
                DB::getInstance()->insert('store_connections', [
                    'name' => Input::get('name'),
                    'service_id' => $service->getId()
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
        'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/MinecraftServer/settings/connection_settings.tpl'
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
                ]
            ])->messages([
                'name' => [
                    Validate::REQUIRED => $store_language->get('admin', 'name_required'),
                    Validate::MIN => $store_language->get('admin', 'name_minimum_x', ['min' => '1']),
                    Validate::MAX => $store_language->get('admin', 'name_maximum_x', ['max' => '64'])
                ]
            ]);

            if ($validation->passed()) {
                DB::getInstance()->update('store_connections', $connection->id, [
                    'name' => Input::get('name')
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