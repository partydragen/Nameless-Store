<?php
/**
 * Store customer class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.0-pr12
 * @license MIT
 */
class Customer {

    private DB $_db;
    private User $_user;

    /**
     * @var object|null The customer's data. Basically just the row from `nl2_store_customers` where the customer ID is the key.
     */
    private $_data;

    /**
     * @var bool Whether this customer is logged in or not.
     */
    private bool $_isLoggedIn = false;

    public function __construct(?User $user = null, string $value = null, string $field = 'id') {
        $this->_db = DB::getInstance();

        if ($user != null && $user->exists()) {
            // Load customer by NamelessMC User
            if (!$this->find($user->data()->id, 'user_id')) {
                // Customer data for NamelessMC User missing, Register it
                $this->create(['user_id' => $user->data()->id, 'integration_id' => 0]);
            }

            $this->_user = $user;
        } else if ($value != null) {
            $this->find($value, $field);

        } else if (Session::exists('store_customer')) {
            $customer = Session::get('store_customer');

            if ($this->find($customer, 'id')) {
                $this->_isLoggedIn = true;
            }
        }
    }

    /**
     * Find a customer by unique identifier (ID, username, identifier, etc).
     * Loads instance variables for this class.
     *
     * @param string|null $value Unique identifier.
     * @param string $field What column to check for their unique identifier in.
     *
     * @return bool True/false on success or failure respectfully.
     */
    public function find(string $value = null, string $field = 'id'): bool {
        $data = $this->_db->get('store_customers', [$field, '=', $value]);
        if ($data->count()) {
            $this->_data = $data->first();

            return true;
        }

        return false;
    }

    /**
     * Update customer data in the database.
     *
     * @param array $fields Column names and values to update.
     */
    public function update(array $fields = []) {
        if (!$this->_db->update('store_customers', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating customer');
        }
    }

    /**
     * Create a new customer.
     *
     * @param array $fields Column names and values to insert to database.
     */
    public function create(array $fields = []) {
        if (!$this->_db->insert('store_customers', $fields)) {
            throw new Exception('There was a problem registering the customer');
        }
        $last_id = $this->_db->lastId();

        $data = $this->_db->get('store_customers', ['id', '=', $last_id]);
        if ($data->count()) {
            $this->_data = $data->first();
        }
        
        return $last_id;
    }

    /**
     * Does this customer exist?
     *
     * @return bool Whether the customer exists (has data) or not.
     */
    public function exists(): bool {
        return (!empty($this->_data));
    }

    /**
     * Get the customer data.
     *
     * @return object This customer data.
     */
    public function data() {
        return $this->_data;
    }
    
    /**
     * Try to get the NamelessMC User for this customer
     *
     * @return User NamelessMC User
     */
    public function getUser(): User {
        return $this->_user ??= (function (): User {
            if ($this->data()->user_id != null) {
                return new User($this->data()->user_id);
            } else if ($this->data()->identifier != null) {
                return new User(str_replace('-', '', $this->data()->identifier), 'uuid');
            }

            return new User($this->data()->username);
        })();
    }

    /**
     * Get the customer credits.
     *
     * @return float The customer credits balance.
     */
    public function getCredits() {
        return number_format($this->_data->cents / 100, 2, '.', '');
    }

    /**
     * Add credits to the customer.
     *
     * @param float $amount The amount of credits to add to their balance
     */
    public function addCredits($amount) {
        $this->_db->createQuery('UPDATE nl2_store_customers SET cents = cents + ? WHERE id = ?', [$amount * 100, $this->_data->id]);
    }

    /**
     * Remove credits from the customer.
     *
     * @param float $amount The amount of credits to remove from their balance
     */
    public function removeCredits($amount) {
        $this->_db->createQuery('UPDATE nl2_store_customers SET cents = cents - ? WHERE id = ?', [$amount * 100, $this->_data->id]);
    }

    public function getPayments(): array {
        $payments_list = [];

        $payments = DB::getInstance()->query('SELECT nl2_store_payments.*, order_id, user_id FROM nl2_store_payments LEFT JOIN nl2_store_orders ON order_id=nl2_store_orders.id WHERE nl2_store_orders.user_id = ? ORDER BY created DESC', [$this->_data->user_id]);
        if ($payments->count()) {
            $payments = $payments->results();

            foreach  ($payments as $paymentQuery) {
                $payment = new Payment($paymentQuery->id);

                $payments_list[] = [
                    'status_id' => $paymentQuery->status_id,
                    'status' => $payment->getStatusHtml(),
                    'currency' => Output::getPurified($paymentQuery->currency),
                    'amount' => Output::getClean($paymentQuery->amount),
                    'date' => date('d M Y, H:i', $paymentQuery->created),
                    'link' => URL::build('/panel/store/payments', 'payment=' . Output::getClean($paymentQuery->id))
                ];
            }
        }

        return $payments_list;
    }
    
    public function login($username, $save = true) {
        // Online mode or offline mode?
        $uuid_linking = $this->_db->get('settings', ['name', '=', 'uuid_linking'])->results();
        $uuid_linking = $uuid_linking[0]->value;

        if ($uuid_linking == '1') {
            // Online mode
            require(ROOT_PATH . '/core/integration/uuid.php'); // For UUID stuff

            $profile = ProfileUtils::getProfile(str_replace(' ', '%20', Input::get('username')));
            $mcname_result = $profile ? $profile->getProfileAsArray() : array();
            if (isset($mcname_result['username']) && !empty($mcname_result['username']) && isset($mcname_result['uuid']) && !empty($mcname_result['uuid'])) {
                $username = Output::getClean($mcname_result['username']);
                $uuid = ProfileUtils::formatUUID(Output::getClean($mcname_result['uuid']));

                if ($this->find($uuid, 'identifier')) {
                    // Customer already exist in database
                    $this->update([
                        'username' => $username,
                        'identifier' => $uuid
                    ]);
                    $this->_isLoggedIn = true;

                    if ($save)
                        Session::put('store_customer', $this->data()->id);

                    return true;
                } else {
                    // Register new customer
                    $this->create([
                        'integration_id' => 1,
                        'username' => $username,
                        'identifier' => $uuid
                    ]);
                    $this->_isLoggedIn = true;

                    if ($save)
                        Session::put('store_customer', $this->data()->id);

                    return true;
                }
            } else {
                // Invalid Minecraft name
                return false;
            }

        } else {
            // Offline mode
            if ($this->find($username, 'username')) {
                // Customer already exist in database
                $this->_isLoggedIn = true;
                if ($save)
                    Session::put('store_customer', $this->data()->id);

                return true;
            } else {
                // Register new customer
                $this->create([
                    'integration_id' => 1,
                    'username' => $username,
                    'identifier' => null
                ]);
                $this->_isLoggedIn = true;

                if ($save)
                    Session::put('store_customer', $this->data()->id);

                return true;
            }
        }

        return false;
    }

    /**
     * Log the customer out if logged in by session.
     */
    public function logout(): void {
        if (Session::exists('store_customer')) {
            Session::delete('store_customer');
            $this->_isLoggedIn = false;
        }
    }

    /**
     * Get if this customer is currently logged in or not.
     *
     * @return bool Whether they're logged in.
     */
    public function isLoggedIn(): bool {
        return $this->_isLoggedIn;
    }

    public function getIdentifier(): string {
        if ($this->exists()) {
            if ($this->_data->user_id) {
                $user = $this->getUser();
                if ($user->exists()) {
                    return Output::getClean($user->data()->uuid);
                }
            } else if ($this->_data->identifier) {
                return Output::getClean($this->_data->identifier);
            }
        }

        return 'none';
    }

    public function getUsername(): string {
        if ($this->exists()) {
            if ($this->_data->user_id) {
                $user = $this->getUser();
                if ($user->exists()) {
                    return Output::getClean($user->data()->username);
                }
            } else if ($this->_data->username) {
                return Output::getClean($this->_data->username);
            }
        }

        return 'Unknown';
    }
}