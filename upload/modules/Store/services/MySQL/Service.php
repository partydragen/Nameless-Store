<?php
class MySQLService extends ServiceBase {
    public function __construct() {
        $id = 3;
        $name = 'MySQL';
        $description = 'Connect to a MySQL database to execute MySQL commands actions';
        $connection_settings = ROOT_PATH . '/modules/Store/services/MySQL/settings/connection_settings.php';
        $action_settings = ROOT_PATH . '/modules/Store/services/MySQL/settings/action_settings.php';

        parent::__construct($id, $name, $description, $connection_settings, $action_settings);
    }

    public function onConnectionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function onActionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function executeAction(Action $action, Order $order, Product $product, Payment $payment, array $placeholders) {
        // Execute this action on all selected connections
        $connections = ($action->data()->own_connections ? $action->getConnections() : $product->getConnections($this->getId()));
        foreach ($connections as $connection) {
            // Replace existing placeholder
            $placeholders['{connection}'] = $connection->name;
            
            $data = json_decode($connection->data);
            if ($data != null && isset($data->password) && !empty($data->password)) {
                $command = $action->data()->command;
                $command = str_replace(array_keys($placeholders), array_values($placeholders), $command);

                $db = DB::getCustomInstance($data->address, $data->database, $data->username, $data->password, $data->port, null, '');
                $db->query($command);

                // Action executed
                DB::getInstance()->insert('store_pending_actions', [
                    'order_id' => $payment->data()->order_id,
                    'action_id' => $action->data()->id,
                    'product_id' => $product->data()->id,
                    'customer_id' => $order->data()->to_customer_id,
                    'connection_id' => $connection->id,
                    'type' => $action->data()->type,
                    'command' => $command,
                    'require_online' => $action->data()->require_online,
                    'order' => $action->data()->order,
                    'status' => 1
                ]);
            }
        }
    }
}

$service = new MySQLService();