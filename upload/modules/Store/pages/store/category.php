<?php
/*
 * Made by Partydragen
 * [https://partydragen.com/resources/resource/5-store-module/](https://partydragen.com/resources/resource/5-store-module/)
 * [https://partydragen.com/](https://partydragen.com/)
 *
 * License: MIT
 *
 * Store module - Category Page
 */

// Always define page name
define('PAGE', 'store');

// Get category ID
$category_id = explode('/', $route);
$category_id = $category_id[count($category_id) - 1];

if (!strlen($category_id)) {
    require_once(ROOT_PATH . '/404.php');
    die();
}

if (is_numeric($category_id)) {
    // Query category by id
    $category_query = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE id = ? AND disabled = 0 AND deleted = 0', [$category_id]);
} else {
    // Query category by url
    $category_query = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE url = ? AND disabled = 0 AND deleted = 0', [$category_id]);
}

if (!$category_query->count()) {
    require_once(ROOT_PATH . '/404.php');
    die();
}
$category = $category_query->first();
$category_id = $category->id;

$store_url = Store::getStorePath();
$page_title = Output::getClean($category->name);
require_once(ROOT_PATH . '/core/templates/frontend_init.php');
require_once(ROOT_PATH . '/modules/Store/core/frontend_init.php');

// Handle player login form submission
if (Input::exists()) {
    if (Token::check()) {
        if (Input::get('type') == 'store_logout') {
            // The logout logic is handled in frontend_init.php, we just need to redirect
            Redirect::to(URL::build($store_url . '/category/' . $category->id));
        } else {
            $validation = Validate::check($_POST, [
                'username' => [
                    Validate::REQUIRED => true,
                    Validate::MIN => 3,
                    Validate::MAX => 16
                ]
            ]);

            if ($validation->passed()) {
                // Attempt to load customer
                if ($to_customer->login(Input::get('username'))) {
                    Redirect::to(URL::build($store_url . '/category/' . $category->id));
                } else {
                    $errors[] = $store_language->get('general', 'unable_to_find_player');
                }
            } else {
                $errors[] = $store_language->get('general', 'unable_to_find_player');
            }
        }
    }
}

// Get products
$products = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE category_id = ? AND disabled = 0 AND hidden = 0 AND deleted = 0 ORDER BY `order` ASC', [$category_id]);

if (!$products->count()) {
    $template->getEngine()->addVariable('NO_PRODUCTS', $store_language->get('general', 'no_products'));
} else {
    $category_products = [];

    foreach ($products->results() as $item) {
        $product = new Product(null, null, $item);

        // Hide product if it has been bought and has a purchase limit of 1
        if ($product->data()->user_limit == 1 && $product->getRealPriceCents($to_customer) <= 0) {
            continue;
        }

        $renderProductEvent = EventHandler::executeEvent('renderStoreProduct', [
            'product' => $product,
            'name' => $product->data()->name,
            'content' => $product->data()->description,
            'image' => (isset($product->data()->image) && !is_null($product->data()->image) ? ((defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/uploads/store/' . Output::getClean(Output::getDecoded($product->data()->image))) : null),
            'link' => URL::build($store_url . '/checkout', 'add=' . Output::getClean($product->data()->id)),
            'hidden' => false,
            'shopping_cart' => $shopping_cart
        ]);

        if ($renderProductEvent['hidden']) {
            continue;
        }

        // Prepare variables for the template
        $original_price_cents = $product->data()->sale_active == 1 ? $product->data()->price_cents - $product->data()->sale_discount_cents : $product->data()->price_cents;
        $final_price_cents = $product->getRealPriceCents($to_customer);

        $category_products[] = [
            'id' => $product->data()->id,
            'name' => Output::getClean($renderProductEvent['name']),
            'user_limit' => $product->data()->user_limit,
            'final_price_cents' => $final_price_cents,
            'original_price_format' => Output::getPurified(
                Store::formatPrice(
                    $original_price_cents,
                    $currency,
                    $currency_symbol,
                    STORE_CURRENCY_FORMAT,
                )
            ),
            'final_price_format' => Output::getPurified(
                Store::formatPrice(
                    $final_price_cents,
                    $currency,
                    $currency_symbol,
                    STORE_CURRENCY_FORMAT,
                )
            ),
            'has_discount' => $final_price_cents < $original_price_cents,
            'sale_active' => $product->data()->sale_active,
            'description' => $renderProductEvent['content'],
            'image' => $renderProductEvent['image'],
            'link' => $product->data()->payment_type != 2 ? URL::build($store_url . '/checkout', 'add=' . Output::getClean($product->data()->id) . '&type=single') : null,
            'subscribe_link' => $product->data()->payment_type != 1 ? URL::build($store_url . '/checkout', 'add=' . Output::getClean($product->data()->id) . '&type=subscribe') : null,
        ];
    }

    $template->getEngine()->addVariable('PRODUCTS', $category_products);
}

// Category description
$renderCategoryEvent = EventHandler::executeEvent('renderStoreCategory', [
    'id' => $category->id,
    'name' => $category->name,
    'content' => $category->description
]);

if (isset($errors) && count($errors))
    $template->getEngine()->addVariable('ERRORS', $errors);

$template->getEngine()->addVariables([
    'STORE' => $store_language->get('general', 'store'),
    'STORE_URL' => URL::build($store_url),
    'HOME' => $store_language->get('general', 'home'),
    'HOME_URL' => URL::build($store_url),
    'CATEGORIES' => $store->getNavbarMenu($category->name),
    'CATEGORY_ID' => $renderCategoryEvent['id'],
    'CATEGORY_NAME' => $renderCategoryEvent['name'],
    'CONTENT' => str_replace('{credits}', $from_customer->getCredits(), $renderCategoryEvent['content']),
    'ACTIVE_CATEGORY' => Output::getClean($category->name),
    'BUY' => $store_language->get('general', 'buy'),
    'ADD_TO_CART' => $store_language->get('general', 'add_to_cart'),
    'SUBSCRIBE' => $store_language->get('general', 'subscribe'),
    'CLOSE' => $language->get('general', 'close'),
    'SALE' => $store_language->get('general', 'sale'),
    'TOKEN' => Token::get(),
]);

if ($store->isPlayerSystemEnabled() && !$to_customer->isLoggedIn()) {
    $template->getEngine()->addVariables([
        'PLEASE_ENTER_USERNAME' => $store_language->get('general', 'please_enter_username'),
        'CONTINUE' => $store_language->get('general', 'continue'),
    ]);

    $template_file = 'store/player_login';
} else {
    $template_file = 'store/category';
}

$template->assets()->include([
    DARK_MODE
        ? AssetTree::PRISM_DARK
        : AssetTree::PRISM_LIGHT,
    AssetTree::TINYMCE_SPOILER,
]);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('store_error')) {
    $errors[] = Session::flash('store_error');
}

if (isset($success))
    $template->getEngine()->addVariables([
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ]);

if (isset($errors) && count($errors))
    $template->getEngine()->addVariables([
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ]);

$template->onPageLoad();

$template->getEngine()->addVariable('WIDGETS_LEFT', $widgets->getWidgets('left'));
$template->getEngine()->addVariable('WIDGETS_RIGHT', $widgets->getWidgets('right'));

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate($template_file);