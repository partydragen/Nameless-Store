<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel fields page
 */

// Can the user view the StaffCP?
if(!$user->handlePanelPageLoad('staffcp.store.manage')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_fields');
$page_title = $store_language->get('admin', 'fields');
require_once(ROOT_PATH . '/core/templates/backend_init.php');
require_once(ROOT_PATH . '/modules/Store/classes/Store.php');

$store = new Store($cache, $store_language);

$field_types = array();
$field_types[1] = array('id' => 1, 'name' => $language->get('admin', 'text'));
$field_types[2] = array('id' => 2, 'name' => $store_language->get('admin', 'options'));
$field_types[3] = array('id' => 3, 'name' => $language->get('admin', 'textarea'));
$field_types[4] = array('id' => 4, 'name' => $store_language->get('admin', 'number'));
$field_types[5] = array('id' => 5, 'name' => $language->get('general', 'email_address'));
$field_types[6] = array('id' => 6, 'name' => $store_language->get('admin', 'radio'));
$field_types[7] = array('id' => 7, 'name' => $store_language->get('admin', 'checkbox'));

if(!isset($_GET['action'])) {
    // Get fields from database
    $fields = DB::getInstance()->query('SELECT * FROM nl2_store_fields WHERE deleted = 0 ORDER BY `order`')->results();
    $fields_array = array();
    if(count($fields)){
        foreach($fields as $field){
            $fields_array[] = array(
                'identifier' => Output::getClean('{' . $field->identifier . '}'),
                'description' => Output::getClean($field->description),
                'type' => $field_types[$field->type]['name'],
                'required' => Output::getClean($field->required),
                'edit_link' => URL::build('/panel/store/fields/', 'action=edit&id='.$field->id),
                'delete_link' => URL::build('/panel/store/fields/', 'action=delete&amp;id=' . $field->id)
            );
        }
    }
            
    $smarty->assign(array(
        'FIELDS_INFO' => $store_language->get('admin', 'fields_info'),
        'IDENTIFIER' => $store_language->get('admin', 'identifier'),
        'DESCRIPTION' => $store_language->get('admin', 'description'),
        'TYPE' => $language->get('admin', 'type'),
        'REQUIRED' => $language->get('admin', 'required'),
        'ACTIONS' => $language->get('general', 'actions'),
        'NEW_FIELD' => $store_language->get('admin', 'new_field'),
        'NEW_FIELD_LINK' => URL::build('/panel/store/fields/', 'action=new'),
        'FIELDS_LIST' => $fields_array,
        'NONE_FIELDS_DEFINED' => $store_language->get('admin', 'none_fields_defined'),
        'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
        'CONFIRM_DELETE_FIELD' => $store_language->get('admin', 'delete_field'),
        'YES' => $language->get('general', 'yes'),
        'NO' => $language->get('general', 'no')
    ));
    
    $template_file = 'store/fields.tpl';
} else {
    switch($_GET['action']) {
        case 'new';
            // New Field
            if(Input::exists()){
                $errors = array();
                if(Token::check(Input::get('token'))){
                    // Validate input
                    $validate = new Validate();
                    $validation = $validate->check($_POST, array(
                        'identifier' => array(
                            'required' => true,
                            'min' => 2,
                            'max' => 32
                        ),
                        'description' => array(
                            'required' => true,
                            'min' => 2,
                            'max' => 255
                        )
                    ));
                                        
                    if($validation->passed()){
                        // Create field
                        try {
                            // Get field type
                            $type = 1;
                            if(array_key_exists($_POST['type'], $field_types)) {
                                $type = $_POST['type'];
                            }
                                                
                            // Is this field required
                            if(isset($_POST['required']) && $_POST['required'] == 'on') $required = 1;
                            else $required = 0;
                                                
                            // Get options into a string
                            $options = str_replace("\n", ',', Input::get('options'));
                                            
                            // Save to database
                            $queries->create('store_fields', array(
                                'identifier' => Output::getClean(Input::get('identifier')),
                                'description' => Output::getClean(Input::get('description')),
                                'type' => $type,
                                'required' => $required,
                                'options' => htmlspecialchars($options),
                                'order' => Input::get('order'),
                                'min' => Input::get('minimum'),
                                'max' => Input::get('maximum')
                            ));
                                    
                            Session::flash('fields_success', $store_language->get('admin', 'field_created_successfully'));
                            Redirect::to(URL::build('/panel/store/fields/'));
                            die();
                        } catch(Exception $e){
                            $errors[] = $e->getMessage();
                        }
                    } else {
                        // Errors
                        foreach($validation->errors() as $item){
                            if(strpos($item, 'is required') !== false){
                                switch($item){
                                    case (strpos($item, 'identifier') !== false):
                                        $errors[] = $store_language->get('admin', 'field_identifier_required');
                                    break;
                                    case (strpos($item, 'description') !== false):
                                        $errors[] = $store_language->get('admin', 'field_description_required');
                                    break;
                                }
                            } else if(strpos($item, 'minimum') !== false){
                                switch($item){
                                    case (strpos($item, 'identifier') !== false):
                                        $errors[] = $store_language->get('admin', 'field_identifier_minimum');
                                    break;
                                    case (strpos($item, 'description') !== false):
                                        $errors[] = $store_language->get('admin', 'field_description_minimum');
                                    break;
                                }
                            } else if(strpos($item, 'maximum') !== false){
                                switch($item){
                                    case (strpos($item, 'identifier') !== false):
                                        $errors[] = $store_language->get('admin', 'field_identifier_maximum');
                                    break;
                                    case (strpos($item, 'description') !== false):
                                        $errors[] = $store_language->get('admin', 'field_description_maximum');
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }
            
            $smarty->assign(array(
                'FIELD_TITLE' => $store_language->get('admin', 'creating_field'),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/fields/'),
                'IDENTIFIER' => $store_language->get('admin', 'identifier'),
                'IDENTIFIER_VALUE' => ((isset($_POST['identifier']) && $_POST['identifier']) ? Output::getClean(Input::get('identifier')) : ''),
                'DESCRIPTION' => $store_language->get('admin', 'description'),
                'DESCRIPTION_VALUE' => ((isset($_POST['description']) && $_POST['description']) ? Output::getClean(Input::get('description')) : ''),
                'TYPE' => $language->get('admin', 'type'),
                'TYPE_VALUE' => ((isset($_POST['type']) && $_POST['type']) ? Output::getClean(Input::get('type')) : 1),
                'TYPES' =>  $field_types,
                'OPTIONS' => $store_language->get('admin', 'options'),
                'OPTIONS_HELP' => $store_language->get('admin', 'options_help'),
                'OPTIONS_VALUE' => ((isset($_POST['options']) && $_POST['options']) ? Output::getClean(Input::get('options')) : ''),
                'CHECKBOX' => $store_language->get('admin', 'checkbox'),
                'RADIO' => $store_language->get('admin', 'radio'),
                'FIELD_ORDER' => $store_language->get('admin', 'field_order'),
                'ORDER_VALUE' => ((isset($_POST['order']) && $_POST['order']) ? Output::getClean(Input::get('order')) : 1),
                'MINIMUM_CHARACTERS' => $store_language->get('admin', 'minimum_characters'),
                'MINIMUM_CHARACTERS_VALUE' => ((isset($_POST['minimum']) && $_POST['minimum']) ? Output::getClean(Input::get('minimum')) : 0),
                'MAXIMUM_CHARACTERS' => $store_language->get('admin', 'maximum_characters'),
                'MAXIMUM_CHARACTERS_VALUE' => ((isset($_POST['maximum']) && $_POST['maximum']) ? Output::getClean(Input::get('maximum')) : 0),
                'REQUIRED' => $language->get('admin', 'required'),
                'REQUIRED_VALUE' => ((isset($_POST['required'])) ? 1 : 0),
                
            ));
        
            $template_file = 'store/fields_form.tpl';
        break;
        case 'edit';
            if(!is_numeric($_GET['id'])){
                Redirect::to(URL::build('/panel/store/fields/'));
                die();
            } else {
                $field = $queries->getWhere('store_fields', array('id', '=', $_GET['id']));
                if(!count($field)){
                    Redirect::to(URL::build('/panel/store/fields/'));
                    die();
                }
            }
            $field = $field[0];

            // Edit Field
            if(Input::exists()){
                $errors = array();
                if(Token::check(Input::get('token'))){
                    // Validate input
                    $validate = new Validate();
                    $validation = $validate->check($_POST, array(
                        'identifier' => array(
                            'required' => true,
                            'min' => 2,
                            'max' => 32
                        ),
                        'description' => array(
                            'required' => true,
                            'min' => 2,
                            'max' => 255
                        )
                    ));
                                        
                    if($validation->passed()){
                        // Create field
                        try {
                            // Get field type
                            $type = 1;
                            if(array_key_exists($_POST['type'], $field_types)) {
                                $type = $_POST['type'];
                            }
                                                
                            // Is this field required
                            if(isset($_POST['required']) && $_POST['required'] == 'on') $required = 1;
                            else $required = 0;
                                                
                            // Get options into a string
                            $options = str_replace("\n", ',', Input::get('options'));
                                            
                            // Save to database
                            $queries->update('store_fields', $field->id, array(
                                'identifier' => Output::getClean(Input::get('identifier')),
                                'description' => Output::getClean(Input::get('description')),
                                'type' => $type,
                                'required' => $required,
                                'options' => htmlspecialchars($options),
                                'min' => Input::get('minimum'),
                                'max' => Input::get('maximum'),
                                '`order`' => Input::get('order')
                            ));
                                    
                            Session::flash('fields_success', $store_language->get('admin', 'field_updated_successfully'));
                            Redirect::to(URL::build('/panel/store/fields/'));
                            die();
                        } catch(Exception $e){
                            $errors[] = $e->getMessage();
                        }
                    } else {
                        // Errors
                        foreach($validation->errors() as $item){
                            if(strpos($item, 'is required') !== false){
                                switch($item){
                                    case (strpos($item, 'identifier') !== false):
                                        $errors[] = $store_language->get('admin', 'field_identifier_required');
                                    break;
                                    case (strpos($item, 'description') !== false):
                                        $errors[] = $store_language->get('admin', 'field_description_required');
                                    break;
                                }
                            } else if(strpos($item, 'minimum') !== false){
                                switch($item){
                                    case (strpos($item, 'identifier') !== false):
                                        $errors[] = $store_language->get('admin', 'field_identifier_minimum');
                                    break;
                                    case (strpos($item, 'description') !== false):
                                        $errors[] = $store_language->get('admin', 'field_description_minimum');
                                    break;
                                }
                            } else if(strpos($item, 'maximum') !== false){
                                switch($item){
                                    case (strpos($item, 'identifier') !== false):
                                        $errors[] = $store_language->get('admin', 'field_identifier_maximum');
                                    break;
                                    case (strpos($item, 'description') !== false):
                                        $errors[] = $store_language->get('admin', 'field_description_maximum');
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }
            
             // Get already inputted options
            if($field->options == null){
                $options = '';
            } else {
                $options = str_replace(',', "\n", htmlspecialchars($field->options));
            }
        
            $smarty->assign(array(
                'FIELD_TITLE' => str_replace('{x}', Output::getClean($field->name), $store_language->get('admin', 'editing_field_x')),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/fields/'),
                'IDENTIFIER' => $store_language->get('admin', 'identifier'),
                'IDENTIFIER_VALUE' => Output::getClean($field->identifier),
                'DESCRIPTION' => $store_language->get('admin', 'description'),
                'DESCRIPTION_VALUE' => Output::getClean($field->description),
                'TYPE' => $language->get('admin', 'type'),
                'TYPE_VALUE' => $field->type,
                'TYPES' => $field_types,
                'OPTIONS' => $store_language->get('admin', 'options'),
                'OPTIONS_HELP' => $store_language->get('admin', 'options_help'),
                'OPTIONS_VALUE' => $options,
                'CHECKBOX' => $store_language->get('admin', 'checkbox'),
                'RADIO' => $store_language->get('admin', 'radio'),
                'FIELD_ORDER' => $store_language->get('admin', 'field_order'),
                'ORDER_VALUE' => $field->order,
                'MINIMUM_CHARACTERS' => $store_language->get('admin', 'minimum_characters'),
                'MINIMUM_CHARACTERS_VALUE' => $field->min,
                'MAXIMUM_CHARACTERS' => $store_language->get('admin', 'maximum_characters'),
                'MAXIMUM_CHARACTERS_VALUE' => $field->max,
                'REQUIRED' => $language->get('admin', 'required'),
                'REQUIRED_VALUE' => $field->required,
            ));
        
            $template_file = 'store/fields_form.tpl';
        break;
        case 'delete';
            // Delete Field
            if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
                Redirect::to(URL::build('/panel/store/fields/'));
                die();
            }
            $queries->update('store_fields', $_GET['id'], array(
                'deleted' => date('U')
            ));
                
            Session::flash('fields_success', $store_language->get('admin', 'field_deleted_successfully'));
            Redirect::to(URL::build('/panel/store/fields/'));
            die();
        break;
        default:
            Redirect::to(URL::build('/panel/store/fields'));
            die();
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

if(Session::exists('fields_success'))
    $success = Session::flash('fields_success');

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
    'FIELDS' => $store_language->get('admin', 'fields')
));

$template->addCSSFiles(array(
    (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.css' => array()
));

$template->addJSFiles(array(
    (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.js' => array()
));

$template->addJSScript('
    var elems = Array.prototype.slice.call(document.querySelectorAll(\'.js-switch\'));
    elems.forEach(function(html) {
        var switchery = new Switchery(html, {color: \'#23923d\', secondaryColor: \'#e56464\'});
    });
');

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);