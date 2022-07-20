<?php
class CreditsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'users/{user}/credits';
        $this->_module = 'Store';
        $this->_description = 'Check user credits';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api, User $user): void {
        $customer = new Customer($user);

        $api->returnArray([
            'customer_id' => (int) $customer->data()->id,
            'cents' => (int) $customer->data()->cents,
            'credits' => (double) $customer->data()->cents / 100
        ]);
    }
}