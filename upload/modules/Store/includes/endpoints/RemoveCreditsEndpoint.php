<?php
class RemoveCreditsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'users/{user}/remove-credits';
        $this->_module = 'Store';
        $this->_description = 'Remove credits from user';
        $this->_method = 'POST';
    }

    public function execute(Nameless2API $api, User $user): void {
        if (!isset($_POST['cents']) && !isset($_POST['credits'])) {
            $api->throwError(Nameless2API::ERROR_INVALID_POST_CONTENTS);
        }

        $credits = $_POST['cents'] ?? $_POST['credits'];
        if (!is_numeric($credits) || $credits <= 0) {
            $api->throwError(StoreApiErrors::ERROR_INVALID_CREDITS_AMOUNT);
        }

        $customer = new Customer($user);
        if (isset($_POST['cents'])) {
            $customer->removeCents($credits);
        } else {
            $customer->removeCents(Store::toCents($credits));
        }

        $api->returnArray(['message' => 'Successfully removed credits from user']);
    }
}