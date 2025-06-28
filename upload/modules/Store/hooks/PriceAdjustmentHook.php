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

    // Cumulative pricing
    public static function cumulativePricing(RenderProductEvent $event): void {
        $product = $event->product;
        $recipient = $event->shopping_cart->getRecipient();
        if ($recipient->exists()) {

            // Check if category exists and has cumulative pricing enabled
            $category_query = DB::getInstance()->query('SELECT cumulative_pricing FROM nl2_store_categories WHERE id = ?', [$product->data()->category_id]);
            if (!$category_query->count() || $category_query->first()->cumulative_pricing == 1) {
                $product->data()->sale_discount_cents = $recipient->calculateSpendingInCategory($product->data()->category_id);
            }

        }
    }

    // Check for discounts
    public static function discounts(RenderProductEvent $event): void {
        $sales = Store::getActiveSales();

        // Handle sales
        foreach ($sales as $sale) {
            $products = json_decode($sale->effective_on ?? '[]');

            $product = $event->product;
            if (in_array($product->data()->id, $products)) {

                if ($sale->discount_type == 1) {
                    // Percentage discount
                    $discount_amount = $product->data()->price_cents * ($sale->discount_amount / 100);

                    $product->data()->sale_active = true;
                    $product->data()->sale_discount_cents += $discount_amount;
                } else if ($sale->discount_type == 2) {
                    // Amount discount
                    $product->data()->sale_active = true;
                    $product->data()->sale_discount_cents += Store::toCents($sale->discount_amount);
                }
            }

            // Prevent the discount from being more than the price itself
            if ($product->data()->sale_discount_cents >= $product->data()->price_cents) {
                $product->data()->sale_discount_cents = $product->data()->price_cents;
            }
        }

        // Handle coupon
        $coupon = $event->shopping_cart->getCoupon();
        if ($coupon != null) {
            $products = json_decode($coupon->data()->effective_on ?? '[]');

            $product = $event->product;
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
    }
}