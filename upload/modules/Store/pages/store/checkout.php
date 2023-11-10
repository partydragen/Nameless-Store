<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Checkout page
 */

// Always define page name
define('PAGE', 'store');
$page_title = $store_language->get('general', 'store');

require_once(ROOT_PATH . '/core/templates/frontend_init.php');
require_once(ROOT_PATH . '/modules/Store/core/frontend_init.php');

if (!$store->isPlayerSystemEnabled() || !Settings::get('allow_guests', '0', 'Store')) {
    if (!$user->isLoggedIn()) {
        Redirect::to(URL::build('/login/'));
    }
}

$store_url = $store->getStoreURL();

if (isset($_GET['do'])) {
    if ($_GET['do'] == 'complete') {
        // Checkout complete page
        $checkout_complete_content = Settings::get('checkout_complete_content', '', 'Store');
        $smarty->assign('CHECKOUT_COMPLETE_CONTENT', Output::getPurified(Output::getDecoded($checkout_complete_content)));

        $template_file = 'store/checkout_complete.tpl';
    } else {
        // Invalid
        Redirect::to(URL::build($store_url . '/checkout/'));
    }
} else if (isset($_GET['add'])) {
    // Add item to shopping cart
    if (!is_numeric($_GET['add'])) {
        die('Invalid product');
    }

    $product = new Product($_GET['add']);
    if (!$product->exists() || $product->data()->deleted != 0 || $product->data()->disabled != 0) {
        die('Invalid product');
    }

    // Execute event with allow modules to interact with it
    $event = new CheckoutAddProductEvent(
        $user,
        $product,
        $from_customer,
        $to_customer,
        $product->getFields()
    );
    EventHandler::executeEvent($event);

    // Check if the event returned any errors
    if ($event->isCancelled()) {
        Session::flash('store_error', $event->getCancelledReason());
        Redirect::to(URL::build($store_url . '/category/' . $product->data()->category_id));
    }

    // Check if the product requires customer input
    $fields = $event->fields;
    if (count($fields)) {
        $product_fields = [];
        foreach ($fields as $field) {
            $product_fields[] = [
                'id' => Output::getClean($field->id),
                'identifier' => Output::getClean($field->identifier),
                'value' => isset($_POST[$field->id]) && !is_array($_POST[$field->id]) ? Output::getClean(Input::get($field->id)) : Output::getClean($field->default_value),
                'description' => Output::getClean($field->description),
                'type' => Output::getClean($field->type),
                'required' => Output::getClean($field->required),
                'options' => explode(',', Output::getClean($field->options))
            ];
        }

        // Deal with any input
        if (Input::exists()) {
            $errors = [];

            if (Token::check()) {
                // Validation
                $to_validate = [];
                foreach ($fields as $field) {
                    $field_validation = [];

                    if ($field->required == 1 /*&& $field->type != 9*/) {
                        $field_validation[Validate::REQUIRED] = true;
                    }

                    if ($field->min != 0) {
                        $field_validation[Validate::MIN] = $field->min;
                    }

                    if ($field->max != 0) {
                        $field_validation[Validate::MAX] = $field->max;
                    }

                    if ($field->regex != null) {
                        $field_validation[Validate::REGEX] = $field->regex;
                    }

                    if (count($field_validation)) {
                        $to_validate[$field->id] = $field_validation;
                    }
                }

                // Modify post validation
                $validate_post = [];
                foreach ($_POST as $key => $item) {
                    $validate_post[$key] = !is_array($item) ? $item : true ;
                }

                $validation = Validate::check($validate_post, $to_validate);
                if ($validation->passed()) {
                    // Validation passed
                    $quantity = 1;
                    $product_fields = [];
                    foreach ($fields as $field) {
                        // Post value exists?
                        if (!isset($_POST[$field->id]))
                            continue;

                        $item = $_POST[$field->id];
                        $value = (!is_array($item) ? $item : implode(', ', $item));

                        $product_fields[$field->id] = [
                            'id' => Output::getClean($field->id),
                            'identifier' => Output::getClean($field->identifier),
                            'value' => $value,
                            'description' => Output::getClean($field->description),
                            'type' => Output::getClean($field->type)
                        ];

                        // Validate quantity field
                        if ($field->identifier == 'quantity') {
                            $quantity = Input::get($field->id);
                            if (!is_numeric($quantity) || $quantity < 1) {
                                Session::flash('store_error', $store_language->get('general', 'invalid_quantity'));
                                Redirect::to(URL::build($store_url . '/checkout/', 'add=' . $_GET['add']));
                            }
                        }

                        // Validate price field for pay what you want where the original price is the minimum price
                        if ($field->identifier == 'price') {
                            $custom_price = Input::get($field->id);
                            if (!is_numeric($custom_price) || Store::toCents($custom_price) < $product->data()->price_cents) {
                                Session::flash('store_error', $store_language->get('general', 'price_must_be_at_least_x', ['price' => Store::fromCents($product->data()->price_cents)]));
                                Redirect::to(URL::build($store_url . '/checkout/', 'add=' . $_GET['add']));
                            }
                        }
                    }

                    // Execute event with allow modules to further validate the fields
                    $validation_event = new CheckoutFieldsValidationEvent(
                        $user,
                        $product,
                        $from_customer,
                        $to_customer,
                        $product_fields,
                        $validation
                    );
                    EventHandler::executeEvent($validation_event);

                    // Check if the event returned any errors
                    if ($validation_event->isCancelled()) {
                        $errors[] = $validation_event->getCancelledReason();
                    } else {
                        $shopping_cart->add($_GET['add'], $quantity, $product_fields);
                        Redirect::to(URL::build($store_url . '/checkout/'));
                    }
                } else {
                    // Validation errors
                    foreach ($validation->errors() as $item) {
                        // Get field name
                        $id = explode(' ', $item);
                        $id = $id[0];

                        $fielderror = DB::getInstance()->get('store_fields', ['id', '=', $id])->results();
                        if (count($fielderror)) {
                            $fielderror = $fielderror[0];

                            if (strpos($item, 'is required') !== false) {
                                $errors[] = $language->get('user', 'field_is_required', ['field' => Output::getClean($fielderror->description)]);
                            } else if (strpos($item, 'minimum') !== false) {
                                $errors[] = $store_language->get('general', 'x_field_minimum_y', ['field' => Output::getClean($fielderror->description), 'min' => $fielderror->min]);
                            } else if (strpos($item, 'maximum') !== false) {
                                $errors[] = $store_language->get('general', 'x_field_maximum_y', ['field' => Output::getClean($fielderror->description), 'max' => $fielderror->max]);
                            } else if (strpos($item, 'regex') !== false) {
                                $errors[] = $store_language->get('general', 'x_field_regex', ['field' => Output::getClean($fielderror->description)]);
                            }
                        }
                    }
                }
            } else {
                // Invalid token
                $errors[] = $language->get('general', 'invalid_token');
            }
        }

        $smarty->assign([
            'PRODUCT_NAME' => Output::getClean($product->data()->name),
            'PRODUCT_FIELDS' => $product_fields,
            'CONTINUE' => $store_language->get('general', 'continue'),
            'TOKEN' => Token::get()
        ]);

        $template_file = 'store/checkout_add.tpl';
    } else {
        // No customer input to fill, continue to next step
        $shopping_cart->add($_GET['add']);
        Redirect::to(URL::build($store_url . '/checkout/'));
    }

} else if (isset($_GET['remove'])) {
    if (!is_numeric($_GET['remove'])) {
        die('Invalid product');
    }
    $shopping_cart->remove($_GET['remove']);

    Redirect::to(URL::build($store_url . '/checkout/'));

} else {
    // Make sure the shopping cart is not empty
    if (!count($shopping_cart->getItems())) {
        Redirect::to(URL::build($store_url));
    }

    // Deal with any input
    if (Input::exists()) {
        $errors = [];

        if (Token::check()) {
            $to_validation = [
                'payment_method' => [
                    Validate::REQUIRED => true
                ],
                't_and_c' => [
                    Validate::REQUIRED => true,
                    Validate::AGREE => true
                ]
            ];

            // Valid, continue with validation
            $validation = Validate::check($_POST, $to_validation); // Execute validation
            if ($validation->passed()) {
                require_once(ROOT_PATH . '/modules/Store/config.php');

                // Load Store config
                if (isset($store_conf) && is_array($store_conf)) {
                    $GLOBALS['store_config'] = $store_conf;
                }

                // Create order
                $amount = new Amount();
                $amount->setCurrency($currency);
                $amount->setTotalCents($shopping_cart->getTotalRealPriceCents());

                $order = new Order();
                $order->setAmount($amount);

                $order->setProducts($shopping_cart->getProducts());
                $order->create($user, $from_customer, $to_customer, $shopping_cart->getItems(), $shopping_cart->getCoupon());

                // Complete order if there is nothing to pay
                $amount_to_pay = $shopping_cart->getTotalRealPriceCents();
                if ($amount_to_pay == 0) {
                    $payment = new Payment();
                    $payment->handlePaymentEvent(Payment::COMPLETED, [
                        'order_id' => $order->data()->id,
                        'gateway_id' => 0,
                        'amount_cents' => 0,
                        'transaction' => 'Free',
                        'currency' => Store::getCurrency()
                    ]);

                    $shopping_cart->clear();
                    Redirect::to(URL::build($store_url . '/checkout/', 'do=complete'));
                }

                $gateway = Gateways::getInstance()->get($_POST['payment_method']);
                if ($gateway) {
                    // Load gateway process
                    $gateway->processOrder($order);
                    if (count($gateway->getErrors())) {
                        $errors = $gateway->getErrors();
                    }
                } else {
                    $errors[] = 'Invalid Gateway';
                }
            } else {
                // Errors
                foreach ($validation->errors() as $validation_error) {
                    if (strpos($validation_error, 'is required') !== false) {
                        // x is required
                        if (strpos($validation_error, 'payment_method') !== false) {
                            $errors[] = $store_language->get('general', 'choose_payment_method');
                        } else if (strpos($validation_error, 't_and_c') !== false) {
                            $errors[] = $store_language->get('general', 'accept_terms');
                        }
                    }
                }
            }
        } else {
            // Invalid token
            $errors[] = $language->get('general', 'invalid_token');
        }
    }

    foreach (Gateways::getInstance()->getAll() as $gateway) {
        if ($gateway->isEnabled()) {
            $gateway->onCheckoutPageLoad($template, $from_customer);
        }
    }

    // Load shopping list
    $shopping_cart_list = [];
    foreach ($shopping_cart->getItems() as $item) {
        $product = $item->getProduct();

        $fields = [];
        foreach ($item->getFields() as $field) {
            if ($field['identifier'] == 'quantity') {
                continue;
            }

            $fields[] = [
                'id' => Output::getClean($field['id']),
                'identifier' => Output::getClean($field['identifier']),
                'description' => Output::getClean($field['description']),
                'value' => Output::getClean($field['value']),
                'type' => Output::getClean($field['type'])
            ];
        }

        $shopping_cart_list[] = [
            'name' => Output::getClean($product->data()->name),
            'quantity' => $item->getQuantity(),
            'price' => Store::fromCents($item->getSubtotalPrice()),
            'real_price' => Store::fromCents($item->getTotalPrice()),
            'sale_discount' => Store::fromCents($item->getTotalDiscounts()),
            'price_format' => Output::getPurified(
                Store::formatPrice(
                    $item->getSubtotalPrice(),
                    $currency,
                    $currency_symbol,
                    STORE_CURRENCY_FORMAT,
                )
            ),
            'real_price_format' => Output::getPurified(
                Store::formatPrice(
                    $item->getTotalPrice(),
                    $currency,
                    $currency_symbol,
                    STORE_CURRENCY_FORMAT,
                )
            ),
            'sale_discount_format' => Output::getPurified(
                Store::formatPrice(
                    $item->getTotalDiscounts(),
                    $currency,
                    $currency_symbol,
                    STORE_CURRENCY_FORMAT,
                )
            ),
            'sale_active' => $product->data()->sale_active,
            'fields' => $fields,
            'remove_link' => URL::build($store_url . '/checkout/', 'remove=' . $product->data()->id),
        ];
    }

    // Load available gateways
    $payment_methods = [];
    foreach (Gateways::getInstance()->getAll() as $gateway) {
        if ($gateway->isEnabled()) {
            // Check of any products require certain gateways
            foreach ($shopping_cart->getProducts() as $product) {
                $allowed_gateways = json_decode($product->data()->allowed_gateways, true) ?? [];
                if (count($allowed_gateways) && !in_array($gateway->getId(), $allowed_gateways)) {
                    continue 2;
                }
            }

            $payment_methods[] = [
                'displayname' => Output::getClean($gateway->getDisplayname()),
                'name' => Output::getClean($gateway->getName())
            ];
        }
    }

    $smarty->assign([
        'TOKEN' => Token::get(),
        'CHECKOUT' => $store_language->get('general', 'checkout'),
        'SHOPPING_CART' => $store_language->get('general', 'shopping_cart'),
        'NAME' => $store_language->get('general', 'name'),
        'OPTIONS' => $store_language->get('general', 'options'),
        'QUANTITY' => $store_language->get('general', 'quantity'),
        'PRICE' => $store_language->get('general', 'price'),
        'TOTAL_PRICE' => $store_language->get('general', 'total_price'),
        'TOTAL_DISCOUNT' => $store_language->get('general', 'total_discount'),
        'PRICE_TO_PAY' => $store_language->get('general', 'price_to_pay'),
        'TOTAL_PRICE_VALUE' => Store::fromCents($shopping_cart->getTotalRealPriceCents()),
        'TOTAL_REAL_PRICE_VALUE' => Store::fromCents($shopping_cart->getTotalRealPriceCents()),
        'TOTAL_DISCOUNT_VALUE' => Store::fromCents($shopping_cart->getTotalDiscountCents()),
        'TOTAL_PRICE_FORMAT_VALUE' => Output::getPurified(
            Store::formatPrice(
                $shopping_cart->getTotalCents(),
                $currency,
                $currency_symbol,
                STORE_CURRENCY_FORMAT,
            )
        ),
        'TOTAL_REAL_PRICE_FORMAT_VALUE' => Output::getPurified(
            Store::formatPrice(
                $shopping_cart->getTotalRealPriceCents(),
                $currency,
                $currency_symbol,
                STORE_CURRENCY_FORMAT,
            )
        ),
        'TOTAL_DISCOUNT_FORMAT_VALUE' => Output::getPurified(
            Store::formatPrice(
                $shopping_cart->getTotalDiscountCents(),
                $currency,
                $currency_symbol,
                STORE_CURRENCY_FORMAT,
            )
        ),
        'REDEEM' => $store_language->get('general', 'redeem'),
        'REDEEM_COUPON' => $store_language->get('general', 'redeem_coupon'),
        'REDEEM_COUPON_HERE' => $store_language->get('general', 'redeem_coupon_here'),
        'REDEEM_COUPON_URL' => URL::build('/queries/redeem_coupon'),
        'REDEEM_COUPON_VALUE' => $shopping_cart->getCoupon() != null ? Output::getClean($shopping_cart->getCoupon()->data()->code) : '',
        'PAYMENT_METHOD' => $store_language->get('general', 'payment_method'),
        'PURCHASE' => $store_language->get('general', 'purchase'),
        'AGREE_T_AND_C_PURCHASE' => $store_language->get('general', 'agree_t_and_c_purchase', [
            'termsLinkStart' => '<a href="'.URL::build('/terms').'" target="_blank">',
            'termsLinkEnd' => '</a>',
        ]),
        'PAYMENT_METHODS' => $payment_methods,
        'SHOPPING_CART_LIST' => $shopping_cart_list
    ]);

    $template_file = 'store/checkout.tpl';
}

// Check if store customer is required and isset
if ($store->isPlayerSystemEnabled() && !$to_customer->isLoggedIn()) {
    Redirect::to(URL::build($store_url));
}

$smarty->assign([
    'STORE' => $store_language->get('general', 'store'),
    'CATEGORIES' => $store->getNavbarMenu(false),
    'TOKEN' => Token::get()
]);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('store_success')) {
    $success = Session::flash('store_success');
}

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