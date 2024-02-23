<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *  NamelessMC version 2.1.2
 *
 *  License: MIT
 *
 *  Store module - panel payments page
 */

class Store_Module extends Module {
    private DB $_db;
    private $_store_language, $_language, $_cache, $_store_url;

    public function __construct($language, $store_language, $pages, $cache, $endpoints) {
        $this->_db = DB::getInstance();
        $this->_language = $language;
        $this->_store_language = $store_language;
        $this->_cache = $cache;
        $this->_store_url = Store::getStorePath();

        $name = 'Store';
        $author = '<a href="https://partydragen.com" target="_blank" rel="nofollow noopener">Partydragen</a> and my <a href="https://partydragen.com/supporters/" target="_blank">Sponsors</a>';
        $module_version = '1.7.1';
        $nameless_version = '2.1.2';

        parent::__construct($this, $name, $author, $module_version, $nameless_version);

        // Define URLs which belong to this module
        $pages->add('Store', $this->_store_url, 'pages/store/index.php', 'store', true);
        $pages->add('Store', $this->_store_url . '/category', 'pages/store/category.php', 'product', true);
        $pages->add('Store', $this->_store_url . '/checkout', 'pages/store/checkout.php');
        $pages->add('Store', $this->_store_url . '/check', 'pages/store/check.php');
        $pages->add('Store', $this->_store_url . '/cancel', 'pages/store/cancel.php');
        $pages->add('Store', $this->_store_url . '/view', 'pages/store/view.php');
        $pages->add('Store', '/store/process', 'pages/backend/process.php');
        $pages->add('Store', '/store/listener', 'pages/backend/listener.php');
        $pages->add('Store', '/panel/store/general_settings', 'pages/panel/general_settings.php');
        $pages->add('Store', '/panel/store/gateways', 'pages/panel/gateways.php');
        $pages->add('Store', '/panel/store/products', 'pages/panel/products.php');
        $pages->add('Store', '/panel/store/product', 'pages/panel/product.php');
        $pages->add('Store', '/panel/store/categories', 'pages/panel/categories.php');
        $pages->add('Store', '/panel/store/payments', 'pages/panel/payments.php');
        $pages->add('Store', '/panel/store/connections', 'pages/panel/connections.php');
        $pages->add('Store', '/panel/store/fields', 'pages/panel/fields.php');
        $pages->add('Store', '/panel/store/sales', 'pages/panel/sales.php');
        $pages->add('Store', '/panel/store/coupons', 'pages/panel/coupons.php');
        $pages->add('Store', '/panel/users/store', 'pages/panel/users_store.php');
        $pages->add('Store', '/queries/payments', 'queries/payments.php');
        $pages->add('Store', '/queries/redeem_coupon', 'queries/redeem_coupon.php');

        $pages->add('Store', '/user/store', 'pages/user/store.php');

        EventHandler::registerEvent(PaymentPendingEvent::class);
        EventHandler::registerEvent(PaymentCompletedEvent::class);
        EventHandler::registerEvent(PaymentRefundedEvent::class);
        EventHandler::registerEvent(PaymentReversedEvent::class);
        EventHandler::registerEvent(PaymentDeniedEvent::class);
        EventHandler::registerEvent(CheckoutAddProductEvent::class);
        EventHandler::registerEvent(CheckoutFieldsValidationEvent::class);
        EventHandler::registerEvent(CustomerProductExpiredEvent::class);
        EventHandler::registerEvent('renderStoreCategory', 'renderStoreCategory', [], true, true);
        EventHandler::registerEvent('renderStoreProduct', 'renderStoreProduct', [], true, true);

        EventHandler::registerListener(CheckoutAddProductEvent::class, [CheckoutAddProductHook::class, 'globalLimit']);
        EventHandler::registerListener(CheckoutAddProductEvent::class, [CheckoutAddProductHook::class, 'userLimit']);
        EventHandler::registerListener(CheckoutAddProductEvent::class, [CheckoutAddProductHook::class, 'requiredProducts']);
        EventHandler::registerListener(CheckoutAddProductEvent::class, [CheckoutAddProductHook::class, 'requiredGroups']);
        EventHandler::registerListener(CheckoutAddProductEvent::class, [CheckoutAddProductHook::class, 'requiredIntegrations']);
        EventHandler::registerListener('renderStoreCategory', [ContentHook::class, 'purify']);
        EventHandler::registerListener('renderStoreCategory', [ContentHook::class, 'renderEmojis'], 10);
        EventHandler::registerListener('renderStoreCategory', [ContentHook::class, 'replaceAnchors'], 15);
        EventHandler::registerListener('renderStoreProduct', [ContentHook::class, 'purify']);
        EventHandler::registerListener('renderStoreProduct', [ContentHook::class, 'renderEmojis'], 10);
        EventHandler::registerListener('renderStoreProduct', [ContentHook::class, 'replaceAnchors'], 15);

        $endpoints->loadEndpoints(ROOT_PATH . '/modules/Store/includes/endpoints');

        define('STORE_CURRENCY_FORMAT', Settings::get('currency_format', '{currencySymbol}{price} {currencyCode}', 'Store'));

        if (Util::isModuleEnabled('Members')) {
            MemberListManager::getInstance()->registerListProvider(new MostPurchasesMemberListProvider($this->_store_language));
            MemberListManager::getInstance()->registerListProvider(new MostSpentMemberListProvider($this->_store_language));
            MemberListManager::getInstance()->registerListProvider(new MostCreditsMemberListProvider($this->_store_language));
        }

        // Check if module version changed
        $cache->setCache('store_module_cache');
        if (!$cache->isCached('module_version')) {
            $cache->store('module_version', $module_version);
        } else {
            if ($module_version != $cache->retrieve('module_version')) {
                // Version have changed, Perform actions
                $this->initialiseUpdate($cache->retrieve('module_version'));
                
                $cache->store('module_version', $module_version);
                
                if ($cache->isCached('update_check')) {
                    $cache->erase('update_check');
                }
            }
        }
    }

    public function onInstall() {
        // Initialise
        $this->initialise();
    }

    public function onUninstall() {
        // Not necessary
    }

    public function onEnable() {
        // Check if we need to initialise again
        $this->initialise();
    }

    public function onDisable() {
        // Not necessary
    }

    public function onPageLoad($user, $pages, $cache, $smarty, $navs, $widgets, $template) {
        // Add link to navbar
        $cache->setCache('nav_location');
        if (!$cache->isCached('store_location')) {
            $link_location = 1;
            $cache->store('store_location', 1);
        } else {
            $link_location = $cache->retrieve('store_location');
        }

        $cache->setCache('navbar_order');
        if (!$cache->isCached('store_order')) {
            $store_order = 21;
            $cache->store('store_order', 21);
        } else {
            $store_order = $cache->retrieve('store_order');
        }

        $cache->setCache('navbar_icons');
        if (!$cache->isCached('store_icon'))
            $icon = '';
        else
            $icon = $cache->retrieve('store_icon');

        $cache->setCache('store_settings');
        if ($cache->isCached('navbar_position'))
            $navbar_pos = $cache->retrieve('navbar_position');
        else
            $navbar_pos = 'top';

        switch ($link_location) {
            case 1:
                // Navbar
                $navs[0]->add('store', $this->_store_language->get('general', 'store'), URL::build($this->_store_url), 'top', null, $store_order, $icon);
            break;
            case 2:
                // "More" dropdown
                $navs[0]->addItemToDropdown('more_dropdown', 'store', $this->_store_language->get('general', 'store'), URL::build($this->_store_url), 'top', null, $icon, $store_order);
            break;
            case 3:
                // Footer
                $navs[0]->add('store', $this->_store_language->get('general', 'store'), URL::build($this->_store_url), 'footer', null, $store_order, $icon);
            break;
        }

        $navs[1]->add('cc_store', $this->_store_language->get('general', 'store'), URL::build('/user/store'), 'top', null, 10);

		// Widgets
		// Latest purchases
		require_once(ROOT_PATH . '/modules/Store/widgets/LatestPurchasesWidget.php');
		$widgets->add(new LatestStorePurchasesWidget($smarty, $this->_language, $this->_store_language, $cache));

        if (defined('BACK_END')) {
            // Define permissions which belong to this module
            PermissionHandler::registerPermissions('Store', [
                'staffcp.store' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('general', 'store'),
                'staffcp.store.settings' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'settings'),
                'staffcp.store.gateways' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'gateways'),
                'staffcp.store.products' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'products'),
                'staffcp.store.payments' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'payments'),
                'staffcp.store.payments.create' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'payments') . ' &raquo; ' . $this->_store_language->get('admin', 'create_payment'),
                'staffcp.store.payments.delete' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'payments') . ' &raquo; ' . $this->_store_language->get('admin', 'delete_payment'),
                'staffcp.store.connections' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'connections'),
                'staffcp.store.fields' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'fields'),
                'staffcp.store.manage_credits' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'manage_users_credits'),
                'staffcp.store.sales' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'sales'),
                'staffcp.store.coupons' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_store_language->get('admin', 'coupons'),
            ]);

            if ($user->hasPermission('staffcp.store')) {
                $cache->setCache('panel_sidebar');
                if (!$cache->isCached('store_order')) {
                    $order = 10;
                    $cache->store('store_order', 10);
                } else {
                    $order = $cache->retrieve('store_order');
                }

                $navs[2]->add('store_divider', mb_strtoupper($this->_store_language->get('general', 'store')), 'divider', 'top', null, $order, '');

                if (!$cache->isCached('store_configuration_icon')) {
                    $icon = '<i class="nav-icon fas fa-wrench"></i>';
                    $cache->store('store_configuration_icon', $icon);
                } else
                    $icon = $cache->retrieve('store_configuration_icon');

                $navs[2]->addDropdown('store_configuration', $this->_store_language->get('admin', 'store_configuration'), 'top', $order + 0.1, $icon);

                if ($user->hasPermission('staffcp.store.settings')) {
                    if (!$cache->isCached('store_settings_icon')) {
                        $icon = '<i class="nav-icon fas fa-cogs"></i>';
                        $cache->store('store_settings_icon', $icon);
                    } else
                        $icon = $cache->retrieve('store_settings_icon');

                    $navs[2]->addItemToDropdown('store_configuration', 'general_settings', $this->_language->get('admin', 'general_settings'), URL::build('/panel/store/general_settings'), 'top', null, $icon, $order + 0.2);
                }

                if ($user->hasPermission('staffcp.store.gateways')) {
                    if (!$cache->isCached('store_gateways_icon')) {
                        $icon = '<i class="nav-icon far fa-credit-card"></i>';
                        $cache->store('store_gateways_icon', $icon);
                    } else
                        $icon = $cache->retrieve('store_gateways_icon');

                    $navs[2]->addItemToDropdown('store_configuration', 'store_gateways', $this->_store_language->get('admin', 'gateways'), URL::build('/panel/store/gateways'), 'top', null, $icon, $order + 0.3);
                }

                if ($user->hasPermission('staffcp.store.connections')) {
                    if (!$cache->isCached('store_connections_icon')) {
                        $icon = '<i class="nav-icon fas fa-plug"></i>';
                        $cache->store('store_connections_icon', $icon);
                    } else
                        $icon = $cache->retrieve('store_connections_icon');

                    $navs[2]->addItemToDropdown('store_configuration', 'store_connections', $this->_store_language->get('admin', 'service_connections'), URL::build('/panel/store/connections'), 'top', null, $icon, $order + 0.4);
                }

                if ($user->hasPermission('staffcp.store.fields')) {
                    if (!$cache->isCached('store_fields_icon')) {
                        $icon = '<i class="nav-icon fas fa-id-card"></i>';
                        $cache->store('store_fields_icon', $icon);
                    } else
                        $icon = $cache->retrieve('store_fields_icon');

                    $navs[2]->addItemToDropdown('store_configuration', 'store_fields', $this->_store_language->get('admin', 'fields'), URL::build('/panel/store/fields'), 'top', null, $icon, $order + 0.5);
                }

                if ($user->hasPermission('staffcp.store.products')) {
                    if (!$cache->isCached('store_products_icon')) {
                        $icon = '<i class="nav-icon fas fa-box-open"></i>';
                        $cache->store('store_products_icon', $icon);
                    } else
                        $icon = $cache->retrieve('store_products_icon');

                    $navs[2]->add('store_products', $this->_store_language->get('general', 'products'), URL::build('/panel/store/products'), 'top', null, ($order + 0.6), $icon);
                }

                if ($user->hasPermission('staffcp.store.payments')) {
                    if (!$cache->isCached('store_payments_icon')) {
                        $icon = '<i class="nav-icon fas fa-donate"></i>';
                        $cache->store('store_payments_icon', $icon);
                    } else
                        $icon = $cache->retrieve('store_payments_icon');

                    $navs[2]->add('store_payments', $this->_store_language->get('admin', 'payments'), URL::build('/panel/store/payments'), 'top', null, ($order + 0.7), $icon);
                }

                if ($user->hasPermission('staffcp.store.sales')) {
                    if (!$cache->isCached('store_sales_icon')) {
                        $icon = '<i class="nav-icon fa-solid fa-tag"></i>';
                        $cache->store('store_sales_icon', $icon);
                    } else
                        $icon = $cache->retrieve('store_sales_icon');

                    $navs[2]->add('store_sales', $this->_store_language->get('admin', 'sales'), URL::build('/panel/store/sales'), 'top', null, ($order + 0.8), $icon);
                }

                if ($user->hasPermission('staffcp.store.coupons')) {
                    if (!$cache->isCached('store_coupons_icon')) {
                        $icon = '<i class="nav-icon fas fa-ticket-alt"></i>';
                        $cache->store('store_coupons_icon', $icon);
                    } else
                        $icon = $cache->retrieve('store_coupons_icon');

                    $navs[2]->add('store_coupons', $this->_store_language->get('admin', 'coupons'), URL::build('/panel/store/coupons'), 'top', null, ($order + 0.9), $icon);
                }
            }

            if ($user->hasPermission('staffcp.store.payments'))
                Core_Module::addUserAction($this->_store_language->get('general', 'store'), URL::build('/panel/users/store/', 'user={id}'));

            if (defined('PANEL_PAGE') && PANEL_PAGE == 'dashboard') {
                // Dashboard graph
                $cache->setCache('dashboard_graph');
                if ($cache->isCached('payments_data')) {
                    $data = $cache->retrieve('payments_data');

                } else {
                    $payments = DB::getInstance()->query(
                        <<<SQL
                            SELECT DATE_FORMAT(FROM_UNIXTIME(`created`), '%Y-%m-%d') d, COUNT(*) c
                            FROM nl2_store_payments
                            WHERE status_id = 1 AND `created` > ? AND `created` < UNIX_TIMESTAMP()
                            GROUP BY DATE_FORMAT(FROM_UNIXTIME(`created`), '%Y-%m-%d')
                        SQL,
                        [strtotime('7 days ago')],
                    );

                    // Output array
                    $data = [];

                    $data['datasets']['payments']['label'] = 'store_language/admin/payments'; // for $store_language->get('admin', 'payments');
                    $data['datasets']['payments']['colour'] = '#4cf702';

                    if ($payments->count()) {
                        foreach ($payments->results() as $day) {
                            $data['_' . $day->d] = ['payments' => $day->c];
                        }
                    }

                    $payments = null;

                    $data = Core_Module::fillMissingGraphDays($data, 'payments');

                    // Sort by date
                    ksort($data);

                    $cache->store('payments_data', $data, 120);
                }

                Core_Module::addDataToDashboardGraph($this->_language->get('admin', 'overview'), $data);
            }
        }

        // Check for module updates
        if (isset($_GET['route']) && $user->isLoggedIn() && $user->hasPermission('admincp.update')) {
            // Page belong to this module?
            $page = $pages->getActivePage();
            if ($page['module'] == 'Store') {

                $cache->setCache('store_module_cache');
                if ($cache->isCached('update_check')) {
                    $update_check = $cache->retrieve('update_check');
                } else {
                    require_once(ROOT_PATH . '/modules/Store/classes/Store.php');
                    $update_check = Store::updateCheck();
                    $cache->store('update_check', $update_check, 3600);
                }

                $update_check = json_decode($update_check);
                if (!isset($update_check->error) && !isset($update_check->no_update) && isset($update_check->new_version)) {  
                    $smarty->assign([
                        'NEW_UPDATE' => (isset($update_check->urgent) && $update_check->urgent == 'true') ? $this->_store_language->get('admin', 'new_urgent_update_available_x', ['module' => $this->getName()]) : $this->_store_language->get('admin', 'new_update_available_x', ['module' => $this->getName()]),
                        'NEW_UPDATE_URGENT' => (isset($update_check->urgent) && $update_check->urgent == 'true'),
                        'CURRENT_VERSION' => $this->_store_language->get('admin', 'current_version_x', ['version' => Output::getClean($this->getVersion())]),
                        'NEW_VERSION' => $this->_store_language->get('admin', 'new_version_x', ['new_version' => Output::getClean($update_check->new_version)]),
                        'NAMELESS_UPDATE' => $this->_store_language->get('admin', 'view_resource'),
                        'NAMELESS_UPDATE_LINK' => Output::getClean($update_check->link)
                    ]);
                }
            }
        }
    }

    public function getDebugInfo(): array {
        // Services
        $services_list = [];
        foreach (Services::getInstance()->getAll() as $service) {
            $services_list[] = [
                'id' => $service->getId(),
                'name' => $service->getName(),
            ];
        }

        // Connections
        $connections_list = [];
        $connections_query = $this->_db->query('SELECT * FROM nl2_store_connections')->results();
        foreach ($connections_query as $data) {
            $connections_list[] = [
                'id' => (int)$data->id,
                'name' => $data->name,
                'service_id' => $data->service_id,
                'last_fetch' => (int)$data->last_fetch,
                'pending_actions' => (int)$this->_db->query('SELECT COUNT(*) AS c FROM nl2_store_pending_actions WHERE connection_id = ? AND status = 0', [$data->id])->first()->c,
                'completed_actions' => (int)$this->_db->query('SELECT COUNT(*) AS c FROM nl2_store_pending_actions WHERE connection_id = ? AND status = 1', [$data->id])->first()->c,
            ];
        }

        // Fields
        $fields_list = [];
        $fields_query = $this->_db->query('SELECT * FROM nl2_store_fields')->results();
        foreach ($fields_query as $data) {
            $fields_list[] = [
                'id' => $data->id,
                'identifier' => $data->identifier,
                'type' => $data->type,
                'required' => $data->required,
                'min' => $data->min,
                'max' => $data->max,
                'options' => $data->options,
                'regex' => $data->regex,
                'default_value' => $data->default_value,
            ];
        }

        // Products
        $products_list = [];
        $products_query = $this->_db->query('SELECT * FROM nl2_store_products WHERE deleted = 0 ORDER BY `order` ASC')->results();
        foreach ($products_query as $data) {
            $product = new Product(null, null, $data);

            $connections = [];
            foreach ($product->getConnections() as $connection) {
                $connections[] = $connection->id;
            }

            $fields = [];
            foreach ($product->getFields() as $field) {
                $fields[] = $field->id;
            }

            $actions = [];
            foreach ($product->getActions() as $action) {
                $action_connections = [];
                if ($action->data()->own_connections) {
                    foreach ($action->getConnections() as $connection) {
                        $action_connections[] = $connection->id;
                    }
                }

                $actions[] = [
                    'id' => $action->data()->id,
                    'trigger' => $action->data()->type,
                    'command' => $action->data()->command,
                    'require_online' => $action->data()->require_online,
                    'own_connections' => $action->data()->own_connections,
                    'service_id' => $action->data()->service_id,
                    'connections' => $action_connections,
                ];
            }

            $products_list[] = [
                'id' => $product->data()->id,
                'name' => $product->data()->name,
                'price_cents' => $product->data()->price_cents,
                'hidden' => $product->data()->hidden,
                'disabled' => $product->data()->disabled,
                'connections' => $connections,
                'fields' => $fields,
                'actions' => $actions
            ];
        }

        $gateways_list = [];
        foreach (Gateways::getInstance()->getAll() as $gateway) {
            $gateways_list[] = [
                'name' => $gateway->getName(),
                'version' => $gateway->getVersion(),
                'store_version' => $gateway->getStoreVersion(),
                'author' => $gateway->getAuthor(),
                'enabled' => $gateway->isEnabled()
            ];
        }

        return [
            'settings' => [
                'allow_guests' => Settings::get('allow_guests', '0', 'Store'),
                'player_login' => Settings::get('player_login', '0', 'Store'),
                'store_path' => Settings::get('store_path', '/store', 'Store'),
                'currency' => Settings::get('currency', 'USD', 'Store'),
                'currency_symbol' => Settings::get('currency_symbol', '$', 'Store'),
                'username_validation_method' => Settings::get('username_validation_method', 'nameless', 'Store'),
            ],
            'services' => $services_list,
            'connections' => $connections_list,
            'fields' => $fields_list,
            'products' => $products_list,
            'gateways' => $gateways_list
        ];
    }

    private function initialiseUpdate($old_version) {
        $old_version = str_replace([".", "-"], "", $old_version);

        // Old converter from pre release
        if (!$this->_db->showTables('store_orders')) {
            // Rename Tabels
            try {
                $this->_db->query('RENAME TABLE nl2_store_packages TO nl2_store_products;');
                $this->_db->query('RENAME TABLE nl2_store_packages_commands TO nl2_store_products_commands;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_products CHANGE required_packages required_products varchar(128);');
                $this->_db->query('ALTER TABLE nl2_store_products_commands CHANGE package_id product_id int(11);');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_payments ADD `order_id` int(11) NOT NULL');
                $this->_db->query('ALTER TABLE nl2_store_payments ADD `fee` varchar(11) DEFAULT NULL');
                
                $this->_db->query('ALTER TABLE nl2_store_payments CHANGE payment_method gateway_id int(11);');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            // Update nl2_store_pending_commands table
            try {
                $this->_db->query('ALTER TABLE nl2_store_pending_commands ADD `order_id` int(11) NOT NULL');
                $this->_db->query('ALTER TABLE nl2_store_pending_commands ADD `command_id` int(11) NOT NULL');
                $this->_db->query('ALTER TABLE nl2_store_pending_commands ADD `product_id` int(11) NOT NULL');

                $this->_db->query('ALTER TABLE nl2_store_pending_commands DROP COLUMN payment_id;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            // Update nl2_store_gateways table
            try {
                $this->_db->query('ALTER TABLE nl2_store_gateways ADD `displayname` varchar(64) NOT NULL');
                
                $this->_db->update('store_gateways', 1, [
                    'name' => 'PayPal',
                    'displayname' => 'PayPal'
                ]);
                
                $this->_db->query('ALTER TABLE nl2_store_gateways DROP COLUMN client_id;');
                $this->_db->query('ALTER TABLE nl2_store_gateways DROP COLUMN client_key;');
                $this->_db->query('ALTER TABLE nl2_store_gateways DROP COLUMN hook_key;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->createTable('store_orders', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) DEFAULT NULL, `player_id` int(11) DEFAULT NULL, `created` int(11) NOT NULL, `ip` varchar(128) DEFAULT NULL, PRIMARY KEY (`id`)');
                $this->_db->createTable('store_orders_products', ' `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, PRIMARY KEY (`id`)');
                
                // Convert old payments
                $payments = $this->_db->query('SELECT * FROM nl2_store_payments')->results();
                foreach ($payments as $payment) {
                    $this->_db->insert('store_orders', [
                        'user_id' => $payment->user_id,
                        'player_id' => $payment->player_id,
                        'created' => $payment->created
                    ]);
                    
                    $last_id = $this->_db->lastId();
                    
                    $packages = $this->_db->query('SELECT * FROM nl2_store_payments_packages WHERE payment_id = ?', [$payment->id])->results();
                    foreach ($packages as $package) {
                        $this->_db->insert('store_orders_products', [
                            'order_id' => $last_id,
                            'product_id' => $package->package_id
                        ]);
                    }
                    
                    $this->_db->update('store_payments', $payment->id, [
                        'order_id' => $last_id
                    ]);
                }
                
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            // Update nl2_store_payments table
            try {
                $this->_db->query('ALTER TABLE nl2_store_payments DROP COLUMN user_id;');
                $this->_db->query('ALTER TABLE nl2_store_payments DROP COLUMN player_id;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            try {
                $this->_db->query('DROP TABLE nl2_store_payments_packages;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->insert('store_settings', [
                    'name' => 'checkout_complete_content',
                    'value' => 'Thanks for your payment, It can take up to 15 minutes for your payment to be processed'
                ]);
                
                $this->_db->insert('store_settings', [
                    'name' => 'currency',
                    'value' => 'USD'
                ]);
                
                $this->_db->insert('store_settings', [
                    'name' => 'currency_symbol',
                    'value' => '$'
                ]);
                
                $allow_guests_query = $this->_db->get('store_settings', ['name', '=', 'allow_guests'])->results();
                if (!count($allow_guests_query)) {
                    $this->_db->insert('store_settings', [
                        'name' => 'allow_guests',
                        'value' => 0
                    ]);
                }
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 110) {
            try {
                $this->_db->createTable('store_connections', ' `id` int(11) NOT NULL AUTO_INCREMENT, `type` varchar(32) NOT NULL, `name` varchar(64) NOT NULL, PRIMARY KEY (`id`)');
            } catch (Exception $e) {
                // Error
            }
            
            try {
                $this->_db->createTable('store_products_connections', ' `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `action_id` int(11) DEFAULT NULL, `connection_id` int(11) NOT NULL, PRIMARY KEY (`id`)');
            } catch (Exception $e) {
                // Error
            }
            
            try {
                // Update main admin group permissions
                $group = $this->_db->get('groups', ['id', '=', 2])->results();
                $group = $group[0];
                
                $group_permissions = json_decode($group->permissions, TRUE);
                $group_permissions['staffcp.store.gateways'] = 1;
                $group_permissions['staffcp.store.manage'] = 1;
                $group_permissions['staffcp.store.products'] = 1;
                
                $group_permissions = json_encode($group_permissions);
                $this->_db->update('groups', 2, ['permissions' => $group_permissions]);
            } catch (Exception $e) {
                // Error
            }
            
            try {
                $this->_db->query('ALTER TABLE nl2_store_products_commands DROP COLUMN server_id;');
            } catch (Exception $e) {
                // Error
            }
            
            try {
                $this->_db->query('RENAME TABLE nl2_store_products_commands TO nl2_store_products_actions;');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            try {
                $this->_db->query('RENAME TABLE nl2_store_pending_commands TO nl2_store_pending_actions;');
                
                $this->_db->query('ALTER TABLE nl2_store_pending_actions CHANGE command_id action_id int(11);');
                $this->_db->query('ALTER TABLE nl2_store_pending_actions CHANGE server_id connection_id int(11);');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
            
            try {
                $this->_db->query('ALTER TABLE nl2_store_products_actions ADD `own_connections` tinyint(1) NOT NULL DEFAULT \'0\'');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 120) {
            if (!$this->_db->showTables('store_fields')) {
                try {
                    $this->_db->createTable("store_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `identifier` varchar(32) NOT NULL, `description` varchar(255) NOT NULL, `type` int(11) NOT NULL, `required` tinyint(1) NOT NULL DEFAULT '0', `min` int(11) NOT NULL DEFAULT '0', `max` int(11) NOT NULL DEFAULT '0', `options` text NULL, `deleted` int(11) NOT NULL DEFAULT '0', `order` int(11) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)");
                } catch (Exception $e) {
                    // Error
                }
            }
        
            try {
                $this->_db->createTable("store_products_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `field_id` int(11) NOT NULL, PRIMARY KEY (`id`)");
            } catch (Exception $e) {
                // Error
            }
            
            if (!$this->_db->showTables('store_orders_products_fields')) {
                try {
                    $this->_db->createTable("store_orders_products_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, `field_id` int(11) NOT NULL, `value` TEXT NOT NULL, PRIMARY KEY (`id`)");
                } catch (Exception $e) {
                    // Error
                }
            }
        }

        if ($old_version < 130) {
            $this->_db->insert('store_settings', [
                'name' => 'player_login',
                'value' => 1
            ]);
            
            try {
                // Update main admin group permissions
                $group = $this->_db->get('groups', ['id', '=', 2])->results();
                $group = $group[0];
                
                $group_permissions = json_decode($group->permissions, TRUE);
                $group_permissions['staffcp.store.connections'] = 1;
                $group_permissions['staffcp.store.fields'] = 1;
                
                $group_permissions = json_encode($group_permissions);
                $this->_db->update('groups', 2, ['permissions' => $group_permissions]);
            } catch (Exception $e) {
                // Error
            }
        }

        if ($old_version < 140) {
            try {
                $this->_db->query('RENAME TABLE nl2_store_players TO nl2_store_customers');
                
                $this->_db->query('ALTER TABLE nl2_store_customers CHANGE `username` `username` varchar(64) DEFAULT NULL');
                $this->_db->query('ALTER TABLE nl2_store_customers CHANGE `uuid` `identifier` varchar(64) DEFAULT NULL');
                $this->_db->query('ALTER TABLE nl2_store_customers ADD `user_id` int(11) DEFAULT NULL');
                $this->_db->query('ALTER TABLE nl2_store_customers ADD `cents` bigint(20) NOT NULL DEFAULT \'0\'');
                $this->_db->query('ALTER TABLE nl2_store_customers ADD `integration_id` int(11) NOT NULL');

                $this->_db->query('UPDATE nl2_store_customers SET `integration_id` = 1 WHERE id <> 0');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_connections DROP COLUMN type');
                
                $this->_db->query('ALTER TABLE nl2_store_connections ADD `service_id` int(11) NOT NULL');
                $this->_db->query('ALTER TABLE nl2_store_connections ADD `data` text DEFAULT NULL');
                
                $this->_db->query('UPDATE nl2_store_connections SET `service_id` = 2 WHERE id <> 0');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_products_actions ADD `service_id` int(11) NOT NULL');
                
                $this->_db->query('UPDATE nl2_store_products_actions SET `service_id` = 2 WHERE id <> 0');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_orders CHANGE `player_id` `to_customer_id` int(11)');
                $this->_db->query('ALTER TABLE nl2_store_orders ADD `from_customer_id` int(11) NOT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_pending_actions CHANGE `player_id` `customer_id` int(11)');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 140) {
            try {
                // Attempt to register customer for old orders bought from namelessmc users and update the orders with the new customer data
                $namelessmc_customers = [];
                $orders = $this->_db->query('SELECT * FROM nl2_store_orders WHERE to_customer_id IS NULL AND user_id IS NOT NULL')->results();
                foreach ($orders as $order) {
                    if (array_key_exists($order->user_id, $namelessmc_customers)) {
                        $customer_id = $namelessmc_customers[$order->user_id];
                    } else {
                        $customer = new Customer(null, $order->user_id, 'user_id');
                        if ($customer->exists()) {
                            $customer_id = $customer->data()->id;
                        } else {
                            $this->_db->insert('store_customers', [
                                'user_id' => $order->user_id,
                                'integration_id' => 0
                            ]);

                            $customer_id = $this->_db->lastId();
                        }

                        $namelessmc_customers[$order->user_id] = $customer_id;
                    }

                    $this->_db->query('UPDATE nl2_store_orders SET `from_customer_id` = ?, `to_customer_id` = ? WHERE id = ?', [$customer_id, $customer_id, $order->id]);
                    $this->_db->query('UPDATE nl2_store_pending_actions SET `customer_id` = ? WHERE order_id = ?', [$customer_id, $order->id]);
                }

                print_r($namelessmc_customers);
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('UPDATE nl2_store_orders SET `from_customer_id` = to_customer_id WHERE id <> 0', []);
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 142) {
            try {
                $this->_db->query('ALTER TABLE nl2_store_connections ADD `last_fetch` int(20) NOT NULL DEFAULT \'0\'');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 143) {
            try {
                $gateway_exists = $this->_db->get('store_gateways', ['name', '=', 'Store Credits']);
                if (!$gateway_exists->count()) {
                    $this->_db->insert('store_gateways', [
                        'name' => 'Store Credits',
                        'displayname' => 'Store Credits',
                        'enabled' => 1
                    ]);
                }
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_products ADD `user_limit` varchar(128) DEFAULT NULL');
                $this->_db->query('ALTER TABLE nl2_store_products ADD `global_limit` varchar(128) DEFAULT NULL');
                $this->_db->query('ALTER TABLE nl2_store_products ADD `required_groups` varchar(128) DEFAULT NULL');
                $this->_db->query('ALTER TABLE nl2_store_products ADD `required_integrations` varchar(128) DEFAULT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_orders_products ADD `quantity` int(11) NOT NULL DEFAULT \'1\'');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_fields ADD `regex` varchar(64) DEFAULT NULL');
                $this->_db->query('ALTER TABLE nl2_store_fields ADD `default_value` varchar(64) NOT NULL DEFAULT \'\'');

                $this->_db->insert('store_fields', [
                    'identifier' => 'quantity',
                    'description' => 'Quantity',
                    'type' => '4',
                    'required' => '1',
                    'min' => '1',
                    'max' => '2',
                    'default_value' => '1',
                    'order' => '0'
                ]);
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 151) {
            try {
                $gateway_exists = $this->_db->get('store_gateways', ['name', '=', 'Store Credits']);
                if (!$gateway_exists->count()) {
                    $this->_db->insert('store_gateways', [
                        'name' => 'Store Credits',
                        'displayname' => 'Store Credits',
                        'enabled' => 1
                    ]);
                }
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 152) {
            if (!$this->_db->showTables('store_sales')) {
                try {
                    $this->_db->createTable("store_sales", " `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(64) NOT NULL, `effective_on` varchar(256) NOT NULL, `discount_type` int(11) NOT NULL, `discount_amount` int(11) NOT NULL, `start_date` int(11) NOT NULL, `expire_date` int(11) NOT NULL, PRIMARY KEY (`id`)");
                } catch (Exception $e) {
                    // Error
                }
            }

            if (!$this->_db->showTables('store_coupons')) {
                try {
                    $this->_db->createTable("store_coupons", " `id` int(11) NOT NULL AUTO_INCREMENT, `code` varchar(64) NOT NULL, `effective_on` varchar(256) NOT NULL, `discount_type` int(11) NOT NULL, `discount_amount` int(11) NOT NULL, `start_date` int(11) NOT NULL, `expire_date` int(11) NOT NULL, `redeem_limit` int(11) NOT NULL DEFAULT '0', `customer_limit` int(11) NOT NULL DEFAULT '0', `min_basket` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)");
                } catch (Exception $e) {
                    // Error
                }
            }

            try {
                if ($this->_db->showTables('store_settings')) {
                    // Convert store settings to NamelessMC settings system
                    $settings = $this->_db->query('SELECT * FROM nl2_store_settings')->results();
                    foreach ($settings as $setting) {
                        Settings::set($setting->name, $setting->value, 'Store');
                    }

                    $this->_db->query('DROP TABLE nl2_store_settings');
                }
            } catch (Exception $e) {
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_orders ADD `coupon_id` int(11) DEFAULT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('UPDATE nl2_store_products SET price = price * 100');
                $this->_db->query('ALTER TABLE nl2_store_products CHANGE `price` `price_cents` int(11) NOT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('UPDATE nl2_store_payments SET amount = amount * 100');
                $this->_db->query('ALTER TABLE nl2_store_payments CHANGE `amount` `amount_cents` int(11) DEFAULT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('UPDATE nl2_store_payments SET fee = fee * 100');
                $this->_db->query('ALTER TABLE nl2_store_payments CHANGE `fee` `fee_cents` int(11) DEFAULT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 153) {
            try {
                $this->_db->query('ALTER TABLE nl2_store_products ADD `allowed_gateways` varchar(128) DEFAULT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_orders_products ADD `amount_cents` int(11) NOT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $field = $this->_db->query('SELECT id FROM nl2_store_fields WHERE identifier = \'price\'');
                if (!$field->count()) {
                    $this->_db->insert('store_fields', [
                        'identifier' => 'price',
                        'description' => 'Pay what you want',
                        'type' => '4',
                        'required' => '1',
                        'min' => '1',
                        'max' => '9',
                        'default_value' => '',
                        'order' => '0'
                    ]);
                }
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_products_actions CHANGE `command` `command` text NOT NULL');
                $this->_db->query('ALTER TABLE nl2_store_pending_actions CHANGE `command` `command` text NOT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 154) {
            try {
                $this->_db->query('ALTER TABLE nl2_store_products ADD `min_player_age` varchar(128) DEFAULT NULL');
                $this->_db->query('ALTER TABLE nl2_store_products ADD `min_player_playtime` varchar(128) DEFAULT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 160) {
            try {
                $this->_db->query('ALTER TABLE nl2_store_products ADD `durability` varchar(128) DEFAULT NULL');
                $this->_db->query('ALTER TABLE nl2_store_products ADD `recurring_payment_type` int(11) NOT NULL DEFAULT \'1\'');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_orders_products ADD `expire` int(11) DEFAULT NULL');
                $this->_db->query('ALTER TABLE nl2_store_orders_products ADD `task_id` int(11) DEFAULT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 163) {
            try {
                $field = $this->_db->query('SELECT id FROM nl2_store_fields WHERE identifier = \'price\'');
                if (!$field->count()) {
                    $this->_db->insert('store_fields', [
                        'identifier' => 'price',
                        'description' => 'Pay what you want',
                        'type' => '4',
                        'required' => '1',
                        'min' => '1',
                        'max' => '9',
                        'default_value' => '',
                        'order' => '0'
                    ]);
                }
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 170) {
            try {
                $this->_db->insert('store_categories', [
                    'name' => 'Home',
                    'description' => Settings::get('store_content', '', 'Store'),
                    'order' => 0
                ]);
            } catch (Exception $e) {
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_products ADD `require_one_product` tinyint(1) NOT NULL DEFAULT \'0\'');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }

            try {
                $this->_db->query('ALTER TABLE nl2_store_categories ADD `url` varchar(255) DEFAULT NULL');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }

        if ($old_version < 171) {
            try {
                $this->_db->query('ALTER TABLE `nl2_store_orders` ADD INDEX `nl2_store_orders_idx_to_customer_id` (`to_customer_id`)');
                $this->_db->query('ALTER TABLE `nl2_store_orders` ADD INDEX `nl2_store_orders_idx_from_customer_id` (`from_customer_id`)');

                $this->_db->query('ALTER TABLE `nl2_store_orders_products` ADD INDEX `nl2_store_orders_products_idx_order_id` (`order_id`)');
                $this->_db->query('ALTER TABLE `nl2_store_orders_products` ADD INDEX `nl2_store_orders_products_idx_product_id` (`product_id`)');

                $this->_db->query('ALTER TABLE `nl2_store_payments` ADD INDEX `nl2_store_payments_idx_order_id` (`order_id`)');

                $this->_db->query('ALTER TABLE `nl2_store_customers` ADD INDEX `nl2_store_customers_idx_user_id` (`user_id`)');
            } catch (Exception $e) {
                // unable to retrieve from config
                echo $e->getMessage() . '<br />';
            }
        }
    }

    private function initialise() {
        // Generate tables
        if (!$this->_db->showTables('store_agreements')) {
            try {
                $this->_db->createTable('store_agreements', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `player_id` int(11) NOT NULL, `agreement_id` varchar(32) NOT NULL, `status_id` int(11) NOT NULL DEFAULT \'0\', `email` varchar(128) NOT NULL, `payment_method` int(11) NOT NULL, `verified` tinyint(1) NOT NULL, `payer_id` varchar(64) NOT NULL, `last_payment_date` int(11) NOT NULL, `next_billing_date` int(11) NOT NULL, `created` int(11) NOT NULL, `updated` int(11) NOT NULL, PRIMARY KEY (`id`)');
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_categories')) {
            try {
                $this->_db->createTable('store_categories', ' `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(128) NOT NULL, `description` mediumtext, `url` varchar(255) DEFAULT NULL, `image` varchar(128) DEFAULT NULL, `only_subcategories` tinyint(1) NOT NULL DEFAULT \'0\', `parent_category` int(11) DEFAULT NULL, `hidden` tinyint(1) NOT NULL DEFAULT \'0\', `disabled` tinyint(1) NOT NULL DEFAULT \'0\', `order` int(11) NOT NULL, `deleted` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)');

                $this->_db->insert('store_categories', [
                    'name' => 'Home',
                    'description' => '',
                    'order' => 0
                ]);
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_products')) {
            try {
                $this->_db->createTable('store_products', ' `id` int(11) NOT NULL AUTO_INCREMENT, `category_id` int(11) NOT NULL, `name` varchar(128) NOT NULL, `price_cents` int(11) NOT NULL, `description` mediumtext, `image` varchar(128) DEFAULT NULL, `durability` varchar(128) DEFAULT NULL, `recurring_payment_type` int(11) NOT NULL DEFAULT \'1\', `global_limit` varchar(128) DEFAULT NULL, `user_limit` varchar(128) DEFAULT NULL, `required_products` varchar(128) DEFAULT NULL, `require_one_product` tinyint(1) NOT NULL DEFAULT \'0\', `required_groups` varchar(128) DEFAULT NULL, `required_integrations` varchar(128) DEFAULT NULL, `min_player_age` varchar(128) DEFAULT NULL, `min_player_playtime` varchar(128) DEFAULT NULL, `allowed_gateways` varchar(128) DEFAULT NULL, `payment_type` tinyint(1) NOT NULL DEFAULT \'1\', `hidden` tinyint(1) NOT NULL DEFAULT \'0\', `disabled` tinyint(1) NOT NULL DEFAULT \'0\', `order` int(11) NOT NULL, `deleted` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)');
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_products_connections')) {
            try {
                $this->_db->createTable('store_products_connections', ' `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `action_id` int(11) DEFAULT NULL, `connection_id` int(11) NOT NULL, PRIMARY KEY (`id`)');
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_products_fields')) {
            try {
                $this->_db->createTable("store_products_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `field_id` int(11) NOT NULL, PRIMARY KEY (`id`)");
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_products_actions')) {
            try {
                $this->_db->createTable('store_products_actions', ' `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `type` int(11) NOT NULL DEFAULT \'1\', `service_id` int(11) NOT NULL, `command` text NOT NULL, `require_online` tinyint(1) NOT NULL DEFAULT \'1\', `own_connections` tinyint(1) NOT NULL DEFAULT \'0\', `order` int(11) NOT NULL, PRIMARY KEY (`id`)');
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_pending_actions')) {
            try {
                $this->_db->createTable('store_pending_actions', ' `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `action_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, `customer_id` int(11) DEFAULT NULL, `connection_id` int(11) NOT NULL, `type` int(11) NOT NULL DEFAULT \'1\', `command` text NOT NULL, `require_online` tinyint(1) NOT NULL DEFAULT \'1\', `status` tinyint(1) NOT NULL DEFAULT \'0\', `order` int(11) NOT NULL, PRIMARY KEY (`id`)');
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_orders')) {
            try {
                $this->_db->createTable('store_orders', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) DEFAULT NULL, `from_customer_id` int(11) NOT NULL, `to_customer_id` int(11) NOT NULL, `created` int(11) NOT NULL, `ip` varchar(128) DEFAULT NULL, `coupon_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`)');

                $this->_db->query('ALTER TABLE `nl2_store_orders` ADD INDEX `nl2_store_orders_idx_to_customer_id` (`to_customer_id`)');
                $this->_db->query('ALTER TABLE `nl2_store_orders` ADD INDEX `nl2_store_orders_idx_from_customer_id` (`from_customer_id`)');
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_orders_products')) {
            try {
                $this->_db->createTable('store_orders_products', ' `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, `quantity` int(11) NOT NULL DEFAULT \'1\', `amount_cents` int(11) NOT NULL, `expire` int(11) DEFAULT NULL, `task_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`)');

                $this->_db->query('ALTER TABLE `nl2_store_orders_products` ADD INDEX `nl2_store_orders_products_idx_order_id` (`order_id`)');
                $this->_db->query('ALTER TABLE `nl2_store_orders_products` ADD INDEX `nl2_store_orders_products_idx_product_id` (`product_id`)');
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_orders_products_fields')) {
            try {
                $this->_db->createTable("store_orders_products_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `product_id` int(11) NOT NULL, `field_id` int(11) NOT NULL, `value` TEXT NOT NULL, PRIMARY KEY (`id`)");
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_payments')) {
            try {
                $this->_db->createTable('store_payments', ' `id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `gateway_id` int(11) NOT NULL, `payment_id` varchar(64) DEFAULT NULL, `agreement_id` varchar(64) DEFAULT NULL, `transaction` varchar(32) DEFAULT NULL, `amount_cents` int(11) DEFAULT NULL, `currency` varchar(11) DEFAULT NULL, `fee_cents` int(11) DEFAULT NULL, `status_id` int(11) NOT NULL DEFAULT \'0\', `created` int(11) NOT NULL, `last_updated` int(11) NOT NULL, PRIMARY KEY (`id`)');

                $this->_db->query('ALTER TABLE `nl2_store_payments` ADD INDEX `nl2_store_payments_idx_order_id` (`order_id`)');
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_customers')) {
            try {
                $this->_db->createTable('store_customers', ' `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) DEFAULT NULL, `integration_id` int(11) NOT NULL, `username` varchar(64) DEFAULT NULL, `identifier` varchar(64) DEFAULT NULL, `cents` bigint(20) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)');

                $this->_db->query('ALTER TABLE `nl2_store_customers` ADD INDEX `nl2_store_customers_idx_user_id` (`user_id`)');
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_connections')) {
            try {
                $this->_db->createTable('store_connections', ' `id` int(11) NOT NULL AUTO_INCREMENT, `service_id` int(11) NOT NULL, `name` varchar(64) NOT NULL, `data` text DEFAULT NULL, `last_fetch` int(11) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)');
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->get('settings', ['module', '=', 'Store'])->count()) {
            Settings::set('checkout_complete_content', 'Thanks for your payment, It can take up to 15 minutes for your payment to be processed', 'Store');
            Settings::set('currency', 'USD', 'Store');
            Settings::set('currency_symbol', '$', 'Store');
            Settings::set('allow_guests', 0, 'Store');
            Settings::set('player_login', 0, 'Store');
        }

        if (!$this->_db->showTables('store_gateways')) {
            try {
                $this->_db->createTable('store_gateways', ' `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(64) NOT NULL, `displayname` varchar(64) NOT NULL, `enabled` tinyint(1) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)');
            } catch (Exception $e) {
                // Error
            }

            $this->_db->insert('store_gateways', [
                'name' => 'PayPal',
                'displayname' => 'PayPal'
            ]);

            $this->_db->insert('store_gateways', [
                'name' => 'PayPalBusiness',
                'displayname' => 'PayPal'
            ]);

            $this->_db->insert('store_gateways', [
                'name' => 'Store Credits',
                'displayname' => 'Store Credits',
                'enabled' => 1
            ]);
        }

        if (!$this->_db->showTables('store_fields')) {
            try {
                $this->_db->createTable("store_fields", " `id` int(11) NOT NULL AUTO_INCREMENT, `identifier` varchar(32) NOT NULL, `description` varchar(255) NOT NULL, `type` int(11) NOT NULL, `required` tinyint(1) NOT NULL DEFAULT '0', `min` int(11) NOT NULL DEFAULT '0', `max` int(11) NOT NULL DEFAULT '0', `options` text NULL, `regex` varchar(64) DEFAULT NULL, `default_value` varchar(64) NOT NULL DEFAULT '', `deleted` int(11) NOT NULL DEFAULT '0', `order` int(11) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)");

                $this->_db->insert('store_fields', [
                    'identifier' => 'quantity',
                    'description' => 'Quantity',
                    'type' => '4',
                    'required' => '1',
                    'min' => '1',
                    'max' => '2',
                    'default_value' => '1',
                    'order' => '0'
                ]);

                $this->_db->insert('store_fields', [
                    'identifier' => 'price',
                    'description' => 'Pay what you want',
                    'type' => '4',
                    'required' => '1',
                    'min' => '1',
                    'max' => '9',
                    'default_value' => '',
                    'order' => '1'
                ]);
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_sales')) {
            try {
                $this->_db->createTable("store_sales", " `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(64) NOT NULL, `effective_on` varchar(256) NOT NULL, `discount_type` int(11) NOT NULL, `discount_amount` int(11) NOT NULL, `start_date` int(11) NOT NULL, `expire_date` int(11) NOT NULL, PRIMARY KEY (`id`)");
            } catch (Exception $e) {
                // Error
            }
        }

        if (!$this->_db->showTables('store_coupons')) {
            try {
                $this->_db->createTable("store_coupons", " `id` int(11) NOT NULL AUTO_INCREMENT, `code` varchar(64) NOT NULL, `effective_on` varchar(256) NOT NULL, `discount_type` int(11) NOT NULL, `discount_amount` int(11) NOT NULL, `start_date` int(11) NOT NULL, `expire_date` int(11) NOT NULL, `redeem_limit` int(11) NOT NULL DEFAULT '0', `customer_limit` int(11) NOT NULL DEFAULT '0', `min_basket` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)");
            } catch (Exception $e) {
                // Error
            }
        }

        try {
            // Update main admin group permissions
            $group = $this->_db->get('groups', ['id', '=', 2])->results();
            $group = $group[0];

            $group_permissions = json_decode($group->permissions, TRUE);
            $group_permissions['staffcp.store'] = 1;
            $group_permissions['staffcp.store.settings'] = 1;
            $group_permissions['staffcp.store.products'] = 1;
            $group_permissions['staffcp.store.payments'] = 1;
            $group_permissions['staffcp.store.gateways'] = 1;
            $group_permissions['staffcp.store.connections'] = 1;
            $group_permissions['staffcp.store.fields'] = 1;

            $group_permissions = json_encode($group_permissions);
            $this->_db->update('groups', 2, ['permissions' => $group_permissions]);
        } catch (Exception $e) {
            // Error
        }
    }
}