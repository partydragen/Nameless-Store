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

        if (isset($_POST['client_id']) && isset($_POST['client_secret']) && strlen($_POST['client_secret']) && strlen($_POST['client_secret'])) {
            StoreConfig::set('paypal_business', [
                'client_id' => $_POST['client_id'],
                'client_secret' => $_POST['client_secret']
            ]);
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
    'SETTINGS_TEMPLATE' => ROOT_PATH . '/modules/Store/gateways/PayPalBusiness/gateway_settings/settings.tpl',
    'ENABLE_VALUE' => ((isset($enabled)) ? $enabled : $gateway->isEnabled())
]);