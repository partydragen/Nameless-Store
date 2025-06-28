<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *  NamelessMC version 2.1.0
 *
 *  Price Adjustment hooks
 */

class ProductVisibilityHook extends HookBase {

    public static function execute(array $params = []): array {
        $product = $params['product'];
        $recipient = $params['shopping_cart']->getRecipient();
        if ($product->data()->hide_if_owned) {
            $user_limit = json_decode($product->data()->user_limit, true) ?? [];
            if (isset($user_limit['limit']) && $user_limit['limit'] > 0) {

                // Check if period is used
                if (isset($user_limit['period']) && $user_limit['period'] != 'no_period' && isset($user_limit['interval']) && $user_limit['interval'] > 0) {
                    $limit = DB::getInstance()->query('SELECT DISTINCT(nl2_store_orders_products.order_id) FROM nl2_store_orders_products INNER JOIN nl2_store_orders ON nl2_store_orders.id=nl2_store_orders_products.order_id INNER JOIN nl2_store_payments ON nl2_store_payments.order_id=nl2_store_orders_products.order_id WHERE product_id = ? AND to_customer_id = ? AND nl2_store_orders.created > ?', [$product->data()->id, $recipient->data()->id, strtotime('-'.$user_limit['interval'].' ' . $user_limit['period'])]);
                } else {
                    $limit = DB::getInstance()->query('SELECT DISTINCT(nl2_store_orders_products.order_id) FROM nl2_store_orders_products INNER JOIN nl2_store_orders ON nl2_store_orders.id=nl2_store_orders_products.order_id INNER JOIN nl2_store_payments ON nl2_store_payments.order_id=nl2_store_orders_products.order_id WHERE product_id = ? AND to_customer_id = ?', [$product->data()->id, $recipient->data()->id]);
                }

                if (count($limit->results()) >= $user_limit['limit']) {
                    $params['hidden'] = true;
                }
            }
        }

        return $params;
    }

}