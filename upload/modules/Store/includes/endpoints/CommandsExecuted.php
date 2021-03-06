<?php
class CommandsExecuted extends EndpointBase {

    public function __construct() {
        $this->_route = 'storeCommandsExecuted';
        $this->_module = 'Store';
        $this->_description = 'Mark commands as complete';
    }

    public function execute(Nameless2API $api) {
        $query = 'UPDATE `nl2_store_pending_commands` SET `status`=1';
        $where = ' WHERE player_id = ?';
        $params = array($_GET['player_id']);

        if (isset($_GET['online'])) {
            $where .= ' AND require_online = ?';
            array_push($params, $_GET['online']);
        }
        
        if (isset($_GET['server_id'])) {
            $where .= ' AND server_id = ?';
            array_push($params, $_GET['server_id']);
        }

        // Ensure the user exists
        $user = $api->getDb()->createQuery($query . $where, $params);



        $api->returnArray(array('success' => true));
    }
}
