<?

/**
* Error
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Error {

	/**
	* CATCHABLE ERRORS
	*
	* @param code string
	* @param message string
	* @param file string
	* @param line string
	*
	* @return void
	*/
    public static function captureNormal($code, $message, $file, $line){
        // Insert all in one table
        $error = array( 'type' => $number, 'message' => $message, 'file' => $file, 'line' => $line );
        // Display content $error variable

		$date = date('M d, Y h:iA');
		$severity = 'Error';
		include SITE_PATH . '/public/templates/errors.php';
    }
 

	/**
	* EXTENSIONS
	*
	* @param exception object
	*
	* @return void
	*/    
    public static function captureException($exception){
		$message = $exception->getMessage();
		$code = $type = $exception->getCode();
		$file = $exception->getFile();
		$line = $exception->getLine();
		$backtrace = $exception->getTraceAsString();
		$date = date('M d, Y h:iA');
		$severity = 'Uncaught Exception';
		include SITE_PATH . '/public/templates/errors.php';
		die;
    }

	/**
	* UNCATCHABLE ERRORS
	*
	* @return mixed[void|bool]
	*/     
    public static function captureShutdown(){
        $error = error_get_last(); 

        if ($error['type'] == E_ERROR || $error['type'] == E_USER_ERROR) {
			$message = $error['message'];
			$code = $type = $error['type'];
			$file = $error['file'];
			$line = $error['line'];
			$date = date('M d, Y h:iA');
			$severity = 'Error';
			include SITE_PATH . '/public/templates/errors.php';
			die;
        } else { 
        	return true; 
        }
    }
}

//set up custom error handling...
set_error_handler(array("\Meagr\Error", 'captureNormal'), E_ERROR);
set_exception_handler(array("\Meagr\Error", 'captureException'));

//prouduces alot of errors
register_shutdown_function(array("\Meagr\Error", 'captureShutdown'));