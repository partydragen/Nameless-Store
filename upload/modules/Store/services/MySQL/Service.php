<?php
class MySQLService extends ServiceBase implements ConnectionsBase {
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

    public function scheduleAction(Action $action, Order $order, Item $item, Payment $payment, array $placeholders) {
        // Execute this action on all selected connections
        foreach ($action->getSelectedConnections($item, $this) as $connection) {
            // Replace existing placeholder
            $placeholders['connection'] = $connection->name;
            
            $data = json_decode($connection->data);
            if ($data != null && isset($data->password) && !empty($data->password)) {
                $command = $action->parseCommand($action->data()->command, $order, $item, $payment, $placeholders);

                $db = DB::getCustomInstance($data->address, $data->database, $data->username, $data->password, $data->port, null, '');
                $db->query($command);

                $task = new ActionTask();
                $task->create($command, $action, $order, $item, $payment, [
                    'connection_id' => $connection->id,
                    'status' => ActionTask::COMPLETED
                ]);
            }
        }
    }

    public function executeAction(ActionTask $task) {

    }
}

$service = new MySQLService();