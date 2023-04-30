<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *  NamelessMC version 2.1.0
 *
 *  Price Adjustment hooks
 */

class PriceAdjustmentHook extends HookBase {
    // Check for discounts
    public static function discounts(array $params = []): array {
        $sales = Store::getActiveSales();

        // Handle sales
        foreach ($sales as $sale) {
            $products = json_decode($sale->effective_on ?? '[]');

            $product = $params['product'];
            if (in_array($product->data()->id, $products)) {

                if ($sale->discount_type == 1) {
                    // Percentage discount
                    $discount_amount = $product->data()->price_cents * ($sale->discount_amount / 100);

                    $product->data()->sale_active = true;
                    $product->data()->sale_discount_cents = $discount_amount;
                } else if ($sale->discount_type == 2) {
                    // Amount discount
                    $product->data()->sale_active = true;
                    $product->data()->sale_discount_cents = Store::toCents($sale->discount_amount);
                }
            }

            // Prevent the discount from being more than the price itself
            if ($product->data()->sale_discount_cents >= $product->data()->price_cents) {
                $product->data()->sale_discount_cents = $product->data()->price_cents;
            }
        }

        // Handle coupon
        $coupon = $params['shopping_cart']->getCoupon();
        if ($coupon != null) {
            $products = json_decode($coupon->data()->effective_on ?? '[]');

            $product = $params['product'];
            if (in_array($product->data()->id, $products)) {

                if ($coupon->data()->discount_type == 1) {
                    // Percentage discount
                    $discount_amount = $product->data()->price_cents * ($coupon->data()->discount_amount / 100);

                    $product->data()->sale_active = true;
                    $product->data()->sale_discount_cents = $discount_amount;
                } else if ($coupon->data()->discount_type == 2) {
                    // Amount discount
                    $product->data()->sale_active = true;
                    $product->data()->sale_discount_cents = Store::toCents($coupon->data()->discount_amount);
                }
            }

            // Prevent the discount from being more than the price itself
            if ($product->data()->sale_discount_cents >= $product->data()->price_cents) {
                $product->data()->sale_discount_cents = $product->data()->price_cents;
            }
        }

        return $params;
    }
}