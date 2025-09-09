<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *  NamelessMC version 2.2.0
 *
 *  License: MIT
 *
 *  PayPalBusiness Gateway autoload file
 */

// Load classes
spl_autoload_register(function ($class) {
    $path = join(DIRECTORY_SEPARATOR, [ROOT_PATH, 'modules', 'Store', 'gateways', 'PayPalBusiness', 'classes', $class . '.php']);
    if (file_exists($path)) {
        require_once($path);
    }
});
