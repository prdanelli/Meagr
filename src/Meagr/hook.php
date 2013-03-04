<?

/**
* Hook
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Hook {

	//our private static array of hooks
	private static $hooks = array();


	/**
	* our hook bind function, inplace of the normal init()
	*
	* @param event to bind, the key to seperate our hooks
	* @param callback array of object => method
	*
	* @return void
	*/
	public static function bind($event, $callback) {
		new Hook($event, $callback);
	}


	/**
	* a private constuct to prevent instantiation 
	*
	* @param event to bind, the key to seperate our hooks
	* @param callback array of object => method
	*
	* @return void
	*/
	private function __construct($event, $callback) {

		//check if the event already exists
		if (! isset(self::$hooks[$event])) {
			//make sure we have a slot
			self::$hooks[$event] = array();
		}

		//add the new callback
		self::$hooks[$event][] = $callback;
	}		


	/**
	* our hook trigger method
	*
	* @param event to bind, the key to seperate our hooks
	* @param args array The additonal informaiton that will be passed to our callback
	*
	* @return mixed
	*/
	public static function trigger($event, $args = array()) {
		//make sure we have a hook to action
		if (! isset(self::$hooks[$event])) {
			return false;
		}

		//loop through our bound hooks
		foreach(self::$hooks[$event] as $hook) { 

			//check if we have a class::method combination
			if (is_string($hook) && strpos($hook, '::')) {
				
				//get our variables filled from the string
				list($class, $method) = explode('::', $hook);

				//add the new instance to an array
				$return[] = call_user_func_array(array(new $class, $method), $args);

				//move on
				continue;
			
			//if we passed in an array with an object and a method name
			} elseif(is_object($hook[0])) {  

				//assign our indexes	
				list($class, $method) = $hook;

				//pass the instance
				$return[] = call_user_func_array(array($class, $method), $args);

				//move on
				continue;
			} 
		}

		return $return;
	}
}