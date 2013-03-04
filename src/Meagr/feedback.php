<?

/**
* Feedback
*
*
* @package Feedback
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Feedback {

	//our instance wrapper
	private static $instance;

	//the type of feedback, also our instance key
	private $type;

	//our feeback prefix
	private $prefix = 'feedback';

	//the default
	private $default = 'default';


	/**
	* multiton instantiation
	*
	* @param type string The name of the type of feedback we are giving
	*
	* @return object
	*/
	public static function init($type = 'errors') {

	    //create a multuton instance
	    if (is_null(self::$instance[$type])) {
	        self::$instance[$type] = new self($type);
	    }

	    return self::$instance[$type];
	}		


	/**
	* set our type var
	*
	* @param type string The name of the type of feedback we are giving, passed from self::init()
	*
	* @return object
	*/
	private function __construct($type) {

		//keep our type
		$this->type = $type;
	}


	/**
	* set a message
	*
	* @param message string The message which is being set as feedback
	* @param group string The group, could the id of the element, or the form, or however the feedback is seperated
	*
	* @return object
	*/
	function set($message, $group = null) { 

		//looks something like [meagr][feedback][errors][form] => array()
		$_SESSION[ID][$this->prefix][$this->type][($group ? : $this->default)][] = $message;
		return $this;
	}


	/**
	* get a message array
	*
	* @param group string The group, could the id of the element, or the form, or however the feedback is seperated
	*
	* @return object
	*/
	function get($group = null) {

		//get our values
		$return = $_SESSION[ID][$this->prefix][$this->type][($group ? : $this->default)]; 

		//unset the slots so we dont store tons of crap
		unset($_SESSION[ID][$this->prefix][$this->type][($group ? : $this->default)]);

		//return the array
		return $return;
	}


	/**
	* check if feedback exists, if group is entered, for only that group, otherwise all feedback
	*
	* @param group string The group, could the id of the element, or the form, or however the feedback is seperated
	*
	* @return object
	*/
	function exists($group = null) {
		$feedback = $_SESSION[ID][$this->prefix][$this->type][($group ? : $this->default)]; 
		if (! empty($feedback)) {
			return true;
		}

		return false;
	}


	/**
	* output the feedback
	*
	* @param group string The group, could the id of the element, or the form, or however the feedback is seperated
	*
	* @return object
	*/
	function show($group = null) {

		//clear the string to start with
		$string = '';

		//check if we have any feedback
		if ($this->exists($group)) {

			//get it, its also cleared here
			$feedback = $this->get($group);

			//loop through and create our string
			foreach($feedback as $item) {
				$string .= '<div class="feedback ' . $this->type . '">'. $item .'</div>';
			}

			return $string;
		}
		
		return false;
	}
}