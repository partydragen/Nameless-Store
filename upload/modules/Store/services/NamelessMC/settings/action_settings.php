<?php
// Create or update action
if (Input::exists()) {
    $errors = [];

    if (Token::check(Input::get('token'))) {
        // New Action
        $validation = Validate::check($_POST, [
            'add_credits' => [
                Validate::MIN => 1,
                Validate::MAX => 11,
                Validate::NUMERIC => true
            ],
            'remove_credits' => [
                Validate::MIN => 1,
                Validate::MAX => 11,
                Validate::NUMERIC => true
            ]
        ])->messages([
            'add_credits' => [
                Validate::NUMERIC => 'Invalid credits amount',
                Validate::MIN => 'Invalid credits amount',
                Validate::MAX => 'Invalid credits amount'
            ],
            'remove_credits' => [
                Validate::NUMERIC => 'Invalid credits amount',
                Validate::MIN => 'Invalid credits amount',
                Validate::MAX => 'Invalid credits amount'
            ]
        ]);

        if ($validation->passed()) {
            $trigger = Input::get('trigger');
            if (!in_array($trigger, [1,2,3,4,5])) {
                $errors[] = 'Invalid Trigger';
            }

            $command = [];
            // Add groups to user
            if (isset($_POST['add_groups']) && is_array($_POST['add_groups']) && count($_POST['add_groups'])) {
                $groups = [];
                foreach ($_POST['add_groups'] as $group) {
                    $groups[] = (int) $group;
                }
                $command['add_groups'] = $groups;
            }

            // Remove groups from user
            if (isset($_POST['remove_groups']) && is_array($_POST['remove_groups']) && count($_POST['remove_groups'])) {
                $groups = [];
                foreach ($_POST['remove_groups'] as $group) {
                    $groups[] = (int) $group;
                }
                $command['remove_groups'] = $groups;
            }

            // Add credits to user
            if (isset($_POST['add_credits']) && is_numeric($_POST['add_credits']) && $_POST['add_credits'] > 0) {
                $command['add_credits'] = Input::get('add_credits');
            }

            // Remove credits from user
            if (isset($_POST['remove_credits']) && is_numeric($_POST['remove_credits']) && $_POST['remove_credits'] > 0) {
                $command['remove_credits'] = Input::get('remove_credits');
            }

            // Send alert to user
            if (isset($_POST['alert']) && !empty($_POST['alert'])) {
                $command['alert'] = Input::get('alert');
            }

            // Add trophies to user
            if (isset($_POST['add_trophies']) && is_array($_POST['add_trophies']) && count($_POST['add_trophies'])) {
                $trophies = [];
                foreach ($_POST['add_trophies'] as $trophy) {
                    $trophies[] = (int) $trophy;
                }
                $command['add_trophies'] = $trophies;
            }

            if (!count($command)) {
                $errors[] = 'You need at least one action';
            }

            if (!count($errors)) {
                if (!$action->exists()) {
                    // Create new action
                    $last_order = DB::getInstance()->query('SELECT id FROM nl2_store_products_actions WHERE product_id = ? ORDER BY `order` DESC LIMIT 1', [$product->id])->results();
                    if (count($last_order)) $last_order = $last_order[0]->order;
                    else $last_order = 0;

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
                    $action->update([
                        'type' => $trigger,
                        'command' => json_encode($command),
                        'require_online' => 0,
                        'own_connections' => 0
                    ]);

                    Session::flash('products_success', $store_language->get('admin', 'action_updated_successfully'));
                    Redirect::to(URL::build('/panel/store/product/', 'product=' . $product->data()->id));
                }
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
        'ADD_GROUPS_VALUE' => ((isset($_POST['add_groups']) && is_array($_POST['add_groups'])) ? $_POST['add_groups'] : []),
        'REMOVE_GROUPS_VALUE' => ((isset($_POST['remove_groups']) && is_array($_POST['remove_groups'])) ? $_POST['remove_groups'] : []),
        'ADD_CREDITS_VALUE' => ((isset($_POST['add_credits']) && $_POST['add_credits']) ? Output::getClean($_POST['add_credits']) : '0.00'),
        'REMOVE_CREDITS_VALUE' => ((isset($_POST['remove_credits']) && $_POST['remove_credits']) ? Output::getClean($_POST['remove_credits']) : '0.00'),
        'ALERT_VALUE' => ((isset($_POST['alert']) && $_POST['alert']) ? Output::getClean($_POST['alert']) : ''),
        'ADD_TROPHIES_VALUE' => ((isset($_POST['add_trophies']) && is_array($_POST['add_trophies'])) ? $_POST['add_trophies'] : []),
    ]);
} else {
    // Updating action
    $command = json_decode($action->data()->command, true);

    $smarty->assign([
        'TRIGGER_VALUE' => Output::getClean($action->data()->type),
        'ADD_GROUPS_VALUE' => ((isset($command['add_groups']) && is_array($command['add_groups'])) ? $command['add_groups'] : []),
        'REMOVE_GROUPS_VALUE' => ((isset($command['remove_groups']) && is_array($command['remove_groups'])) ? $command['remove_groups'] : []),
        'ADD_CREDITS_VALUE' => ((isset($command['add_credits']) && $command['add_credits']) ? Output::getClean($command['add_credits']) : '0.00'),
        'REMOVE_CREDITS_VALUE' => ((isset($command['remove_credits']) && $command['remove_credits']) ? Output::getClean($command['remove_credits']) : '0.00'),
        'ALERT_VALUE' => ((isset($command['alert']) && $command['alert']) ? Output::getClean($command['alert']) : ''),
        'ADD_TROPHIES_VALUE' => ((isset($command['add_trophies']) && is_array($command['add_trophies'])) ? $command['add_trophies'] : []),
    ]);
}

$smarty->assign([
    'ALL_GROUPS' => $groups = DB::getInstance()->orderAll('groups', '`order`', 'ASC')->results(),
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/services/NamelessMC/settings/action_settings.tpl'
]);

$template->addJSScript('
    $(document).ready(() => {
        $(\'#inputAddGroups\').select2({ placeholder: "No groups selected" });
    })

    $(document).ready(() => {
        $(\'#inputRemoveGroups\').select2({ placeholder: "No groups selected" });
    })
');

// Trophies Module integration
if (Util::isModuleEnabled('Trophies')) {
    $trophies_list = [];

    $trophies = DB::getInstance()->query('SELECT id, title FROM nl2_trophies');
    foreach ($trophies->results() as $trophy) {
        $trophies_list[] = [
            'id' => $trophy->id,
            'title' => Output::getClean($trophy->title)
        ];
    }

    $smarty->assign([
        'TROPHIES_LIST' => $trophies_list
    ]);

    $template->addJSScript('
        $(document).ready(() => {
            $(\'#inputAddTrophies\').select2({ placeholder: "No trophies selected" });
        })
    ');
}