<?php
class RemoveCreditsEndpoint extends EndpointBase {

    public function __construct() {
        $this->_route = 'removeCredits';
        $this->_module = 'Store';
        $this->_description = 'Remove credits from user';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api) {
        $api->validateParams($_POST, ['user', 'credits']);

        // Ensure user exists
        $user = $api->getUser('id', $_POST['user']);

        $credits = $_POST['credits'];
        if (!is_numeric($credits)) {
            $api->throwError(250, 'Invalid credits amount');
        }

        $customer = new Customer($user);
        $customer->removeCredits($credits);

        $api->returnArray(['message' => 'Successfully added credits to user']);
    }
}