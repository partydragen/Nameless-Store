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
	private $_language, $_cache, $_store_language;

    public function __construct(Smarty $smarty, Language $language, Language $store_language, Cache $cache) {
		$this->_smarty = $smarty;
		$this->_language = $language;
		$this->_store_language = $store_language;
		$this->_cache = $cache;

        // Get widget
        $widget_query = self::getData('Latest Purchases');

        parent::__construct(self::parsePages($widget_query));

		// Set widget variables
		$this->_module = 'Store';
		$this->_name = 'Latest Purchases';
		$this->_location = $widget_query->location;
		$this->_description = 'Displays a list of your store\'s most recent purchases.';
		$this->_settings = ROOT_PATH . '/modules/Store/widgets/admin/latest_purchases.php';
		$this->_order = $widget_query->order;
	}

	public function initialise(): void {
		// Generate HTML code for widget
		$this->_cache->setCache('store_data');

		if ($this->_cache->isCached('latest_purchases')) {
			$latest_purchases = $this->_cache->retrieve('latest_purchases');

		} else {
			if ($this->_cache->isCached('purchase_limit')) {
				$purchase_limit = intval($this->_cache->retrieve('purchase_limit'));
			} else {
				$purchase_limit = 10;
			}

            $latest_purchases_query = DB::getInstance()->query('SELECT nl2_store_payments.*, identifier, username, order_id, nl2_store_orders.user_id, to_customer_id FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id LEFT JOIN nl2_store_customers ON to_customer_id=nl2_store_customers.id ORDER BY created DESC LIMIT ' . $purchase_limit)->results();
			$latest_purchases = [];

			if (count($latest_purchases_query)) {
				$timeago = new TimeAgo(TIMEZONE);

				foreach ($latest_purchases_query as $purchase) {
                    // Recipient
                    if ($purchase->to_customer_id) {
                        $recipient = new Customer(null, $purchase->to_customer_id, 'id');
                    } else {
                        $recipient = new Customer(null, $purchase->user_id, 'user_id');
                    }

                    if ($recipient->exists() && $recipient->getUser()->exists()) {
                        $recipient_user = $recipient->getUser();
                        $username = $recipient->getUsername();
                        $avatar = $recipient_user->getAvatar();
                        $style = $recipient_user->getGroupStyle();
                        $identifier = Output::getClean($recipient->getIdentifier());
                        $user_id = $recipient_user->data()->id;
                    } else {
                        $username = $recipient->getUsername();
                        $avatar = AvatarSource::getAvatarFromUUID(Output::getClean($recipient->getIdentifier()));
                        $style = '';
                        $identifier = Output::getClean($recipient->getIdentifier());
                        $user_id = null;
                    }

					$latest_purchases[] = [
						'avatar' => $avatar,
						'profile' => URL::build('/profile/' . $username),
						'price' => Store::fromCents($purchase->amount_cents),
                        'price_format' => Output::getPurified(
                            Store::formatPrice(
                                $purchase->amount_cents,
                                $purchase->currency,
                                Store::getCurrencySymbol(),
                                STORE_CURRENCY_FORMAT,
                            )
                        ),
						'currency' => Output::getClean($purchase->currency),
						'currency_symbol' => Output::getClean(Store::getCurrencySymbol()),
						'uuid' => Output::getClean($purchase->identifier),
						'date_full' => date(DATE_FORMAT, $purchase->created),
						'date_friendly' => $timeago->inWords($purchase->created, $this->_language),
						'style' => $style,
						'username' => $username,
						'user_id' => $user_id
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