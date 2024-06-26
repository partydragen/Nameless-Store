<?php
require_once ROOT_PATH . '/modules/Store/services/RCON/SourceQuery/bootstrap.php';

use xPaw\SourceQuery\SourceQuery;

class RCONService extends ServiceBase implements ConnectionsBase {
    public function __construct() {
        $id = 4;
        $name = 'RCON';
        $description = 'Connect your RCON to execute commands on your game server';
        $connection_settings = ROOT_PATH . '/modules/Store/services/RCON/settings/connection_settings.php';
        $action_settings = ROOT_PATH . '/modules/Store/services/RCON/settings/action_settings.php';

        parent::__construct($id, $name, $description, $connection_settings, $action_settings);
    }

    public function onConnectionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function onActionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function executeAction(Action $action, Order $order, Item $item, Payment $payment, array $placeholders) {
        // Execute this action on all selected connections
        $product = $item->getProduct();
        $connections = ($action->data()->own_connections ? $action->getConnections() : $product->getConnections($this->getId()));
        foreach ($connections as $connection) {
            // Replace existing placeholder
            $placeholders['{connection}'] = $connection->name;

            $data = json_decode($connection->data);
            if ($data != null && isset($data->password) && !empty($data->password)) {
                $command = $action->data()->command;
                $command = str_replace(array_keys($placeholders), array_values($placeholders), $command);

                $success = false;
                try {
                    $rcon = new SourceQuery( );
                    $rcon->Connect($data->address, $data->port, $data->timeout, SourceQuery::SOURCE);
                    $rcon->SetRconPassword($data->password);

                    $rcon->Rcon($command);

                    $success = true;
                } catch( Exception $e ) {

                } finally {
                    $rcon->Disconnect();
                }

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
                    'status' => (int)$success
                ]);
            }
        }
    }
}

$service = new RCONService();