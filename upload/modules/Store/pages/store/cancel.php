<?php
require_once(ROOT_PATH . '/modules/Store/gateways/paypal/paypal.php');

/*        $allowedParams = array(
            'page_size' => 10,
            'status' => 'active',
            'page' => 0
        );

$plan = new \PayPal\Api\Plan();
//var_dump($plan->all(array(), $apiContext));
//echo '<pre>', print_r($plan->all($allowedParams, $apiContext)), '</pre>';


echo '<pre>', print_r($plan->get("P-2WL77837UA421141FIIGTQHQ", $apiContext)), '</pre>';
*/
// Agreement successfully made
if(!isset($_GET['aid'])) {
	die('Invalid Token');
}

$agreementId = $_GET['aid'];
$agreement = new \PayPal\Api\Agreement();
$agreement->setId($agreementId);

$agreementStateDescriptor = new \PayPal\Api\AgreementStateDescriptor();
$agreementStateDescriptor->setNote("reActivate the agreement");

try {
    $agreement = \PayPal\Api\Agreement::get($agreement->getId(), $apiContext);
	//var_dump($agreement->reActivate($agreementStateDescriptor, $apiContext));
    //var_dump($agreement->reActivate($agreementStateDescriptor, $apiContext));
	var_dump($agreement->suspend($agreementStateDescriptor, $apiContext));
} catch (Exception $ex) {
    echo "Failed to get activate";
    var_dump($ex);
    exit();
}