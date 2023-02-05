<?php
require_once(ROOT_PATH . '/modules/Store/core/frontend_init.php');

// Handle input
if (Input::exists()) {
    $errors = [];

    if (Token::check()) {
        $validation = Validate::check($_POST, [
            'coupon' => [
                Validate::MIN => 2,
                Validate::MAX => 64
            ]
        ]);

        if ($validation->passed()) {
            if (!empty($_POST['coupon'])) {
                $coupon = DB::getInstance()->query('SELECT * FROM nl2_store_coupons WHERE code = ? AND start_date < ? AND expire_date > ? ', [$_POST['coupon'], date('U'), date('U')]);
                if ($coupon->count()) {
                    $coupon = new Coupon($coupon->first()->id);
                    if ($coupon->exists()) {
                        // Check redeem limit
                        if ($coupon->data()->redeem_limit > 0) {
                            $limit = DB::getInstance()->query('SELECT DISTINCT(nl2_store_orders.id) FROM nl2_store_orders INNER JOIN nl2_store_payments ON nl2_store_payments.order_id=nl2_store_orders.id WHERE coupon_id = ?', [$coupon->data()->id]);
                            if (count($limit->results()) >= $coupon->data()->redeem_limit) {
                                Session::flash('store_error', $store_language->get('general', 'redeem_limit_reached'));
                                Redirect::to(URL::build(Store::getStorePath() . '/checkout'));
                            }
                        }

                        // Check customer redeem limit
                        if ($coupon->data()->customer_limit > 0) {
                            $limit = DB::getInstance()->query('SELECT DISTINCT(nl2_store_orders.id) FROM nl2_store_orders INNER JOIN nl2_store_payments ON nl2_store_payments.order_id=nl2_store_orders.id WHERE coupon_id = ? AND from_customer_id = ?', [$coupon->data()->id, $from_customer->data()->id]);
                            if (count($limit->results()) >= $coupon->data()->customer_limit) {
                                Session::flash('store_error', $store_language->get('general', 'customer_redeem_limit_reached'));
                                Redirect::to(URL::build(Store::getStorePath() . '/checkout'));
                            }
                        }

                        // Check for minimum basket value
                        if ($coupon->data()->min_basket > 0) {
                            if ($coupon->data()->min_basket > $shopping_cart->getTotalCents()) {
                                Session::flash('store_error', $store_language->get('general', 'redeem_min_basket_value', ['min_basket' => store::fromCents($coupon->data()->min_basket)]));
                                Redirect::to(URL::build(Store::getStorePath() . '/checkout'));
                            }
                        }

                        $shopping_cart->setCoupon($coupon);

                        Session::flash('store_success', $store_language->get('general', 'successfully_applied_coupon'));
                    }
                } else {
                    Session::flash('store_error', $store_language->get('general', 'invalid_coupon'));
                }
            } else {
                $shopping_cart->setCoupon(null);
            }
        } else {
            Session::flash('store_error', $store_language->get('general', 'invalid_coupon'));
        }
    }
}

Redirect::to(URL::build(Store::getStorePath() . '/checkout'));