<?php
/**
 * Amount class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.0-pr12
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
     * Set the amount to charge.
     *
     * @param string|double $total
     */
    public function setTotal($total): void {
        $this->_total = $total;
    }

    /**
     * The amount to charge.
     *
     * @return string
     */
    public function getTotal(): string {
        return $this->_total;
    }
}