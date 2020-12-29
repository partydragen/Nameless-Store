<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr8
 *
 *  License: MIT
 *
 *  Latest Posts Widget
 */
class LatestSubscribersWidget extends WidgetBase {
	private $_smarty, $_language, $_cache, $_user;

    public function __construct($pages = array(), $smarty, $cache, $user, $language){
    	$this->_smarty = $smarty;
    	$this->_cache = $cache;
    	$this->_user = $user;
    	$this->_language = $language;

        parent::__construct($pages);

        // Get order
        $order = DB::getInstance()->query('SELECT `order` FROM nl2_widgets WHERE `name` = ?', array('Latest Subscribers'))->first();

        // Set widget variables
        $this->_module = 'Store';
        $this->_name = 'Latest Subscribers';
        $this->_description = 'Display latest Subscribers.';
        $this->_order = $order->order;
    }

    public function initialise(){
	    $queries = new Queries();
	    $timeago = new Timeago(TIMEZONE);

	    if($this->_user->isLoggedIn()) {
		    $user_group = $this->_user->data()->group_id;
		    $secondary_groups = $this->_user->data()->secondary_groups;
	    } else {
		    $user_group = null;
		    $secondary_groups = null;
	    }

	    if($user_group){
		    $cache_name = 'forum_discussions_' . $user_group . '-' . $secondary_groups;
	    } else {
		    $cache_name = 'forum_discussions_guest';
	    }

	    $this->_cache->setCache('store_latest_subscribers');

	    if($this->_cache->isCached('subscribers')){
		    $template_array = $this->_cache->retrieve('subscribers');

	    } else {
		    // Generate latest posts
		    $subscribers = DB::getInstance()->query('SELECT * FROM nl2_store_agreements ORDER BY created LIMIT 5')->results();

		    $template_array = array();

		    // Generate an array to pass to template
		    foreach($subscribers as $sub) {
			    // Get the name of the forum from the ID
			    $package = $queries->getWhere('store_packages', array('id', '=', $sub->package_id));
			    $package = $package[0];
				
				$username = $this->_user->idToNickname($sub->user_id);

			    // Add to array
			    $template_array[] = array(
					'package_name' => Output::getClean($package->name),
					'package_price' => Output::getClean($package->price),
					'user_username' => Output::getClean($username),
					'user_link' => '/profile/' . Output::getClean($username),
					'user_avatar' => $this->_user->getAvatar($sub->user_id, "../", 64)
			    );

			    $n++;
		    }

		    $this->_cache->store('subscribers', $template_array, 60);
	    }

	    // Generate HTML code for widget
	    $this->_smarty->assign('LATEST_SUBSCRIBERS_ARRAY', $template_array);

	    $this->_content = $this->_smarty->fetch('store/widgets/latest_subscribers.tpl');
    }
}