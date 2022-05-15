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
    private $_db,
            $_data;
    
    public function __construct($user = null, $value = null, $field = 'id', ) {
        $this->_db = DB::getInstance();

        if ($user != null && $user->exists()) {
            // Load customer by NamelessMC User
            $data = $this->_db->get('store_customer', ['user_id', '=', $user->data()->id]);
            if ($data->count()) {
                $this->_data = $data->first();
            } else {
                // Customer data for NamelessMC User missing, Register it
                $this->create(['user_id' => $user->data()->id]);
            }
        } else if ($value != null) {
            $data = $this->_db->get('store_customer', [$field, '=', $value]);
        }
    }
    
    /**
     * Update customer data in the database.
     *
     * @param array $fields Column names and values to update.
     */
    public function update(array $fields = []) {
        if (!$this->_db->update('store_customer', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating customer');
        }
    }

    /**
     * Create a new customer.
     *
     * @param array $fields Column names and values to insert to database.
     */
    public function create(array $fields = []) {
        if (!$this->_db->insert('store_customer', $fields)) {
            throw new Exception('There was a problem registering the customer');
        }
        $last_id = $this->_db->lastId();
        
        $data = $this->_db->get('store_customer', ['id', '=', $last_id]);
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
        $this->_db->createQuery('UPDATE nl2_store_customer SET cents = cents + ? WHERE id = ?', [$amount * 100, $this->_data->id]);
    }

    /**
     * Remove credits from the customer.
     *
     * @param float $amount The amount of credits to remove from their balance
     */
    public function removeCredits($amount) {
        $this->_db->createQuery('UPDATE nl2_store_customer SET cents = cents - ? WHERE id = ?', [$amount * 100, $this->_data->id]);
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
}