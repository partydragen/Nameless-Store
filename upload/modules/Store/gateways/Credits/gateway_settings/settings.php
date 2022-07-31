<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module
 */

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

$smarty->assign([
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/gateways/Credits/gateway_settings/settings.tpl',
    'ENABLE_VALUE' => ((isset($enabled)) ? $enabled : $gateway->isEnabled())
]);