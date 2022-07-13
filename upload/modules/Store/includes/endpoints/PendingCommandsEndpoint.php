<?php
class PendingCommandsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'store/pending-commands';
        $this->_module = 'Store';
        $this->_description = 'List all pending commands';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api): void {
        $query = 'SELECT * FROM nl2_store_pending_actions';
        $where = ' WHERE status = 0';
        $order = ' ORDER BY `order` ASC';
        $params = [];

        if (isset($_GET['connection_id']) || isset($_GET['server_id'])) {
            $where .= ' AND connection_id = ?';
            array_push($params, (isset($_GET['connection_id']) ? $_GET['connection_id'] : $_GET['server_id']));

            $api->getDb()->update('store_connections', isset($_GET['connection_id']) ? $_GET['connection_id'] : $_GET['server_id'], [
                'last_fetch' => date('U')
            ]);
        }

        // Ensure the user exists
        $commands_query = $api->getDb()->query($query . $where . $order, $params)->results();

        $customers_commands = [];
        foreach ($commands_query as $command) {
            $customers_commands[$command->customer_id][] = [
                'id' => (int)$command->id,
                'command' => $command->command,
                'order_id' => (int) $command->order_id,
                'require_online' => (boolean) $command->require_online
            ];
        }

        $customers = [];
        foreach ($customers_commands as $customer_id => $commands) {
            $customer = new Customer(null, $customer_id);
            if ($customer->exists() && $customer->data()->username != null) {
                $customers[] = [
                    'customer_id' => (int) $customer->data()->id,
                    'user_id' => (int) $customer->data()->user_id,
                    'identifier' => $customer->data()->identifier != null ? $this->formatUUID(str_replace('-', '', $customer->data()->identifier)) : null,
                    'username' => $customer->data()->username,
                    'commands' => $commands
                ];
            }
        }

        // Online mode or offline mode?
        $uuid_linking = $api->getDb()->get('settings', ['name', '=', 'uuid_linking'])->results();
        $uuid_linking = ($uuid_linking[0]->value == '1' ? true : false);

        $api->returnArray(['online_mode' => $uuid_linking, 'customers' => $customers]);
    }
    
    /**
    * @param $uuid string UUID to format
    * @return string Properly formatted UUID (According to UUID v4 Standards xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx WHERE y = 8,9,A,or B and x = random digits.)
    */
    public static function formatUUID($uuid) {
        $uid = "";
        $uid .= substr($uuid, 0, 8)."-";
        $uid .= substr($uuid, 8, 4)."-";
        $uid .= substr($uuid, 12, 4)."-";
        $uid .= substr($uuid, 16, 4)."-";
        $uid .= substr($uuid, 20);
        return $uid;
    }
}
