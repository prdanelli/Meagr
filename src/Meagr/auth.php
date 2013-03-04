<?

/**
* Auth
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr;

class Auth {


	/**
	* check the session exists
	*
	* @return bool
	*/
	static function check() {
		//get our encrypted session data 
		$check = Input::session('member'); 
		
		//if the check wasnt found, it returns '-5' (no idea why)
		//check for a positive int and return
		if ((int) $check > 0) {
		\Meagr\Debug::init('log')->add(array('message' => 'Auth check ok',
										'class' => __METHOD__, 
										'status' => 'success', 
										'backtrace' => Debug::backtrace()));
			return (int) $check;

		//otherwise the user isnt logged in	
		} else {
		\Meagr\Debug::init('log')->add(array('message' => 'Auth check failed',
										'class' => __METHOD__, 
										'status' => 'error', 
										'backtrace' => Debug::backtrace()));
			return false;
		}
	}


	/**
	* create the users session
	*
	* @return void
	*/
	static function create($id) { 
		Input::session('member', $id); 

		return;
	}


	/**
	* return the current session
	*
	* @return mixed[object|bool]
	*/
	static function current() {

		//if the user is logged in, they check() will return their ID as an INT
		//otherwise bool false
		$check = self::check();
		if (! is_int($check) and $check > 0) {
			return false;
		}

		//get our config memeber settings (table / column names)
		$config = Config::settings('member'); 
		
		//get the user - but we havent specified a table at this point
		$model = Model::init()->where('id', $check); 
		
		//so force the table here
		$model->table = $config['table'];
		
		//now we can execute and request just the first result
		$result = $model->go(1); 
		
		//check we have an object back and its not empty
		if ($result instanceOf Model and ! empty($result)) {
			//and return to muma.
			return $result;
		}

		//if we got here, they're not from around here
		return false;
	}


	/**
	* destroy the current session
	*
	* @return void
	*/
	static function destroy(){
		unset($_SESSION[ID]['member']);
	}
}