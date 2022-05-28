<?php
class RemoveCreditsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'users/{user}/remove-credits';
        $this->_module = 'Store';
        $this->_description = 'Remove credits from user';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api, User $user): void {
        $api->validateParams($_POST, ['credits']);

        $credits = $_POST['credits'];
        if (!is_numeric($credits)) {
            $api->throwError(StoreApiErrors::ERROR_INVALID_CREDITS_AMOUNT);
        }

        $customer = new Customer($user);
        $customer->removeCredits($credits);

        $api->returnArray(['message' => 'Successfully removed credits from user']);
    }
}