<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Category Page
 */

// Always define page name
define('PAGE', 'store');

require_once(ROOT_PATH . '/modules/Store/core/frontend_init.php');

// Get category ID
$category_id = explode('/', $route);
$category_id = $category_id[count($category_id) - 1];

if (!strlen($category_id)) {
    require_once(ROOT_PATH . '404.php');
    die();
}

if (is_numeric($category_id)) {
    // Query category by id
    $category = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE id = ? AND disabled = 0', [$category_id]);
} else {
    // Query category by url
    $category = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE url = ? AND disabled = 0', [$category_id]);
}

if (!$category->count()) {
    require_once(ROOT_PATH . '/404.php');
    die();
}

$category = $category->first();
$category_id = $category->id;
$store_url = $store->getStoreURL();

$page_metadata = DB::getInstance()->get('page_descriptions', ['page', '=', $store_url . '/view'])->results();
if (count($page_metadata)) {
    define('PAGE_DESCRIPTION', str_replace(['{site}', '{category_title}', '{description}'], [SITE_NAME, Output::getClean($category->name), Output::getClean(strip_tags(Output::getDecoded($category->description)))], $page_metadata[0]->description));
    define('PAGE_KEYWORDS', $page_metadata[0]->tags);
}

$page_title = Output::getClean($category->name);
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

if (Input::exists()) {
    if (Token::check()) {
        $errors = [];

        if (Input::get('type') == 'store_login') {
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
                    $errors[] = $language->get('user', 'invalid_mcname');
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
    $smarty->assign('NO_PRODUCTS', $store_language->get('general', 'no_products'));
} else {
    $category_products = [];

    foreach ($products->results() as $item) {
        $product = new Product(null, null, $item);

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

        $category_products[] = [
            'id' => $product->data()->id,
            'name' => Output::getClean($renderProductEvent['name']),
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
            'sale_active' => $product->data()->sale_active,
            'description' => $renderProductEvent['content'],
            'image' => $renderProductEvent['image'],
            'link' => $renderProductEvent['link']
        ];
    }

    $smarty->assign('PRODUCTS', $category_products);
}

// Category description
$renderCategoryEvent = EventHandler::executeEvent('renderStoreCategory', [
    'id' => $category->id,
    'name' => $category->name,
    'content' => $category->description
]);

$smarty->assign([
    'ACTIVE_CATEGORY' => Output::getClean($category->name),
    'BUY' => $store_language->get('general', 'buy'),
    'CLOSE' => $language->get('general', 'close'),
    'SALE' => $store_language->get('general', 'sale')
]);

if (isset($errors) && count($errors))
    $smarty->assign('ERRORS', $errors);

$smarty->assign([
    'STORE' => $store_language->get('general', 'store'),
    'STORE_URL' => URL::build($store_url),
    'HOME' => $store_language->get('general', 'home'),
    'HOME_URL' => URL::build($store_url),
    'CATEGORIES' => $store->getNavbarMenu($category->name),
    'CATEGORY_ID' => $renderCategoryEvent['id'],
    'CATEGORY_NAME' => $renderCategoryEvent['name'],
    'CONTENT' => str_replace('{credits}', $from_customer->getCredits(), $renderCategoryEvent['content']),
    'TOKEN' => Token::get(),
]);

if ($store->isPlayerSystemEnabled() && !$to_customer->isLoggedIn()) {
    $smarty->assign([
        'PLEASE_ENTER_USERNAME' => $store_language->get('general', 'please_enter_username'),
        'CONTINUE' => $store_language->get('general', 'continue'),
    ]);
    
    $template_file = 'store/player_login.tpl';
} else {
    $template_file = 'store/category.tpl';
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
    $smarty->assign([
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ]);

if (isset($errors) && count($errors))
    $smarty->assign([
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ]);

$template->onPageLoad();

$smarty->assign('WIDGETS_LEFT', $widgets->getWidgets('left'));
$smarty->assign('WIDGETS_RIGHT', $widgets->getWidgets('right'));

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate($template_file, $smarty);
