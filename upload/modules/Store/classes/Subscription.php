<?php
/**
 * Store subscription class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.1.0
 * @license MIT
 */
class Subscription {

    private DB $_db;

    /**
     * @var SubscriptionData|null The subscription's data. Basically just the row from `nl2_store_subscription` where the user ID is the key.
     */
    private ?SubscriptionData $_data;

    public function __construct(string $value, string $field = 'id') {
        $this->_db = DB::getInstance();

        $data = $this->_db->get('store_subscriptions', [$field, '=', $value]);
        if ($data->count()) {
            $this->_data = new SubscriptionData($data->first());
        }
    }

    /**
     * Update a subscription data in the database.
     *
     * @param array $fields Column names and values to update.
     */
    public function update(array $fields = []): void {
        if (!$this->_db->update('store_subscriptions', $this->data()->id, $fields)) {
            throw new Exception('There was a problem updating product');
        }
    }

    /**
     * Does this subscription exist?
     *
     * @return bool Whether the subscription exists (has data) or not.
     */
    public function exists(): bool {
        return (!empty($this->_data));
    }

    /**
     * Get the subscription data.
     *
     * @return null|SubscriptionData This subscription data.
     */
    public function data(): ?SubscriptionData {
        return $this->_data;
    }

    /**
     * Get gateway used for this subscription.
     *
     * @return null|GatewayBase Gateway used for this subscription.
     */
    public function getGateway(): ?GatewayBase {
        if ($this->exists() && $this->data()->gateway_id != 0) {
            return Gateways::getInstance()->get($this->data()->gateway_id);
        }

        return null;
    }

    // Cancel subscription.
    public function cancel(): bool {
        $gateway = Gateways::getInstance()->get($this->data()->gateway_id);
        if ($gateway instanceof SupportSubscriptions) {
            return $gateway->cancelSubscription($this);
        }

        return false;
    }

    // Charge payment from customer.
    public function chargePayment(): bool {
        $gateway = Gateways::getInstance()->get($this->data()->gateway_id);
        if ($gateway instanceof SupportSubscriptions) {
            return $gateway->chargePayment($this);
        }

        return false;
    }

    public function cancelled(): void {
        $this->update([
            'status_id' => 2,
            'updated' => date('U')
        ]);

        EventHandler::executeEvent(new SubscriptionCancelledEvent($this));
    }
}