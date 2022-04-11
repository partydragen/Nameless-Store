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

class StoreConfig {

    /**
     * Get a config value from `modules/Store/config.php` file.
     * 
     * @param string $path `/` seperated path of key to get from config file.
     */
    public static function get($path = null) {
        if ($path) {
            if (!isset($GLOBALS['store_config'])) {
                throw new Exception('Store config unavailable');
            }

            $config = $GLOBALS['store_config'];

            $path = explode('/', $path);

            foreach ($path as $bit) {
                if (isset($config[$bit])) {
                    $config = $config[$bit];
                } else {
                    $not_matched = true;
                }
            }

            if (!isset($not_matched)) return $config;
        }

        return false;
    }

    /**
     * Write a value to `modules/Store/config.php` file.
     * 
     * @param string $key `/` seperated path of key to set.
     * @param mixed $value Value to set under $key.
     */
    public static function set($settings) {
        if (!file_exists(ROOT_PATH . '/modules/Store/config.php')) {
            fopen(ROOT_PATH . '/modules/Store/config.php', 'w');
        }

        require(ROOT_PATH . '/modules/Store/config.php');

        $loadedConfig = json_decode(file_get_contents(ROOT_PATH . '/modules/Store/config.php'), true);

        if (!isset($store_conf) || !is_array($store_conf)) {
            $store_conf = [];
        }

        foreach ($settings as $key => $value) {
            $path = explode('/', $key);

            if (!is_array($path)) {
                $store_conf[$key] = $value;
            } else {
                $loc = &$store_conf;
                foreach ($path as $step) {
                    $loc = &$loc[$step];
                }
                $loc = $value;
            }
        }

        return static::write($store_conf);
    }

    /**
     * Overwrite new `modules/Store/config.php` file.
     * 
     * @param array $config New config array to store.
     */
    public static function write($config) {
        $file = fopen(ROOT_PATH . '/modules/Store/config.php', 'wa+');
        fwrite($file, '<?php' . PHP_EOL . '$store_conf = ' . var_export($config, true) . ';');
        return fclose($file);
    }
}
