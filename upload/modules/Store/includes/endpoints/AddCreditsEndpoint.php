<?php
class AddCreditsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'users/{user}/addcredits';
        $this->_module = 'Store';
        $this->_description = 'Add credits to user';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api, User $user): void {
        $api->validateParams($_POST, ['credits']);

        $credits = $_POST['credits'];
        if (!is_numeric($credits)) {
            $api->throwError(250, 'Invalid credits amount');
        }

        $customer = new Customer($user);
        $customer->addCredits($credits);

        $api->returnArray(['message' => 'Successfully removed credits from user']);
    }
}