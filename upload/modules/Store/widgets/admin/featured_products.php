<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Featured products widget settings
 */

// Check input
if (Input::exists()) {
    if (Token::check(Input::get('token'))) {

        $products = [];
        if (isset($_POST['featured_products']) && count($_POST['featured_products'])) {
            foreach ($_POST['featured_products'] as $product) {
                $products[] = intval($product);
            }
        }

        Settings::set('featured_products', json_encode($products), 'Store');
        $success = $language->get('admin', 'widget_updated');
    } else {
        $errors = [$language->get('general', 'invalid_token')];
    }
}

$featured_products = json_decode(Settings::get('featured_products', '[]', 'Store')) ?? [];
$products = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE deleted = 0 ORDER BY `order` ASC');
$products_list = [];

if ($products->count()) {
    foreach ($products->results() as $product) {
        $products_list[] = [
            'value' => Output::getClean($product->id),
            'name' => Output::getClean($product->name),
            'selected' => in_array($product->id, $featured_products)
        ];
    }
} else
    $template->getEngine()->addVariable('NO_PRODUCTS', $store_language->get('general', 'no_products'));

$template->getEngine()->addVariables([
    'INFO' => $language->get('general', 'info'),
    'WIDGET_CACHED' => $store_language->get('general', 'widget_cached'),
    'FEATURED_PRODUCTS' => $store_language->get('general', 'featured_products'),
    'PRODUCTS_LIST' => $products_list,
    'SETTINGS_TEMPLATE' => 'store/widgets/featured_products.tpl'
]);

$template->addJSScript('
    $(document).ready(() => {
        $(\'#inputFeaturedProducts\').select2({ placeholder: "No products selected" });
    })
');