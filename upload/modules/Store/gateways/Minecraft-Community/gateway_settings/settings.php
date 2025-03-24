<?php
/**
 * Minecraft-Community gateway settings page
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.1.2
 * @license MIT
 */
require_once(ROOT_PATH . '/modules/Store/classes/StoreConfig.php');

if (Input::exists()) {
    if (Token::check()) {
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

$template->getEngine()->addVariables([
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/gateways/Minecraft-Community/gateway_settings/settings.tpl',
    'ENABLE_VALUE' => ((isset($enabled)) ? $enabled : $gateway->isEnabled())
]);