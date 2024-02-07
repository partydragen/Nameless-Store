<?php
// Returns set of users for the StaffCP Users tab
header('Content-type: application/json;charset=utf-8');

if (!$user->isLoggedIn() || !$user->hasPermission('staffcp.store.payments')) {
    die(json_encode('Unauthenticated'));
}

$total = DB::getInstance()->query('SELECT COUNT(*) as `total` FROM nl2_store_payments', [])->first()->total;

$query = 'SELECT nl2_store_payments.*, identifier, username, order_id, nl2_store_orders.user_id, to_customer_id FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id LEFT JOIN nl2_store_customers ON to_customer_id=nl2_store_customers.id';
$limit = '';
$order = ' ORDER BY created DESC';
$params = [];
$where = '';

if (isset($_GET['start']) && $_GET['length'] != -1) {
    $limit .= ' LIMIT ' . (int)$_GET['start'] . ', ' . (int)$_GET['length'];
} else {
    // default 10
    $limit .= ' LIMIT 10';
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