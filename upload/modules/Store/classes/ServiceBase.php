<?php
/**
 * Base class store services need to extend.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.0-pr13
 * @license MIT
 */
abstract class ServiceBase {

    /**
     * @var string The service id
     */
    private string $_id;

    /**
     * @var string The service name
     */
    private string $_name;

    /**
     * @var string The service description
     */
    private string $_description;

    /**
     * @var string|null The connection settings path.
     */
    protected ?string $_connection_settings;

    /**
     * @var string The action settings path.
     */
    protected string $_action_settings;

    public function __construct($id, $name, $description, $connection_settings, $action_settings) {
        $this->_id = $id;
        $this->_name = $name;
        $this->_description = $description;
        $this->_connection_settings = $connection_settings;
        $this->_action_settings = $action_settings;
    }

    /**
     * Get id of this service.
     *
     * @return int Id of service.
     */
    public function getId(): int {
        return $this->_id;
    }

    /**
     * Get name of this service.
     *
     * @return string Name of service.
     */
    public function getName(): string {
        return $this->_name;
    }

    /**
     * Get description of this service.
     *
     * @return string Description of service.
     */
    public function getDescription(): string {
        return $this->_description;
    }

    /**
     * Get connection settings path.
     *
     * @return string|null Connection settings path.
     */
    public function getConnectionSettings(): ?string {
        return $this->_connection_settings;
    }

    /**
     * Get action settings path.
     *
     * @return string Action settings path.
     */
    public function getActionSettings(): string {
        return $this->_action_settings;
    }

    /**
     * Called when connection settings page is loaded
     */
    abstract public function onConnectionSettingsPageLoad(TemplateBase $template, Fields $fields);

    /**
     * Called when action settings page is loaded
     */
    abstract public function onActionSettingsPageLoad(TemplateBase $template, Fields $fields);

    /**
     * Execute product action on connection
     */
    abstract public function executeAction(Action $action, Order $order, Product $product, Payment $payment, array $placeholders);
}