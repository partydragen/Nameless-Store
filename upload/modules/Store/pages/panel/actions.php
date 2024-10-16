<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel actions page
 */

// Can the user view the StaffCP?
if (!$user->handlePanelPageLoad('staffcp.store.products')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

if (!isset($_GET['product'])) {
    define('PAGE', 'panel');
    define('PARENT_PAGE', 'store_configuration');
    define('PANEL_PAGE', 'store_actions');
} else {
    define('PAGE', 'panel');
    define('PARENT_PAGE', 'store');
    define('PANEL_PAGE', 'store_products');
}

$page_title = $store_language->get('general', 'products');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

$store = new Store();
$services = Services::getInstance();

if (!isset($_GET['action'])) {
    // Get global actions
    $actions_array = [];
    foreach (ActionsHandler::getInstance()->getActions() as $action) {
        $type = 'Unknown';
        switch ($action->data()->type) {
            case 1:
                $type = 'Purchase';
                break;
            case 2:
                $type = 'Refund';
                break;
            case 3:
                $type = 'Changeback';
                break;
            case 4:
                $type = 'Renewal';
                break;
            case 5:
                $type = 'Expire';
                break;
        }

        $actions_array[] = [
            'id' => Output::getClean($action->data()->id),
            'command' => Output::getClean(Text::truncate($action->data()->command, 120)),
            'type' => $type,
            'service' => $action->getService()->getName(),
            'requirePlayer' => ($action->data()->require_online ? 'Yes' : 'No'),
            'edit_link' => URL::build('/panel/store/actions', 'action=edit&aid=' . $action->data()->id),
            'delete_link' => URL::build('/panel/store/actions', 'action=delete&aid=' . $action->data()->id)
        ];
    }

    $smarty->assign([
        'GLOBAL_ACTIONS' => $store_language->get('admin', 'global_actions'),
        'NEW_ACTION' => $store_language->get('admin', 'new_action'),
        'NEW_ACTION_LINK' => URL::build('/panel/store/actions/' , 'action=new'),
        'ACTION_LIST' => $actions_array,
    ]);

    $template_file = 'store/actions.tpl';
} else {
    $product = null;
    if (isset($_GET['product'])) {
        $product = new Product($_GET['product']);
        if (!$product->exists()) {
            Redirect::to(URL::build('/panel/store/actions'));
        }
    }

    switch ($_GET['action']) {
        case 'new';
            // New action for product
            if (!isset($_GET['service'])) {
                // Select service type
                $services_list = [];
                foreach ($services->getAll() as $service) {
                    $services_list[] = [
                        'id' => Output::getClean($service->getId()),
                        'name' => Output::getClean($service->getName()),
                        'description' => Output::getClean($service->getDescription()),
                        'select_link' => URL::build('/panel/store/actions/' , 'action=new&service=' . $service->getId() . (isset($_GET['product']) ? '&product=' . $_GET['product'] : '')),
                    ];
                }

                $smarty->assign([
                    'ACTION_TITLE' => $store_language->get('admin', 'new_action_for_x', ['product' => $product != null ? $product->data()->name : 'Global']),
                    'BACK' => $language->get('general', 'back'),
                    'BACK_LINK' => $product == null ? URL::build('/panel/store/actions') : URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
                    'SERVICES_LIST' => $services_list
                ]);

                $template_file = 'store/products_action_type.tpl';
            } else {
                if (!is_numeric($_GET['service'])) {
                    Redirect::to(URL::build('/panel/store/actions'));
                }

                $service = $services->get($_GET['service']);
                if ($service == null) {
                    Redirect::to(URL::build('/panel/store/actions'));
                }
                $action = new Action($service);

                $fields = new Fields();
                if (file_exists($service->getActionSettings())) {
                    $securityPolicy->secure_dir = [ROOT_PATH . '/modules/Store', ROOT_PATH . '/custom/panel_templates'];
                    require_once($service->getActionSettings());
                }
                $service->onActionSettingsPageLoad($template, $fields);

                $smarty->assign([
                    'ACTION_TITLE' => $store_language->get('admin', 'new_action_for_x', ['product' => 'Global']),
                    'BACK' => $language->get('general', 'back'),
                    'BACK_LINK' => $product == null ? URL::build('/panel/store/actions') : URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
                    'FIELDS' => $fields->getAll(),
                    'VIEW_PLACEHOLDERS' => $store_language->get('admin', 'view_placeholders'),
                    'ACTION_TYPE' => $product != null ? 'product' : 'global'
                ]);

                $template_file = 'store/products_action_form.tpl';
            }
            break;
        case 'edit';
            // Editing action for product
            if (!isset($_GET['aid']) || !is_numeric($_GET['aid'])) {
                Redirect::to(URL::build('/panel/store/actions'));
            }

            $action = ActionsHandler::getInstance()->getAction($_GET['aid']);
            if ($action == null) {
                Redirect::to(URL::build('/panel/store/actions'));
            }
            $service = $action->getService();

            $fields = new Fields();
            if (file_exists($service->getActionSettings())) {
                $securityPolicy->secure_dir = [ROOT_PATH . '/modules/Store', ROOT_PATH . '/custom/panel_templates'];
                require_once($service->getActionSettings());
            }
            $action->getService()->onActionSettingsPageLoad($template, $fields);

            $smarty->assign([
                'ACTION_TITLE' => $store_language->get('admin', 'editing_action_for_x', ['product' => $product != null ? $product->data()->name : 'Global']),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => $product == null ? URL::build('/panel/store/actions') : URL::build('/panel/store/product/' , 'product=' . $product->data()->id),
                'FIELDS' => $fields->getAll(),
                'VIEW_PLACEHOLDERS' => $store_language->get('admin', 'view_placeholders'),
                'ACTION_TYPE' => $product != null ? 'product' : 'global'
            ]);

            $template_file = 'store/products_action_form.tpl';
            break;
        case 'delete';
            // Delete product
            if (!isset($_GET['aid']) || !is_numeric($_GET['aid'])) {
                Redirect::to(URL::build('/panel/store/actions'));
            }

            $action = ActionsHandler::getInstance()->getAction($_GET['aid']);
            if ($action != null) {
                $action->delete();
                Session::flash('products_success', $store_language->get('admin', 'action_deleted_successfully'));
            }

            Redirect::to($product == null ? URL::build('/panel/store/actions') : URL::build('/panel/store/product/' , 'product=' . $product->data()->id));
            break;
        default:
            Redirect::to(URL::build('/panel/store/actions'));
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('products_success'))
    $success = Session::flash('products_success');

if (isset($success))
    $smarty->assign([
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ]);

if (isset($errors) && count($errors))
    $smarty->assign([
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ]);

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('general', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'ACTIONS' => $store_language->get('admin', 'actions')
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);