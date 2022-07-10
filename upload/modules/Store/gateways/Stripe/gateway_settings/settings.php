<?php
/**
 * Stripe gateway settings page
 *
 * @package Modules\Store
 * @author Supercrafter100
 * @version 2.0.0-pre13
 * @license MIT
 */
require_once(ROOT_PATH . '/modules/Store/classes/StoreConfig.php');

if (Input::exists()) {
    if (Token::check()) {
        if (isset($_POST['publishable_key']) && isset($_POST['secret_key']) && strlen($_POST['publishable_key']) && strlen($_POST['secret_key'])) {
            $settings = [];
            $settings['stripe/publishable_key'] = $_POST['publishable_key'];
            $settings['stripe/secret_key'] = $_POST['secret_key'];

            StoreConfig::set($settings);
        }

        // Is this gateway enabled
        if (isset($_POST['enable']) && $_POST['enable'] == 'on') $enabled = 1;
        else $enabled = 0;

        DB::getInstance()->update('store_gateways', $gateway->getId(), [
            'enabled' => $enabled
        ]);

        Session::flash('gateways_success', $language->get('admin', 'successfully_updated'));
    } else
        $errors = [$language->get('general', 'invalid_token')];
}

$smarty->assign([
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/gateways/Stripe/gateway_settings/settings.tpl',
    'ENABLE_VALUE' => ((isset($enabled)) ? $enabled : $gateway->isEnabled())
]);