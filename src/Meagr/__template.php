<?

/**
* class template
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class __template {

	private static $instance;

	private $key;

	/**
	* multiton 
	*
	* @param 
	*
	* @return 
	*/	
	public static function init($key = 'default') {

	    //create a multuton instance
	    if (is_null(self::$instance[$key])) {
	        self::$instance[$key] = new self($key);
	    }

	    return self::$instance[$key];
	}	

	//singleton instantiation
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }	

}