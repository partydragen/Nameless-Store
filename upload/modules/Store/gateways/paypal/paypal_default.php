<?php
require_once(ROOT_PATH . '/core/gateways/paypal/PayPal-PHP-SDK/autoload.php');

$apiContext = new \PayPal\Rest\ApiContext(
	new \PayPal\Auth\OAuthTokenCredential(
		'{client_id}',
		'{client_secret}'
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

try {
	$data = $queries->getWhere('settings', array('name', '=', 'resources_paypal_hook'));
	if(!count($data)){
		$key = md5(uniqid());
		$queries->create('settings', array(
			'name' => 'resources_paypal_hook',
			'value' => $key
		));

		// Create API webhook
		$webhook = new \PayPal\Api\Webhook();
		$webhookEventTypes = array();

		$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.COMPLETED"}');
		$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.DENIED"}');
		$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.REFUNDED"}');
		$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.REVERSED"}');

		$webhook->setUrl(rtrim(Util::getSelfURL(), '/') . URL::build('/resources/listener/', 'key=' . $key));
		$webhook->setEventTypes($webhookEventTypes);
		$output = $webhook->create($apiContext);
		$id = $output->getId();

		$queries->create('settings', array(
			'name' => 'resources_paypal_hook_id',
			'value' => $id
		));

	}
} catch(Exception $e){
	ErrorHandler::logCustomError($e->getData());
}