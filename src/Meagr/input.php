<?

/**
* Input
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr;

class Input {


	/**
	* Get or set our $_GET var via . sperated array access
	*
	* @param key string A period seperated string of array keys and values
	*
	* @return array
	*/
	static function get($key) {
		if (! is_null($value)) {
			Arr::set($_GET, $key, $value);
			return;
		}

		return Arr::get($_GET, $key);
	}


	/**
	* Get or set our $_POST var via . sperated array access
	*
	* @param key string A period seperated string of array keys and values
	* @param value string The value to be set
	*
	* @return array
	*/
	static function post($key, $value = null) {
		if (! is_null($value)) {
			Arr::set($_POST, $key, $value);
			return;
		}

		return Arr::get($_POST, $key);
	}


	/**
	* Get or set our $_SESSION var via . sperated array access
	*
	* @param key string A period seperated string of array keys and values
	* @param value string The value to be set
	*
	* @return array
	*/
	static function session($key, $value = null) {
		if (! is_null($value)) { 
			Arr::set($_SESSION, ID . '.' . $key, $value);
		}

		return Arr::get($_SESSION, ID . '.' . $key);
	}


	/**
	* Get or set our $_COOKIE var via . sperated array access
	*
	* @param key string A period seperated string of array keys and values
	* @param value string The value to be set
	*
	* @return string
	*/
	static function cookie($key, $value = null) {
		$key = ID . '_' . $key; 

		if (! is_null($value)) {
			//set the cookie expirey to 24 hours time
			setcookie($key, Encrypt::encrypt($value), time() + (3600 * 24));
			return;
		}

		return Encrypt::decrypt($_COOKIE[$key]);
	}

	/**
	 * Fetch an item from the SERVER array
	 *
	 * @param   string  The index key
	 * @param   mixed   The default value
	 * @return  string|array
	 */
	public static function server($index = null) {
		return (is_null($index) and func_num_args() === 0) ? $_SERVER : Arr::get($_SERVER, strtoupper($index));
	}	
}