<?php
class PendingCommands extends EndpointBase {

    public function __construct() {
        $this->_route = 'pendingStoreCommands';
        $this->_module = 'Store';
        $this->_description = 'Get pending commands';
    }

    public function execute(Nameless2API $api) {
        $query = 'SELECT nl2_store_pending_actions.*, nl2_store_players.id as pid, IFNULL(nl2_store_players.username, nl2_users.username) as username, IFNULL(nl2_store_players.uuid, nl2_users.uuid) as uuid, user_id FROM nl2_store_pending_actions
        LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id
        LEFT JOIN nl2_store_players ON nl2_store_pending_actions.player_id=nl2_store_players.id
        LEFT JOIN nl2_users ON nl2_store_orders.user_id=nl2_users.id';

        $where = ' WHERE status = 0';
        $params = array();

        if (isset($_GET['connection_id']) || isset($_GET['server_id'])) {
            $where .= ' AND connection_id = ?';
            array_push($params, (isset($_GET['connection_id']) ? $_GET['connection_id'] : $_GET['server_id']));
        }

        // Ensure the user exists
        $commands_query = $api->getDb()->query($query . $where, $params)->results();

        $commands_array = array();
        foreach($commands_query as $command) {
            if($command->uuid == null && $command->username == null) {
                continue;
            }
            
            $commands_array[] = array(
                'id' => $command->id,
                'command' => $command->command,
                'order_id' => (int) $command->order_id,
                'user_id' => (int) $command->user_id,
                'player_id' => (int) $command->player_id,
                'username' => $command->username,
                'uuid' => $command->uuid != null ? $this->formatUUID(str_replace('-', '', $command->uuid)) : null,
                'require_online' => (boolean) $command->require_online,
                'order' => (int) $command->order,
            );
        }
        
        
        $api->returnArray(array('commands' => $commands_array));
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
