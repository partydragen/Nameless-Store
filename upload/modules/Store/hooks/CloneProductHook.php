<?php
/*
 * Clone product event listener handler class.
 */

class CloneProductHook {

    public static function execute(ProductClonedEvent $event): void {
        $db = DB::getInstance();
        $new_product_id = $event->product->data()->id;
        $source_product_id = $event->cloned_product->data()->id;
        $insert = static function (string $table, array $data) use ($db): void {
            if (!$db->insert($table, $data)) {
                throw new RuntimeException('Unable to clone product data into ' . $table);
            }
        };

        // Clone checkout fields assigned to the product.
        $fields = $db->query('SELECT * FROM nl2_store_products_fields WHERE product_id = ?', [$source_product_id]);
        foreach ($fields->results() as $field) {
            $data = get_object_vars($field);
            unset($data['id']);
            $data['product_id'] = $new_product_id;
            $insert('store_products_fields', $data);
        }

        // Clone the product-level service connections.
        $connections = $db->query('SELECT * FROM nl2_store_products_connections WHERE product_id = ? AND action_id IS NULL', [$source_product_id]);
        foreach ($connections->results() as $connection) {
            $data = get_object_vars($connection);
            unset($data['id']);
            $data['product_id'] = $new_product_id;
            $insert('store_products_connections', $data);
        }

        // Clone product-specific actions and remap their own service connections.
        $actions = $db->query('SELECT * FROM nl2_store_products_actions WHERE product_id = ? ORDER BY `order` ASC', [$source_product_id]);
        foreach ($actions->results() as $action) {
            $source_action_id = $action->id;
            $data = get_object_vars($action);
            unset($data['id']);
            $data['product_id'] = $new_product_id;

            $insert('store_products_actions', $data);

            $new_action_id = $db->lastId();
            $action_connections = $db->query('SELECT * FROM nl2_store_products_connections WHERE action_id = ?', [$source_action_id]);
            foreach ($action_connections->results() as $connection) {
                $connection_data = get_object_vars($connection);
                unset($connection_data['id']);
                $connection_data['product_id'] = $new_product_id;
                $connection_data['action_id'] = $new_action_id;
                $insert('store_products_connections', $connection_data);
            }
        }

        // Keep the clone in the same sales and coupon scopes as the source.
        foreach (['store_sales', 'store_coupons'] as $table) {
            $records = $db->query('SELECT id, effective_on FROM nl2_' . $table);
            foreach ($records->results() as $record) {
                $product_ids = json_decode($record->effective_on ?? '[]', true);
                if (!is_array($product_ids)) {
                    continue;
                }

                $product_ids = array_map('intval', $product_ids);
                if (!in_array($source_product_id, $product_ids, true)) {
                    continue;
                }

                $product_ids[] = $new_product_id;
                if (!$db->update($table, $record->id, [
                    'effective_on' => json_encode(array_values(array_unique($product_ids)))
                ])) {
                    throw new RuntimeException('Unable to clone product scope in ' . $table);
                }
            }
        }

        // Preserve featured-product placement when the widget is configured.
        $featured_products = json_decode(Settings::get('featured_products', '[]', 'Store'), true);
        $featured_products = is_array($featured_products) ? $featured_products : [];
        $featured_products = array_map('intval', $featured_products);
        if (in_array($source_product_id, $featured_products, true)) {
            $featured_products[] = $new_product_id;
            $featured_products = json_encode(array_values(array_unique($featured_products)));
            $setting_update = $db->query(
                'INSERT INTO nl2_settings (`name`, `value`, `module`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = ?',
                ['featured_products', $featured_products, 'Store', $featured_products]
            );
            if ($setting_update->error()) {
                throw new RuntimeException('Unable to clone featured product setting');
            }
        }

        // Gateway metadata is intentionally not cloned: it contains external product
        // and billing-plan identifiers which must be generated for the new product.
    }
}
