<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel sales page
 */

// Can the user view the StaffCP?
if (!$user->handlePanelPageLoad('staffcp.store.sales')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

const PAGE = 'panel';
const PARENT_PAGE = 'store';
const PANEL_PAGE = 'store_sales';
$page_title = $store_language->get('admin', 'sales');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

$store = new Store();

if (!isset($_GET['action'])) {
    // Get sales from database
    $sales = DB::getInstance()->query('SELECT * FROM nl2_store_sales ORDER BY `expire_date` DESC');
    if ($sales->count()) {
        $sales_list = [];

        foreach ($sales->results() as $sale) {
            $sales_list[] = [
                'name' => Output::getClean($sale->name),
                'active' => date('U') > $sale->start_date && $sale->expire_date > date('U'),
                'edit_link' => URL::build('/panel/store/sales/', 'action=edit&id=' . $sale->id),
                'delete_link' => URL::build('/panel/store/sales/', 'action=delete&id=' . $sale->id)
            ];
        }

        $template->getEngine()->addVariables([
            'SALES_LIST' => $sales_list,
            'NAME' => $store_language->get('admin', 'name'),
            'ACTIVE' => $language->get('admin', 'active'),
            'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
            'CONFIRM_DELETE_SALE' => $store_language->get('admin', 'confirm_delete_sale'),
            'YES' => $language->get('general', 'yes'),
            'NO' => $language->get('general', 'no')
        ]);
    }

    $template->getEngine()->addVariables([
        'NEW_SALE' => $store_language->get('admin', 'new_sale'),
        'NEW_SALE_LINK' => URL::build('/panel/store/sales/', 'action=new'),
        'NO_SALES' => $store_language->get('admin', 'no_sales'),
    ]);

    $template_file = 'store/sales';
} else {
    switch ($_GET['action']) {
        case 'new';
            // New Sale
            if (Input::exists()) {
                $errors = [];

                if (Token::check()) {
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'name' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 2,
                            Validate::MAX => 64
                        ],
                        'discount_type' => [
                            Validate::REQUIRED => true,
                        ],
                        'discount_amount' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 1,
                            Validate::MAX => 11,
                            Validate::NUMERIC => true
                        ],
                        'start_date' => [
                            Validate::REQUIRED => true
                        ],
                        'expire_date' => [
                            Validate::REQUIRED => true
                        ]
                    ]);

                    if ($validation->passed()) {
                        // Create sale
                        try {
                            // Convert selected products array to int
                            $products = [];
                            foreach ($_POST['products'] ?? [] as $product) {
                                $products[] = (int) $product;
                            }

                            // Save to database
                            DB::getInstance()->insert('store_sales', [
                                'name' => Input::get('name'),
                                'effective_on' => json_encode($products),
                                'discount_type' => Input::get('discount_type'),
                                'discount_amount' => Input::get('discount_amount'),
                                'start_date' => strtotime($_POST['start_date']),
                                'expire_date' => strtotime($_POST['expire_date']),
                            ]);

                            Session::flash('sales_success', $store_language->get('admin', 'sale_created_successfully'));
                            Redirect::to(URL::build('/panel/store/sales/'));
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

            $products_list = [];
            $products = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE deleted = 0')->results();
            foreach ($products as $product) {
                $products_list[] = [
                    'id' => $product->id,
                    'name' => Output::getClean($product->name),
                    'selected' => ((isset($_POST['products']) && is_array($_POST['products'])) ? in_array($product->id, $_POST['products']) : false)
                ];
            }

            $template->getEngine()->addVariables([
                'SALE_TITLE' => $store_language->get('admin', 'creating_sale'),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/sales/'),
                'PRODUCTS_LIST' => $products_list,
                'NAME' => $store_language->get('admin', 'name'),
                'NAME_VALUE' => ((isset($_POST['name']) && $_POST['name']) ? Output::getClean(Input::get('name')) : ''),
                'DISCOUNT_TYPE' => $store_language->get('admin', 'discount_type'),
                'DISCOUNT_TYPE_VALUE' => ((isset($_POST['discount_type']) && $_POST['discount_type']) ? Output::getClean(Input::get('discount_type')) : ''),
                'AMOUNT' => $store_language->get('admin', 'amount'),
                'AMOUNT_VALUE' => ((isset($_POST['discount_amount']) && $_POST['discount_amount']) ? Output::getClean(Input::get('discount_amount')) : ''),
                'PERCENTAGE' => $store_language->get('admin', 'percentage'),
                'PRODUCTS' => $store_language->get('admin', 'products'),
                'START_DATE' => $store_language->get('admin', 'start_date'),
                'START_DATE_VALUE' => ((isset($_POST['start_date']) && $_POST['start_date']) ? Input::get('start_date') : date('Y-m-d\TH:i')),
                'START_DATE_MIN' => date('Y-m-d\TH:i'),
                'EXPIRE_DATE' => $store_language->get('admin', 'expire_date'),
                'EXPIRE_DATE_VALUE' => ((isset($_POST['expire_date']) && $_POST['expire_date']) ? Input::get('expire_date') : date('Y-m-d\TH:i', strtotime('+7 days'))),
                'EXPIRE_DATE_MIN' => date('Y-m-d\TH:i'),
            ]);

            $template_file = 'store/sales_form';
        break;
        case 'edit';
            if (!is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/store/sales/'));
            }

            $sale = DB::getInstance()->get('store_sales', ['id', '=', $_GET['id']]);
            if (!$sale->count()) {
                Redirect::to(URL::build('/panel/store/sales/'));
            }
            $sale = $sale->first();

            // Edit Sale
            if (Input::exists()) {
                $errors = [];

                if (Token::check()) {
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'name' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 2,
                            Validate::MAX => 64
                        ],
                        'discount_type' => [
                            Validate::REQUIRED => true,
                        ],
                        'discount_amount' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 1,
                            Validate::MAX => 11,
                            Validate::NUMERIC => true
                        ],
                        'start_date' => [
                            Validate::REQUIRED => true
                        ],
                        'expire_date' => [
                            Validate::REQUIRED => true
                        ]
                    ]);

                    if ($validation->passed()) {
                        // Edit sale
                        try {
                            // Convert selected products array to int
                            $products = [];
                            foreach ($_POST['products'] ?? [] as $product) {
                                $products[] = (int) $product;
                            }

                            // Save to database
                            DB::getInstance()->update('store_sales', $sale->id, [
                                'name' => Input::get('name'),
                                'effective_on' => json_encode($products),
                                'discount_type' => Input::get('discount_type'),
                                'discount_amount' => Input::get('discount_amount'),
                                'start_date' => strtotime($_POST['start_date']),
                                'expire_date' => strtotime($_POST['expire_date']),
                            ]);

                            Session::flash('sales_success', $store_language->get('admin', 'sale_updated_successfully'));
                            Redirect::to(URL::build('/panel/store/sales/'));
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

            $products_list = [];
            $effective_on = json_decode($sale->effective_on, true) ?? [];
            $products = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE deleted = 0')->results();
            foreach ($products as $product) {
                $products_list[] = [
                    'id' => $product->id,
                    'name' => Output::getClean($product->name),
                    'selected' => in_array($product->id, $effective_on)
                ];
            }

            $template->getEngine()->addVariables([
                'SALE_TITLE' => $store_language->get('admin', 'editing_sale_x', ['sale' => Output::getClean($sale->name)]),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/sales/'),
                'PRODUCTS_LIST' => $products_list,
                'NAME' => $store_language->get('admin', 'name'),
                'NAME_VALUE' => Output::getClean($sale->name),
                'DISCOUNT_TYPE' => $store_language->get('admin', 'discount_type'),
                'DISCOUNT_TYPE_VALUE' => Output::getClean($sale->discount_type),
                'AMOUNT' => $store_language->get('admin', 'amount'),
                'AMOUNT_VALUE' => Output::getClean($sale->discount_amount),
                'PERCENTAGE' => $store_language->get('admin', 'percentage'),
                'PRODUCTS' => $store_language->get('admin', 'products'),
                'START_DATE' => $store_language->get('admin', 'start_date'),
                'START_DATE_VALUE' => date('Y-m-d\TH:i', $sale->start_date),
                'START_DATE_MIN' => date('Y-m-d\TH:i', $sale->start_date),
                'EXPIRE_DATE' => $store_language->get('admin', 'expire_date'),
                'EXPIRE_DATE_VALUE' => date('Y-m-d\TH:i', $sale->expire_date),
                'EXPIRE_DATE_MIN' => date('Y-m-d\TH:i', $sale->expire_date),
            ]);

            $template_file = 'store/sales_form';
        break;
        case 'delete';
            // Delete sale
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/store/sales/'));
            }

            if (Token::check()) {
                DB::getInstance()->delete('store_sales', ['id', $_GET['id']]);
                Session::flash('sales_success', $store_language->get('admin', 'sale_deleted_successfully'));
            } else {
                Session::flash('sales_success', $language->get('general', 'invalid_token'));
            }
            Redirect::to(URL::build('/panel/store/sales'));
        break;
        default:
            Redirect::to(URL::build('/panel/store/sales'));
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('sales_success'))
    $success = Session::flash('sales_success');

if (isset($success))
    $template->getEngine()->addVariables([
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ]);

if (isset($errors) && count($errors))
    $template->getEngine()->addVariables([
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ]);

$template->getEngine()->addVariables([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('general', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'SALES' => $store_language->get('admin', 'sales')
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file);