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
if (!$user->handlePanelPageLoad('staffcp.store.fields')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store_configuration');
define('PANEL_PAGE', 'store_fields');
$page_title = $store_language->get('admin', 'fields');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

$store = new Store($cache, $store_language);

$field_types = [];
$field_types[1] = ['id' => 1, 'name' => $language->get('admin', 'text')];
$field_types[2] = ['id' => 2, 'name' => $store_language->get('admin', 'options')];
$field_types[3] = ['id' => 3, 'name' => $language->get('admin', 'textarea')];
$field_types[4] = ['id' => 4, 'name' => $store_language->get('admin', 'number')];
$field_types[5] = ['id' => 5, 'name' => $language->get('user', 'email_address')];
$field_types[6] = ['id' => 6, 'name' => $store_language->get('admin', 'radio')];
$field_types[7] = ['id' => 7, 'name' => $store_language->get('admin', 'checkbox')];

if (!isset($_GET['action'])) {
    // Get fields from database
    $fields = DB::getInstance()->query('SELECT * FROM nl2_store_fields WHERE deleted = 0 ORDER BY `order`')->results();
    $fields_array = [];
    if (count($fields)) {
        foreach ($fields as $field) {
            $fields_array[] = [
                'identifier' => Output::getClean('{' . $field->identifier . '}'),
                'description' => Output::getClean($field->description),
                'type' => $field_types[$field->type]['name'],
                'required' => Output::getClean($field->required),
                'reserved' => ($field->identifier == 'quantity' || $field->identifier == 'price'),
                'edit_link' => URL::build('/panel/store/fields/', 'action=edit&id='.$field->id),
                'delete_link' => URL::build('/panel/store/fields/', 'action=delete&amp;id=' . $field->id)
            ];
        }
    }
            
    $smarty->assign([
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
    ]);
    
    $template_file = 'store/fields.tpl';
} else {
    switch ($_GET['action']) {
        case 'new';
            // New Field
            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'identifier' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 2,
                            Validate::MAX => 32
                        ],
                        'description' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 2,
                            Validate::MAX => 255
                        ]
                    ])->messages([
                        'identifier' => [
                            Validate::REQUIRED => $store_language->get('admin', 'field_identifier_required'),
                            Validate::MIN => $store_language->get('admin', 'field_identifier_minimum'),
                            Validate::MAX => $store_language->get('admin', 'field_identifier_maximum')
                        ],
                        'description' => [
                            Validate::REQUIRED => $store_language->get('admin', 'field_description_required'),
                            Validate::MIN => $store_language->get('admin', 'field_description_minimum'),
                            Validate::MAX => $store_language->get('admin', 'field_description_maximum')
                        ]
                    ]);

                    if ($validation->passed()) {
                        // Create field
                        try {
                            // Get field type
                            $type = 1;
                            if (array_key_exists($_POST['type'], $field_types)) {
                                $type = $_POST['type'];
                            }

                            // Is this field required
                            if (isset($_POST['required']) && $_POST['required'] == 'on') $required = 1;
                            else $required = 0;

                            // Get options into a string
                            $options = str_replace("\n", ',', Input::get('options'));

                            // Save to database
                            DB::getInstance()->insert('store_fields', [
                                'identifier' => Input::get('identifier'),
                                'description' => Input::get('description'),
                                'type' => $type,
                                'required' => $required,
                                'options' => $options,
                                'order' => Input::get('order'),
                                'min' => Input::get('minimum'),
                                'max' => Input::get('maximum'),
                                'default_value' => Input::get('default'),
                                'regex' => !empty(Input::get('regex')) ? Input::get('regex') : null
                            ]);

                            Session::flash('fields_success', $store_language->get('admin', 'field_created_successfully'));
                            Redirect::to(URL::build('/panel/store/fields/'));
                        } catch (Exception $e) {
                            $errors[] = $e->getMessage();
                        }
                    } else {
                        // Errors
                        $errors = $validation->errors();
                    }
                } else {
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }

            $smarty->assign([
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
                'DEFAULT_VALUE' => ((isset($_POST['default']) && $_POST['default']) ? Output::getClean(Input::get('default')) : ''),
                'MINIMUM_CHARACTERS' => $store_language->get('admin', 'minimum_characters'),
                'MINIMUM_CHARACTERS_VALUE' => ((isset($_POST['minimum']) && $_POST['minimum']) ? Output::getClean(Input::get('minimum')) : 0),
                'MAXIMUM_CHARACTERS' => $store_language->get('admin', 'maximum_characters'),
                'MAXIMUM_CHARACTERS_VALUE' => ((isset($_POST['maximum']) && $_POST['maximum']) ? Output::getClean(Input::get('maximum')) : 0),
                'REGEX_VALUE' => ((isset($_POST['regex']) && $_POST['regex']) ? Output::getClean(Input::get('regex')) : ''),
                'REQUIRED' => $language->get('admin', 'required'),
                'REQUIRED_VALUE' => ((isset($_POST['required'])) ? 1 : 0),
            ]);

            $template_file = 'store/fields_form.tpl';
        break;
        case 'edit';
            if (!is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/store/fields/'));
            } else {
                $field = DB::getInstance()->get('store_fields', ['id', '=', $_GET['id']])->results();
                if (!count($field)) {
                    Redirect::to(URL::build('/panel/store/fields/'));
                }
            }
            $field = $field[0];

            // Edit Field
            if (Input::exists()) {
                $errors = [];

                if (Token::check(Input::get('token'))) {
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'identifier' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 2,
                            Validate::MAX => 32
                        ],
                        'description' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 2,
                            Validate::MAX => 255
                        ]
                    ])->messages([
                        'identifier' => [
                            Validate::REQUIRED => $store_language->get('admin', 'field_identifier_required'),
                            Validate::MIN => $store_language->get('admin', 'field_identifier_minimum'),
                            Validate::MAX => $store_language->get('admin', 'field_identifier_maximum')
                        ],
                        'description' => [
                            Validate::REQUIRED => $store_language->get('admin', 'field_description_required'),
                            Validate::MIN => $store_language->get('admin', 'field_description_minimum'),
                            Validate::MAX => $store_language->get('admin', 'field_description_maximum')
                        ]
                    ]);

                    if ($validation->passed()) {
                        // Create field
                        try {
                            // Get field type
                            $type = 1;
                            if (array_key_exists($_POST['type'], $field_types)) {
                                $type = $_POST['type'];
                            }

                            // Is this field required
                            if (isset($_POST['required']) && $_POST['required'] == 'on') $required = 1;
                            else $required = 0;

                            // Get options into a string
                            $options = str_replace("\n", ',', Input::get('options'));

                            // Save to database
                            DB::getInstance()->update('store_fields', $field->id, [
                                'identifier' => Input::get('identifier'),
                                'description' => Input::get('description'),
                                'type' => $type,
                                'required' => $required,
                                'options' => $options,
                                'min' => Input::get('minimum'),
                                'max' => Input::get('maximum'),
                                'order' => Input::get('order'),
                                'default_value' => Input::get('default'),
                                'regex' => !empty(Input::get('regex')) ? Input::get('regex') : null
                            ]);

                            Session::flash('fields_success', $store_language->get('admin', 'field_updated_successfully'));
                            Redirect::to(URL::build('/panel/store/fields/'));
                        } catch (Exception $e) {
                            $errors[] = $e->getMessage();
                        }
                    } else {
                        // Errors
                        $errors = $validation->errors();
                    }
                } else {
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }
            
             // Get already inputted options
            if ($field->options == null) {
                $options = '';
            } else {
                $options = str_replace(',', "\n", htmlspecialchars($field->options));
            }
        
            $smarty->assign([
                'FIELD_TITLE' => $store_language->get('admin', 'editing_field_x', ['field' => Output::getClean($field->identifier)]),
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
                'DEFAULT_VALUE' => Output::getClean($field->default_value),
                'MINIMUM_CHARACTERS' => $store_language->get('admin', 'minimum_characters'),
                'MINIMUM_CHARACTERS_VALUE' => $field->min,
                'MAXIMUM_CHARACTERS' => $store_language->get('admin', 'maximum_characters'),
                'MAXIMUM_CHARACTERS_VALUE' => $field->max,
                'REGEX_VALUE' => Output::getClean($field->regex ?? ''),
                'REQUIRED' => $language->get('admin', 'required'),
                'REQUIRED_VALUE' => $field->required,
                'RESERVED_FIELD' => ($field->identifier == 'quantity' || $field->identifier == 'price')
            ]);
        
            $template_file = 'store/fields_form.tpl';
        break;
        case 'delete';
            // Delete Field
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/store/fields/'));
            }
            DB::getInstance()->update('store_fields', $_GET['id'], [
                'deleted' => date('U')
            ]);

            Session::flash('fields_success', $store_language->get('admin', 'field_deleted_successfully'));
            Redirect::to(URL::build('/panel/store/fields/'));
        break;
        default:
            Redirect::to(URL::build('/panel/store/fields'));
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('fields_success'))
    $success = Session::flash('fields_success');

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
    'FIELDS' => $store_language->get('admin', 'fields')
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);