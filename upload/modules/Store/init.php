<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *  NamelessMC version 2.0.0-pr13
 *
 *  License: MIT
 *
 *  Store initialisation file
 */

// Language
$store_language = new Language(ROOT_PATH . '/modules/Store/language', LANGUAGE);

require_once(ROOT_PATH . '/modules/Store/autoload.php');

require_once(ROOT_PATH . '/modules/Store/module.php');
$module = new Store_Module($language, $store_language, $pages, $cache, $endpoints);