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
        if (isset($_POST['client_id']) && isset($_POST['client_secret']) && strlen($_POST['client_id']) && strlen($_POST['client_secret'])) {
            $settings = [];
            $settings['minecraft-community/client_id'] = $_POST['client_id'];
            $settings['minecraft-community/client_secret'] = $_POST['client_secret'];

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
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/gateways/Minecraft-Community/gateway_settings/settings.tpl',
    'ENABLE_VALUE' => ((isset($enabled)) ? $enabled : $gateway->isEnabled())
]);