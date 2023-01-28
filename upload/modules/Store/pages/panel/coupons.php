<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel coupons page
 */

// Can the user view the StaffCP?
if (!$user->handlePanelPageLoad('staffcp.store.coupons')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

const PAGE = 'panel';
const PARENT_PAGE = 'store';
const PANEL_PAGE = 'store_coupons';
$page_title = $store_language->get('admin', 'coupons');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

$store = new Store($cache, $store_language);

if (!isset($_GET['action'])) {
    // Get coupons from database
    $coupons = DB::getInstance()->query('SELECT * FROM nl2_store_coupons ORDER BY `expire_date` DESC');
    if ($coupons->count()) {
        $coupons_list = [];

        foreach ($coupons->results() as $coupon) {
            $coupons_list[] = [
                'code' => Output::getClean($coupon->code),
                'active' => date('U') > $coupon->start_date && $coupon->expire_date > date('U'),
                'edit_link' => URL::build('/panel/store/coupons/', 'action=edit&id=' . $coupon->id),
                'delete_link' => URL::build('/panel/store/coupons/', 'action=delete&id=' . $coupon->id)
            ];
        }

        $smarty->assign([
            'COUPONS_LIST' => $coupons_list,
            'CODE' => $store_language->get('admin', 'code'),
            'ACTIVE' => $language->get('admin', 'active'),
            'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
            'CONFIRM_DELETE_COUPON' => $store_language->get('admin', 'confirm_delete_coupon'),
            'YES' => $language->get('general', 'yes'),
            'NO' => $language->get('general', 'no')
        ]);
    }

    $smarty->assign([
        'NEW_COUPON' => $store_language->get('admin', 'new_coupon'),
        'NEW_COUPON_LINK' => URL::build('/panel/store/coupons/', 'action=new'),
        'NO_COUPONS' => $store_language->get('admin', 'no_coupons'),
    ]);

    $template_file = 'store/coupons.tpl';
} else {
    switch ($_GET['action']) {
        case 'new';
            // New coupon
            if (Input::exists()) {
                $errors = [];

                if (Token::check()) {
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'code' => [
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
                        // Create coupon
                        try {
                            // Convert selected products array to int
                            $products = [];
                            foreach ($_POST['products'] ?? [] as $product) {
                                $products[] = (int) $product;
                            }

                            // Save to database
                            DB::getInstance()->insert('store_coupons', [
                                'code' => Input::get('code'),
                                'effective_on' => json_encode($products),
                                'discount_type' => Input::get('discount_type'),
                                'discount_amount' => Input::get('discount_amount'),
                                'start_date' => strtotime($_POST['start_date']),
                                'expire_date' => strtotime($_POST['expire_date']),
                                'redeem_limit' => Input::get('redeem_limit'),
                                'customer_limit' => Input::get('customer_redeem_limit'),
                                'min_basket' => Store::toCents(Input::get('min_basket')),
                            ]);

                            Session::flash('coupons_success', $store_language->get('admin', 'coupon_created_successfully'));
                            Redirect::to(URL::build('/panel/store/coupons'));
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

            $smarty->assign([
                'COUPON_TITLE' => $store_language->get('admin', 'creating_coupon'),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/coupons'),
                'PRODUCTS_LIST' => $products_list,
                'CODE' => $store_language->get('admin', 'code'),
                'CODE_VALUE' => ((isset($_POST['code']) && $_POST['code']) ? Output::getClean(Input::get('code')) : SecureRandom::alphanumeric(16)),
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
                'REDEEM_LIMIT' => $store_language->get('admin', 'redeem_limit'),
                'REDEEM_LIMIT_VALUE' => ((isset($_POST['redeem_limit']) && $_POST['redeem_limit']) ? Output::getClean(Input::get('redeem_limit')) : '0'),
                'CUSTOMER_REDEEM_LIMIT' => $store_language->get('admin', 'customer_redeem_limit'),
                'CUSTOMER_REDEEM_LIMIT_VALUE' => ((isset($_POST['customer_redeem_limit']) && $_POST['customer_redeem_limit']) ? Output::getClean(Input::get('customer_redeem_limit')) : '0'),
                'MIN_BASKET' => $store_language->get('admin', 'minimum_basket'),
                'MIN_BASKET_VALUE' => ((isset($_POST['min_basket']) && $_POST['min_basket']) ? Output::getClean(Input::get('min_basket')) : '0.00'),
            ]);

            $template_file = 'store/coupons_form.tpl';
        break;
        case 'edit';
            if (!is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/store/coupons'));
            }

            $coupon = DB::getInstance()->get('store_coupons', ['id', '=', $_GET['id']]);
            if (!$coupon->count()) {
                Redirect::to(URL::build('/panel/store/coupons'));
            }
            $coupon = $coupon->first();

            // Edit coupon
            if (Input::exists()) {
                $errors = [];

                if (Token::check()) {
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'code' => [
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
                        // Edit coupon
                        try {
                            // Convert selected products array to int
                            $products = [];
                            foreach ($_POST['products'] ?? [] as $product) {
                                $products[] = (int) $product;
                            }

                            // Save to database
                            DB::getInstance()->update('store_coupons', $coupon->id, [
                                'code' => Input::get('code'),
                                'effective_on' => json_encode($products),
                                'discount_type' => Input::get('discount_type'),
                                'discount_amount' => Input::get('discount_amount'),
                                'start_date' => strtotime($_POST['start_date']),
                                'expire_date' => strtotime($_POST['expire_date']),
                                'redeem_limit' => Input::get('redeem_limit'),
                                'customer_limit' => Input::get('customer_redeem_limit'),
                                'min_basket' => Store::toCents(Input::get('min_basket')),
                            ]);

                            Session::flash('coupons_success', $store_language->get('admin', 'coupon_updated_successfully'));
                            Redirect::to(URL::build('/panel/store/coupons'));
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
            $effective_on = json_decode($coupon->effective_on, true) ?? [];
            $products = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE deleted = 0')->results();
            foreach ($products as $product) {
                $products_list[] = [
                    'id' => $product->id,
                    'name' => Output::getClean($product->name),
                    'selected' => in_array($product->id, $effective_on)
                ];
            }

            $smarty->assign([
                'COUPON_TITLE' => $store_language->get('admin', 'editing_coupon_x', ['coupon' => Output::getClean($coupon->code)]),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/coupons'),
                'PRODUCTS_LIST' => $products_list,
                'CODE' => $store_language->get('admin', 'code'),
                'CODE_VALUE' => Output::getClean($coupon->code),
                'DISCOUNT_TYPE' => $store_language->get('admin', 'discount_type'),
                'DISCOUNT_TYPE_VALUE' => Output::getClean($coupon->discount_type),
                'AMOUNT' => $store_language->get('admin', 'amount'),
                'AMOUNT_VALUE' => Output::getClean($coupon->discount_amount),
                'PERCENTAGE' => $store_language->get('admin', 'percentage'),
                'PRODUCTS' => $store_language->get('admin', 'products'),
                'START_DATE' => $store_language->get('admin', 'start_date'),
                'START_DATE_VALUE' => date('Y-m-d\TH:i', $coupon->start_date),
                'START_DATE_MIN' => date('Y-m-d\TH:i', $coupon->start_date),
                'EXPIRE_DATE' => $store_language->get('admin', 'expire_date'),
                'EXPIRE_DATE_VALUE' => date('Y-m-d\TH:i', $coupon->expire_date),
                'EXPIRE_DATE_MIN' => date('Y-m-d\TH:i', $coupon->expire_date),
                'REDEEM_LIMIT' => $store_language->get('admin', 'redeem_limit'),
                'REDEEM_LIMIT_VALUE' => Output::getClean($coupon->redeem_limit),
                'CUSTOMER_REDEEM_LIMIT' => $store_language->get('admin', 'customer_redeem_limit'),
                'CUSTOMER_REDEEM_LIMIT_VALUE' => Output::getClean($coupon->customer_limit),
                'MIN_BASKET' => $store_language->get('admin', 'minimum_basket'),
                'MIN_BASKET_VALUE' => Store::fromCents($coupon->min_basket),
            ]);

            $template_file = 'store/coupons_form.tpl';
        break;
        case 'delete';
            // Delete coupon
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Redirect::to(URL::build('/panel/store/coupons'));
            }

            if (Token::check()) {
                DB::getInstance()->delete('store_coupons', ['id', $_GET['id']]);
                Session::flash('coupons_success', $store_language->get('admin', 'coupon_deleted_successfully'));
            } else {
                Session::flash('coupons_success', $language->get('general', 'invalid_token'));
            }
            Redirect::to(URL::build('/panel/store/coupons'));
        break;
        default:
            Redirect::to(URL::build('/panel/store/coupons'));
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('coupons_success'))
    $success = Session::flash('coupons_success');

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
    'COUPONS' => $store_language->get('admin', 'coupons')
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);