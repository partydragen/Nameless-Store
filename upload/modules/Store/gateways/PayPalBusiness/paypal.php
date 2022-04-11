<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module
 */

$client_id = StoreConfig::get('paypal_business/client_id');
$client_secret = StoreConfig::get('paypal_business/client_secret');
if ($client_id && $client_secret) {
	try {
		require_once(ROOT_PATH . '/modules/Store/gateways/PayPalBusiness/autoload.php');
		$apiContext = new \PayPal\Rest\ApiContext(
			new \PayPal\Auth\OAuthTokenCredential(
				$client_id,
				$client_secret
			)
		);

		$apiContext->setConfig(
			[
				'log.LogEnabled' => true,
				'log.FileName' => ROOT_PATH . '/cache/logs/PayPal.log',
				'log.LogLevel' => 'FINE',
				'mode' => 'live',
			]
		);

        $hook_key = StoreConfig::get('paypal_business/hook_key');
		if (!$hook_key) {
            $key = md5(uniqid());

			// Create API webhook
			$webhook = new \PayPal\Api\Webhook();
			$webhookEventTypes = [];

			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.COMPLETED"}');
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.DENIED"}');
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.REFUNDED"}');
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.REVERSED"}');
			
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.CREATED"}');
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.CANCELLED"}');
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.SUSPENDED"}');
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.RE-ACTIVATED"}');
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.UPDATED"}');
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.SUBSCRIPTION.EXPIRED"}');
			
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.PLAN.CREATED"}');
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"BILLING.PLAN.UPDATED"}');

			$webhook->setUrl(rtrim(Util::getSelfURL(), '/') . URL::build('/store/listener', 'gateway=PayPalBusiness&key=' . $key));
			$webhook->setEventTypes($webhookEventTypes);
			$output = $webhook->create($apiContext);
			$id = $output->getId();
			
            StoreConfig::set(['paypal_business/key' => $key, 'paypal_business/hook_key' => $id]);
		}
	} catch (Exception $e) {
		die($e->getData());
		ErrorHandler::logCustomError($e->getData());
		die('PayPal integration incorrectly configured!');
	}
} else {
	die('Please configure PayPal Business gateway in the StaffCP first!');
}