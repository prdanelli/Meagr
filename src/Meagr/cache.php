<?

/**
* Cache
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class cache {

	//our key for this instance
	private $key = ''; 

	//the cache directory
	private $dir = ''; 

	//our isntance wrapper
	private static $instance = null;

	//the duration the cache is valid for
	public $duration; 

	//our content, either from a file, or generated
	private $body; 

	//our cache exists flag - false by default
	public $exists = false;

	//our config settings
	public $config = array();

	//the time we're currently at
	private $timestamp; 

	//our current folder perms (for debugging)
	private $dir_perms;

	//our language code
	public $lang_code; 


	/**
	* create and return our instance
	*
	* @param key string Our instance key
	*
	* @return object
	*/
	public static function init($key = 'default') {

	    //create a multuton instance
	    if (is_null(self::$instance[$key])) {
	        self::$instance[$key] = new self($key);
	    }

	    return self::$instance[$key];
	}	


	/**
	* set config keys and isntance wide settings
	*
	* @param key string Our instance key
	*
	* @return void
	*/
	private function __construct($key) {
		
		//our instance and distingushing key
		$this->key = $key; 

		//if our key is the default
		if ($this->key == 'default') {

			//generate our cache key from the full url
			$this->key = md5(Uri::full()); 				
		}
	
		//get our cache settings
		$this->config = Config::settings('cache');

		//get our cache config from both system and app settings
		$this->duration = $this->config['duration'];

		//if we are in debug mode, force the duration to 0 (as in off)
		$this->duration = (IS_DEBUG === true) ? $this->duration : 0;		
		
		//create our cache path
		$this->dir = PUBLIC_PATH . rtrim($this->config['dir'], '/') . '/'; 

		//set our language code
		$this->lang_code = (defined('LANG_CODE') ? '-' . LANG_CODE : '');

		//create our filename default and append a language code to the filename if it exists
		$this->file = $this->checkDir() . $this->key . $this->lang_code . '.' . ltrim($this->config['file_ext'], '.');

		//has our file type been passed into the init: self::init('css')->concat($array);
		if (isset($this->config[$this->key])) {

			//check the cache dir is in place and add our file name
			$this->file = $this->checkDir() . $this->config[$this->key]; 
		} 	

		//for debugging
		$this->timestamp = Timer::mtime();
	}	


	/**
	* check if the cache exists
	*
	* @param duration int The duration the cache should have existed for
	*
	* @return bool
	*/
	public function exists($duration = null) {

		//if our duration isnt passed in, use the system default
		if (is_null($duration)) {
			$duration = $this->duration; 
		}

		//check that our file exists, or needs to be rebuilt
		if (! file_exists($this->file)) {
			$this->exists = false;
			return $this->exists;
		}

		//check if the cache file already exists and the modified date is within our cache duration
		if (self::cacheValid($this->file, $this->duration)) {

			//if so, set the flag for the instance
			$this->exists = true;

		//otherwise	
		} else {

			//tell the instance it will hve to generate the content
			$this->exists = false;
		}

	    //return this for method chaining
	    return $this->exists;		
	}


	/**
	* get the cache with the key
	*
	* @return mixed[html|bool]
	*/
	public function get() {

		if ($this->exists) { 

			//return our html var from the cache file contents
		    return file_get_contents($this->file);
		} 

		//return false as we have no content
		return false;
	}


	/**
	* set the cache with key
	*
	* @param content string Our content string which we want to cache
	*
	* @return object
	*/
	public function set($content) {

		//add a note to the start of the content before its cached
		$this->body = "<!-- Cached copy, generated ". date('H:i:s') ." --> \n\n " . $content;

		//write the content to our file
		file_put_contents($this->file, $this->body);

		//set the cache exists flag
		$this->exists = true;

		//return this for chaining
		return $this;
	}


	/**
	* set the duration of the instance cache duration
	*
	* @param duration int The duration we want our cache to last
	*
	* @return object
	*/
	public function setDuration($duration = null) {
		
		//if our duration isnt passed in, use the system default
		if (! is_null($duration)) {
			$this->duration = $duration; 
		}

		//if we are in debug mode, force the duration to 0 (as in off)
		if (IS_DEBUG === true) {
			$this->duration = 0;
		}

		return $this;
	}	


	/**
	* combine an array of files into one file
	*
	* @param array array An array of absolute file locations, which we want to combine into a single file
	*
	* @return object
	*/
	public function concat($array) { 

		//make sure we have work to do
		if (empty($array) or ! is_array($array)) {
			return false;
		}

		//check our file is within the cache duration and none of the files have been updated
		if (self::cacheValid($this->file, $this->duration) and ! self::filesUpdated($this->file, $array)) {

			//if our files havent been updated and our cache time is valid, swap the file path to a uri
			$this->file = self::pathToUri($this->file); 

			//return self to skip the following steps
			return $this;
		}

		//create the new handle with our css file
		$handle = fopen($this->file, 'w'); 

		//loop the array of css files, adding them to the cache file
		foreach($array as $concat_file) {

			//check if we have been passed in URLs which we cant use, so swap
			$concat_file = self::uriToPath($concat_file);

			if (! file_exists($concat_file)) {
				continue;
			}

			$content = " \n\n /* ====== ". self::pathToUri($concat_file) ." ====== ". Timer::mtime() ." */ \n\n " . file_get_contents($concat_file); 
			fwrite($handle, $content);				
		}

		//close the conntection
		fclose($handle);

		//return the location of the file we've been working on
		$this->file = self::pathToUri($this->file);
		return $this;		
	}


	/**
	* check if a file is within the cache time passed as duration
	*
	* @param file string the absolute file location
	* @param duration int The time to compare against the files age
	*
	* @return bool
	*/
	static function cacheValid($file, $duration) {
		return file_exists($file) && (time() - $duration < filemtime($file)); 
	}


	/**
	* check if any of the files have been updated since the cache was created
	*
	* @param check_file string The absolute file location of the file we are checking
	* @param files array An array of absolute file locations that we are checking against our check_file
	*
	* @return bool
	*/
	static function filesUpdated($check_file, $files) {
		
		//set our flag
		$updated = false; 

		//the time that we will be comparing against
		$check_file = filemtime($check_file);

		//do we need to do anything
		if (is_null($files)) {
			return false;
		}

		//loop our file array
		foreach($files as $file) {

			//check the modified time of each file against our cache file
			if (filemtime($file) > $check_file) {
				return true;
			}
		}
	}


	/**
	* change a path to a uri
	*
	* @param path string The path which is to be turned into a publically accessibly URI
	*
	* @return string
	*/
	static function pathToUri($path) {
		return str_replace(PUBLIC_PATH, PUBLIC_URL, $path);
	}


	/**
	* change a uri to a path
	*
	* @param  publically accessibly URI to be turned into an absolute path
	*
	* @return string
	*/
	static function uriToPath($path) {
		return str_replace(PUBLIC_URL, PUBLIC_PATH, $path);
	}	


	/**
	* check if the cache dir exists
	*
	* @return string
	*/
	private function checkDir() {

		//if the instance cache dir is missing
		if (! is_dir($this->dir)) { 

			//make it...
			mkdir($this->dir); 

			//...and set the mode
			chmod($this->dir, 0755);
		}

		$this->dir_perms = substr(sprintf('%o', fileperms($this->dir)), -4); 
		return $this->dir;
	}
}