<?php
class MinecraftServerService extends ServiceBase {
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

    public function executeAction(Action $action, Order $order, Product $product, Payment $payment, array $placeholders) {
        // Plugin handle username and uuid replacement
        unset($placeholders['{username}']);
        unset($placeholders['{uuid}']);

        // Execute this action on all selected connections
        $connections = ($action->data()->own_connections ? $action->getConnections() : $product->getConnections($this->getId()));
        foreach ($connections as $connection) {
            // Replace existing placeholder
            $placeholders['{connection}'] = $connection->name;

            // Replace the command placeholders
            $command = $action->data()->command;
            $command = str_replace(array_keys($placeholders), array_values($placeholders), $command);

            // Save queued command
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
            ]);
        }
    }
}

$service = new MinecraftServerService();