<?php
class CategoryList extends EndpointBase {

    public function __construct() {
        $this->_route = 'storeCategories';
        $this->_module = 'Store';
        $this->_description = 'Get list of categories';
    }

    public function execute(Nameless2API $api) {
        $query = 'SELECT * FROM nl2_store_categories WHERE deleted = 0;';

        // Ensure the user exists
        $categories_query = $api->getDb()->query($query)->results();

        $categories_array = array();
        foreach($categories_query as $category) {
            if($category->name == null) {
                continue;
            }
            
            $category_array[] = array(
                'id' => $category->id,
                'name' => $category->name,
                'order' => (int)$category->order,
            );
        }
        
        $api->returnArray(array('categories' => $category_array));
    }
}
