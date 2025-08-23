<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Featured products widget
 */

class FeaturedProductsWidget extends WidgetBase {

    private Cache $_cache;
    private Language $_language;
    private Language $_store_language;

    public function __construct(TemplateEngine $engine, Language $language, Language $store_language, Cache $cache) {
        $this->_module = 'Store';
        $this->_name = 'Featured Products';
        $this->_description = 'Display a store product to feature across your website.';
        $this->_settings = ROOT_PATH . '/modules/Store/widgets/admin/featured_products.php';

        $this->_engine = $engine;
        $this->_language = $language;
        $this->_store_language = $store_language;
        $this->_cache = $cache;
    }

    public function initialise(): void {
        // Generate HTML code for widget
        $featured_products_list = [];
        $featured_products = json_decode(Settings::get('featured_products', '[]', 'Store')) ?? [];

        if (count($featured_products)) {
            $store_url = Store::getStorePath();
            $currency = Output::getClean(Store::getCurrency());
            $currency_symbol = Output::getClean(Store::getCurrencySymbol());

            foreach ($featured_products as $item) {
                $product = new Product($item);

                $renderProductEvent = new RenderProductEvent($product, ShoppingCart::getInstance());
                EventHandler::executeEvent($renderProductEvent);

                if ($renderProductEvent->hidden) {
                    continue;
                }

                $featured_products_list[] = [
                    'id' => $product->data()->id,
                    'name' => Output::getClean($renderProductEvent->name),
                    'price' => Store::fromCents($product->data()->price_cents),
                    'real_price' => Store::fromCents($product->getRealPriceCents()),
                    'sale_discount' => Store::fromCents($product->data()->sale_discount_cents),
                    'price_format' => Output::getPurified(
                        Store::formatPrice(
                            $product->data()->price_cents,
                            $currency,
                            $currency_symbol,
                            STORE_CURRENCY_FORMAT,
                        )
                    ),
                    'real_price_format' => Output::getPurified(
                        Store::formatPrice(
                            $product->getRealPriceCents(),
                            $currency,
                            $currency_symbol,
                            STORE_CURRENCY_FORMAT,
                        )
                    ),
                    'sale_discount_format' => Output::getPurified(
                        Store::formatPrice(
                            $product->data()->sale_discount_cents,
                            $currency,
                            $currency_symbol,
                            STORE_CURRENCY_FORMAT,
                        )
                    ),
                    'has_discount' => $product->data()->sale_discount_cents > 0,
                    'sale_active' => $product->data()->sale_active,
                    'description' => $renderProductEvent->content,
                    'image' => $renderProductEvent->image,
                    'link' => $product->data()->payment_type != 2 ? URL::build($store_url . '/checkout', 'add=' . Output::getClean($product->data()->id) . '&type=single') : null,
                    'subscribe_link' => $product->data()->payment_type != 1 ? URL::build($store_url . '/checkout', 'add=' . Output::getClean($product->data()->id) . '&type=subscribe') : null,
                ];
            }
        }

        $this->_engine->addVariables([
            'FEATURED_PRODUCTS' => $this->_store_language->get('general', 'featured_products'),
            'FEATURED_PRODUCTS_LIST' => $featured_products_list,
            'VIEW' => $this->_language->get('general', 'view'),
            'SALE' => $this->_store_language->get('general', 'sale')
        ]);

        $this->_content = $this->_engine->fetch('store/widgets/featured_products');
    }
}