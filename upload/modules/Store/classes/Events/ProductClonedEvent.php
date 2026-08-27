<?php

class ProductClonedEvent extends AbstractEvent {

    /** The newly created product. */
    public Product $product;

    /** The source product that was cloned. */
    public Product $cloned_product;

    public function __construct(int $product_id, int $cloned_product_id) {
        $this->product = new Product((string) $product_id);
        $this->cloned_product = new Product((string) $cloned_product_id);
    }

    public static function name(): string {
        return 'cloneProduct';
    }

    public static function description(): string {
        return (new Language(ROOT_PATH . '/modules/Store/language'))->get('admin', 'clone_product');
    }

    public static function internal(): bool {
        return true;
    }
}
