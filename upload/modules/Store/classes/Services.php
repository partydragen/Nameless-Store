<?php
/**
 * Services class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.0-pr13
 * @license MIT
 */
class Services {

    /** @var Services */
    private static $_instance = null;

    /**
     * @var ServiceBase[] The list of services.
     */
    private array $_services;

    // Constructor
    public function __construct() {
        $directories = glob(ROOT_PATH . '/modules/Store/services/*' , GLOB_ONLYDIR);
        foreach ($directories as $directory) {
            $folders = explode('/', $directory);

            if (file_exists(ROOT_PATH . '/modules/Store/services/' . $folders[count($folders) - 1] . '/Service.php')) {
                require_once(ROOT_PATH . '/modules/Store/services/' . $folders[count($folders) - 1] . '/Service.php');

                $this->_services[$service->getId()] = $service;
            }
        }
    }

    /**
     * Get or create a new Services instance.
     * 
     * @return Services Instance
     */
    public static function getInstance() {
        if (!isset(self::$_instance)) {
            self::$_instance = new Services();
        }

        return self::$_instance;
    }

    /**
     * List all services.
     *
     * @return ServiceBase[] List of services.
     */
    public function getAll(): iterable  {
        return $this->_services;
    }

    /**
     * Get a service by id.
     *
     * @param int $id Id of service to get.
     *
     * @return ServiceBase|null Instance of service with same id, null if it doesn't exist.
     */
    public function get(int $id) {
        if (array_key_exists($id, $this->_services)) {
            return $this->_services[$id];
        }

        return null;
    }
}