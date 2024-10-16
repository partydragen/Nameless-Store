<?php
class MinecraftServerService extends ServiceBase implements ConnectionsBase {
    public function __construct() {
        $id = 2;
        $name = 'Minecraft Server';
        $description = 'Connect your Minecraft servers with Service Connections and make actions execute commands on they';
        $connection_settings = ROOT_PATH . '/modules/Store/services/MinecraftServer/settings/connection_settings.php';
        $action_settings = ROOT_PATH . '/modules/Store/services/MinecraftServer/settings/action_settings.php';

        parent::__construct($id, $name, $description, $connection_settings, $action_settings);
    }

    public function onConnectionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function onActionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function scheduleAction(Action $action, Order $order, Item $item, Payment $payment, array $placeholders) {
        // Plugin handle username and uuid replacement
        unset($placeholders['username']);
        unset($placeholders['uuid']);

        // Execute this action on all selected connections
        $product = $item->getProduct();
        $connections = ($action->data()->own_connections ? $action->getConnections() : $product->getConnections($this->getId()));
        foreach ($connections as $connection) {
            // Replace existing placeholder
            $placeholders['connection'] = $connection->name;

            // Replace the command placeholders
            $command = $action->parseCommand($action->data()->command, $order, $item, $payment, $placeholders);

            $task = new ActionTask();
            $task->create($command, $action, $order, $item, $payment, [
                'connection_id' => $connection->id
            ]);
        }
    }

    public function executeAction(ActionTask $task) {

    }
}

$service = new MinecraftServerService();