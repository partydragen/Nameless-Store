<?php
class CommandsExecuted extends EndpointBase {

    public function __construct() {
        $this->_route = 'storeCommandsExecuted';
        $this->_module = 'Store';
        $this->_description = 'Mark commands as complete';
    }

    public function execute(Nameless2API $api) {
        $query = 'UPDATE `nl2_store_pending_actions` SET `status`=1';
        $where = ' WHERE player_id = ?';
        $params = array($_GET['player_id']);

        if (isset($_GET['online'])) {
            $where .= ' AND require_online = ?';
            array_push($params, $_GET['online']);
        }
        
        if (isset($_GET['connection_id']) || isset($_GET['server_id'])) {
            $where .= ' AND connection_id = ?';
            array_push($params, (isset($_GET['connection_id']) ? $_GET['connection_id'] : $_GET['server_id']));
        }

        // Ensure the user exists
        $user = $api->getDb()->createQuery($query . $where, $params);



        $api->returnArray(array('success' => true));
    }
}
