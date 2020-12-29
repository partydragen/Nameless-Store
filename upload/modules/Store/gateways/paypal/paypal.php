<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/
 *
 *  Store module
 */

$gateway_data = DB::getInstance()->query('SELECT * FROM `nl2_store_gateways` WHERE name = \'PayPal\' AND enabled = 1')->results();
if(count($gateway_data) && $gateway_data[0]->client_id != null && $gateway_data[0]->client_key != null){
	try {
		require_once(ROOT_PATH . '/modules/Store/gateways/paypal/autoload.php');
		$apiContext = new \PayPal\Rest\ApiContext(
			new \PayPal\Auth\OAuthTokenCredential(
				$gateway_data[0]->client_id,
				$gateway_data[0]->client_key
			)
		);

		$apiContext->setConfig(
			array(
				'log.LogEnabled' => true,
				'log.FileName' => ROOT_PATH . '/cache/logs/PayPal.log',
				'log.LogLevel' => 'FINE',
				'mode' => 'live',
			)
		);

		if($gateway_data[0]->hook_key == null) {
			$key = $queries->getWhere('store_settings', array('name', '=', 'store_hook_key'));
			if(!count($key)) {
				$key = md5(uniqid());
				$queries->create('store_settings', array(
					'name' => 'store_hook_key',
					'value' => $key
				));
			} else {
				$key = $key[0]->value;
			}

			// Create API webhook
			$webhook = new \PayPal\Api\Webhook();
			$webhookEventTypes = array();

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

			$webhook->setUrl(rtrim(Util::getSelfURL(), '/') . URL::build('/store/payment_listener/', 'key=' . $key));
			$webhook->setEventTypes($webhookEventTypes);
			$output = $webhook->create($apiContext);
			$id = $output->getId();
			
			$queries->update('store_gateways', $gateway_data[0]->id, array(
				'hook_key' => $id
			));
		}
	} catch(Exception $e){
		die($e->getData());
		ErrorHandler::logCustomError($e->getData());
		die('PayPal integration incorrectly configured!');
	}
} else {
	die('Please configure Store module in the StaffCP first!');
}