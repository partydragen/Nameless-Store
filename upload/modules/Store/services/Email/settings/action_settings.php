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
            ]
        ])->messages([
            'subject' => [
                Validate::REQUIRED => 'Email subject is required!',
                Validate::MAX => 'Email subject is to long!'
            ],
            'content' => [
                Validate::REQUIRED => 'Email content is required!',
                Validate::MAX => 'Email content is to long!'
            ]
        ]);

        if ($validation->passed()) {
            $trigger = Input::get('trigger');
            if (!in_array($trigger, [1,2,3,4,5])) {
                $errors[] = 'Invalid Trigger';
            }

            if (!$action->exists()) {
                // Create new action
                $last_order = DB::getInstance()->query('SELECT id FROM nl2_store_products_actions WHERE product_id = ? ORDER BY `order` DESC LIMIT 1', [$product->id])->results();
                if (count($last_order)) $last_order = $last_order[0]->order;
                else $last_order = 0;

                $email = [];
                $email['subject'] = Input::get('subject');
                $email['content'] = Input::get('content');

                DB::getInstance()->insert('store_products_actions', [
                    'product_id' => $product->data()->id,
                    'type' => $trigger,
                    'service_id' => $service->getId(),
                    'command' => json_encode($email),
                    'require_online' => 0,
                    'order' => $last_order + 1,
                    'own_connections' => 0
                ]);
                $lastId = DB::getInstance()->lastId();

                Session::flash('products_success', $store_language->get('admin', 'action_created_successfully'));
                Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
            } else {
                // Update existing action

                $email = [];
                $email['subject'] = Input::get('subject');
                $email['content'] = Input::get('content');

                $action->update([
                    'type' => $trigger,
                    'command' => json_encode($email),
                    'require_online' => 0,
                    'own_connections' => 0
                ]);

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

if (!$action->exists()) {
    // Creating new action
    $smarty->assign([
        'TRIGGER_VALUE' => ((isset($_POST['trigger'])) ? Output::getClean($_POST['trigger']) : 1),
        'EMAIL_SUBJECT_VALUE' => ((isset($_POST['subject']) && $_POST['subject']) ? Output::getClean($_POST['subject']) : ''),
        'EMAIL_CONTENT_VALUE' => ((isset($_POST['content']) && $_POST['content']) ? Output::getClean($_POST['content']) : '')
    ]);
} else {
    // Updating action
    $email = json_decode($action->data()->command, true);

    $smarty->assign([
        'TRIGGER_VALUE' => Output::getClean($action->data()->type),
        'EMAIL_SUBJECT_VALUE' => Output::getClean($email['subject']),
        'EMAIL_CONTENT_VALUE' => Output::getPurified($email['content'], true)
    ]);
}

$template->assets()->include([
    AssetTree::TINYMCE,
]);

$template->addJSScript(Input::createTinyEditor($language, 'inputEmailContent', null, false, true));

$smarty->assign([
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/Email/settings/action_settings.tpl'
]);