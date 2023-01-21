<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *  NamelessMC version 2.0.2
 *
 *  Price Adjustment hooks
 */

class PriceAdjustmentHook extends HookBase {
    // Check for discounts
    public static function discounts(array $params = []): array {
        $sales = Store::getActiveSales();
        
        foreach ($sales as $sale) {
            $products = json_decode($sale->effective_on ?? []);

            $product = $params['product'];
            if (in_array($product->data()->id, $products)) {

                if ($sale->discount_type == 1) {
                    // Percentage discount
                    $discount_amount = $product->data()->price * ($sale->discount_amount / 100);

                    $product->data()->sale_active = true;
                    $product->data()->sale_discount = $discount_amount;
                } else if ($sale->discount_type == 2) {
                    // Amount discount
                    $product->data()->sale_active = true;
                    $product->data()->sale_discount = $sale->discount_amount;
                }
                
                // Prevent the discount from being more than the price itself
                if ($product->data()->sale_discount >= $product->data()->price) {
                    $product->data()->sale_discount = $product->data()->price;
                }
            }
        }

        return $params;
    }
}