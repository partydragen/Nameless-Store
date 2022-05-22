<?php
class RemoveCreditsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'removeCredits';
        $this->_route = 'users/{user}/removecredits';
        $this->_module = 'Store';
        $this->_description = 'Remove credits from user';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api, User $user): void {
        $api->validateParams($_POST, ['credits']);

        $credits = $_POST['credits'];
        if (!is_numeric($credits)) {
            $api->throwError(250, 'Invalid credits amount');
        }

        $customer = new Customer($user);
        $customer->removeCredits($credits);

        $api->returnArray(['message' => 'Successfully added credits to user']);
    }
}