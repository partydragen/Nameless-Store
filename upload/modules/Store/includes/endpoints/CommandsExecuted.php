<?php
class CommandsExecuted extends EndpointBase {

    public function __construct() {
        $this->_route = 'storeCommandsExecuted';
        $this->_module = 'Store';
        $this->_description = 'Mark commands as complete';
    }

    public function execute(Nameless2API $api) {
        $commands = $_POST['commands'];
        if(!is_array($commands) || !count($commands)) {
            $api->throwError(110, 'No commands provided');
        }
        
        $ids = '(';
        foreach ($commands as $id) {
            if (is_numeric($id)) {
                $ids .= ((int) $id) . ',';
            }
        }
        $ids = rtrim($ids, ',') . ')';

        // Ensure the user exists
        $user = $api->getDb()->createQuery('UPDATE `nl2_store_pending_actions` SET `status`=1 WHERE id IN ' . $ids);
        
        $api->returnArray(array('success' => true));
    }
}