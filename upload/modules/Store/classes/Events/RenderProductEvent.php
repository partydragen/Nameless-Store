<?php
class RenderProductEvent extends AbstractEvent {

    public Product $product;
    public ShoppingCart $shopping_cart;
    public string $name;
    public string $content;
    public ?string $image;
    public bool $hidden;

    public function __construct(Product $product, ShoppingCart $shopping_cart) {
        $this->product = $product;
        $this->shopping_cart = $shopping_cart;
        $this->name = $product->data()->name;
        $this->content = $product->data()->description;
        $this->image = (isset($product->data()->image) && !is_null($product->data()->image) ? ((defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/uploads/store/' . Output::getClean(Output::getDecoded($product->data()->image))) : null);
        $this->hidden = false;
    }

    public static function name(): string {
        return 'renderProduct';
    }

    public static function description(): string {
        return 'renderProduct';
    }

    public static function internal(): bool {
        return true;
    }

    public static function return(): bool {
        return true;
    }
}