<?php
class APIService extends ServiceBase {
    public function __construct() {
        $id = 7;
        $name = 'API';
        $description = 'With this action you can execute api endpoints';
        $connection_settings = null;
        $action_settings = ROOT_PATH . '/modules/Store/services/API/settings/action_settings.php';

        parent::__construct($id, $name, $description, $connection_settings, $action_settings);
    }

    public function onActionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function scheduleAction(Action $action, Order $order, Item $item, Payment $payment, array $placeholders) {
        $task = new ActionTask();
        $task->create($action->data()->command, $action, $order, $item, $payment, [
            'connection_id' => 0,
            'status' => ActionTask::COMPLETED
        ]);

        $command = json_decode($action->data()->command, true);

        $http_type = $action->parseCommand($command['http_type'], $order, $item, $payment, $placeholders);
        $http_url = $action->parseCommand($command['http_url'], $order, $item, $payment, $placeholders);
        $http_headers = $action->parseCommand($command['http_headers'], $order, $item, $payment, $placeholders);
        $http_body = $action->parseCommand($command['http_body'], $order, $item, $payment, $placeholders);

        $http_body = str_replace("\n", "", $http_body);
        //$headers = explode('=', Output::getClean(str_replace("\r" , "", $field->options)));

        switch ($http_type) {
            case 'GET':
                HttpClient::get($http_url);
                break;

            case 'POST':
                HttpClient::post($http_url, $http_body, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]);
                break;

            case 'PUT':
                HttpClient::put($http_url, $http_body, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]);
                break;

            case 'PATCH':
                HttpClient::patch($http_url, $http_body, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]);
                break;

            case 'DELETE':
                HttpClient::delete($http_url, $http_body, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]);
                break;
        }
    }

    public function executeAction(ActionTask $task) {

    }
}

$service = new APIService();