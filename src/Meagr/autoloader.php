<?

/**
* Autoloader
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Autoloader {

	public static function load($classname) {

		//we need debug, so include it
    	if (! class_exists('Debug')) { 
    		require_once CORE_PATH . '/debug.php';
    	}

	    $classname = ltrim($classname, '\\');
	    $filename  = '';
	    $namespace = '';

	    if ($lastNsPos = strrpos($classname, '\\')) {
	        $namespace = substr($classname, 0, $lastNsPos);
	        $classname = substr($classname, $lastNsPos + 1);
	    	$filename = SITE_PATH . '/' . strtolower(str_replace('\\', DS, $namespace) . DS);
	    }
	
	    $filename .= strtolower($classname) . '.php'; 
	    if (file_exists($filename) and ! class_exists(basename($classname))) { 

			Debug::init('file')->add(array('message' => str_replace(SITE_PATH, '', $filename),
										'filesize' => filesize($filename),
										'class' => __METHOD__, 
										'status' => 'success', 
										'backtrace' => Debug::backtrace()));
	    	
    		require_once $filename;	 
    		return;	     	
	    } 	

		Debug::init('file')->add(array('message' => 'Failed to load ' . $filename,
										'class' => __METHOD__, 
										'status' => 'error', 
										'backtrace' => Debug::backtrace()));

		return $filename !== false;
	}
}

spl_autoload_register(__NAMESPACE__ . '\Autoloader::load');
\Meagr\Debug::init('log')->add(array('message' => 'Register autoloader',
										'class' => null, 
										'status' => 'success', 
										'backtrace' => Debug::backtrace()));