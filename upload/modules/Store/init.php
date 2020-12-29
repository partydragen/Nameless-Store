<?php
/*
 *	Made by Samerton
 *  https://github.com/samerton
 *  NamelessMC version 2.0.0-pr5
 *
 *  License: MIT
 *
 *  Store initialisation file
 */

// Language
$store_language = new Language(ROOT_PATH . '/modules/Store/language', LANGUAGE);

require_once(ROOT_PATH . '/modules/Store/module.php');
$module = new Store_Module($language, $store_language, $pages, $cache, $endpoints);