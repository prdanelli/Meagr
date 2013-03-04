<? 

/**
* Arr
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/	

namespace Meagr;

class Arr {


	/**
	* Gets a dot-notated key from an array
	*
	* @param array array 
	*
	* @return 
	*/
	public static function get($array, $key){
		if ( ! is_array($array) and ! $array instanceof \ArrayAccess) {
			throw new MeagrException('First parameter must be an array or ArrayAccess object.');
		}

		if (is_null($key)) {
			return $array;
		}

		if (is_array($key)) {
			$return = array();
			foreach ($key as $k) {
				$return[$k] = static::get($array, $k);
			}
			return $return;
		}

		foreach (explode('.', $key) as $key_part) {
			if (($array instanceof \ArrayAccess and isset($array[$key_part])) === false) {
				if ( ! is_array($array) or ! array_key_exists($key_part, $array)) {
					return false;
				}
			}

			$array = $array[$key_part];
		}

		return $array;
	}

	//set the array value
	public static function set(& $array, $key, $value = null) {
		//if nokey is passed
		if (is_null($key)) {
			return $array;
		}

		//if the key provided is an array
		if (is_array($key)) {

			//set an array of key to value pairs
			foreach ($key as $k => $v) {
				static::set($array, $k, $v); 
			}

		//otherwise
		} else {	
			//check for dot notation
			$keys = explode('.', $key);

			//loop through the keys 
			while (count($keys) > 1) {
			
				//take the first array element as $key
				$key = array_shift($keys);

				//if the value of key isnt an index inside the array
				if ( ! isset($array[$key]) or ! is_array($array[$key])) {
					//the key is set to a blank array
					$array[$key] = array();
				}

				//array is assigned by reference to the array key, blank or not
				$array =& $array[$key];
			}

			//hmm///
			$array[array_shift($keys)] = $value;
		}
	}



	// Unsets dot-notated key from an array
	public static function delete(& $array, $key) {
		if (is_null($key)) {
			return false;
		}

		if (is_array($key)) {
			$return = array();
			foreach ($key as $k) {
				$return[$k] = static::delete($array, $k);
			}
			return $return;
		}

		$key_parts = explode('.', $key);

		if ( ! is_array($array) or ! array_key_exists($key_parts[0], $array)) {
			return false;
		}

		$this_key = array_shift($key_parts);

		if ( ! empty($key_parts)) {
			$key = implode('.', $key_parts);
			return static::delete($array[$this_key], $key);

		} else {
			unset($array[$this_key]);
		}

		return true;
	}
}