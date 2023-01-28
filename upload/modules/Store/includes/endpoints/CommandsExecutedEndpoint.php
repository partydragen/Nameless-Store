<?php
class CommandsExecutedEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'store/commands-executed';
        $this->_module = 'Store';
        $this->_description = 'Mark commands as complete';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api): void {
        $commands = $_POST['commands'];
        if (!is_array($commands) || !count($commands)) {
            $api->throwError('store:no_commands_provided');
        }
        
        $ids = '(';
        foreach ($commands as $id) {
            if (is_numeric($id)) {
                $ids .= ((int) $id) . ',';
            }
        }
        $ids = rtrim($ids, ',') . ')';

        // Ensure the user exists
        $user = $api->getDb()->query('UPDATE `nl2_store_pending_actions` SET `status`=1 WHERE id IN ' . $ids);
        
        $api->returnArray(['success' => true]);
    }
}