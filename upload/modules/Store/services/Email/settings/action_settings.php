<?php
// Create or update action
if (Input::exists()) {
    $errors = [];

    if (Token::check(Input::get('token'))) {
        // New Action
        $validation = Validate::check($_POST, [
            'subject' => [
                Validate::REQUIRED => true,
                Validate::MAX => 128
            ],
            'content' => [
                Validate::REQUIRED => true,
                Validate::MAX => 65000
            ],
            'trigger' => [
                Validate::REQUIRED => true,
                Validate::IN => [1,2,3,4,5],
            ]
        ])->messages([
            'subject' => [
                Validate::REQUIRED => 'Email subject is required!',
                Validate::MAX => 'Email subject is to long!'
            ],
            'content' => [
                Validate::REQUIRED => 'Email content is required!',
                Validate::MAX => 'Email content is to long!'
            ],
            'trigger' => [
                Validate::IN => 'Invalid Trigger'
            ]
        ]);

        if ($validation->passed()) {
            // Run for each quantity?
            if (isset($_POST['each_quantity']) && $_POST['each_quantity'] == 'on') $each_quantity = 1;
            else $each_quantity = 0;

            // Run for each product?
            if (isset($_POST['each_product']) && $_POST['each_product'] == 'on') $each_product = 1;
            else $each_product = 0;


            $email = [];
            $email['subject'] = Input::get('subject');
            $email['content'] = Input::get('content');

            if (!$action->exists()) {
                // Create new action
                $last_order = DB::getInstance()->query('SELECT `order` FROM nl2_store_products_actions ORDER BY `order` DESC LIMIT 1')->results();
                if (count($last_order)) $last_order = $last_order[0]->order;
                else $last_order = 0;

                DB::getInstance()->insert('store_products_actions', [
                    'product_id' => $product != null ? $product->data()->id : null,
                    'type' => Input::get('trigger'),
                    'service_id' => $service->getId(),
                    'command' => json_encode($email),
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
                    'command' => json_encode($email),
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
        'EMAIL_SUBJECT_VALUE' => ((isset($_POST['subject']) && $_POST['subject']) ? Output::getClean($_POST['subject']) : ''),
        'EMAIL_CONTENT_VALUE' => ((isset($_POST['content']) && $_POST['content']) ? Output::getClean($_POST['content']) : ''),
        'EACH_QUANTITY_VALUE' => 1,
        'EACH_PRODUCT_VALUE' => 1,
    ]);
} else {
    // Updating action
    $email = json_decode($action->data()->command, true);

    $template->getEngine()->addVariables([
        'TRIGGER_VALUE' => Output::getClean($action->data()->type),
        'EMAIL_SUBJECT_VALUE' => Output::getClean($email['subject']),
        'EMAIL_CONTENT_VALUE' => Output::getPurified($email['content'], true),
        'EACH_QUANTITY_VALUE' => $action->data()->each_quantity,
        'EACH_PRODUCT_VALUE' => $action->data()->each_product,
    ]);
}

$template->assets()->include([
    AssetTree::TINYMCE,
]);

$template->addJSScript(Input::createTinyEditor($language, 'inputEmailContent', null, false, true));

$template->getEngine()->addVariables([
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/Email/settings/action_settings.tpl'
]);