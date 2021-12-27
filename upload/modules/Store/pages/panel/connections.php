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
if(!$user->handlePanelPageLoad('staffcp.store.connections')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_connections');
$page_title = $store_language->get('admin', 'connections');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

if(!isset($_GET['action'])) {
    
    $connections = DB::getInstance()->query('SELECT * FROM nl2_store_connections');
    if($connections->count()){
        $connections = $connections->results();

        $connections_list = array();
        foreach($connections as $connection){
            $connections_list[] = array(
                'id' => Output::getClean($connection->id),
                'name' => Output::getClean($connection->name),
                'type' => Output::getClean($connection->type),
                'edit_link' => URL::build('/panel/store/connections/', 'action=edit&id=' . Output::getClean($connection->id))
            );
        }

        $smarty->assign('CONNECTIONS_LIST', $connections_list);
    }
    
    $smarty->assign(array(
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
    ));
    
    $template_file = 'store/connections.tpl';
} else {
    switch($_GET['action']) {
        case 'new';
            // Create new connections
            if(Input::exists()){
                $errors = array();
                if(Token::check(Input::get('token'))){
                    // Update product
                    $validate = new Validate();
                    $validation = $validate->check($_POST, array(
                        'name' => array(
                            'required' => true,
                            'min' => 1,
                            'max' => 64
                        )
                    ));
                        
                    if ($validation->passed()){
                        // Save to database
                        $queries->create('store_connections', array(
                            'name' => Output::getClean(Input::get('name')),
                            'type' => 'Minecraft',
                        ));
                        
                        Session::flash('connections_success', $store_language->get('admin', 'connection_updated_successfully'));
                        Redirect::to(URL::build('/panel/store/connections'));
                        die();
                    } else {
                        // Errors
                        foreach($validation->errors() as $item){
                            if(strpos($item, 'is required') !== false){
                                switch($item){
                                    case (strpos($item, 'name') !== false):
                                        $errors[] = $store_language->get('admin', 'name_required');
                                    break;
                                }
                            } else if(strpos($item, 'maximum') !== false){
                                switch($item){
                                    case (strpos($item, 'name') !== false):
                                        $errors[] = str_replace('{x}', '64', $store_language->get('admin', 'name_maximum_x'));
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // Invalid token
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }
            
            $smarty->assign(array(
                'CONNECTIONS_TITLE' => $store_language->get('admin', 'creating_new_connection'),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/connections/'),
                'NAME' => $language->get('admin', 'name'),
                'NAME_VALUE' => ((isset($_POST['name']) && $_POST['name']) ? Output::getClean(Input::get('name')) : '')
            ));
            
            $template_file = 'store/connections_form.tpl';
        break;
        case 'edit';
            // Edit connections
            if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
                Redirect::to(URL::build('/panel/store/connections/'));
                die();
            }
            
            $connection = DB::getInstance()->query('SELECT * FROM nl2_store_connections WHERE id = ?', array($_GET['id']));
            if(!$connection->count()) {
                Redirect::to(URL::build('/panel/store/connections/'));
                die();
            }
            $connection = $connection->first();
            
            if(Input::exists()){
                $errors = array();
                if(Token::check(Input::get('token'))){
                    // Update product
                    $validate = new Validate();
                    $validation = $validate->check($_POST, array(
                        'name' => array(
                            'required' => true,
                            'min' => 1,
                            'max' => 64
                        )
                    ));
                        
                    if ($validation->passed()){
                        $queries->update('store_connections', $connection->id, array(
                            'name' => Output::getClean(Input::get('name'))
                        ));
                        
                        Session::flash('connections_success', $store_language->get('admin', 'connection_updated_successfully'));
                        Redirect::to(URL::build('/panel/store/connections'));
                        die();
                    } else {
                        // Errors
                        foreach($validation->errors() as $item){
                            if(strpos($item, 'is required') !== false){
                                switch($item){
                                    case (strpos($item, 'name') !== false):
                                        $errors[] = $store_language->get('admin', 'name_required');
                                    break;
                                }
                            } else if(strpos($item, 'maximum') !== false){
                                switch($item){
                                    case (strpos($item, 'name') !== false):
                                        $errors[] = str_replace('{x}', '64', $store_language->get('admin', 'name_maximum_x'));
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // Invalid token
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }
            
            $smarty->assign(array(
                'CONNECTIONS_TITLE' => str_replace('{x}', Output::getClean($connection->name), $store_language->get('admin', 'editing_connection_x')),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/connections/'),
                'NAME' => $language->get('admin', 'name'),
                'NAME_VALUE' => Output::getClean($connection->name)
            ));
            
            $template_file = 'store/connections_form.tpl';
        break;
        case 'delete';
            // Delete connections
            if (Input::exists()) {
                if (Token::check(Input::get('token'))) {
                    if (isset($_POST['id'])) {
                        $queries->delete('store_connections', array('id', '=', $_POST['id']));
                        $queries->delete('store_products_connections', array('connection_id', '=', $_POST['id']));

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
            die();
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets, $templates);

if(Session::exists('connections_success'))
    $success = Session::flash('connections_success');

if(Session::exists('connections_error'))
    $errors = array(Session::flash('connections_error'));

if(isset($success))
    $smarty->assign(array(
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ));

if(isset($errors) && count($errors))
    $smarty->assign(array(
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ));

$smarty->assign(array(
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('general', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'CONNECTIONS' => $store_language->get('admin', 'connections')
));

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);