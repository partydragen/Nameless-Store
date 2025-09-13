<?php
class LoadShoppingCartEvent extends AbstractEvent {
    public ShoppingCart $shoppingCart;

    public function __construct(ShoppingCart $shoppingCart) {
        $this->shoppingCart = $shoppingCart;
    }

    public static function name(): string {
        return 'loadShoppingCart';
    }

    public static function description(): string {
        return 'loadShoppingCart';
    }
}