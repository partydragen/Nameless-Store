<?php
class ListCategoriesEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'store/categories';
        $this->_module = 'Store';
        $this->_description = 'List all store categories';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api): void {
        $query = 'SELECT * FROM nl2_store_categories WHERE deleted = 0;';

        // Ensure the user exists
        $categories_query = $api->getDb()->query($query)->results();

        $categories_array = [];
        foreach ($categories_query as $category) {
            $category_array[] = [
                'id' => (int) $category->id,
                'name' => Output::getClean($category->name),
                'order' => (int) $category->order,
                'hidden' => (bool) $product->hidden,
                'disabled' => (bool) $product->disabled
            ];
        }

        $api->returnArray(['categories' => $category_array]);
    }
}
