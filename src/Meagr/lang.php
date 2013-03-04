<?

/**
* Language
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Language {

	//our instance wrapper
	private static $instance;

	//our language code
	private $lang;

	//our translations take from the language file
	public $translations = array();


	/**
	* multiton instance
	*
	* @param lang string Our current language code
	*
	* @return object
	*/
	public static function init($lang = 'EN') {

	    //create a multuton instance
	    if (is_null(self::$instance[$lang])) {
	        self::$instance[$lang] = new self($lang);
	    }

	    return self::$instance[$lang];
	}	


	/**
	* prevent direct instantition
	*
	* @param lang string Our current language code
	*
	* @return void
	*/
	private function __construct($lang) {

		$this->config = Config::settings('language');

		//maintain our language code
		$this->lang = $lang;

		//load our translations file
		$this->load();
	}


	/**
	* method is the name of the config class method we want to merge with the app config
	*
	* @return mixed[object|bool]
	*/
	private function load() {

		//set our first possible location
		$paths = array(
					MODULE_PATH . '/config/', 
					MODULE_PATH . '/' . SITE_SUBDOMAIN .'config/' . strtolower(ENVIRONMENT) . '/language/'
				);

		//loop our paths
		foreach($paths as $path) { 

			//append the code and trailing filetype
			$path .= strtolower($this->lang) . '.php'; 

			//check if the file exists
			if (file_exists($path)) { 

				//get it...
				require $path;

				//...and add the language array to our instance
				$this->translations = $language;

				//break here
				return $this;
			}				 
		}

		//if we got here, we couldnt find the file we want, 
		//so first check that the default isnt the nissing file (or we cause in infinate loop)
		if ($this->lang !== $this->config['default']) {
			
			// so fallback to the default
			$this->lang = $this->config['default'];

			//and run aagin
			$this->load();
		}

		//if we got here, shit went bad
		return false;
	}	


	/**
	* set a keyword
	*
	* @param key string The key to the language value 
	* @param value string The language translation to be retrived
	*
	* @return object
	*/
	public function set($key, $value) {
		$this->translations[$key] = $value;
		return $this;
	}


	/**
	* get a keyword string
	*
	* @param key string The key to the language value 
	* @param default string The value to be returned if there is none set
	*
	* @return mixed[string|bool]
	*/
	public function get($key, $default = null) {
		return ($this->translations[$key]) ? : ($default) ? : false;
	}


	/**
	* process the array of translations of the content 
	*
	* @param callback mixed[string|closure|array] The method of filtering the content
	*
	* @return object
	*/
    function filter($callback = null) {

        //if the body isnt set yet, set it
        if (empty($this->translations)) {
            $this->translations();
        }

        // if the closure is null, then just return
        if (is_null($callback)) {
            return $this;
        }

        //if we've been passed a function name as a string
        if (is_string($callback) and is_callable($callback)) {
            $this->translations = $callback($this->translations);
            return $this;
        }

        //if we've been passed a closure function
        if ($callback instanceof \Closure) {
            $this->translations = $callback($this->translations);
        }

        //if we've been passed an array of class and method names
        if (is_array($callback) and is_callable($callback)) {
            $this->translations = call_user_func_array($callback, $this->translations);
        }

        //return
    	return $this;
    }
}