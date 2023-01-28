<?php
// Handle input
if (Input::exists()) {
    $errors = [];

    if (Token::check()) {
        $validation = Validate::check($_POST, [
            'coupon' => [
                Validate::REQUIRED => true,
                Validate::MIN => 2,
                Validate::MAX => 64
            ]
        ]);

        if ($validation->passed()) {
            $coupon = DB::getInstance()->query('SELECT * FROM nl2_store_coupons WHERE code = ? AND start_date < ? AND expire_date > ? ', [$_POST['coupon'], date('U'), date('U')]);
            if ($coupon->count()) {

            } else {
                Session::flash('store_error', $store_language->get('general', 'invalid_coupon'));
            }
        } else {
            Session::flash('store_error', $store_language->get('general', 'invalid_coupon'));
        }
    }
}

Redirect::to(URL::build(Store::getStorePath() . '/checkout/'));