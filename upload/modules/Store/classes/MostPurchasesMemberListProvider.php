<?php

/**
 * Most purchases member list provider
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.1.2
 * @license MIT
 */
class MostPurchasesMemberListProvider extends MemberListProvider {

    public function __construct(Language $language) {
        $this->_name = 'most_purchases';
        $this->_friendly_name = $language->get('general', 'most_purchases');
        $this->_module = 'Store';
        $this->_icon = 'shopping cart icon';
    }

    protected function generator(): array {
        return [
            'SELECT nl2_store_customers.user_id, COUNT(*) AS `count` FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id LEFT JOIN nl2_store_customers ON to_customer_id=nl2_store_customers.id INNER JOIN nl2_users ON nl2_store_customers.user_id=nl2_users.id WHERE status_id = 1 GROUP BY user_id ORDER BY `count` DESC',
            'user_id',
            'count'
        ];
    }
}