<?

/**
* validate
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Validate {

	static $prefixes = array('is', 'not', 'valid');

	/**
	* validate data against a series of rules 
	*
	* @param rule mixed[string|closure|array] The valiaation rule we wish to check
	* @param checks mixed[string|array] The checks we wish to perform
	* @param value string The value which is to be validated
	*
	* @return mixed
	*/
	public static function validate($rule, $checks, $value) { 

		//clear our errors array
		$errors = array();

		//make sure we have a rule
		if ((string) trim($rule) == '') { 
			return false;
		}

		//checks arent empty 
		if (empty($checks)) { 
			return false;
		}

		//if the rules arnt an array, make them one
		if (! is_array($checks)) { 
			$checks = array($checks);
		}

		//first check are have a supported rule and its available
		if (! in_array($rule, self::$prefixes)) { 
			return false;
		}			

		if (! is_callable(array(self, $rule))) { 
			return false;
		}

		// loop our rules
		foreach($checks as $type => $message) { //p($checks);

			//incase we get blanks or non associative array
			if (empty($type) or ! is_string($type)) {
				continue;
			}
			
			//run the check and if false is returned, store the error message
			if (! self::$rule($type, $value)) {
				$errors[] = $message;
			}
		}

		//if we have errors, return them
		if ($errors) {
			return $errors;
		}

		//otherwise return false (which means no errors oddly)
		return false;
	}


	/**
	* used to check if our value IS something, string, int, empty etc 
	*
	* @param type string The type of IS check to be performed
	* @param value mixed[string|int] The value to be performed
	*
	* @return mixed
	*/
	protected static function is($type, $value) {
		switch($type) {
			case 'int' :
				return (is_int($value)) ? $value : false;
				break;
			case 'string' : 
				return (is_string($value)) ? $value : false;
				break;
			case 'empty' :
				return (empty($value)) ? $value : false;
				break;	

		}
	}


	/**
	* used to check if our value is NOT something, string, int, empty etc 
	*
	* @param type string The type of is NOT check to be performed
	* @param value mixed[string|int] The value to be performed
	*
	* @return mixed
	*/
	protected static function not($type, $value) {
		switch($type) {
			case 'int' :
				return (! is_int($value)) ? $value : false;
				break;
			case 'string' : 
				return (! is_string($value)) ? $value : false;
				break;
			case 'empty' :
				return (! empty($value)) ? $value : false;
				break;	
		}
	}	


	/**
	* used to check if our value is a VALID something, string, int, empty, email etc 
	*
	* @param type string The type of is valid check to be performed
	* @param value mixed[string|int] The value to be performed
	*
	* @return mixed
	*/
	protected static function valid($type, $value) {
		switch($type) {

			case "html" : 
				return filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
				break;
			case "url" : 
				return filter_var($value, FILTER_SANITIZE_URL);
				break;
			case "int" : 
				return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
				break;
			case "email" : 
				return filter_var($value, FILTER_VALIDATE_EMAIL);
				break;
			case "gmail" : 
				if(filter_var($value, FILTER_VALIDATE_EMAIL) and stripos($value, 'gmail.com')) {
					return true;
				}
				break;				
			case "string" : 
			default :
				return filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
				break;
		}
	}
}
