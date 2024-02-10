<?php
// Returns set of users for the StaffCP Users tab
header('Content-type: application/json;charset=utf-8');

if (!$user->isLoggedIn() || !$user->hasPermission('staffcp.store.payments')) {
    die(json_encode('Unauthenticated'));
}
$sortColumns = ['id' => 'id', 'amount_cents' => 'amount', 'created' => 'date'];

$total = DB::getInstance()->query('SELECT COUNT(*) as `total` FROM nl2_store_payments', [])->first()->total;

$query = 'SELECT nl2_store_payments.*, order_id, nl2_store_orders.user_id, to_customer_id FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id';
$limit = '';
$order = '';
$params = [];
$where = '';

if (isset($_GET['search']) && $_GET['search']['value'] != '') {
    $customers_list = [0];
    $customers = DB::getInstance()->query('SELECT nl2_store_customers.id FROM `nl2_store_customers` LEFT JOIN nl2_users ON nl2_users.id=user_id WHERE nl2_users.username LIKE ? OR nl2_store_customers.username LIKE ?', ['%' . $_GET['search']['value'] . '%', '%' . $_GET['search']['value'] . '%']);
    foreach ($customers->results() as $customer) {
        $customers_list[] = $customer->id;
    }

    $customers = implode(',', $customers_list);
    $where .= ' WHERE to_customer_id IN ('.$customers.')';
}

if (isset($_GET['order']) && count($_GET['order'])) {
    $orderBy = [];

    for ($i = 0, $j = count($_GET['order']); $i < $j; $i++) {
        $column = (int)$_GET['order'][$i]['column'];
        $requestColumn = $_GET['columns'][$column];

        $column = array_search($requestColumn['data'], $sortColumns);
        if ($column) {
            $dir = $_GET['order'][$i]['dir'] === 'asc' ?
                'DESC' :
                'ASC';

            $orderBy[] = '`' . $column . '` ' . $dir;
        }
    }

    if (count($orderBy)) {
        $order .= ' ORDER BY ' . implode(', ', $orderBy);
    } else {
        $order .= ' ORDER BY created DESC';
    }
} else {
    $order .= ' ORDER BY created DESC';
}

if (isset($_GET['start']) && $_GET['length'] != -1) {
    $limit .= ' LIMIT ' . (int)$_GET['start'] . ', ' . (int)$_GET['length'];
} else {
    // default 10
    $limit .= ' LIMIT 10';
}

if (strlen($where) > 0) {
    $totalFiltered = DB::getInstance()->query('SELECT COUNT(*) as `total` FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id' . $where, $params)->first()->total;
}

$data = [];
$results = DB::getInstance()->query($query . $where . $order . $limit, $params);
if ($results->count()) {
    foreach ($results->results() as $result) {
        $payment = new Payment(null, null, $result);

        // Recipient
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
        $obj->id = $result->id;
        $obj->username = $username;
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
        $obj->date = date(DATE_FORMAT, $result->created);
        $obj->date_unix = $result->created;

        $data[] = $obj;
    }
}

echo json_encode(
    [
        'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 0,
        'recordsTotal' => $total,
        'recordsFiltered' => $totalFiltered ?? $total,
        'data' => $data
    ],
    JSON_PRETTY_PRINT
);