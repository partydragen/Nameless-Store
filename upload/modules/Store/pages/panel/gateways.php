<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel gateways page
 */

// Can the user view the StaffCP?
if (!$user->handlePanelPageLoad('staffcp.store.gateways')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store_configuration');
define('PANEL_PAGE', 'store_gateways');
$page_title = $store_language->get('admin', 'gateways');

require_once(ROOT_PATH . '/core/templates/backend_init.php');

$store = new Store($cache, $store_language);

if (!isset($_GET['gateway'])) {

    // Make sure config exist
    $config_path = ROOT_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'Store' . DIRECTORY_SEPARATOR . 'config.php';
    if (!file_exists($config_path)) {
        if (is_writable(ROOT_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'Store')) {
            StoreConfig::write([
                'installed' => true
            ]);
        } else {
            $errors = [$store_language->get('admin', 'unavailable_generate_config')];

            if (function_exists('posix_geteuid')) {
                $uid = posix_geteuid();
                $gid = posix_getegid();
                $chown_command = 'sudo chown -R ' . $uid . ':' . $gid . ' ' . Output::getClean(ROOT_PATH . '/modules/Store');
                $errors[] = 'The ssh command to fix this for your system was determined to be: <code style="color: blue;">' . $chown_command . '</code> Please check if it makes sense before running it.';
            } else {
                $errors[] = '<strong>Example</strong> ssh command to change owner recursively: <code style="color: blue;">sudo chown -R www-data: ' . Output::getClean(ROOT_PATH . '/modules/Store') . '</code>';
            }
        }
    }

    if (!isset($errors)) {
        $gateways_list = [];
        foreach (Gateways::getInstance()->getAll() as $gateway) {
            $gateways_list[] = [
                'name' => Output::getClean($gateway->getName()),
                'version' => Output::getClean($gateway->getVersion()),
                'store_version' => Output::getClean($gateway->getStoreVersion()),
                'author' => Output::getPurified($gateway->getAuthor()),
                'author_x' => $language->get('admin', 'author_x', ['author' => Output::getPurified($gateway->getAuthor())]),
                'enabled' => $gateway->isEnabled(),
                'edit_link' => URL::build('/panel/store/gateways/', 'gateway=' . Output::getClean($gateway->getName())),
                'supports_subscriptions' => $gateway instanceof SupportSubscriptions
            ];
        }

        // Get gateways from Nameless website
        $cache->setCache('all_gateways');
        if ($cache->isCached('all_gateways')) {
            $all_gateways = $cache->retrieve('all_gateways');
        } else {
            $all_gateways = [];
            $all_gateways_query = HttpClient::get('https://namelesscms.com/index.php?route=/api/v2/resources&category=12');

            if ($all_gateways_query->hasError()) {
                $all_gateways_error = $all_gateways_query->getError();
            } else {
                $all_gateways_query = json_decode($all_gateways_query->contents());
                $timeago = new TimeAgo(TIMEZONE);

                foreach ($all_gateways_query->resources as $item) {
                    $all_gateways[] = [
                        'name' => Output::getClean($item->name),
                        'description' => Output::getPurified($item->description),
                        'description_short' => Text::truncate(Output::getPurified($item->description)),
                        'author' => Output::getClean($item->author->username),
                        'author_x' => $language->get('admin', 'author_x', ['author' => Output::getClean($item->author->username)]),
                        'updated_x' => $language->get('admin', 'updated_x', ['updatedAt' => date(DATE_FORMAT, $item->updated)]),
                        'url' => Output::getClean($item->url),
                        'latest_version' => Output::getClean($item->latest_version),
                        'rating' => Output::getClean($item->rating),
                        'downloads' => Output::getClean($item->downloads),
                        'views' => Output::getClean($item->views),
                        'rating_full' => $language->get('admin', 'rating_x', ['rating' => Output::getClean($item->rating * 2) . '/100']),
                        'downloads_full' => $language->get('admin', 'downloads_x', ['downloads' => Output::getClean($item->downloads)]),
                        'views_full' =>  $language->get('admin', 'views_x', ['views' => Output::getClean($item->views)])
                    ];
                }

                $cache->store('all_gateways', $all_gateways, 3600);
            }
        }

        if (count($all_gateways)) {
            if (count($all_gateways) > 3) {
                $rand_keys = array_rand($all_gateways, 3);
                $all_gateways = [$all_gateways[$rand_keys[0]], $all_gateways[$rand_keys[1]], $all_gateways[$rand_keys[2]]];
            }
        }

        $smarty->assign([
            'GATEWAYS_LIST' => $gateways_list,
            'FIND_GATEWAYS' => $store_language->get('admin', 'find_gateways'),
            'VIEW' => $language->get('general', 'view'),
            'GATEWAY' => $store_language->get('admin', 'gateway'),
            'STATS' => $language->get('admin', 'stats'),
            'ACTIONS' => $language->get('general', 'actions'),
            'WEBSITE_GATEWAYS' => $all_gateways,
            'VIEW_ALL_GATEWAYS' => $store_language->get('admin', 'view_all_gateways'),
            'VIEW_ALL_GATEWAYS_LINK' => 'https://namelesscms.com/resources/category/12-store-gateways/',
            'UNABLE_TO_RETRIEVE_GATEWAYS' => $all_gateways_error ?? $store_language->get('admin', 'unable_to_retrieve_gateways'),
        ]);
    }

    $smarty->assign([
        'PAYMENT_METHOD' => $store_language->get('admin', 'payment_method'),
        'EDIT' => $language->get('general', 'edit'),
        'ENABLED' => $language->get('admin', 'enabled'),
        'DISABLED' => $language->get('admin', 'disabled'),
        'SUPPORTS_SUBSCRIPTIONS' => $store_language->get('admin', 'supports_subscriptions')
    ]);

    $template_file = 'store/gateways.tpl';
} else {
    $gateway = Gateways::getInstance()->get($_GET['gateway']);

    $securityPolicy->secure_dir = [ROOT_PATH . '/modules/Store', ROOT_PATH . '/custom/panel_templates'];

    if (file_exists(ROOT_PATH . '/modules/Store/config.php')) {
        // File exist, Make sure its writeable
        if (!is_writable(ROOT_PATH . '/modules/Store/config.php')) {
            $errors = [$store_language->get('admin', 'config_not_writable')];

            if (function_exists('posix_geteuid')) {
                $uid = posix_geteuid();
                $gid = posix_getegid();
                $chown_command = 'sudo chown -R ' . $uid . ':' . $gid . ' ' . Output::getClean(ROOT_PATH . '/modules/Store');
                $errors[] = 'The ssh command to fix this for your system was determined to be: <code style="color: blue;">' . $chown_command . '</code> Please check if it makes sense before running it.';
            } else {
                $errors[] = '<strong>Example</strong> ssh command to change owner recursively: <code style="color: blue;">sudo chown -R www-data: ' . Output::getClean(ROOT_PATH . '/modules/Store') . '</code>';
            }
        }
    } else if (!is_writable(ROOT_PATH . '/modules/Store')) {
        // File don't exist
        Redirect::to(URL::build('/panel/store/gateways'));
    }

    require_once($gateway->getSettings());

    $smarty->assign([
        'EDITING_GATEWAY' => $store_language->get('admin', 'editing_gateway_x', ['gateway' => Output::getClean($gateway->getName())]),
        'BACK' => $language->get('general', 'back'),
        'BACK_LINK' => URL::build('/panel/store/gateways')
    ]);

    $template_file = 'store/gateway_settings.tpl';
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Session::exists('gateways_success'))
    $success = Session::flash('gateways_success');

if (isset($success))
    $smarty->assign([
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ]);

if (isset($errors) && count($errors))
    $smarty->assign([
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ]);

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('general', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'GATEWAYS' => $store_language->get('admin', 'gateways')
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);