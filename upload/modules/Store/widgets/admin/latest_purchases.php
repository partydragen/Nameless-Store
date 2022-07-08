<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Latest purchases widget settings
 */

// Check input
$cache->setCache('store_data');

if (Input::exists()) {
	if (Token::check(Input::get('token'))) {
		if (isset($_POST['limit']) && $_POST['limit'] > 0)
			$cache->store('purchase_limit', (int)$_POST['limit']);
		else
			$cache->store('purchase_limit', 10);

	} else {
		$errors = [$language->get('general', 'invalid_token')];
	}
}

if ($cache->isCached('purchase_limit'))
	$purchase_limit = (int)$cache->retrieve('purchase_limit');
else
	$purchase_limit = 10;

$smarty->assign([
	'LATEST_PURCHASES_LIMIT' => $store_language->get('general', 'latest_purchases_limit'),
	'LATEST_PURCHASES_LIMIT_VALUE' => Output::getClean($purchase_limit),
	'INFO' => $language->get('general', 'info'),
	'WIDGET_CACHED' => $store_language->get('general', 'widget_cached'),
	'SETTINGS_TEMPLATE' => 'store/widgets/latest_purchases.tpl'
]);
