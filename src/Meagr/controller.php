<?

/**
* Controller
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Controller {


	/**
	* The default method which is run before each other method if no class __before() method is found 
	*
	* @return mixed
	*/
	public static function __before() {

		\Meagr\Debug::init('log')->add(array('message' => 'Load Controller Before',
										'class' => __METHOD__, 
										'status' => 'success', 
										'backtrace' => Debug::backtrace()));
	}


	/**
	* The default method which is run after each other method if no class __after() method is found 
	*
	* @return mixed
	*/
	public static function __after() { 
		\Meagr\Debug::init('log')->add(array('message' => 'Load Controller After',
										'class' => __METHOD__, 
										'status' => 'success', 
										'backtrace' => Debug::backtrace()));
	}


	/**
	* The default index method 
	*
	* @return mixed
	*/	
	public static function GET_index() {
		View::view('home', array('controller' => __METHOD__));
	}


	/**
	* The default 404 method 
	*
	* @return mixed
	*/
	public static function GET_404(){
		View::view('404', array('controller' => __METHOD__, 'backtrace' => Debug::backtrace()));
	}
}