<?php
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
	$keyval = explode('=', $keyval);
	if (count($keyval) == 2)
		$myPost[$keyval[0]] = urldecode($keyval[1]);
}
// read the post from PayPal system and add 'cmd'
$req = 'cmd=_notify-validate';
if (function_exists('get_magic_quotes_gpc')) {
	$get_magic_quotes_exists = true;
}
foreach ($myPost as $key => $value) {
	if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
		$value = urlencode(stripslashes($value));
	} else {
		$value = urlencode($value);
	}
	$req .= "&$key=$value";
}

$_POST = $myPost;

//Post IPN data back to paypal to validate
$ch = curl_init('https://ipnpb.paypal.com/cgi-bin/webscr');

curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));


if (!($res = curl_exec($ch))) {
	// error_log("Got " . curl_error($ch) . " when processing IPN data");
	curl_close($ch);
	exit;
}
curl_close($ch);

if(isset($_POST['payment_status'])) {
    file_put_contents(ROOT_PATH . '/calls/payment_' . $_POST['payment_status'] . '_'.date('U').'.txt', $req);
} else {
    file_put_contents(ROOT_PATH . '/calls/payment_invalid_'.date('U').'.txt', $req);
}

if (strcmp($res, "VERIFIED") == 0) {
    $receiver_email = $_POST['receiver_email'];
    
	$item_name = $_POST['item_name']; // product id
	$item_number = $_POST['item_number']; // server id
	$payment_status = $_POST['payment_status'];
	$payment_amount = $_POST['mc_gross'];
	$payment_currency = $_POST['mc_currency'];
    $payment_fee = $_POST['mc_fee'];
	$transaction_id = $_POST['txn_id'];
	$payer_email = $_POST['payer_email'];
	$user_id = $_POST['custom'];
    
    $paypal_email = StoreConfig::get('paypal/email');

    if ($paypal_email == $receiver_email) {
        
        // Handle event
        switch($payment_status) {
            case 'Completed';
                // Payment complete
                $payment = new Payment($transaction_id, 'transaction');
                if($payment->exists()) {
                    // Payment exists
                    $data = array(
                        'user_id' => $user_id,
                        'player_id' => 0,
                        'payment_id' => null,
                        'payment_method' => $gateway->getId(),
                        'transaction' => $transaction_id,
                        'amount' => $payment_amount,
                        'currency' => $payment_currency
                    );
                } else {
                    // Payment exists
                    $payment->create(array(
                        'user_id' => $user_id,
                        'player_id' => 0,
                        'payment_id' => null,
                        'payment_method' => $gateway->getId(),
                        'transaction' => $transaction_id,
                        'created' => date('U'),
                        'last_updated' => date('U'),
                        'status_id' => 1,
                        'amount' => $payment_amount,
                        'currency' => $payment_currency
                    ));
                    
                    // Register packages
                    $packages = explode(',', $item_number);
                    foreach($packages as $item) {
                        $queries->create('store_payments_packages', array(
                            'payment_id' => $payment->data()->id,
                            'package_id' => $item
                        ));
                    }
                }
                
                $payment->handlePaymentEvent('COMPLETED', $data);
            break;
            default:
                // Payment refunded
                $payment = new Payment($transaction_id, 'transaction');
                if($payment->exists()) {
                    // Payment exists
                    $payment->handlePaymentEvent('REFUNDED', array());
                }
            break;
        }
        
        echo 'success';
    } else {
        echo 'fail 2';
    }
} else {
    echo 'fail';
}