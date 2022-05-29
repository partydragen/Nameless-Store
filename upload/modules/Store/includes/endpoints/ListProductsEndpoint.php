<?php
class ListProductsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'store/products';
        $this->_module = 'Store';
        $this->_description = 'List all products or in a category';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api): void {
        $query = 'SELECT * FROM nl2_store_products';
        $where = ' WHERE deleted = 0';
        $order = ' ORDER BY `order` ASC';
        $params = [];

        if (isset($_GET['category_id'])) {
            $where .= ' AND category_id = ?';
            array_push($params, $_GET['category_id']);
        }

        // Ensure the user exists
        $products_query = $api->getDb()->query($query . $where . $order, $params)->results();

        $products_array = [];
        foreach ($products_query as $product) {
            $products_array[] = [
                'id' => (int) $product->id,
                'category_id' => (int) $product->category_id,
                'name' => Output::getClean($product->name),
                'price' => (double) $product->price,
                'hidden' => (bool) $product->hidden,
                'disabled' => (bool) $product->disabled
            ];
        }
 
        $api->returnArray(['products' => $products_array]);
    }
}
