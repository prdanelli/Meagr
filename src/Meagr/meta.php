<?

/**
* Meta
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Meta {

	//our instance wrapper
	private static $instance;

	//the key used to seperate our instances
	private $key;

	//our meta values
	private $meta = array(); 

	//the global config for meta values
	public $config;


	/**
	* multiton 
	*
	* @param key string The key that is used to seperate our instances
	*
	* @return object
	*/
	public static function init($key = 'default') {

	    //create a multuton instance
	    if (is_null(self::$instance[$key])) {
	        self::$instance[$key] = new self($key);
	    }

	    return self::$instance[$key];
	}	


	/**
	* prevent normal instantiation 
	*
	* @param key string The key that is used to seperate our instances
	*
	* @return void
	*/
	private function __construct($key) {

		//if our key is the default
		if ($this->key == 'default') {

			//generate our meta key from the full url - and make sure its just alphanumeric
			$this->key = md5(Uri::full()); 				
		}		

		$this->config = Config::settings('meta');
	}


	/**
	* set a meta value
	*
	* @param meta_key string The key of the value we wantt o store [title|keywords|description|etc]
	* @param meta_value string The value to be stored 
	*
	* @return object
	*/
	public function set($meta_key = null, $meta_value = null) {

		//we need a key
		if (is_null($meta_key)) {
			return false;
		}

		//add our value to the meta array or if the value is empty, use the config defaults
		$this->meta[$meta_key] = ($meta_value) ? : $this->config[$meta_key];

		//return self for chaining
		return $this;
	}


	/**
	* get our meta key
	*
	* @param meta_key string The key of the value we wantt o store [title|keywords|description|etc]
	*
	* @return mixed[bool|string]
	*/
	public function get($meta_key = null) {

		//we need a key
		if (is_null($meta_key)) {
			return false;
		}
		// return our meta key, or if its empty return the config default
		return ($this->meta[$meta_key]) ? : $this->config[$meta_key]; 
	}

	/**
	* magic 
	* we can now do: 
	* echo \Meagr\Meta::init()->title(); as it accesses the the meta array with the $name 'title' 
	*
	* @param name string The name of our key within the $meta array
	* @param arguments string Redundant and not used here
	*
	* @return string
	*/

	public function __call($name, $arguments) {

		if (isset($this->config[$name])) {
			return $this->config[$name];
		}

		throw new MeagrException($name . ' not found');
	}
}