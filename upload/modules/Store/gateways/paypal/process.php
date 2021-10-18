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
        
		$player_id = 0;
		$store_player = $_SESSION['store_player'];
		$player = DB::getInstance()->query('SELECT * FROM nl2_store_players WHERE uuid = ?', array($store_player['uuid']))->results();
		if(count($player)) {
			$queries->update('store_players', $player[0]->id, array(
				'username' => $store_player['username'],
				'uuid' => $store_player['uuid']
			));
			
			$player_id = $player[0]->id;
		} else {
			$queries->create('store_players', array(
				'username' => $store_player['username'],
				'uuid' => $store_player['uuid']
			));
			
			$player_id = DB::getInstance()->lastId();
		}
        
                // Save payment to database
                $queries->create('store_payments', array(
                    'user_id' => ($user->isLoggedIn() ? $user->data()->id : null),
                    'player_id' => $player_id,
                    'payment_id' => null,
                    'payment_method' => $gateway->getId(),
                    'transaction' => $_POST['txn_id'],
                    'created' => date('U'),
                    'last_updated' => date('U'),
                    'status_id' => 0,
                    'amount' => $_POST['mc_gross'],
                    'currency' => $_POST['mc_currency'],
                ));
                
		Redirect::to(URL::build($store_url . '/checkout/', 'do=complete'));
		die();
	} else {
		// Invalid
		Redirect::to(URL::build($store_url . '/checkout/', 'do=cancel'));
		die();
	}

} else {
    // Build packages id string
    $packages_ids = '';
    foreach($shopping_cart->getPackages() as $package) {
        $packages_ids .= (int) $package->id . ',';
    }
    $packages_ids = rtrim($packages_ids, ',');

    // Build package names string
    $packages_names = '';
    foreach($shopping_cart->getPackages() as $package) {
        $packages_names .= $package->name . ', ';
    }
    $packages_names = rtrim($packages_names, ', ');

    $return_url = rtrim(Util::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPal&do=success');
    $cancel_url = rtrim(Util::getSelfURL(), '/') . URL::build($store_url . '/checkout/', 'gateway=PayPal&do=cancel');
    $listener_url = rtrim(Util::getSelfURL(), '/') . URL::build('/store/listener/', 'gateway=PayPal');

    $paypal_email = StoreConfig::get('paypal/email');
    //https://www.paypal.com/cgi-bin/webscr
    ?>
    
    <form name="pay" action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_top">
      <input type="hidden" name="cmd" value="_xclick">
      <input type="hidden" name="business" value="<?php echo $paypal_email; ?>" />
      <input type="hidden" name="currency_code" value="USD" />
      <input type="hidden" name="amount" value="<?php echo $shopping_cart->getTotalPrice(); ?>" />
      <input type="hidden" name="item_name" value="<?php echo $packages_names; ?>">
      <input type="hidden" name="item_number" value="<?php echo $packages_ids; ?>">
      <input type="hidden" name="custom" value="<?php echo $user_id; ?>">
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