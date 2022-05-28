<?php
/**
 * Contains namespaced API error messages for the Store module.
 * These have no versioning, and are not meant to be used by any other modules.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.0-pr13
 * @license MIT
 */
class StoreApiErrors {
    public const ERROR_PAYMENT_NOT_FOUND = 'store:payment_not_found';
    public const ERROR_INVALID_CREDITS_AMOUNT = 'store:invalid_credits_amount';
}