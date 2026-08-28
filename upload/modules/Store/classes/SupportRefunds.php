<?php

/**
 * Implemented by gateways which can refund completed payments.
 */
interface SupportRefunds {

    /**
     * Request a full or partial refund from the payment provider.
     *
     * @param Payment $payment The Store payment to refund.
     * @param int $amount_cents The amount to refund in the smallest currency unit.
     * @param string $reason The staff-provided reason for the refund.
     * @return GatewayRefundResult|null The provider result, or null when the request failed.
     * Pass false as the result's completed value when the provider accepted an
     * asynchronous refund which will be completed by a later webhook.
     */
    public function refundPayment(Payment $payment, int $amount_cents, string $reason): ?GatewayRefundResult;
}
