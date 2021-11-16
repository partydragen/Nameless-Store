<?php
class PendingCommands extends EndpointBase {

    public function __construct() {
        $this->_route = 'pendingStoreCommands';
        $this->_module = 'Store';
        $this->_description = 'Get pending commands';
    }

    public function execute(Nameless2API $api) {
        $query = 'SELECT nl2_store_pending_actions.*, nl2_store_players.id as pid, username, uuid FROM nl2_store_pending_actions INNER JOIN nl2_store_orders ON order_id=nl2_store_orders.id INNER JOIN nl2_store_players ON nl2_store_pending_actions.player_id=nl2_store_players.id';

        $where = ' WHERE status = 0';
        $params = array();

        if (isset($_GET['server_id'])) {
            $where .= ' AND server_id = ?';
            array_push($params, $_GET['server_id']);
        }

        // Ensure the user exists
        $commands_query = $api->getDb()->query($query . $where, $params)->results();

        $commands_array = array();
        foreach($commands_query as $command) {
            $commands_array[] = array(
                'command' => $command->command,
                'order_id' => (int) $command->order_id,
                'player_id' => (int) $command->pid,
                'username' => $command->username,
                'uuid' => $command->uuid,
                'require_online' => (boolean) $command->require_online,
                'order' => (int) $command->order,
            );
        }
        
        
        $api->returnArray(array('commands' => $commands_array));
    }
}
