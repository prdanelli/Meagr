<?

/**
* View
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class View {

	/**
	* get the correct partial for the app, 
	* or the site, checking several locations
	*
	* @param partialname string The name of the partial required
	* @param data array Any data that is required within the partial
	*
	* @return html
	*/	
	static function partial($partialname, $data = null) {

		//set the detault
		$partial_data = false;

		//get the class that the request is coming from
		$calling_class = get_called_class(); 
		
		//get the class name and swap \ for / as well as controllers for views to check HMVC 
		$class = strtolower(str_replace('controllers', 'views', str_replace('\\', '/', $calling_class))); 

		//check for the default mvc location
		$locations = array(
				MODULE_PATH . '/views/partials/' . $partialname . '.php',
				PUBLIC_PATH . '/templates/views/partials/' . $partialname . '.php', 
				SITE_PATH . '/' . $class . '/partials/' . $partialname . '.php'
			);

		foreach($locations as $location) {
			if (is_file($location)) {
				$partial = $location;
				break;
			}
		}

		//incase we passed in a full file path
		if (! is_file($partial)) {
			$partial = $partialname;
		}

		if (is_file($partial)) { 

			//extract the data passed to us
			if (!empty($data) and is_array($data)){
				extract($data);
			}

			ob_start();
			require $partial;
			$partial_data = ob_get_clean();


			Debug::init('log')->add(array('message' => 'Including partial: ' . $partial,
											'class' => __METHOD__, 
											'status' => 'success', 
											'backtrace' => Debug::backtrace()));					
			return $partial_data; 
		} 

		Debug::init('log')->add(array('message' => 'Failted to including partial: ' . $partial,
										'class' => __METHOD__, 
										'status' => 'error', 
										'backtrace' => Debug::backtrace()));
		// echo 'couldnt include';
		return false;									
	}

	/**
	* accepts $viewname as a php file name, 
	* or alternatively module::filename could be used to specify the target app 
	*
	* @param viewname string The name of the view file
	* @param data array Extra data that is to be passed to the view
	* @param template string The template file to be used to render the required view
	*
	* @return html string
	*/
	static function view($viewname, $data = null, $template = 'default') {

		//check for the default mvc location
		$view = MODULE_PATH . '/views/' . $viewname . '.php'; 
		if (! is_file($view)) {

			//get the class that the request is coming from
			$calling_class = get_called_class(); 

			//get the class name and swap \ for / as well as controllers for views to check HMVC 
			$class = strtolower(str_replace('controllers', 'views', str_replace('\\', '/', $calling_class))); 	
			$view = SITE_PATH . '/' . $class . '/' . $viewname . '.php';
		}

		//specify a particular app to use by searching for :: in the viewname
		// so member::viewname would look in ... app/member/views/viewname
		if (! is_file($view) and strpos($viewname, '::')) {
			$segments = explode('::', $viewname); 
			$class = $segments[0]; 
			$viewname = $segments[1];
			$view = MODULE_PATH . '/' . strtolower($class) . '/views/' . strtolower($viewname) . '.php';
		}

		if (is_file($view)) { 
			if (!empty($data) and is_array($data)){
				extract($data);
			}

			ob_start();
			require $view;
			$content = ob_get_contents();
			ob_end_clean();

			Debug::init('log')->add(array('message' => 'Including view: ' . $view,
											'class' => __METHOD__, 
											'status' => 'success', 
											'backtrace' => Debug::backtrace()));	

			//render the template file with the compiled view data
			self::template($template, $content);
		
		//if the view file cannot be found
		} else {
			throw new MeagrException('The requested view file "'. $view .'" could not be found');
		} 
	}	

	/**
	* get the template and add in the partial 
	*
	* @param template string The template file we wish to include
	* @param content string/html The extra data we wish to pass to the template
	*
	* @return void
	*/
	static function template($template, $content = null) { 
		require SITE_PATH . '/public/templates/' . $template . '.php';
	}
}