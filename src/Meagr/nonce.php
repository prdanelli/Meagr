<?

/**
* Nonce
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Nonce {

	/**
	* create a URL query string which can be appended to links
	* This method creates a key / value pair for a url string
	*
	* @param action string The name of action we want to create
	* @param user string An additional param to make the nonce more complicated
	*
	* @return string
	*/
	static function string($action = '', $user = ''){
		return '_nonce=' . self::create($action, $user);
	}


	/**
	* create an input for forms 
	* This method creates an nonce for a form field
	*
	* @param action string The name of action we want to create
	* @param user string An additional param to make the nonce more complicated
	*
	* @return string
	*/
	static function input($action = '', $user = ''){
		return "<input type='hidden' name='_nonce' value='" . self::create($action . $user) . "' />";
	}


	/**
	* This method creates an nonce. It should be called by one of the previous two functions.
	*
	* @param action string The name of action we want to create
	* @param user string An additional param to make the nonce more complicated
	*
	* @return string
	*/
	static function create($action = '', $user = ''){
		return substr(self::hash($action . $user), -12, 10);
	}


	/**
	* This method validates an nonce
	*
	* @param nonce string The nonce to be valiated
	* @param action string The name of action we want to create
	* @param user string An additional param to make the nonce more complicated
	*
	* @return string
	*/
	static function valid($nonce, $action = '', $user = ''){
		// Nonce generated 0-12 hours ago
		if (substr(self::hash($action . $user), -12, 10) == $nonce) {
			return true;
		}

		return false;
	}


	/**
	* This method generates the nonce timestamp
	*
	* @param action string The name of action we want to create
	* @param user string An additional param to make the nonce more complicated
	*
	* @return string
	*/
	static function hash($action = '', $user = '') { 
		$i = ceil(time() / (MEAGR_NONCE_DURATION / 2));
		return md5($i . $action . $user . $action);
	}
}