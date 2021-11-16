<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - StorePlayer class
 */
 
class Player {
    /** @var DB */
    private $_db,
            $_data,
            $_isLoggedIn;
    
    public function __construct($player = null, $field = 'id') {
        $this->_db = DB::getInstance();

        if (!$player) {
            if (Session::exists('store_player')) {
                $player = Session::get('store_player');
                
                if($this->find($player, $field)) {
                    $this->_isLoggedIn = true;
                }
            }
        } else {
            $this->find($player, $field);
        }
    }
    
    public function find($value, $field) {
        $data = $this->_db->get('store_players', array($field, '=', $value));
        if ($data->count()) {
            $this->_data = $data->first();
            
            return true;
        }
        
        return false;
    }
    
    public function login($username, $save = true) {
        // Online mode or offline mode?
        $uuid_linking = $this->_db->get('settings', array('name', '=', 'uuid_linking'))->results();
        $uuid_linking = $uuid_linking[0]->value;
        
        if ($uuid_linking == '1') {
            // Online mode
			require(ROOT_PATH . '/core/integration/uuid.php'); // For UUID stuff
				
			$profile = ProfileUtils::getProfile(str_replace(' ', '%20', Input::get('username')));
			$mcname_result = $profile ? $profile->getProfileAsArray() : array();
			if(isset($mcname_result['username']) && !empty($mcname_result['username']) && isset($mcname_result['uuid']) && !empty($mcname_result['uuid'])){
				$username = Output::getClean($mcname_result['username']);
				$uuid = ProfileUtils::formatUUID(Output::getClean($mcname_result['uuid']));
                
                if($this->find($uuid, 'uuid')) {
                    // Player already exist in database
                    $this->_db->update('store_players', $this->data()->id, array(
                        'username' => $username,
                        'uuid' => $uuid
                    ));
                    $this->_isLoggedIn = true;
                    
                    if($save)
                        Session::put('store_player', $this->data()->id);
                        
                    return true;
                } else {
                    // Register new player
                    $this->_db->insert('store_players', array(
                        'username' => $username,
                        'uuid' => $uuid
                    ));
                    $this->find($this->_db->lastId(), 'id');
                    $this->_isLoggedIn = true;
                
                    if($save)
                        Session::put('store_player', $this->data()->id);
                    
                    return true;
                }
			} else {
				// Invalid Minecraft name
				return false;
			}
        } else {
            
            // Offline mode
            if($this->find($username, 'username')) {
                // Player already exist in database
                $this->_isLoggedIn = true;
                if($save)
                    Session::put('store_player', $this->data()->id);
                
                return true;
            } else {
                // Register new player
                $this->_db->insert('store_players', array(
                    'username' => $username,
                    'uuid' => null
                ));
                $this->find($this->_db->lastId(), 'id');
                $this->_isLoggedIn = true;

                if($save)
                    Session::put('store_player', $this->data()->id);
                    
                return true;
            }
        }
        
        return false;
    }
    
    public function logout() {
        if (Session::exists('store_player')) {
            Session::delete('store_player');
            $this->_isLoggedIn = false;
        }
    }
    
    public function isLoggedIn() {
        return $this->_isLoggedIn;
    }
    
    public function data() {
        return $this->_data;
    }
    
    public function getId() {
        return Output::getClean($this->_data->id);
    }
    
    public function getUUID() {
        return Output::getClean($this->_data->uuid);
    }
    
    public function getUsername() {
        return Output::getClean($this->_data->username);
    }
}