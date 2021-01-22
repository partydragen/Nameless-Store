<?php
class Store {
    private $_db;

    // Constructor, connect to database
    public function __construct() {
        $this->_db = DB::getInstance();
    }
	
	// Get all payments
	public function getAllPayments() {
		$payments = $this->_db->query('SELECT nl2_store_payments.*, uuid, username FROM nl2_store_payments LEFT JOIN nl2_store_players ON player_id=nl2_store_players.id ORDER BY created DESC')->results();
		
		return $payments;
	}
	
	// Get all categories
	public function getAllCategories() {
		$categories = $this->_db->query('SELECT * FROM nl2_store_categories WHERE deleted = 0 ORDER BY `order` ASC')->results();
			
		$categories_array = array();
		foreach($categories as $category){
			$categories_array[] = array(
				'id' => Output::getClean($category->id),
				'name' => Output::getClean($category->name)
			);
		}
		
		return $categories_array;
	}
    
    // Add pending commands
    public function addPendingCommands($player_id, $payment_id, $type) {
        $packages = $this->_db->query('SELECT * FROM nl2_store_payments_packages INNER JOIN nl2_store_packages ON nl2_store_packages.id=package_id WHERE payment_id = ?', array($payment_id))->results();
        foreach($packages as $package) {
            $commands = $this->_db->query('SELECT * FROM nl2_store_packages_commands WHERE package_id = ? AND type = ? ORDER BY `order`', array($package->id, $type))->results();
            foreach($commands as $command) {
                $this->_db->insert('store_pending_commands', array(
                    'payment_id' => $payment_id,
                    'player_id' => $player_id,
                    'server_id' => $command->server_id,
                    'type' => $command->type,
                    'command' => $command->command,
                    'require_online' => $command->require_online,
                    'order' => $command->order,
                ));
            }
        }
    }
    
    public function deletePendingCommands($payment_id) {
        $this->_db->createQuery('DELETE FROM nl2_store_pending_commands WHERE payment_id = ? AND status = 0', array($payment_id))->results();
    }
    
    /*
     *  Check for Module updates
     *  Returns JSON object with information about any updates
     */
    public static function updateCheck($current_version = null) {
        $queries = new Queries();

        // Check for updates
        if (!$current_version) {
            $current_version = $queries->getWhere('settings', array('name', '=', 'nameless_version'));
            $current_version = $current_version[0]->value;
        }

        $uid = $queries->getWhere('settings', array('name', '=', 'unique_id'));
        $uid = $uid[0]->value;
		
		$enabled_modules = Module::getModules();
		foreach($enabled_modules as $enabled_item){
			if($enabled_item->getName() == 'Store'){
				$module = $enabled_item;
				break;
			}
		}
		

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, 'https://api.partydragen.com/stats.php?uid=' . $uid . '&version=' . $current_version . '&module=Store&module_version='.$module->getVersion() . '&domain='. Util::getSelfURL());

        $update_check = curl_exec($ch);
        curl_close($ch);

		$info = json_decode($update_check);
		if (isset($info->message)) {
			die($info->message);
		}
		
        return $update_check;
    }
}