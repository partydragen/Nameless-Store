<?php
class ProductsList extends EndpointBase {

    public function __construct() {
        $this->_route = 'storeProducts';
        $this->_module = 'Store';
        $this->_description = 'Get list of products in a category';
    }

    public function execute(Nameless2API $api) {
        $query = 'SELECT * FROM nl2_store_products';

        $where = ' WHERE deleted = 0';
        $params = [];

        if (isset($_GET['category_id'])) {
            $where .= ' AND category_id = ?';
            array_push($params, $_GET['category_id']);
        }

        // Ensure the user exists
        $products_query = $api->getDb()->query($query . $where, $params)->results();

        $products_array = [];
        foreach ($products_query as $product) {
            $products_array[] = [
                'id' => (int) $product->id,
                'category_id' => (int) $product->category_id,
                'name' => Output::getClean($product->name),
                'price' => (double) $product->price,
                'order' => (int) $category->order,
                'hidden' => (bool) $product->hidden,
                'disabled' => (bool) $product->disabled
            ];
        }
        
        $api->returnArray(['products' => $products_array]);
    }
}
