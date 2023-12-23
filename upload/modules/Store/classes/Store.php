<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module
 */

class Store {
    private $_db,
            $_cache;

    /**
     * @var array The list of the active sales.
     */
    private static array $_active_sales;

    /**
     * @var Language Instance of Language class for translations
     */
    private static Language $_store_language;

    // Constructor, connect to database
    public function __construct($cache, $store_language) {
        $this->_db = DB::getInstance();

        $this->_cache = $cache;
    }

    public function getStoreURL(): string {
        return Settings::get('store_path', '/store', 'Store');
    }

    // Get all products
    public function getProducts() {
        $products_list = [];

        $products = $this->_db->query('SELECT * FROM nl2_store_products WHERE deleted = 0 ORDER BY `order` ASC')->results();
        foreach ($products as $data) {
            $product = new Product(null, null, $data);

            $products_list[] = $product;
        }

        return $products_list;
    }
    
    // Get all payments
    public function getAllPayments() {
        $payments = $this->_db->query('SELECT nl2_store_payments.*, identifier, username, order_id, nl2_store_orders.user_id, to_customer_id FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id LEFT JOIN nl2_store_customers ON to_customer_id=nl2_store_customers.id ORDER BY created DESC')->results();

        return $payments;
    }
    
    // Get all categories
    public function getAllCategories() {
        $categories = $this->_db->query('SELECT * FROM nl2_store_categories WHERE deleted = 0 ORDER BY `order` ASC')->results();

        $categories_array = [];
        foreach ($categories as $category) {
            $categories_array[] = [
                'id' => Output::getClean($category->id),
                'name' => Output::getClean($category->name)
            ];
        }

        return $categories_array;
    }
    
    // Get all connections
    public function getAllConnections() {
        $connections = $this->_db->query('SELECT * FROM nl2_store_connections')->results();

        $connections_array = [];
        foreach ($connections as $connection) {
            $connections_array[] = [
                'id' => Output::getClean($connection->id),
                'name' => Output::getClean($connection->name)
            ];
        }

        return $connections_array;
    }
    
    // Get navbar menu
    public function getNavbarMenu($active) {
        $store_url = $this->getStoreURL();
        $categories = [];

        $categories_query = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE parent_category IS NULL AND disabled = 0 AND hidden = 0 AND deleted = 0 ORDER BY `order` ASC')->results();
        if (count($categories_query)) {
            foreach ($categories_query as $item) {
                $subcategories_query = DB::getInstance()->query('SELECT id, `name`, `url` FROM nl2_store_categories WHERE parent_category = ? AND disabled = 0 AND hidden = 0 AND deleted = 0 ORDER BY `order` ASC', [$item->id])->results();

                $subcategories = [];
                $sub_active = false;
                if (count($subcategories_query)) {
                    foreach ($subcategories_query as $subcategory) {
                        $sub_active = Output::getClean($active) == Output::getClean($subcategory->name);

                        $subcategories[] = [
                            'url' => URL::build($store_url . '/category/' . (empty($subcategory->url) ? $subcategory->id : $subcategory->url)),
                            'title' => Output::getClean($subcategory->name),
                            'active' => $sub_active
                        ];
                    }
                }

                $categories[$item->id] = [
                    'url' => URL::build($store_url . '/category/' . (empty($item->url) ? $item->id : $item->url)),
                    'title' => Output::getClean($item->name),
                    'subcategories' => $subcategories,
                    'active' => !$sub_active && Output::getClean($active) == Output::getClean($item->name),
                    'only_subcategories' => Output::getClean($item->only_subcategories)
                ];
            }
        }

        return $categories;
    }

    public function isPlayerSystemEnabled(): bool {
        return Settings::get('player_login', '0', 'Store');
    }

    /**
     * @return Language The current language instance for translations
     */
    public static function getLanguage(): Language {
        if (!isset(self::$_store_language)) {
            self::$_store_language = new Language(ROOT_PATH . '/modules/Store/language');
        }

        return self::$_store_language;
    }

    public static function getStorePath(): string {
        return Settings::get('store_path', '/store', 'Store');
    }

    public static function getCurrency(): string {
        return Settings::get('currency', 'USD', 'Store');
    }

    public static function getCurrencySymbol(): string {
        return Settings::get('currency_symbol', '$', 'Store');
    }

    /**
     * Helper function to format price with currency
     *
     * @param $price_cents int Price
     * @param $currencyCode string Currency code (eg GBP, USD, EUR)
     * @param $currencySymbol string Currency symbol
     * @param $format ?string Format
     * @return string Formatted price with currency
     */
    public static function formatPrice(int $price_cents, string $currencyCode, string $currencySymbol, ?string $format = '{currencySymbol}{price} {currencyCode}'): string {
        return str_replace([
            '{currencyCode}',
            '{currencySymbol}',
            '{price}'
        ], [
            $currencyCode,
            $currencySymbol,
            sprintf('%0.2f', $price_cents / 100),
        ], $format);
    }

    /**
     * Get the active sales.
     *
     * @return array The active sales.
     */
    public static function getActiveSales(): array {
        return self::$_active_sales ??= (function (): array {
            return DB::getInstance()->query('SELECT * FROM nl2_store_sales WHERE start_date < ? AND expire_date > ? ORDER BY `expire_date` DESC', [date('U'), date('U')])->results();
        })();
    }

    /*
     *  Check for Module updates
     *  Returns JSON object with information about any updates
     */
    public static function updateCheck() {
        $current_version = Settings::get('nameless_version');
        $uid = Settings::get('unique_id');

        $enabled_modules = Module::getModules();
        foreach ($enabled_modules as $enabled_item) {
            if ($enabled_item->getName() == 'Store') {
                $module = $enabled_item;
                break;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, 'https://api.partydragen.com/stats.php?uid=' . $uid . '&version=' . $current_version . '&module=Store&module_version='.$module->getVersion() . '&domain='. URL::getSelfURL());

        $update_check = curl_exec($ch);
        curl_close($ch);

        $info = json_decode($update_check);
        if (isset($info->message)) {
            die($info->message);
        }

        return $update_check;
    }

    public static function toCents($value): int {
        return (int) (string) ((float) preg_replace("/[^0-9.]/", "", $value) * 100);
    }
    
    public static function fromCents(int $cents): string {
        return sprintf('%0.2f', $cents / 100);
    }
}