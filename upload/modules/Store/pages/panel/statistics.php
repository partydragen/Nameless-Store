<?php
/*
 * Store module - StaffCP statistics
 */

if (!$user->handlePanelPageLoad('staffcp.store.statistics')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_statistics');
$page_title = $store_language->get('admin', 'statistics');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

$store = new Store();
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);
$template->assets()->include([AssetTree::CHART_JS]);

$ranges = [7, 30, 90, 365];
$days = isset($_GET['days']) && in_array((int) $_GET['days'], $ranges, true)
    ? (int) $_GET['days']
    : 30;
$start = strtotime('-' . ($days - 1) . ' days midnight');
$now = time();
$db = DB::getInstance();

$payment_summary = $db->query(
    'SELECT COALESCE(SUM(amount_cents), 0) revenue, COUNT(*) payments '
    . 'FROM nl2_store_payments WHERE status_id = 1 AND created BETWEEN ? AND ?',
    [$start, $now]
)->first();
$credit_summary = $db->query(
    'SELECT COALESCE(SUM(amount_cents), 0) revenue FROM nl2_store_order_credits '
    . 'WHERE status_id = ? AND updated BETWEEN ? AND ?',
    [OrderCredit::COMPLETED, $start, $now]
)->first();
$customer_summary = $db->query(
    'SELECT COUNT(DISTINCT orders.from_customer_id) customers '
    . 'FROM nl2_store_payments payments INNER JOIN nl2_store_orders orders ON orders.id = payments.order_id '
    . 'WHERE payments.status_id = 1 AND payments.created BETWEEN ? AND ?',
    [$start, $now]
)->first();
$refund_summary = $db->query(
    'SELECT COALESCE(SUM(amount_cents), 0) refunded FROM nl2_store_payment_refunds '
    . 'WHERE status_id = ? AND updated BETWEEN ? AND ?',
    [Payment::REFUND_COMPLETED, $start, $now]
)->first();
$credit_refund_summary = $db->query(
    'SELECT COALESCE(SUM(amount_cents), 0) refunded FROM nl2_store_order_credits '
    . 'WHERE status_id = ? AND updated BETWEEN ? AND ?',
    [OrderCredit::REFUNDED, $start, $now]
)->first();
$active_subscriptions = $db->query(
    'SELECT COUNT(*) total FROM nl2_store_subscriptions WHERE status_id = ?',
    [Subscription::ACTIVE]
)->first();

$revenue_cents = (int) ($payment_summary->revenue ?? 0) + (int) ($credit_summary->revenue ?? 0);
$payments_count = (int) ($payment_summary->payments ?? 0);
$average_payment_cents = $payments_count > 0 ? (int) round($revenue_cents / $payments_count) : 0;

// Build a complete day series so quiet days remain visible on the chart.
$daily = [];
for ($date = $start; $date <= $now; $date = strtotime('+1 day', $date)) {
    $key = date('Y-m-d', $date);
    $daily[$key] = ['revenue' => 0, 'payments' => 0];
}

$daily_payments = $db->query(
    "SELECT DATE_FORMAT(FROM_UNIXTIME(created), '%Y-%m-%d') day, "
    . 'COALESCE(SUM(amount_cents), 0) revenue, COUNT(*) payments '
    . 'FROM nl2_store_payments WHERE status_id = 1 AND created BETWEEN ? AND ? '
    . "GROUP BY DATE_FORMAT(FROM_UNIXTIME(created), '%Y-%m-%d') ORDER BY day",
    [$start, $now]
)->results();
foreach ($daily_payments as $row) {
    if (isset($daily[$row->day])) {
        $daily[$row->day]['revenue'] += (int) $row->revenue;
        $daily[$row->day]['payments'] += (int) $row->payments;
    }
}

$daily_credits = $db->query(
    "SELECT DATE_FORMAT(FROM_UNIXTIME(updated), '%Y-%m-%d') day, COALESCE(SUM(amount_cents), 0) revenue "
    . 'FROM nl2_store_order_credits WHERE status_id = ? AND updated BETWEEN ? AND ? '
    . "GROUP BY DATE_FORMAT(FROM_UNIXTIME(updated), '%Y-%m-%d') ORDER BY day",
    [OrderCredit::COMPLETED, $start, $now]
)->results();
foreach ($daily_credits as $row) {
    if (isset($daily[$row->day])) {
        $daily[$row->day]['revenue'] += (int) $row->revenue;
    }
}

$chart_labels = [];
$chart_revenue = [];
$chart_payments = [];
foreach ($daily as $date => $values) {
    $chart_labels[] = date('M j', strtotime($date));
    $chart_revenue[] = Store::fromCents($values['revenue']);
    $chart_payments[] = $values['payments'];
}

$gateway_rows = $db->query(
    'SELECT COALESCE(gateways.displayname, ?) gateway, COALESCE(SUM(payments.amount_cents), 0) revenue '
    . 'FROM nl2_store_payments payments LEFT JOIN nl2_store_gateways gateways ON gateways.id = payments.gateway_id '
    . 'WHERE payments.status_id = 1 AND payments.created BETWEEN ? AND ? '
    . 'GROUP BY payments.gateway_id, gateways.displayname ORDER BY revenue DESC',
    [$store_language->get('admin', 'system'), $start, $now]
)->results();

$gateway_labels = [];
$gateway_values = [];
$credits_gateway_index = null;
foreach ($gateway_rows as $row) {
    $gateway_labels[] = Output::getClean($row->gateway);
    $gateway_values[] = Store::fromCents((int) $row->revenue);
    if ($row->gateway === 'Store Credits') {
        $credits_gateway_index = count($gateway_values) - 1;
    }
}
if ((int) ($credit_summary->revenue ?? 0) > 0) {
    if ($credits_gateway_index === null) {
        $gateway_labels[] = $store_language->get('general', 'credits');
        $gateway_values[] = Store::fromCents((int) $credit_summary->revenue);
    } else {
        $gateway_values[$credits_gateway_index] += Store::fromCents((int) $credit_summary->revenue);
    }
}

$top_products = $db->query(
    'SELECT products.name, SUM(order_products.quantity) quantity, '
    . 'SUM(order_products.amount_cents * order_products.quantity) revenue '
    . 'FROM nl2_store_orders_products order_products '
    . 'INNER JOIN nl2_store_products products ON products.id = order_products.product_id '
    . 'INNER JOIN (SELECT order_id, MIN(created) created FROM nl2_store_payments '
    . 'WHERE status_id = 1 AND created BETWEEN ? AND ? GROUP BY order_id) paid_orders '
    . 'ON paid_orders.order_id = order_products.order_id '
    . 'GROUP BY order_products.product_id, products.name ORDER BY revenue DESC LIMIT 8',
    [$start, $now]
)->results();

$top_product_labels = [];
$top_product_values = [];
$top_product_list = [];
foreach ($top_products as $row) {
    $top_product_labels[] = Output::getClean($row->name);
    $top_product_values[] = (int) $row->quantity;
    $top_product_list[] = [
        'name' => Output::getClean($row->name),
        'quantity' => (int) $row->quantity,
        'revenue' => Output::getPurified(Store::formatPrice(
            (int) $row->revenue,
            Store::getCurrency(),
            Store::getCurrencySymbol(),
            STORE_CURRENCY_FORMAT
        ))
    ];
}

$format_cents = static fn (int $cents): string => Output::getPurified(Store::formatPrice(
    $cents,
    Store::getCurrency(),
    Store::getCurrencySymbol(),
    STORE_CURRENCY_FORMAT
));

$range_options = [];
foreach ($ranges as $range) {
    $range_options[] = [
        'value' => $range,
        'label' => $store_language->get('admin', 'last_x_days', ['days' => $range]),
        'selected' => $days === $range
    ];
}

$template->getEngine()->addVariables([
    'PARENT_PAGE' => PARENT_PAGE,
    'PAGE' => PANEL_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('general', 'store'),
    'STATISTICS' => $store_language->get('admin', 'statistics'),
    'DATE_RANGE' => $store_language->get('admin', 'date_range'),
    'RANGE_OPTIONS' => $range_options,
    'REVENUE' => $store_language->get('admin', 'revenue'),
    'COMPLETED_PAYMENTS' => $store_language->get('admin', 'completed_payments'),
    'AVERAGE_PAYMENT' => $store_language->get('admin', 'average_payment'),
    'UNIQUE_CUSTOMERS' => $store_language->get('admin', 'unique_customers'),
    'ACTIVE_SUBSCRIPTIONS' => $store_language->get('admin', 'active_subscriptions'),
    'REFUNDED_AMOUNT' => $store_language->get('admin', 'refunded_amount'),
    'REVENUE_VALUE' => $format_cents($revenue_cents),
    'PAYMENTS_VALUE' => $payments_count,
    'AVERAGE_PAYMENT_VALUE' => $format_cents($average_payment_cents),
    'CUSTOMERS_VALUE' => (int) ($customer_summary->customers ?? 0),
    'ACTIVE_SUBSCRIPTIONS_VALUE' => (int) ($active_subscriptions->total ?? 0),
    'REFUNDED_VALUE' => $format_cents(
        (int) ($refund_summary->refunded ?? 0) + (int) ($credit_refund_summary->refunded ?? 0)
    ),
    'REVENUE_OVER_TIME' => $store_language->get('admin', 'revenue_over_time'),
    'PAYMENTS' => $store_language->get('admin', 'payments'),
    'GATEWAY_BREAKDOWN' => $store_language->get('admin', 'gateway_breakdown'),
    'TOP_PRODUCTS' => $store_language->get('admin', 'top_products'),
    'UNITS_SOLD' => $store_language->get('admin', 'units_sold'),
    'NO_DATA' => $store_language->get('admin', 'no_statistics_data'),
    'CHART_LABELS' => $chart_labels,
    'CHART_REVENUE' => $chart_revenue,
    'CHART_PAYMENTS' => $chart_payments,
    'GATEWAY_LABELS' => $gateway_labels,
    'GATEWAY_VALUES' => $gateway_values,
    'TOP_PRODUCT_LABELS' => $top_product_labels,
    'TOP_PRODUCT_VALUES' => $top_product_values,
    'TOP_PRODUCT_LIST' => $top_product_list
]);

$template->onPageLoad();
require(ROOT_PATH . '/core/templates/panel_navbar.php');
$template->displayTemplate('store/statistics');
