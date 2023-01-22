<?php
/**
 * Amount class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
class Amount {

    /**
     * @var string The currency code.
     */
    private string $_currency;

    /**
     * @var string The currency code.
     */
    private string $_total;

    /**
     * Set currency code (3-letters)
     *
     * @param string $currency
     */
    public function setCurrency(string $currency): void {
        $this->_currency = $currency;
    }

    /**
     * Get currency code (3-letters).
     *
     * @return string
     */
    public function getCurrency(): string {
        return $this->_currency;
    }

    /**
     * Set the amount of cents to charge.
     *
     * @param int $total
     */
    public function setTotalCents(int $total): void {
        $this->_total = $total;
    }

    /**
     * The amount of cents to charge.
     *
     * @return int
     */
    public function getTotalCents(): int {
        return $this->_total;
    }
}