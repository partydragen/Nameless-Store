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

    public function scheduleAction(Action $action, Order $order, Item $item, Payment $payment, array $placeholders) {
        // Execute this action on all selected connections
        $product = $item->getProduct();
        $connections = ($action->data()->own_connections ? $action->getConnections() : $product->getConnections($this->getId()));
        foreach ($connections as $connection) {
            // Replace existing placeholder
            $placeholders['connection'] = $connection->name;

            $data = json_decode($connection->data);
            if ($data != null && isset($data->password) && !empty($data->password)) {
                $command = $action->parseCommand($action->data()->command, $order, $item, $payment, $placeholders);

                $success = ActionTask::PENDING;
                try {
                    $rcon = new SourceQuery( );
                    $rcon->Connect($data->address, $data->port, $data->timeout, SourceQuery::SOURCE);
                    $rcon->SetRconPassword($data->password);

                    $rcon->Rcon($command);

                    $success = ActionTask::COMPLETED;
                } catch( Exception $e ) {

                } finally {
                    $rcon->Disconnect();
                }

                $task = new ActionTask();
                $task->create($command, $action, $order, $item, $payment, [
                    'connection_id' => $connection->id,
                    'status' => $success
                ]);
            }
        }
    }

    public function executeAction(ActionTask $task) {

    }
}

$service = new RCONService();