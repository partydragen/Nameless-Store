<?php
/**
 * Credit contribution attached to an order which is paid by another gateway.
 *
 * Credits are reserved immediately so a customer cannot spend them twice while
 * the external payment is in progress. The reservation is completed with the
 * payment, or returned when checkout is cancelled or denied.
 *
 * @package Modules\Store
 */
class OrderCredit {
    public const RESERVED = 0;
    public const COMPLETED = 1;
    public const RELEASED = 2;
    public const REFUNDED = 3;

    private DB $_db;
    private ?object $_data = null;

    public function __construct(?int $order_id = null) {
        $this->_db = DB::getInstance();

        if ($order_id !== null) {
            $credit = $this->_db->get('store_order_credits', ['order_id', '=', $order_id]);
            if ($credit->count()) {
                $this->_data = $credit->first();
            }
        }
    }

    public function exists(): bool {
        return $this->_data !== null;
    }

    public function data(): ?object {
        return $this->_data;
    }

    public function getAmountCents(): int {
        return $this->exists() ? (int) $this->_data->amount_cents : 0;
    }

    public function isReserved(): bool {
        return $this->exists() && (int) $this->_data->status_id === self::RESERVED;
    }

    /**
     * Reserve up to the requested number of credits for an order.
     * Repeated calls for the same active reservation are idempotent.
     */
    public static function reserve(Order $order, Customer $customer, int $requested_cents): self {
        if (!$order->exists() || !$customer->exists() || $requested_cents < 1) {
            return new self($order->exists() ? (int) $order->data()->id : null);
        }

        $db = DB::getInstance();
        $db->beginTransaction();

        try {
            $existing_query = $db->query(
                'SELECT * FROM nl2_store_order_credits WHERE order_id = ? FOR UPDATE',
                [(int) $order->data()->id]
            );

            if ($existing_query->count()) {
                $existing = $existing_query->first();
                if (in_array((int) $existing->status_id, [self::RESERVED, self::COMPLETED], true)) {
                    $db->commitTransaction();
                    return new self((int) $order->data()->id);
                }
            }

            $balance_query = $db->query(
                'SELECT cents FROM nl2_store_customers WHERE id = ? FOR UPDATE',
                [(int) $customer->data()->id]
            );
            $balance = $balance_query->count() ? (int) $balance_query->first()->cents : 0;
            $amount_cents = min($requested_cents, max(0, $balance));

            if ($amount_cents < 1) {
                $db->commitTransaction();
                return new self((int) $order->data()->id);
            }

            $db->query(
                'UPDATE nl2_store_customers SET cents = cents - ? WHERE id = ? AND cents >= ?',
                [$amount_cents, (int) $customer->data()->id, $amount_cents]
            );
            if ($db->count() !== 1) {
                throw new Exception('The credit balance changed while the order was being reserved');
            }

            $db->insert('store_transactions', [
                'customer_id' => (int) $customer->data()->id,
                'received_by' => null,
                'action' => 'remove_cents',
                'cents' => -$amount_cents,
                'time' => date('U'),
                'info' => 'Credit contribution reserved for order #' . $order->data()->id
            ]);
            $transaction_id = (int) $db->lastId();

            $values = [
                'customer_id' => (int) $customer->data()->id,
                'amount_cents' => $amount_cents,
                'transaction_id' => $transaction_id,
                'status_id' => self::RESERVED,
                'updated' => date('U')
            ];

            if ($existing_query->count()) {
                $db->update('store_order_credits', $existing_query->first()->id, $values);
            } else {
                $db->insert('store_order_credits', array_merge($values, [
                    'order_id' => (int) $order->data()->id,
                    'created' => date('U')
                ]));
            }

            $db->commitTransaction();
        } catch (Throwable $e) {
            $db->rollBackTransaction();
            throw $e;
        }

        return new self((int) $order->data()->id);
    }

    public function complete(?int $payment_id = null): void {
        if (!$this->isReserved()) {
            return;
        }

        $this->_db->update('store_order_credits', $this->_data->id, [
            'payment_id' => $payment_id,
            'status_id' => self::COMPLETED,
            'updated' => date('U')
        ]);
        $this->_data->payment_id = $payment_id;
        $this->_data->status_id = self::COMPLETED;
    }

    /** Return a checkout reservation to the customer's balance. */
    public function release(): void {
        $this->returnCredits(self::RESERVED, self::RELEASED, 'Credit contribution released for order #');
    }

    /** Return completed credits after the external share is fully refunded. */
    public function refund(?int $payment_id = null): void {
        if (
            $this->exists()
            && $this->_data->payment_id !== null
            && (int) $this->_data->payment_id !== (int) $payment_id
        ) {
            return;
        }

        $this->returnCredits(self::COMPLETED, self::REFUNDED, 'Credit contribution refunded for order #');
    }

    private function returnCredits(int $expected_status, int $new_status, string $description): void {
        if (!$this->exists()) {
            return;
        }

        $this->_db->beginTransaction();
        try {
            $credit_query = $this->_db->query(
                'SELECT * FROM nl2_store_order_credits WHERE id = ? FOR UPDATE',
                [(int) $this->_data->id]
            );
            if (!$credit_query->count() || (int) $credit_query->first()->status_id !== $expected_status) {
                $this->_db->commitTransaction();
                return;
            }

            $credit = $credit_query->first();
            $this->_db->query(
                'UPDATE nl2_store_customers SET cents = cents + ? WHERE id = ?',
                [(int) $credit->amount_cents, (int) $credit->customer_id]
            );
            $this->_db->insert('store_transactions', [
                'customer_id' => (int) $credit->customer_id,
                'received_by' => null,
                'action' => 'add_cents',
                'cents' => (int) $credit->amount_cents,
                'time' => date('U'),
                'info' => $description . $credit->order_id
            ]);
            $this->_db->update('store_order_credits', $credit->id, [
                'status_id' => $new_status,
                'updated' => date('U')
            ]);

            $this->_db->commitTransaction();
            $this->_data->status_id = $new_status;
        } catch (Throwable $e) {
            $this->_db->rollBackTransaction();
            throw $e;
        }
    }

    public static function completeForOrder(int $order_id, ?int $payment_id = null): void {
        (new self($order_id))->complete($payment_id);
    }

    public static function releaseForOrder(int $order_id): void {
        (new self($order_id))->release();
    }

    public static function refundForOrder(int $order_id, ?int $payment_id = null): void {
        (new self($order_id))->refund($payment_id);
    }
}
