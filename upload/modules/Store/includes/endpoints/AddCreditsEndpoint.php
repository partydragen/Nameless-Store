<?php
class AddCreditsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'users/{user}/add-credits';
        $this->_module = 'Store';
        $this->_description = 'Add credits to user';
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
            $customer->addCents($credits);
        } else {
            $customer->addCents(Store::toCents($credits));
        }

        $api->returnArray(['message' => 'Successfully added credits to user']);
    }
}