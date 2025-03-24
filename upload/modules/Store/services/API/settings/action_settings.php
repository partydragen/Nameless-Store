<?php
// Create or update action
if (Input::exists()) {
    $errors = [];

    if (Token::check(Input::get('token'))) {
        // New Action
        $validation = Validate::check($_POST, [
            'trigger' => [
                Validate::REQUIRED => true,
                Validate::IN => [1,2,3,4,5],
            ],
            'http_type' => [
                Validate::REQUIRED => true,
            ],
            'http_url' => [
                Validate::REQUIRED => true,
                Validate::MIN => 11
            ],
            'http_headers' => [
                Validate::MAX => 10000
            ],
            'http_body' => [
                Validate::MAX => 10000
            ],
        ])->messages([
            'trigger' => [
                Validate::IN => 'Invalid Trigger'
            ]
        ]);

        if ($validation->passed()) {
            $command = [
                'http_type' => Input::get('http_type'),
                'http_url' => Input::get('http_url'),
                'http_headers' => Input::get('http_headers'),
                'http_body' => Input::get('http_body'),
            ];

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

                DB::getInstance()->insert('store_products_actions', [
                    'product_id' => $product != null ? $product->data()->id : null,
                    'type' => Input::get('trigger'),
                    'service_id' => $service->getId(),
                    'command' => json_encode($command),
                    'require_online' => 0,
                    'order' => $last_order + 1,
                    'own_connections' => 0,
                    'each_quantity' => $each_quantity,
                    'each_product' => $each_product,
                ]);

                Session::flash('products_success', $store_language->get('admin', 'action_created_successfully'));
            } else {
                // Update existing action
                $action->update([
                    'type' => Input::get('trigger'),
                    'command' => json_encode($command),
                    'require_online' => 0,
                    'own_connections' => 0,
                    'each_quantity' => $each_quantity,
                    'each_product' => $each_product,
                ]);

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
    // Creating new action
    $template->getEngine()->addVariables([
        'TRIGGER_VALUE' => ((isset($_POST['trigger'])) ? Output::getClean($_POST['trigger']) : 1),
        'HTTP_TYPE_VALUE' => ((isset($_POST['http_type']) && $_POST['http_type']) ? Output::getClean($_POST['http_type']) : ''),
        'HTTP_URL_VALUE' => ((isset($_POST['http_url']) && $_POST['http_url']) ? Output::getClean($_POST['http_url']) : ''),
        'HTTP_HEADERS_VALUE' => ((isset($_POST['http_headers']) && $_POST['http_headers']) ? Output::getClean($_POST['http_headers']) : ''),
        'HTTP_BODY_VALUE' => ((isset($_POST['http_body']) && $_POST['http_body']) ? Output::getClean($_POST['http_body']) : ''),
        'EACH_QUANTITY_VALUE' => 1,
        'EACH_PRODUCT_VALUE' => 1,
    ]);
} else {
    // Updating action
    $command = json_decode($action->data()->command, true);

    $template->getEngine()->addVariables([
        'TRIGGER_VALUE' => Output::getClean($action->data()->type),
        'HTTP_TYPE_VALUE' => Output::getClean($command['http_type']),
        'HTTP_URL_VALUE' => Output::getClean($command['http_url']),
        'HTTP_HEADERS_VALUE' => Output::getClean($command['http_headers']),
        'HTTP_BODY_VALUE' => Output::getClean($command['http_body']),
        'EACH_QUANTITY_VALUE' => $action->data()->each_quantity,
        'EACH_PRODUCT_VALUE' => $action->data()->each_product,
    ]);
}

$template->getEngine()->addVariables([
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/API/settings/action_settings.tpl',
    'ALL_ROLES' => $roles,
    'BODY_JSON' => Output::getClean('{"username":"{username}", "products":"{orderProducts}", "referral":"{referralUser}"}')
]);