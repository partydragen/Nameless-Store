<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - ShoppingCart class
 */
 
class ShoppingCart {
    private $_items,
            $_products;
    
    // Constructor
    public function __construct(){
        $this->_items = (isset($_SESSION['shopping_cart']) ? $_SESSION['shopping_cart'] : array());
        
        if(count($this->_items)) {
            $products_ids = '(';
            foreach($this->_items as $product) {
                $products_ids .= (int) $product['id'] . ',';
            }
            $products_ids = rtrim($products_ids, ',');
            $products_ids .= ')';
            
            // Get prodcuts
            $this->_products = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE id in '.$products_ids.' AND deleted = 0 ')->results();
        }
    }
    
    // Add product to shopping cart
    public function add($product_id, $quantity = 1) {
        $shopping_cart = (isset($_SESSION['shopping_cart']) ? $_SESSION['shopping_cart'] : array());
        
        $shopping_cart[$product_id] = array(
            'id' => $product_id,
            'quantity' => $quantity
        );
        
        $_SESSION['shopping_cart'] = $shopping_cart;
    }
    
    // Remove product from shopping cart
    public function remove($product_id) {
        unset($_SESSION['shopping_cart'][$product_id]);
    }
    
    // Clear the shopping cart
    public function clear() {
        unset($_SESSION['shopping_cart']);
    }
    
    // Get the items from the shopping cart
    public function getItems() {
        return $this->_items;
    }
    
    // Get the products from the shopping cart
    public function getProducts() {
        return $this->_products;
    }
    
    // Get total price to pay
    public function getTotalPrice() {
        $price = 0;
        
        foreach($this->_products as $product) {
            $price += $product->price;
        }
        
        return $price;
    }
}