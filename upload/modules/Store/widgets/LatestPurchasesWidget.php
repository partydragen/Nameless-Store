<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Latest purchases widget
 */

class LatestStorePurchasesWidget extends WidgetBase {
	private $_smarty, $_language, $_cache, $_user, $_store_language;

	public function __construct($pages, $smarty, $language, $store_language, $cache, $user) {
		parent::__construct($pages);

		$this->_smarty = $smarty;
		$this->_language = $language;
		$this->_store_language = $store_language;
		$this->_cache = $cache;
		$this->_user = $user;

		// Get order
		$order = DB::getInstance()->query('SELECT `order` FROM nl2_widgets WHERE `name` = ?', ['Latest Purchases'])->first();

		// Set widget variables
		$this->_module = 'Store';
		$this->_name = 'Latest Purchases';
		$this->_location = 'right';
		$this->_description = 'Displays a list of your store\'s most recent purchases.';
		$this->_settings = ROOT_PATH . '/modules/Store/widgets/admin/latest_purchases.php';
		$this->_order = $order->order;
	}

	public function initialise() {
		// Generate HTML code for widget
		$this->_cache->setCache('store_data');
		$queries = new Queries();

		if ($this->_cache->isCached('latest_purchases')) {
			$latest_purchases = $this->_cache->retrieve('latest_purchases');

		} else {
			if ($this->_cache->isCached('purchase_limit')) {
				$purchase_limit = intval($this->_cache->retrieve('purchase_limit'));
			} else {
				$purchase_limit = 10;
			}

            $latest_purchases_query = DB::getInstance()->query('SELECT nl2_store_payments.*, uuid, username, order_id, user_id, player_id FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id LEFT JOIN nl2_store_players ON player_id=nl2_store_players.id ORDER BY created DESC LIMIT ' . $purchase_limit)->results();
			$latest_purchases = [];

			if (count($latest_purchases_query)) {
				$timeago = new Timeago(TIMEZONE);

				foreach ($latest_purchases_query as $purchase) {
                    if ($purchase->player_id != null) {
                        // Custumer paid as a guest, attempt to load user by uuid
                        $payment_user = new User(str_replace('-', '', $purchase->uuid), 'uuid');
                        if ($payment_user->exists()) {
                            $username = Output::getClean($purchase->username);
                            $avatar = $payment_user->getAvatar();
                            $style = $payment_user->getGroupClass();
                        } else {
                            $username = Output::getClean($purchase->username);
                            $avatar = Util::getAvatarFromUUID(Output::getClean($purchase->uuid));
                            $style = '';
                        }
                    } else if ($purchase->user_id != null) {
                        // Custumer paid while being logged in
                        $payment_user = new User($purchase->user_id);
                        
                        $username = $payment_user->getDisplayname(true);
                        $avatar = $payment_user->getAvatar();
                        $style = $payment_user->getGroupClass();
                    }

					$latest_purchases[] = [
						'avatar' => $avatar,
						'profile' => URL::build('/profile/' . $username),
						'price' => Output::getClean($purchase->amount),
						'currency' => Output::getClean($purchase->currency),
						'currency_symbol' => '$',
						'uuid' => Output::getClean($purchase->uuid),
						'date_full' => date('d M Y, H:i', $purchase->created),
						'date_friendly' => $timeago->inWords(date('d M Y, H:i', $purchase->created), $this->_language->getTimeLanguage()),
						'style' => $style,
						'username' => $username,
						'user_id' => $purchase->user_id
					];

				}
			}

			$this->_cache->store('latest_purchases', $latest_purchases, 120);

			$latest_purchases_query = null;
		}

		if (count($latest_purchases)) {
			$this->_smarty->assign([
				'LATEST_PURCHASES' => $this->_store_language->get('general', 'latest_purchases'),
				'LATEST_PURCHASES_LIST' => $latest_purchases
			]);

		} else
			$this->_smarty->assign([
				'LATEST_PURCHASES' => $this->_store_language->get('general', 'latest_purchases'),
				'NO_PURCHASES' => $this->_store_language->get('general', 'no_purchases')
			]);

		$this->_content = $this->_smarty->fetch('store/widgets/latest_purchases.tpl');
	}
}