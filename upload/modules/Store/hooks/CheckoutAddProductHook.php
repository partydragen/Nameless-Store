<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *  NamelessMC version 2.1.0
 *
 *  CheckoutAddProduct hooks
 */

class CheckoutAddProductHook extends HookBase {

    // Check global product limit
    public static function globalLimit(CheckoutAddProductEvent $event): void {
        $product = $event->product;

        $global_limit = json_decode($product->data()->global_limit, true) ?? [];
        if (isset($global_limit['limit']) && $global_limit['limit'] > 0) {
            // Check if period is used
            if (isset($global_limit['period']) && $global_limit['period'] != 'no_period' && isset($global_limit['interval']) && $global_limit['interval'] > 0) {
                $limit = DB::getInstance()->query('SELECT DISTINCT(nl2_store_orders_products.order_id) FROM nl2_store_orders_products INNER JOIN nl2_store_payments ON nl2_store_payments.order_id=nl2_store_orders_products.order_id WHERE product_id = ? AND created > ?', [$product->data()->id, strtotime('-'.$global_limit['interval'].' ' . $global_limit['period'])]);
            } else {
                $limit = DB::getInstance()->query('SELECT DISTINCT(nl2_store_orders_products.order_id) FROM nl2_store_orders_products INNER JOIN nl2_store_payments ON nl2_store_payments.order_id=nl2_store_orders_products.order_id WHERE product_id = ?', [$product->data()->id]);
            }

            if (count($limit->results()) >= $global_limit['limit']) {
                $event->setCancelled(true, Store::getLanguage()->get('general', 'product_global_limit_reached'));
            }
        }
    }

    // Check user product limit
    public static function userLimit(CheckoutAddProductEvent $event): void {
        $product = $event->product;
        $recipient = $event->recipient;

        $user_limit = json_decode($product->data()->user_limit, true) ?? [];
        if (isset($user_limit['limit']) && $user_limit['limit'] > 0) {
            // Check if period is used
            if (isset($user_limit['period']) && $user_limit['period'] != 'no_period' && isset($user_limit['interval']) && $user_limit['interval'] > 0) {
                $limit = DB::getInstance()->query('SELECT DISTINCT(nl2_store_orders_products.order_id) FROM nl2_store_orders_products INNER JOIN nl2_store_orders ON nl2_store_orders.id=nl2_store_orders_products.order_id INNER JOIN nl2_store_payments ON nl2_store_payments.order_id=nl2_store_orders_products.order_id WHERE product_id = ? AND to_customer_id = ? AND nl2_store_orders.created > ?', [$product->data()->id, $recipient->data()->id, strtotime('-'.$user_limit['interval'].' ' . $user_limit['period'])]);
            } else {
                $limit = DB::getInstance()->query('SELECT DISTINCT(nl2_store_orders_products.order_id) FROM nl2_store_orders_products INNER JOIN nl2_store_orders ON nl2_store_orders.id=nl2_store_orders_products.order_id INNER JOIN nl2_store_payments ON nl2_store_payments.order_id=nl2_store_orders_products.order_id WHERE product_id = ? AND to_customer_id = ?', [$product->data()->id, $recipient->data()->id]);
            }

            if (count($limit->results()) >= $user_limit['limit']) {
                $event->setCancelled(true, Store::getLanguage()->get('general', 'product_user_limit_reached'));
            }
        }
    }

    // Check for required products
    public static function requiredProducts(CheckoutAddProductEvent $event): void {
        $product = $event->product;
        $recipient = $event->recipient;

        $required_products = json_decode($product->data()->required_products, true) ?? [];
        if (count($required_products)) {
            $has_bought = [];
            $bought_products = $recipient->getPurchasedProducts();
            foreach ($required_products as $item) {
                if (array_key_exists($item, $bought_products)) {
                    $has_bought[] = $item;
                }
            }

            // Check if user only need one or all of the required products.
            if ($product->data()->require_one_product ? count($has_bought) < 1 : count($has_bought) != count($required_products)) {
                foreach ($required_products as $item) {
                    if (!array_key_exists($item, $bought_products)) {
                        $target_product = new Product($item);

                        $event->setCancelled(true, Store::getLanguage()->get('general', 'product_requires_products', [
                            'product' => Output::getClean($target_product->data()->name)
                        ]));
                    }
                }
            }
        }
    }

    // Check for required groups
    public static function requiredGroups(CheckoutAddProductEvent $event): void {
        $user = $event->user;

        if ($user->isLoggedIn()) {
            $product = $event->product;
            $recipient = $event->recipient;

            $required_groups = json_decode($product->data()->required_groups, true) ?? [];
            if (count($required_groups)) {
                $user_groups = $recipient->getUser()->getAllGroupIds();
                foreach ($required_groups as $item) {
                    if(!array_key_exists($item, $user_groups)) {
                        $group = DB::getInstance()->query('SELECT name FROM nl2_groups WHERE id = ?', [$item])->first();

                        $event->setCancelled(true, Store::getLanguage()->get('general', 'product_requires_groups', [
                            'group' => Output::getClean($group->name ?? 'Unknown')
                        ]));
                    }
                }
            }
        }
    }

    // Check for any required integrations
    public static function requiredIntegrations(CheckoutAddProductEvent $event): void {
        $user = $event->user;

        if ($user->isLoggedIn()) {
            $product = $event->product;

            foreach ($product->getRequiredIntegrations() as $integration) {
                $integrationUser = $user->getIntegration($integration->getName());
                if ($integrationUser == null || $integrationUser->data()->username == null || $integrationUser->data()->identifier == null) {
                    $event->setCancelled(true, Store::getLanguage()->get('general', 'product_requires_integration', [
                        'integration' => Output::getClean($integration->getName()),
                        'linkStart' => '<a href="' . URL::build('/user/connections') . '">',
                        'linkEnd' => '</a>'
                    ]));
                }
            }
        }
    }
}