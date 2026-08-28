<?php

/**
 * Result returned by a gateway after accepting a refund request.
 */
class GatewayRefundResult {

    private string $_transaction_id;
    private bool $_completed;

    public function __construct(string $transaction_id, bool $completed = true) {
        $this->_transaction_id = $transaction_id;
        $this->_completed = $completed;
    }

    /**
     * Get the refund identifier assigned by the gateway.
     */
    public function getTransactionId(): string {
        return $this->_transaction_id;
    }

    /**
     * Whether the gateway reports the refund as completed already.
     */
    public function isCompleted(): bool {
        return $this->_completed;
    }
}
