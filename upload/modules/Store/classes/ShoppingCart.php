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
            $_packages;
    
    // Constructor
    public function __construct(){
        $this->_items = (isset($_SESSION['shopping_cart']) ? $_SESSION['shopping_cart'] : array());
        
        if(count($this->_items)) {
            $packages_ids = '(';
            foreach($this->_items as $package) {
                $packages_ids .= (int) $package['id'] . ',';
            }
            $packages_ids = rtrim($packages_ids, ',');
            $packages_ids .= ')';
            
            // Get packages
            $this->_packages = DB::getInstance()->query('SELECT * FROM nl2_store_packages WHERE id in '.$packages_ids.' AND deleted = 0 ')->results();
        }
    }
    
    // Add package to shopping cart
    public function add($package_id, $quantity = 1) {
        $shopping_cart = (isset($_SESSION['shopping_cart']) ? $_SESSION['shopping_cart'] : array());
        
        $shopping_cart[$package_id] = array(
            'id' => $package_id,
            'quantity' => $quantity
        );
        
        $_SESSION['shopping_cart'] = $shopping_cart;
    }
    
    // Remove package from shopping cart
    public function remove($package_id) {
        unset($_SESSION['shopping_cart'][$package_id]);
    }
    
    // Clear the shopping cart
    public function clear() {
        unset($_SESSION['shopping_cart']);
    }
    
    // Get the items from the shopping cart
    public function getItems() {
        return $this->_items;
    }
    
    // Get the packages from the shopping cart
    public function getPackages() {
        return $this->_packages;
    }
    
    // Get total price to pay
    public function getTotalPrice() {
        $price = 0;
        
        foreach($this->_packages as $package) {
            $price += $package->price;
        }
        
        return $price;
    }
}