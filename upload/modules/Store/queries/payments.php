<?php
// Returns payments for the StaffCP payments table.
header('Content-type: application/json;charset=utf-8');

if (!$user->isLoggedIn() || !$user->hasPermission('staffcp.store.payments')) {
    die(json_encode('Unauthenticated'));
}

$db = DB::getInstance();
$sortable_columns = [
    'id' => 'payments.id',
    'username' => 'COALESCE(users.username, customers.username, order_users.username)',
    'amount' => 'payments.amount_cents',
    'status' => 'payments.status_id',
    'type' => 'payments.subscription_id',
    'date' => 'payments.created',
];
$from = ' FROM nl2_store_payments payments'
    . ' LEFT JOIN nl2_store_orders orders ON payments.order_id = orders.id'
    . ' LEFT JOIN nl2_store_customers customers ON orders.to_customer_id = customers.id'
    . ' LEFT JOIN nl2_users users ON customers.user_id = users.id'
    . ' LEFT JOIN nl2_users order_users ON orders.user_id = order_users.id';

$total = $db->query('SELECT COUNT(*) AS total FROM nl2_store_payments')->first()->total;
$where_parts = [];
$params = [];

$search_request = $_GET['search'] ?? [];
$search = is_array($search_request) && is_string($search_request['value'] ?? null)
    ? trim($search_request['value'])
    : '';
if ($search !== '') {
    $search_value = '%' . $search . '%';
    $where_parts[] = '(users.username LIKE ? OR customers.username LIKE ? OR order_users.username LIKE ? OR payments.transaction LIKE ? OR payments.payment_id LIKE ? OR CAST(payments.id AS CHAR) LIKE ? OR CAST(payments.order_id AS CHAR) LIKE ?)';
    array_push($params, $search_value, $search_value, $search_value, $search_value, $search_value, $search_value, $search_value);
}

$status_request = $_GET['status'] ?? '';
$status = is_scalar($status_request) ? (string) $status_request : '';
if (in_array($status, ['0', '1', '2', '3', '4'], true)) {
    $where_parts[] = 'payments.status_id = ?';
    $params[] = (int) $status;
}

$payment_type_request = $_GET['payment_type'] ?? '';
$payment_type = is_string($payment_type_request) ? $payment_type_request : '';
if ($payment_type === 'one_time') {
    $where_parts[] = 'payments.subscription_id IS NULL';
} elseif ($payment_type === 'subscription') {
    $where_parts[] = 'payments.subscription_id IS NOT NULL';
}

$where = count($where_parts) ? ' WHERE ' . implode(' AND ', $where_parts) : '';
$total_filtered = count($where_parts)
    ? $db->query('SELECT COUNT(*) AS total' . $from . $where, $params)->first()->total
    : $total;

$order_by = [];
$requested_orders = $_GET['order'] ?? [];
$requested_columns = is_array($_GET['columns'] ?? null) ? $_GET['columns'] : [];
foreach (is_array($requested_orders) ? $requested_orders : [] as $requested_order) {
    if (!is_array($requested_order)) {
        continue;
    }

    $column_index = (int) ($requested_order['column'] ?? -1);
    $column_name = is_array($requested_columns[$column_index] ?? null)
        ? ($requested_columns[$column_index]['data'] ?? '')
        : '';
    if (isset($sortable_columns[$column_name])) {
        $direction = ($requested_order['dir'] ?? '') === 'asc' ? 'ASC' : 'DESC';
        $order_by[] = $sortable_columns[$column_name] . ' ' . $direction;
    }
}
$order = count($order_by) ? ' ORDER BY ' . implode(', ', $order_by) : ' ORDER BY payments.created DESC';

$start = max(0, (int) (is_scalar($_GET['start'] ?? null) ? $_GET['start'] : 0));
$length = (int) (is_scalar($_GET['length'] ?? null) ? $_GET['length'] : 25);
if (!in_array($length, [10, 25, 50, 100], true)) {
    $length = 25;
}
$limit = ' LIMIT ' . $start . ', ' . $length;

$data = [];
$results = $db->query(
    'SELECT payments.*, orders.user_id, orders.to_customer_id' . $from . $where . $order . $limit,
    $params
);

foreach ($results->results() as $result) {
    $payment = new Payment(null, null, $result);

    if ($result->to_customer_id) {
        $recipient = new Customer(null, $result->to_customer_id, 'id');
    } else {
        $recipient = new Customer(null, $result->user_id, 'user_id');
    }

    if ($recipient->exists() && $recipient->getUser()->exists()) {
        $recipient_user = $recipient->getUser();
        $username = $recipient->getUsername();
        $avatar = $recipient_user->getAvatar();
        $style = $recipient_user->getGroupStyle();
        $link = URL::build('/panel/users/store/', 'user=' . $recipient_user->data()->id);
    } else {
        $username = $recipient->getUsername();
        $avatar = AvatarSource::getAvatarFromUUID(Output::getClean($recipient->getIdentifier()));
        $style = '';
        $link = URL::build('/panel/store/payments/', 'customer=' . $username);
    }

    $obj = new stdClass();
    $obj->id = (int) $result->id;
    $obj->username = Output::getClean($username);
    $obj->user_style = $style;
    $obj->user_profile = $link;
    $obj->user_avatar = $avatar;
    $obj->amount = Output::getPurified(
        Store::formatPrice(
            $result->amount_cents,
            $result->currency,
            Store::getCurrencySymbol(),
            STORE_CURRENCY_FORMAT,
        )
    );
    $obj->status = str_replace('"', '\'', $payment->getStatusHtml());
    $obj->type = $result->subscription_id === null
        ? Store::getLanguage()->get('admin', 'one_time_payment')
        : Store::getLanguage()->get('admin', 'subscription_payment');
    $obj->is_subscription = $result->subscription_id !== null;
    $obj->date = date(DATE_FORMAT, $result->created);

    $data[] = $obj;
}

echo json_encode(
    [
        'draw' => (int) (is_scalar($_GET['draw'] ?? null) ? $_GET['draw'] : 0),
        'recordsTotal' => (int) $total,
        'recordsFiltered' => (int) $total_filtered,
        'data' => $data,
    ],
    JSON_PRETTY_PRINT
);
