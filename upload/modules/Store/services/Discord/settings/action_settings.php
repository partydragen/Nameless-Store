<?php
// Create or update action
if (Input::exists()) {
    $errors = [];

    if (Token::check(Input::get('token'))) {
        // New Action
        $validation = Validate::check($_POST, [
            'webhook_url' => [
                Validate::REQUIRED => true,
                Validate::MAX => 128
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
                $command = [];

                // Add roles to user
                if (isset($_POST['add_roles']) && is_array($_POST['add_roles']) && count($_POST['add_roles'])) {
                    $groups = [];
                    foreach ($_POST['add_roles'] as $group) {
                        $groups[] = (int)$group;
                    }
                    $command['add_roles'] = $groups;
                }

                // Remove roles from user
                if (isset($_POST['remove_roles']) && is_array($_POST['remove_roles']) && count($_POST['remove_roles'])) {
                    $groups = [];
                    foreach ($_POST['remove_roles'] as $group) {
                        $groups[] = (int)$group;
                    }
                    $command['remove_roles'] = $groups;
                }

                // Webhook
                if (!empty(Input::get('webhook_url')) || !empty(Input::get('webhook_content')) || !empty(Input::get('embed_title')) || !empty(Input::get('embed_content')) || !empty(Input::get('embed_footer'))) {
                    $command['webhook'] = [
                        'url' => Input::get('webhook_url'),
                        'content' => Input::get('webhook_content'),
                        'embeds' => [
                            [
                                'title' => Input::get('embed_title'),
                                'description' => Input::get('embed_content'),
                                'footer' => [
                                    'text' => Input::get('embed_footer'),
                                ]
                            ]
                        ],
                    ];
                }

                DB::getInstance()->insert('store_products_actions', [
                    'product_id' => $product->data()->id,
                    'type' => $trigger,
                    'service_id' => $service->getId(),
                    'command' => json_encode($command),
                    'require_online' => 0,
                    'order' => $last_order + 1,
                    'own_connections' => 0
                ]);
                $lastId = DB::getInstance()->lastId();

                Session::flash('products_success', $store_language->get('admin', 'action_created_successfully'));
                Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
            } else {
                // Update existing action
                $command = [];

                // Add roles to user
                if (isset($_POST['add_roles']) && is_array($_POST['add_roles']) && count($_POST['add_roles'])) {
                    $groups = [];
                    foreach ($_POST['add_roles'] as $group) {
                        $groups[] = (int)$group;
                    }
                    $command['add_roles'] = $groups;
                }

                // Remove roles from user
                if (isset($_POST['remove_roles']) && is_array($_POST['remove_roles']) && count($_POST['remove_roles'])) {
                    $groups = [];
                    foreach ($_POST['remove_roles'] as $group) {
                        $groups[] = (int)$group;
                    }
                    $command['remove_roles'] = $groups;
                }

                // Webhook
                if (!empty(Input::get('webhook_url')) || !empty(Input::get('webhook_content')) || !empty(Input::get('embed_title')) || !empty(Input::get('embed_content')) || !empty(Input::get('embed_footer'))) {
                    $command['webhook'] = [
                        'url' => Input::get('webhook_url'),
                        'content' => Input::get('webhook_content'),
                        'embeds' => [
                            [
                                'title' => Input::get('embed_title'),
                                'description' => Input::get('embed_content'),
                                'footer' => [
                                    'text' => Input::get('embed_footer'),
                                ]
                            ]
                        ],
                    ];
                }

                $action->update([
                    'type' => $trigger,
                    'command' => json_encode($command),
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
        'ADD_ROLES_VALUE' => ((isset($_POST['add_roles']) && is_array($_POST['add_roles'])) ? $_POST['add_roles'] : []),
        'REMOVE_ROLES_VALUE' => ((isset($_POST['remove_roles']) && is_array($_POST['remove_roles'])) ? $_POST['remove_roles'] : []),
        'WEBHOOK_URL_VALUE' => ((isset($_POST['webhook_url']) && $_POST['webhook_url']) ? Output::getClean($_POST['webhook_url']) : ''),
        'WEBHOOK_CONTENT_VALUE' => ((isset($_POST['webhook_content']) && $_POST['webhook_content']) ? Output::getClean($_POST['webhook_content']) : ''),
        'WEBHOOK_EMBED_TITLE_VALUE' => ((isset($_POST['embed_title']) && $_POST['embed_title']) ? Output::getClean($_POST['embed_title']) : ''),
        'WEBHOOK_EMBED_CONTENT_VALUE' => ((isset($_POST['embed_content']) && $_POST['embed_content']) ? Output::getClean($_POST['embed_content']) : ''),
        'WEBHOOK_EMBED_FOOTER_VALUE' => ((isset($_POST['embed_footer']) && $_POST['embed_footer']) ? Output::getClean($_POST['embed_footer']) : '')
    ]);
} else {
    // Updating action
    $command = json_decode($action->data()->command, true);
    $webhook = $command['webhook'];

    $smarty->assign([
        'TRIGGER_VALUE' => Output::getClean($action->data()->type),
        'ADD_ROLES_VALUE' => ((isset($command['add_roles']) && is_array($command['add_roles'])) ? $command['add_roles'] : []),
        'REMOVE_ROLES_VALUE' => ((isset($command['remove_roles']) && is_array($command['remove_roles'])) ? $command['remove_roles'] : []),
        'WEBHOOK_URL_VALUE' => Output::getClean($webhook['url']),
        'WEBHOOK_CONTENT_VALUE' => Output::getClean($webhook['content']),
        'WEBHOOK_EMBED_TITLE_VALUE' => Output::getClean($webhook['embeds'][0]['title']),
        'WEBHOOK_EMBED_CONTENT_VALUE' => Output::getClean($webhook['embeds'][0]['description']),
        'WEBHOOK_EMBED_FOOTER_VALUE' => Output::getClean($webhook['embeds'][0]['footer']['text'])
    ]);
}

$template->addJSScript('
    $(document).ready(() => {
        $(\'#inputAddRoles\').select2({ placeholder: "No roles selected" });
    })

    $(document).ready(() => {
        $(\'#inputRemoveRoles\').select2({ placeholder: "No roles selected" });
    })
');

$roles = [];
foreach (Discord::getRoles() as $role) {
    $roles[] = [
        'id' => $role['id'],
        'name' => Output::getClean($role['name']),
    ];
}

$smarty->assign([
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/Discord/settings/action_settings.tpl',
    'ALL_ROLES' => $roles
]);