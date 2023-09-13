<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel connections page
 */
 
// Can the user view the StaffCP?
if (!$user->handlePanelPageLoad('staffcp.store.connections')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store_configuration');
define('PANEL_PAGE', 'store_connections');
$page_title = $store_language->get('admin', 'service_connections');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

$services = Services::getInstance();

if (!isset($_GET['action'])) {

    $connections = DB::getInstance()->query('SELECT * FROM nl2_store_connections');
    if ($connections->count()) {
        $connections = $connections->results();

        $connections_list = [];
        foreach ($connections as $connection) {
            $service = $services->get($connection->service_id);
            if ($service == null) {
                continue;
            }

            $connections_list[] = [
                'id' => Output::getClean($connection->id),
                'name' => Output::getClean($connection->name),
                'service' => Output::getClean($service->getName()),
                'edit_link' => URL::build('/panel/store/connections/', 'action=edit&id=' . $connection->id),
                'error' => $service->getId() == 2 && $connection->last_fetch < strtotime('-1 hour') ? 'There has been no API fetch within the last hour, Is the nameless plugin installed, and is store module integration enabled in modules.yaml?' : false,
                'queued_actions' => $store_language->get('admin', 'queued_actions_results', [
                    'pending_actions' => DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_store_pending_actions WHERE connection_id = ? AND status = 0', [$connection->id])->first()->c,
                    'completed_actions' => DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_store_pending_actions WHERE connection_id = ? AND status = 1', [$connection->id])->first()->c
                ]),
            ];
        }

        $smarty->assign('CONNECTIONS_LIST', $connections_list);
    }

    $smarty->assign([
        'CONNECTIONS_INFO' => $store_language->get('admin', 'connections_info'),
        'NO_CONNECTIONS' => $store_language->get('admin', 'no_connections'),
        'NEW_CONNECTION' => $store_language->get('admin', 'new_connection'),
        'NEW_CONNECTION_LINK' => URL::build('/panel/store/connections', 'action=new'),
        'CONNECTION_ID' => $store_language->get('admin', 'connection_id'),
        'NAME' => $language->get('admin', 'name'),
        'TYPE' => $language->get('admin', 'type'),
        'ACTIONS' => $language->get('general', 'actions'),
        'DELETE_LINK' => URL::build('/panel/store/connections', 'action=delete'),
        'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
        'CONFIRM_DELETE_CONNECTION' => $store_language->get('admin', 'confirm_delete_connection'),
        'TOKEN' => Token::get(),
        'YES' => $language->get('general', 'yes'),
        'NO' => $language->get('general', 'no'),
        'WARNING' => $language->get('general', 'warning'),
        'QUEUED_ACTIONS' => $store_language->get('admin', 'queued_actions'),
    ]);
    
    $template_file = 'store/connections.tpl';
} else {
    switch ($_GET['action']) {
        case 'new';
            // Create new connections
            if (!isset($_GET['service'])) {
                // Select service type
                $services_list = [];
                foreach ($services->getAll() as $service) {
                    if ($service->getConnectionSettings() == null) {
                        continue;
                    }

                    $services_list[] = [
                        'id' => Output::getClean($service->getId()),
                        'name' => Output::getClean($service->getName()),
                        'description' => Output::getClean($service->getDescription()),
                        'select_link' => URL::build('/panel/store/connections', 'action=new&service=' . $service->getId())
                    ];
                }

                $smarty->assign([
                    'CONNECTIONS_TITLE' => 'Select Connection Type',
                    'BACK' => $language->get('general', 'back'),
                    'BACK_LINK' => URL::build('/panel/store/connections/'),
                    'SERVICES_LIST' => $services_list
                ]);

                $template_file = 'store/connections_type.tpl';
            } else {
                if (!is_numeric($_GET['service'])) {
                    URL::build('/panel/store/connections', 'action=new');
                }

                $service = $services->get($_GET['service']);
                if ($service == null) {
                    URL::build('/panel/store/connections', 'action=new');
                }

                $fields = new Fields();

                if (file_exists($service->getConnectionSettings())) {
                    $securityPolicy->secure_dir = [ROOT_PATH . '/modules/Store', ROOT_PATH . '/custom/panel_templates'];
                    require_once($service->getConnectionSettings());
                }

                $smarty->assign([
                    'CONNECTIONS_TITLE' => $store_language->get('admin', 'creating_new_connection'),
                    'BACK' => $language->get('general', 'back'),
                    'BACK_LINK' => URL::build('/panel/store/connections/'),
                    'FIELDS' => $fields->getAll()
                ]);
                
                $template_file = 'store/connections_form.tpl';
            }
        break;
        case 'edit';
            // Edit connections
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/store/connections/'));
            }

            $connection = DB::getInstance()->query('SELECT * FROM nl2_store_connections WHERE id = ?', [$_GET['id']]);
            if (!$connection->count()) {
                Redirect::to(URL::build('/panel/store/connections/'));
            }
            $connection = $connection->first();

            $service = $services->get($connection->service_id);
            if ($service == null) {
                URL::build('/panel/store/connections', 'action=new');
            }

            $fields = new Fields();
            if (file_exists($service->getConnectionSettings())) {
                $securityPolicy->secure_dir = [ROOT_PATH . '/modules/Store', ROOT_PATH . '/custom/panel_templates'];
                require_once($service->getConnectionSettings());
            }

            $smarty->assign([
                'CONNECTIONS_TITLE' => $store_language->get('admin', 'editing_connection_x', ['connection' => Output::getClean($connection->name)]),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/connections/'),
                'FIELDS' => $fields->getAll()
            ]);
            
            $template_file = 'store/connections_form.tpl';
        break;
        case 'delete';
            // Delete connections
            if (Input::exists()) {
                if (Token::check(Input::get('token'))) {
                    if (isset($_POST['id'])) {
                        DB::getInstance()->delete('store_connections', ['id', '=', $_POST['id']]);
                        DB::getInstance()->delete('store_products_connections', ['connection_id', '=', $_POST['id']]);

                        Session::flash('connections_success', $store_language->get('admin', 'connection_deleted_successfully'));
                    }
                } else {
                    Session::flash('connections_error', $language->get('general', 'invalid_token'));
                }
            }
            die();
        break;
        default:
            Redirect::to(URL::build('/panel/store/connections'));
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('connections_success'))
    $success = Session::flash('connections_success');

if (Session::exists('connections_error'))
    $errors = [Session::flash('connections_error')];

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
    'SERVICE_CONNECTIONS' => $store_language->get('admin', 'service_connections')
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);