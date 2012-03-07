<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Facebook Core 
 * 
 * Provides access to the latest Facebook PHP SDK
 * 
 * @author Muntasir Mohiuddin
 */

// Include the Facebook PHP SDK Class
require_once "facebook_sdk/facebook.php";

class Facebook_Core extends Facebook {
	/**
	 * @var Facebook configuration
	 */
	protected static $config;
	
	
	/** 
	 * Constructor of the Facebook API
	 * 
	 * @return Facebook instance
	 */
	function __construct($config){
		self::$config = $config;
		parent::__construct(array(
		  'appId'  	=> self::$config['app_id'],
		  'secret' 	=> self::$config['secret'],
		  'file_upload'	=> self::$config['file_upload'],
		));
	}	


	/**
	 * Returns Facebook config variable. This is a generic function so it would work such as config_<config_name>.
	 * Call to get a config variable is only possible from a non-static context as config is set only during __construct call.
	 *
	 * @var name string name of the config 
	 * @return mixed returns the config value from the config array
	 **/	
	static function config($name) {
		return self::$config[$name];
	}

	
	/**
	 * Basic me call 
	 **/
	function me() {
		return $this->api('/me');
	}
	

	/**
	 * Gets user's friends limited by limit and offset
	 *
	 * @var limit int number of friends to get
	 * @var limit int offset on the limit
	 **/
	function friends($limit=null, $offset=null) {
		return $this->api('/me/friends/?1' . (($limit) ? "&limit=" . $limit : '') . (($offset) ? "&offset=" . $offset : ''));
	}

	
	/**
	 * Make batch API call to Facebook graph API end point
	 *
	 * @var batch_data mixed array of variables consisting method and relative_url
	 * @return mixed returns an array consisting the result of batch api call.
	 **/
	public function api_batch($batch_data) {
		$post_url = self::$DOMAIN_MAP['graph'];
		
		$post_data = http_build_query(array(
			'access_token' => $this->get_access_token(),
			'batch' => json_encode($batch_data)
		));
		
		$params = array('http' => array(
			'method' => 'POST',
			'header' => 'Content-type: application/x-www-form-urlencoded',
			'content' => $post_data
		));
		
		$context = stream_context_create($params);
		$responses = json_decode(@file_get_contents($post_url, false, $context), true);
		
		$return = array();
		
		foreach($responses as $response) {
			$return[] = json_decode($response['body'], true); 
		}
		
		return $return;
	}
	

	###########
	# WRAPERS #
	###########
	
	/**
	 * Override non-static function references. If 
	 *
	 * @var name string function name with underscore. this parameter is case sensitive
	 * @var arguments mixed array of arguments
	 **/	
	public function __call($name, $arguments) {
		$return = null;
		
		if(preg_match('/^config_/', $name)>0){
			$return = $this->config(str_replace("config_", "", $name));			
		} else {
			$new_name = $this->get_new_function_name($name);
			
			if(method_exists($this, $new_name)){
				$return = call_user_func(array($this, $new_name), $arguments);
			} else {
				trigger_error("Fatal error: no function by name " . $new_name . " or " . $name, E_USER_ERROR);
			}
		}
		return $return;
	}

	/**
	 * Override static function references.
	 *
	 * @var name string function name with underscore. this parameter is case sensitive
	 * @var arguments mixed array of arguments
	 **/	
	public static function __callStatic($name, $arguments) {
		$return = null;
		
		$new_name = self::$get_new_function_name($name);
		
		if(method_exists($this, $new_name)){
			$return = call_user_func(array(self, $new_name), $arguments);
		} else {
			trigger_error("Fatal error: no function by name " . $new_name . " or " . $name, E_USER_ERROR);
		}
		
		return $return;
	}

	/**
	 * Convert the underscored function name to a CamelCased funtion name
	 *
	 * @var name string function name with underscore
	 * @return string CamelCase function name
	 **/	
	protected function get_new_function_name($name){
		$new_name = preg_replace('/([a-z])_([a-z])/e', '"$1".ucfirst("$2")', $name);
		return $new_name;
	}

	/**
	 * Convert the CamelCased function name to an underscored funtion name
	 *
	 * @var name string CamelCase function name
	 * @return string function name with underscore
	 **/	
	protected function get_old_function_name($name){
		$new_name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
		return $new_name;
	}
	
}
