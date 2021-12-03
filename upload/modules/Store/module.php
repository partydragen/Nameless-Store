<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel payments page
 */

class Store_Module extends Module {
	private $_store_language, $_language, $_cache, $_store_url;

	public function __construct($language, $store_language, $pages, $cache, $endpoints){
		$this->_language = $language;
		$this->_store_language = $store_language;
		$this->_cache = $cache;

		$name = 'Store';
		$author = '<a href="https://partydragen.com/" target="_blank" rel="nofollow noopener">Partydragen</a>';
		$module_version = '1.1.0';
		$nameless_version = '2.0.0-pr12';

		parent::__construct($this, $name, $author, $module_version, $nameless_version);

		// Get variables from cache
		$cache->setCache('store_settings');
		if($cache->isCached('store_url')){
			$this->_store_url = Output::getClean(rtrim($cache->retrieve('store_url'), '/'));
		} else {
			$this->_store_url = '/store';
		}

		// Define URLs which belong to this module
		$pages->add('Store', $this->_store_url, 'pages/store/index.php', 'store', true);
		$pages->add('Store', $this->_store_url . '/category', 'pages/store/category.php', 'product', true);
		$pages->add('Store', $this->_store_url . '/checkout', 'pages/store/checkout.php');
		$pages->add('Store', $this->_store_url . '/check', 'pages/store/check.php');
		$pages->add('Store', $this->_store_url . '/cancel', 'pages/store/cancel.php');
		$pages->add('Store', $this->_store_url . '/view', 'pages/store/view.php');
        $pages->add('Store', '/store/process', 'pages/backend/process.php');
        $pages->add('Store', '/store/listener', 'pages/backend/listener.php');
		$pages->add('Store', '/panel/store', 'pages/panel/index.php');
        $pages->add('Store', '/panel/store/gateways', 'pages/panel/gateways.php');
		$pages->add('Store', '/panel/store/products', 'pages/panel/products.php');
		$pages->add('Store', '/panel/store/categories', 'pages/panel/categories.php');
		$pages->add('Store', '/panel/store/payments', 'pages/panel/payments.php');
        $pages->add('Store', '/panel/store/connections', 'pages/panel/connections.php');
        $pages->add('Store', '/panel/store/fields', 'pages/panel/fields.php');
		
        HookHandler::registerEvent('paymentPending',  $store_language->get('admin', 'payment_pending'));
		HookHandler::registerEvent('paymentCompleted', $store_language->get('admin', 'payment_completed'));
        HookHandler::registerEvent('paymentRefunded', $store_language->get('admin', 'payment_refunded'));
        HookHandler::registerEvent('paymentReversed', $store_language->get('admin', 'payment_reversed'));
        HookHandler::registerEvent('paymentDenied', $store_language->get('admin', 'payment_denied'));
        
        
        // Autoload API Endpoints
        Util::loadEndpoints(join(DIRECTORY_SEPARATOR, array(ROOT_PATH, 'modules', 'Store', 'includes', 'endpoints')), $endpoints);
        
		// Check if module version changed
		$cache->setCache('store_module_cache');
		if(!$cache->isCached('module_version')){
			$cache->store('module_version', $module_version);
		} else {
			if($module_version != $cache->retrieve('module_version')) {
				// Version have changed, Perform actions
                $this->initialiseUpdate($cache->retrieve('module_version'));
                
				$cache->store('module_version', $module_version);
				
                if($cache->isCached('update_check')){
                    $cache->erase('update_check');
                }
			}
		}
	}

	public function onInstall(){
		// Initialise
		$this->initialise();
	}

	public function onUninstall(){
		// Not necessary
	}

	public function onEnable(){
		// Check if we need to initialise again
		$this->initialise();
	}

	public function onDisable(){
		// Not necessary
	}

	public function onPageLoad($user, $pages, $cache, $smarty, $navs, $widgets, $template){
		// Classes
		require_once(ROOT_PATH . '/modules/Store/classes/Store.php');
        
		// Add link to navbar
        $cache->setCache('nav_location');
        if(!$cache->isCached('store_location')){
            $link_location = 1;
            $cache->store('store_location', 1);
        } else {
            $link_location = $cache->retrieve('store_location');
        }

		$cache->setCache('navbar_order');
		if(!$cache->isCached('store_order')){
			$store_order = 21;
			$cache->store('store_order', 21);
		} else {
			$store_order = $cache->retrieve('store_order');
		}

		$cache->setCache('navbar_icons');
		if(!$cache->isCached('store_icon'))
			$icon = '';
		else
			$icon = $cache->retrieve('store_icon');

		$cache->setCache('store_settings');
		if($cache->isCached('navbar_position'))
			$navbar_pos = $cache->retrieve('navbar_position');
		else
			$navbar_pos = 'top';

        switch($link_location){
            case 1:
                // Navbar
                $navs[0]->add('store', $this->_store_language->get('general', 'store'), URL::build($this->_store_url), 'top', null, $store_order, $icon);
            break;
            case 2:
                // "More" dropdown
                $navs[0]->addItemToDropdown('more_dropdown', 'forum', $this->_store_language->get('general', 'store'), URL::build($this->_store_url), 'top', null, $icon, $store_order);
            break;
            case 3:
                // Footer
                $navs[0]->add('store', $this->_store_language->get('general', 'store'), URL::build($this->_store_url), 'footer', null, $store_order, $icon);
            break;
        }

		if(defined('BACK_END')){
			// Define permissions which belong to this module
			PermissionHandler::registerPermissions('Store', array(
				'staffcp.store' => $this->_store_language->get('admin', 'staffcp_store'),
				'staffcp.store.settings' => $this->_store_language->get('admin', 'staffcp_store_settings'),
                'staffcp.store.gateways' => $this->_store_language->get('admin', 'staffcp_store_gateways'),
				'staffcp.store.products' => $this->_store_language->get('admin', 'staffcp_store_products'),
				'staffcp.store.payments' => $this->_store_language->get('admin', 'staffcp_store_payments'),
                'staffcp.store.manage' => $this->_store_language->get('admin', 'staffcp_store_manage'),
			));
		
			if($user->hasPermission('staffcp.store')){
				$cache->setCache('panel_sidebar');
				if(!$cache->isCached('store_order')){
					$order = 18;
					$cache->store('store_order', 18);
				} else {
					$order = $cache->retrieve('store_order');
				}

				$navs[2]->add('store_divider', mb_strtoupper($this->_store_language->get('general', 'store')), 'divider', 'top', null, $order, '');

				if($user->hasPermission('staffcp.store.settings')){
					if(!$cache->isCached('store_icon')){
						$icon = '<i class="nav-icon fas fa-shopping-cart"></i>';
						$cache->store('store_icon', $icon);
					} else
						$icon = $cache->retrieve('store_icon');

					$navs[2]->add('store', $this->_store_language->get('general', 'store'), URL::build('/panel/store'), 'top', null, ($order + 0.1), $icon);
				}
                
				if($user->hasPermission('staffcp.store.gateways')){
					if(!$cache->isCached('store_gateways_icon')){
						$icon = '<i class="nav-icon far fa-credit-card"></i>';
						$cache->store('store_gateways_icon', $icon);
					} else
						$icon = $cache->retrieve('store_gateways_icon');

					$navs[2]->add('store_gateways', $this->_store_language->get('admin', 'gateways'), URL::build('/panel/store/gateways'), 'top', null, ($order + 0.2), $icon);
				}

				if($user->hasPermission('staffcp.store.products')){
					if(!$cache->isCached('store_products_icon')){
						$icon = '<i class="nav-icon fas fa-box-open"></i>';
						$cache->store('store_products_icon', $icon);
					} else
						$icon = $cache->retrieve('store_products_icon');

					$navs[2]->add('store_products', $this->_store_language->get('general', 'products'), URL::build('/panel/store/products'), 'top', null, ($order + 0.3), $icon);
				}

				if($user->hasPermission('staffcp.store.payments')){
					if(!$cache->isCached('store_payments_icon')){
						$icon = '<i class="nav-icon fas fa-donate"></i>';
						$cache->store('store_payments_icon', $icon);
					} else
						$icon = $cache->retrieve('store_payments_icon');

					$navs[2]->add('store_payments', $this->_store_language->get('admin', 'payments'), URL::build('/panel/store/payments'), 'top', null, ($order + 0.4), $icon);
				}
                
                if($user->hasPermission('staffcp.store.manage')){
					if(!$cache->isCached('store_connections_icon')){
						$icon = '<i class="nav-icon fas fa-plug"></i>';
						$cache->store('store_connections_icon', $icon);
					} else
						$icon = $cache->retrieve('store_connections_icon');

					$navs[2]->add('store_connections', $this->_store_language->get('admin', 'connections'), URL::build('/panel/store/connections'), 'top', null, ($order + 0.5), $icon);
				}
                
                if($user->hasPermission('staffcp.store.manage')){
					if(!$cache->isCached('store_fields_icon')){
						$icon = '<i class="nav-icon fas fa-id-card"></i>';
						$cache->store('store_fields_icon', $icon);
					} else
						$icon = $cache->retrieve('store_fields_icon');

					$navs[2]->add('store_fields', $this->_store_language->get('admin', 'fields'), URL::build('/panel/store/fields'), 'top', null, ($order + 0.5), $icon);
				}
			}
		}
        
		// Check for module updates
        if(isset($_GET['route']) && $user->isLoggedIn() && $user->hasPermission('admincp.update')){
            // Page belong to this module?
            $page = $pages->getActivePage();
            if($page['module'] == 'Store'){

                $cache->setCache('store_module_cache');
                if($cache->isCached('update_check')){
                    $update_check = $cache->retrieve('update_check');
                } else {
					require_once(ROOT_PATH . '/modules/Store/classes/Store.php');
                    $update_check = Store::updateCheck();
                    $cache->store('update_check', $update_check, 3600);
                }

                $update_check = json_decode($update_check);
				if(!isset($update_check->error) && !isset($update_check->no_update) && isset($update_check->new_version)){	
                    $smarty->assign(array(
                        'NEW_UPDATE' => str_replace('{x}', $this->getName(), (isset($update_check->urgent) && $update_check->urgent == 'true') ? $this->_store_language->get('admin', 'new_urgent_update_available_x') : $this->_store_language->get('admin', 'new_update_available_x')),
                        'NEW_UPDATE_URGENT' => (isset($update_check->urgent) && $update_check->urgent == 'true'),
                        'CURRENT_VERSION' => str_replace('{x}', $this->getVersion(), $this->_store_language->get('admin', 'current_version_x')),
                        'NEW_VERSION' => str_replace('{x}', Output::getClean($update_check->new_version), $this->_store_language->get('admin', 'new_version_x')),
                        'UPDATE' => $this->_store_language->get('admin', 'view_resource'),
                        'UPDATE_LINK' => Output::getClean($update_check->link)
                    ));
				}
            }
        }
	}
    
    private function initialiseUpdate($old_version){
        $old_version = str_replace(array(".", "-"), "", $old_version);
        $queries = new Queries();
        
		// Generate tables
		try {
			$engine = Config::get('mysql/engine');
			$charset = Config::get('mysql/charset');
		} catch(Exception $e){
			$engine = 'InnoDB';
			$charset = 'utf8mb4';
		}

		if(!$engine || is_array($engine))
			$engine = 'InnoDB';

		if(!$charset || is_array($charset))
			$charset = 'latin1';

        // Old converter from pre release
        if(!$queries->tableExists('store_orders')) {
            // Rename Tabels
            try {
                DB::getInstance()->createQuery('RENAME TABLE nl2_store_packages TO nl2_store_products;');
                DB::getInstance()->createQuery('RENAME TABLE nl2_store_packages_commands TO nl2_store_products_commands;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            try {
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_products CHANGE required_packages required_products varchar(128);');
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_products_commands CHANGE package_id product_id int(11);');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            try {
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_payments ADD `order_id` int(11) NOT NULL');
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_payments ADD `fee` varchar(11) DEFAULT NULL');
                
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_payments CHANGE payment_method gateway_id int(11);');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            // Update nl2_store_pending_commands table
            try {
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_pending_commands ADD `order_id` int(11) NOT NULL');
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_pending_commands ADD `command_id` int(11) NOT NULL');
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_pending_commands ADD `product_id` int(11) NOT NULL');

                DB::getInstance()->createQuery('ALTER TABLE nl2_store_pending_commands DROP COLUMN payment_id;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            // Update nl2_store_gateways table
            try {
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_gateways ADD `displayname` varchar(64) NOT NULL');
                
                $queries->update('store_gateways', 1, array(
                    'name' => 'PayPal',
                    'displayname' => 'PayPal'
                ));
                
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_gateways DROP COLUMN client_id;');
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_gateways DROP COLUMN client_key;');
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_gateways DROP COLUMN hook_key;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            try {
                $queries->createTable('store_orders', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) DEFAULT NULL, `player_id` int(11) DEFAULT NULL, `created` int(11) NOT NULL, `ip` varchar(128) DEFAULT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
                $queries->createTable('store_orders_products', ' `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
                
                // Convert old payments
                $payments = DB::getInstance()->query('SELECT * FROM nl2_store_payments')->results();
                foreach($payments as $payment) {
                    $queries->create('store_orders', array(
                        'user_id' => $payment->user_id,
                        'player_id' => $payment->player_id,
                        'created' => $payment->created
                    ));
                    
                    $last_id = $queries->getLastId();
                    
                    $packages = DB::getInstance()->query('SELECT * FROM nl2_store_payments_packages WHERE payment_id = ?', array($payment->id))->results();
                    foreach($packages as $package) {
                        $queries->create('store_orders_products', array(
                            'order_id' => $last_id,
                            'product_id' => $package->package_id
                        ));
                    }
                    
                    $queries->update('store_payments', $payment->id, array(
                        'order_id' => $last_id
                    ));
                }
                
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            // Update nl2_store_payments table
            try {
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_payments DROP COLUMN user_id;');
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_payments DROP COLUMN player_id;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            try {
                DB::getInstance()->createQuery('DROP TABLE nl2_store_payments_packages;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            try {
                $queries->create('store_settings', array(
                    'name' => 'checkout_complete_content',
                    'value' => 'Thanks for your payment, It can take up to 15 minutes for your payment to be processed'
                ));
                
                $queries->create('store_settings', array(
                    'name' => 'currency',
                    'value' => 'USD'
                ));
                
                $queries->create('store_settings', array(
                    'name' => 'currency_symbol',
                    'value' => '$'
                ));
                
                $allow_guests_query = $queries->getWhere('store_settings', array('name', '=', 'allow_guests'));
                if(!count($allow_guests_query)) {
                    $queries->create('store_settings', array(
                        'name' => 'allow_guests',
                        'value' => 0
                    ));
                }
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }
        
        if($old_version < 110) {
			try {
				$queries->createTable('store_connections', ' `id` int(11) NOT NULL AUTO_INCREMENT, `type` varchar(32) NOT NULL, `name` varchar(64) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
            
			try {
				$queries->createTable('store_products_connections', ' `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `action_id` int(11) DEFAULT NULL, `connection_id` int(11) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
            
            try {
                // Update main admin group permissions
                $group = $queries->getWhere('groups', array('id', '=', 2));
                $group = $group[0];
                
                $group_permissions = json_decode($group->permissions, TRUE);
                $group_permissions['staffcp.store.gateways'] = 1;
                $group_permissions['staffcp.store.manage'] = 1;
                $group_permissions['staffcp.store.products'] = 1;
                
                $group_permissions = json_encode($group_permissions);
                $queries->update('groups', 2, array('permissions' => $group_permissions));
			} catch(Exception $e){
				// Error
			}
            
            try {
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_products_commands DROP COLUMN server_id;');
            } catch(Exception $e){
				// Error
			}
            
            try {
                DB::getInstance()->createQuery('RENAME TABLE nl2_store_products_commands TO nl2_store_products_actions;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            try {
                DB::getInstance()->createQuery('RENAME TABLE nl2_store_pending_commands TO nl2_store_pending_actions;');
                
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_pending_actions CHANGE command_id action_id int(11);');
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_pending_actions CHANGE server_id connection_id int(11);');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            try {
                DB::getInstance()->createQuery('ALTER TABLE nl2_store_products_actions ADD `own_connections` tinyint(1) NOT NULL DEFAULT \'0\'');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }
        
        if($old_version < 120) {
            if(!$queries->tableExists('store_fields')){
                try {
                    $queries->createTable("store_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `identifier` varchar(32) NOT NULL, `description` varchar(255) NOT NULL, `type` int(11) NOT NULL, `required` tinyint(1) NOT NULL DEFAULT '0', `min` int(11) NOT NULL DEFAULT '0', `max` int(11) NOT NULL DEFAULT '0', `options` text NULL, `deleted` int(11) NOT NULL DEFAULT '0', `order` int(11) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)", "ENGINE=$engine DEFAULT CHARSET=$charset");
                } catch(Exception $e){
                    // Error
                }
            }
        
            try {
                $queries->createTable("store_products_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `field_id` int(11) NOT NULL, PRIMARY KEY (`id`)", "ENGINE=$engine DEFAULT CHARSET=$charset");
            } catch(Exception $e){
                // Error
            }
            
            if(!$queries->tableExists('store_orders_products_fields')){
                try {
                    $queries->createTable("store_orders_products_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, `field_id` int(11) NOT NULL, `value` TEXT NOT NULL, PRIMARY KEY (`id`)", "ENGINE=$engine DEFAULT CHARSET=$charset");
                } catch(Exception $e){
                    // Error
                }
            }
        }
    }
	
	private function initialise(){
		// Generate tables
		try {
			$engine = Config::get('mysql/engine');
			$charset = Config::get('mysql/charset');
		} catch(Exception $e){
			$engine = 'InnoDB';
			$charset = 'utf8mb4';
		}

		if(!$engine || is_array($engine))
			$engine = 'InnoDB';

		if(!$charset || is_array($charset))
			$charset = 'latin1';

		$queries = new Queries();
		
		if(!$queries->tableExists('store_agreements')) {
			try {
				$queries->createTable('store_agreements', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `player_id` int(11) NOT NULL, `agreement_id` varchar(32) NOT NULL, `status_id` int(11) NOT NULL DEFAULT \'0\', `email` varchar(128) NOT NULL, `payment_method` int(11) NOT NULL, `verified` tinyint(1) NOT NULL, `payer_id` varchar(64) NOT NULL, `last_payment_date` int(11) NOT NULL, `next_billing_date` int(11) NOT NULL, `created` int(11) NOT NULL, `updated` int(11) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
		
		if(!$queries->tableExists('store_categories')) {
			try {
				$queries->createTable('store_categories', ' `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(128) NOT NULL, `description` mediumtext, `image` varchar(128) DEFAULT NULL, `only_subcategories` tinyint(1) NOT NULL DEFAULT \'0\', `parent_category` int(11) DEFAULT NULL, `hidden` tinyint(1) NOT NULL DEFAULT \'0\', `disabled` tinyint(1) NOT NULL DEFAULT \'0\', `order` int(11) NOT NULL, `deleted` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
		
		if(!$queries->tableExists('store_products')) {
			try {
				$queries->createTable('store_products', ' `id` int(11) NOT NULL AUTO_INCREMENT, `category_id` int(11) NOT NULL, `name` varchar(128) NOT NULL, `price` varchar(8) NOT NULL, `description` mediumtext, `image` varchar(128) DEFAULT NULL, `required_products` varchar(128) DEFAULT NULL, `payment_type` tinyint(1) NOT NULL DEFAULT \'1\', `hidden` tinyint(1) NOT NULL DEFAULT \'0\', `disabled` tinyint(1) NOT NULL DEFAULT \'0\', `order` int(11) NOT NULL, `deleted` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
        
		if(!$queries->tableExists('store_products_connections')) {
			try {
				$queries->createTable('store_products_connections', ' `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `action_id` int(11) DEFAULT NULL, `connection_id` int(11) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
        
        if(!$queries->tableExists('store_products_fields')){
            try {
                $queries->createTable("store_products_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `field_id` int(11) NOT NULL, PRIMARY KEY (`id`)", "ENGINE=$engine DEFAULT CHARSET=$charset");
            } catch(Exception $e){
                // Error
            }
        }
		
		if(!$queries->tableExists('store_products_actions')) {
			try {
				$queries->createTable('store_products_actions', ' `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `type` int(11) NOT NULL DEFAULT \'1\', `command` varchar(2048) NOT NULL, `require_online` tinyint(1) NOT NULL DEFAULT \'1\', `own_connections` tinyint(1) NOT NULL DEFAULT \'0\', `order` int(11) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
        
		if(!$queries->tableExists('store_pending_actions')) {
			try {
				$queries->createTable('store_pending_actions', ' `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `action_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, `player_id` int(11) DEFAULT NULL, `connection_id` int(11) NOT NULL, `type` int(11) NOT NULL DEFAULT \'1\', `command` varchar(2048) NOT NULL, `require_online` tinyint(1) NOT NULL DEFAULT \'1\', `status` tinyint(1) NOT NULL DEFAULT \'0\', `order` int(11) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
        
		if(!$queries->tableExists('store_orders')) {
			try {
				$queries->createTable('store_orders', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) DEFAULT NULL, `player_id` int(11) DEFAULT NULL, `created` int(11) NOT NULL, `ip` varchar(128) DEFAULT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
        
		if(!$queries->tableExists('store_orders_products')) {
			try {
				$queries->createTable('store_orders_products', ' `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
        
        if(!$queries->tableExists('store_orders_products_fields')){
            try {
                $queries->createTable("store_orders_products_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, `field_id` int(11) NOT NULL, `value` TEXT NOT NULL, PRIMARY KEY (`id`)", "ENGINE=$engine DEFAULT CHARSET=$charset");
            } catch(Exception $e){
                // Error
            }
        }
		
		if(!$queries->tableExists('store_payments')) {
			try {
				$queries->createTable('store_payments', ' `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `gateway_id` int(11) NOT NULL, `payment_id` varchar(64) DEFAULT NULL, `agreement_id` varchar(64) DEFAULT NULL, `transaction` varchar(32) DEFAULT NULL, `amount` varchar(11) DEFAULT NULL, `currency` varchar(11) DEFAULT NULL, `fee` varchar(11) DEFAULT NULL, `status_id` int(11) NOT NULL DEFAULT \'0\', `created` int(11) NOT NULL, `last_updated` int(11) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
		
		if(!$queries->tableExists('store_players')) {
			try {
				$queries->createTable('store_players', ' `id` int(11) NOT NULL AUTO_INCREMENT, `username` varchar(64) NOT NULL, `uuid` varchar(64) DEFAULT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
        
        if(!$queries->tableExists('store_connections')) {
			try {
				$queries->createTable('store_connections', ' `id` int(11) NOT NULL AUTO_INCREMENT, `type` varchar(32) NOT NULL, `name` varchar(64) NOT NULL, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
		}
		
		if(!$queries->tableExists('store_settings')) {
			try {
				$queries->createTable('store_settings', ' `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(64) NOT NULL, `value` text, PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
            
            $queries->create('store_settings', array(
                'name' => 'checkout_complete_content',
                'value' => 'Thanks for your payment, It can take up to 15 minutes for your payment to be processed'
            ));
                
            $queries->create('store_settings', array(
                'name' => 'currency',
                'value' => 'USD'
            ));
                
            $queries->create('store_settings', array(
                'name' => 'currency_symbol',
                'value' => '$'
            ));
            
            $queries->create('store_settings', array(
				'name' => 'allow_guests',
				'value' => 0
			));
		}
		
		if(!$queries->tableExists('store_gateways')) {
			try {
				$queries->createTable('store_gateways', ' `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(64) NOT NULL, `displayname` varchar(64) NOT NULL, `enabled` tinyint(1) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
			} catch(Exception $e){
				// Error
			}
			
			$queries->create('store_gateways', array(
				'name' => 'PayPal',
                'displayname' => 'PayPal'
			));
            
			$queries->create('store_gateways', array(
				'name' => 'PayPalBusiness',
                'displayname' => 'PayPal'
			));
		}
        
        if(!$queries->tableExists('store_fields')){
            try {
                $queries->createTable("store_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `identifier` varchar(32) NOT NULL, `description` varchar(255) NOT NULL, `type` int(11) NOT NULL, `required` tinyint(1) NOT NULL DEFAULT '0', `min` int(11) NOT NULL DEFAULT '0', `max` int(11) NOT NULL DEFAULT '0', `options` text NULL, `deleted` int(11) NOT NULL DEFAULT '0', `order` int(11) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)", "ENGINE=$engine DEFAULT CHARSET=$charset");
            } catch(Exception $e){
                // Error
            }
        }
        
		try {
			// Update main admin group permissions
			$group = $queries->getWhere('groups', array('id', '=', 2));
			$group = $group[0];
			
			$group_permissions = json_decode($group->permissions, TRUE);
			$group_permissions['staffcp.store'] = 1;
            $group_permissions['staffcp.store.settings'] = 1;
            $group_permissions['staffcp.store.products'] = 1;
            $group_permissions['staffcp.store.payments'] = 1;
            $group_permissions['staffcp.store.gateways'] = 1;
            $group_permissions['staffcp.store.manage'] = 1;
			
			$group_permissions = json_encode($group_permissions);
			$queries->update('groups', 2, array('permissions' => $group_permissions));
		} catch(Exception $e){
			// Error
		}
	}
}