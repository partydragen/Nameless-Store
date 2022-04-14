<?php
/**
 * Base class gateways need to extend.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.0-pr12
 * @license MIT
 */
abstract class GatewayBase {

    /**
     * @var string The gateway id
     */
    private string $_id;

    /**
     * @var string The gateway name
     */
    private string $_name;

    /**
     * @var string The gateway displayname
     */
    private string $_displayname;

    /**
     * @var bool Whether this gateway is enabled or not.
     */
    private bool $_enabled;

    /**
     * @var string The path to gateway settings file.
     */
    private string $_settings;

    /**
     * @var array Array of errors added by the gateway
     */
    private array $_errors = [];

    public function __construct($name, $settings) {
        $this->_name = $name;
        $this->_settings = $settings;

        $db = DB::getInstance();
        $gateway_query = $db->query('SELECT id, displayname, enabled FROM nl2_store_gateways WHERE `name` = ?', [$name])->first();
        if ($gateway_query) {
            $this->_id = $gateway_query->id;
            $this->_displayname = $gateway_query->displayname;
            $this->_enabled = $gateway_query->enabled;
        } else {
            $gateway_query = $db->createQuery('INSERT INTO `nl2_store_gateways` (`name`, `displayname`, `enabled`) VALUES (?, ?, ?)', [$name, $name, 0]);

            $this->_id = $db->lastId();
            $this->_displayname = $name;
            $this->_enabled = 0;
        }
    }

    /**
     * Get id of this gateway.
     *
     * @return int Id of gateway.
     */
    public function getId(): int {
        return $this->_id;
    }

    /**
     * Get name of this gateway.
     *
     * @return string Name of gateway.
     */
    public function getName(): string {
        return $this->_name;
    }

    /**
     * Get displayname of this gateway.
     *
     * @return string Displayname of gateway.
     */
    public function getDisplayname(): string {
        return $this->_displayname;
    }

    /**
     * Get if this gateway is enabled
     *
     * @return bool Check if gateway is enabled
     */
    public function isEnabled(): bool {
        return $this->_enabled;
    }

    /**
     * Get the path to gateway settings page file
     *
     * @return string Settings The path to gateway settings page file
     */
    public function getSettings(): string {
        return $this->_settings;
    }

    /**
     * Add a error to the errors array
     *
     * @param string $error The error message
     */
    public function addError(string $error): void {
        $this->_errors[] = $error;
    }

    /**
     * Get any errors from the functions given by this gateway
     *
     * @return array Any errors
     */
    public function getErrors(): array {
        return $this->_errors;
    }

    /**
     * Process order to continue to gateway for payment.
     *
     * @param Order $order The order to process to gateway.
     */
    abstract public function processOrder(Order $order): void;

    /**
     * Called when customer return from gateway.
     *
     * @return bool whether this payment was completed or not.
     */
    abstract public function handleReturn(): bool;

    /**
     * Handle webhook events from gateway.
     */
    abstract public function handleListener(): void;
}