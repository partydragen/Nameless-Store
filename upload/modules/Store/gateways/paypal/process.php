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

if(isset($_GET['do'])){
	if($_GET['do'] == 'success'){

        $payment = new Payment($_POST['txn_id'], 'transaction');
        if(!$payment->exists()) {
            // Register pending payment
            $payment->create(array(
                'order_id' => $_POST['custom'],
                'gateway_id' => $gateway->getId(),
                'transaction' => $_POST['txn_id'],
                'created' => date('U'),
                'last_updated' => date('U'),
                'status_id' => 0,
                'amount' => $_POST['mc_gross'],
                'currency' => $_POST['mc_currency'],
                'fee' => $_POST['mc_fee']
            ));
        }

		Redirect::to(URL::build($store_url . '/checkout/', 'do=complete'));
		die();
	} else {
		// Invalid
		Redirect::to(URL::build($store_url . '/checkout/', 'do=cancel'));
		die();
	}

} else {
    // Build product names string
    $product_names = '';
    foreach($order->getProducts() as $product) {
        $product_names .= $product->name . ', ';
    }
    $product_names = rtrim($product_names, ', ');

    $return_url = rtrim(Util::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPal&do=success');
    $cancel_url = rtrim(Util::getSelfURL(), '/') . URL::build($store_url . '/checkout/', 'gateway=PayPal&do=cancel');
    $listener_url = rtrim(Util::getSelfURL(), '/') . URL::build('/store/listener/', 'gateway=PayPal');

    $paypal_email = StoreConfig::get('paypal/email');
    
    $currency = Output::getClean($configuration->get('store', 'currency'));
    //https://www.paypal.com/cgi-bin/webscr
    //https://www.sandbox.paypal.com/cgi-bin/webscr
    ?>
    
    <form name="pay" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
      <input type="hidden" name="cmd" value="_xclick">
      <input type="hidden" name="business" value="<?php echo $paypal_email; ?>" />
      <input type="hidden" name="currency_code" value="<?php echo $currency; ?>" />
      <input type="hidden" name="amount" value="<?php echo $shopping_cart->getTotalPrice(); ?>" />
      <input type="hidden" name="item_name" value="<?php echo $product_names; ?>">
      <input type="hidden" name="item_number" value="<?php echo $order->data()->id; ?>">
      <input type="hidden" name="custom" value="<?php echo $order->data()->id; ?>">
      <input type="hidden" name="return" value="<?php echo $return_url; ?>">
      <input type="hidden" name="cancel_return" value="<?php echo $cancel_url; ?>">
      <input type="hidden" name="rm" value="2">
      <input type="hidden" name="notify_url" value="<?php echo $listener_url; ?>" />
    </form>

    <script type="text/javascript">
        document.pay.submit();
    </script>
   
<?php
}
?>