<?php
/**
 * Coupon class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
class Coupon {

    private $_data;

    public function __construct(string $value = null, string $field = 'id') {
        $data = DB::getInstance()->get('store_coupons', [$field, '=', $value]);
        if ($data->count()) {
            $this->_data = $data->first();
        }
    }

    /**
     * Does this coupon exist?
     *
     * @return bool Whether the coupon exists (has data) or not.
     */
    public function exists(): bool {
        return (!empty($this->_data));
    }

    /**
     * @return object This coupon's data.
     */
    public function data() {
        return $this->_data;
    }
}