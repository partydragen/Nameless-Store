<?php
class ProductsList extends EndpointBase {

    public function __construct() {
        $this->_route = 'storeProducts';
        $this->_module = 'Store';
        $this->_description = 'Get list of products in a category';
    }

    public function execute(Nameless2API $api) {
        $query = 'SELECT * FROM nl2_store_products WHERE deleted = 0 AND category_id = ?;';

        $where = ' WHERE deleted = 0';
        $params = array();

        if (isset($_GET['category_id'])) {
            array_push($params, $_GET['category_id']);
        }

        // Ensure the user exists
        $products_query = $api->getDb()->query($query, $params)->results();

        $products_array = array();
        foreach($products_query as $product) {
            if($product->name == null || $product->disabled) {
                continue;
            }
            
            $products_array[] = array(
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'price' => (double)$product->price,
            );
        }
        
        $api->returnArray(array('products' => $products_array));
    }
}
