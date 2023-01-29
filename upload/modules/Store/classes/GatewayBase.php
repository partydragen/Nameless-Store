<?php
/**
 * Base class gateways need to extend.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.2
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
     * @var string The gateway author
     */
    private string $_author;

    /**
     * @var string The gateway version
     */
    private string $_version;

    /**
     * @var string The gateway store version
     */
    private string $_store_version;

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

    public function __construct(string $name, string $author, string $version, string $store_version, string $settings) {
        $this->_name = $name;
        $this->_settings = $settings;
        $this->_author = $author;
        $this->_version = $version;
        $this->_store_version = $store_version;

        $db = DB::getInstance();
        $gateway_query = $db->query('SELECT id, displayname, enabled FROM nl2_store_gateways WHERE `name` = ?', [$name])->first();
        if ($gateway_query) {
            $this->_id = $gateway_query->id;
            $this->_displayname = $gateway_query->displayname;
            $this->_enabled = $gateway_query->enabled;
        } else {
            $gateway_query = $db->query('INSERT INTO `nl2_store_gateways` (`name`, `displayname`, `enabled`) VALUES (?, ?, ?)', [$name, $name, 0]);

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
     * Set displayname of this gateway.
     */
    public function setDisplayname(string $displayname): void {
        $this->_displayname = $displayname;
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
     * Change the enabled state of this gateway.
     */
    public function setEnabled(bool $enabled): void {
        $this->_enabled = $enabled;
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
     * Called when customer view checkout page
     *
     * @param Order $template The template.
     * @param Order $customer The customer who is viewing the checkout page.
     */
    abstract public function onCheckoutPageLoad(TemplateBase $template, Customer $customer): void;

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

    /**
     * Get this gateway's author.
     *
     * @return string The author of this gateway.
     */
    public function getAuthor(): string {
        return $this->_author;
    }

    /**
     * Get this gateway's version.
     *
     * @return string The version of this gateway.
     */
    public function getVersion(): string {
        return $this->_version;
    }

    /**
     * Get this gateway's supported Store version.
     *
     * @return string The supported Store version of this gateway.
     */
    public function getStoreVersion(): string {
        return $this->_store_version;
    }
}