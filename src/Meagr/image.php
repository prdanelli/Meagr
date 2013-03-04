<?

/**
* Image
* 
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*  
*/

namespace Meagr; 

use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Filter; 

class Image {

	//the class instance wrapper
	private static $instance;

	//the imagine instance wrapper
	private $imagine;

	//the image intance which is to be manipulated
	private $image; 

	//the file which we want to dealw ith
	public $filename; 

	//our tranform wrapper
	private $transform; 

	//the actual name of the filename (basename)
	public $base;

	//are we making a thumbnail
	private $is_thumb = false;

	//are we making a collage
	private $is_collage = false;

	//the thumbnail name and path
	private $thumb_filename; 

	//the name of the file we will save
	private $save_filename;

	//the name of the library currently being used
	private $library = '';

	//if we create a new image, we might need these
	public $box_width; 
	public $box_height;

	//the time an image is valid before needing rebuilding, or bool or on or off
	private $cache = false;

	//if cache is required, this flag tells all the methods to return immediately
	private $use_cache = false;

	//an array of image processing options
	private $options = array(
			'quality' => 50, 
			'resolution-x' => 150,
			'resolution-y' => 120,
			'flatten' => false            
		);

	//which library we are using
	private $is_imagick = false;
	private $is_gd = false;


	/**
	* init our class instance 
	*
	* @return object
	*/  
	public static function init() {
		return new self;
	}   


	/**
	* init our class instance 
	*
	* @return void
	*/  
	function __construct() {

		if (! class_exists('\Imagine\Imagick\Imagine')) {
			//include the imagine package
			require_once 'phar://'.VENDOR_PATH.'/imagine/imagine.phar';
		}

		//check if the image magic extension is availble
		if (extension_loaded('imagick')) {
			$this->imagine = new \Imagine\Imagick\Imagine();
			$this->is_imagick = true;
		} else {
			$this->imagine = new \Imagine\Gd\Imagine();
			$this->is_gd = true;
		}

		//retrireve the image config
		$this->config = Config::settings('image');
	}


	/**
	* create a new image instance with our file as well as set instance options
	*
	* @param filename string The URL / path to the file
	*
	* @return object
	*/
	function open($filename = null) { 

		if (! is_null($filename)) {
			$this->filename = $filename;
		}

		if (strpos($this->filename, PUBLIC_URL)) {
			$this->filename = str_replace(PUBLIC_URL, PUBLIC_PATH, $this->filename); 
		}

		try{
			//create our image instance from the open filename 
			$this->image = $this->imagine->open($this->filename);

			//set our filename for when files are saved
			$this->base = basename($this->filename);
			$this->save_filename = $this->chkdir($this->config['cache-dir']) . '/' . $this->base;

			//exit here
			return $this;

		} catch(\Imagine\Exception\InvalidArgumentException $e) {
			\Meagr\Debug::init('log')->add(array('message' => $e->getMessage(),
											'class' => __METHOD__, 
											'status' => 'error', 
											'backtrace' => Debug::backtrace()));    
			return $this;

		} catch(\Imagine\Exception\RuntimeException $e) {
			\Meagr\Debug::init('log')->add(array('message' => $e->getMessage(),
											'class' => __METHOD__, 
											'status' => 'error', 
											'backtrace' => Debug::backtrace()));            
			return $this;
		}
	}


	function text($text, $capture_options){
		return $this;

	}

 
	/**
	* set width / height
	*
	* @param w int The width of the image
	* @param h int The height of the image
	*
	* @return object
	*/   
	function resize($w = 150, $h = 100) {

		//are we using the cached image
		if ($this->use_cache) {
			return $this;
		}

		if (is_null($this->image)) {
			return $this;
		}

		$this->image->resize(new Box($w, $h));
		return $this;
	}

	
	/**
	* Crop the image, optionally at specific coordinates
	*
	* @param w int The width of the cropped image
	* @param h int The height of the cropped image
	* @param x int The X coordinate the image should start at
	* @param y int The Y coordinate the image should start at
	*
	* @return object
	*/
	function crop($w = 100, $h = 100, $x = 0, $y = 0) {

		//are we using the cached image
		if ($this->use_cache) {
			return $this;
		}

		$this->image->crop(new Point($x, $y), new Box($w, $h));
		return $this;
	}
 

	/**
	* Set the angle of the rendered image
	*
	* @param degrees int The angle of the image after rendering
	*
	* @return object
	*/    
	function rotate($degrees = 180) {

		//are we using the cached image
		if ($this->use_cache) {
			return $this;
		}

		$this->image->rotate($degrees);
		return $this;
	}
 

	/**
	* function set flatten to false for gif/png
	*
	* @param bool bool Should the image be flattened
	*
	* @return object
	*/    
	function flatten($bool) {
		$this->setOption('flatten', $bool);
		return $this;
	}
 

	/**
	* function set quality
	*
	* @param percent int The quality of the rendered image out of 100
	*
	* @return object
	*/   
	function quality($percent) {
		$this->setOption('quality', $percent);
		return $this;
	}

 
	/**
	* Set the X resolution - NOT supported in GD Library
	*
	* @param pixel_density int The density of pixels along the X axis
	*
	* @return object
	*/
	function resolutionX($pixel_density) {
		$this->setOption('resolution-x', $pixel_density);
		return $this;
	}


	/**
	* Set the Y resolution - NOT supported in GD Library
	*
	* @param pixel_density int The density of pixels along the Y axis
	*
	* @return object
	*/
	function resolutionY($pixel_density) {
		$this->setOption('resolution-y', $pixel_density);
		return $this;
	}    


	/**
	* create a thumbnail of the image
	*
	* @param w int The width of the thumbnail
	* @param h int The height of the thumbnail
	* @param append_wh bool Should the width and height be appended to the image name on save
	*
	* @return object
	*/
	function thumb($w, $h, $append_wh = true) {

		//are we using the cached image
		if ($this->use_cache) {
			return $this;
		}

		//confirm we have a transform instance
		if (empty($this->transform)) {
			$this->transform = new \Imagine\Filter\Transformation();
		}

		//prepend the filename with the width and height if required
		$thumb_base = 'thumb-' . ($append_wh ? $w . '-' . $h . '-' : '') . $this->base;

		//set the thumbnail filename
		$this->thumb_filename = $this->chkdir($this->config['thumb-dir']) . '/' .  $thumb_base; 

		//set up the transform instance of imagine, passing in the thumbname filepath created above
		$this->transform->thumbnail(new Box($w, $h))->save($this->thumb_filename);

		//set the flag
		$this->is_thumb = true;         

		//return object
		return $this; 
	}


	/**
	* create a new empty image, this is filled by colours, text, collages etc
	*
	* @param w int The width of the thumbnail
	* @param h int The height of the thumbnail
	*
	* @return object
	*/
	function create($w = 100, $h = 100) {

		//are we using the cached image
		if ($this->use_cache) {
			return $this;
		}

		$this->image = $this->imagine->create(new \Imagine\Image\Box($w, $h));
		$this->box_height = $h;
		$this->box_width = $w;
		return $this;
	}


	/**
	* write an array of files into a collage of images and save the output
	*
	* @param array array The array of image urls or paths
	* @param w int The width of each of the collage images
	* @param h int The height of each of the collage images
	*
	* @return object
	*/
	function collage($array, $w = 100, $h = 100) {

		//are we using the cached image
		if ($this->use_cache) {
			return $this;
		}

		//if we dont have an array of images, exit
		if (! is_array($array) or empty($array)) {
			return $this;
		}

		//initial coordinates
		$x = 0; 
		$y = 0;

		//loop through the array
		foreach ($array as $path) {

			//see if we have a url location
			if (strpos($path, PUBLIC_URL)) {

				//if so, make sure we only work with a path
				$path = Cache::uriToPath($path);
			}

			//make sure the images exist
			if (! file_exists($path)) {
				continue;
			}
			
			//create a resize instance for each image path
			$img = self::init()
						 ->open($path)
						 ->resize($w, $h)
						 // ->cache(true)
						 ->save();       

			try{
				// paste photo at current position
				$this->image->paste($this->imagine->open($img), new Point($x, $y));
				
			} catch(Exception $e) {
				\Meagr\Debug::init('log')->add(array('message' => $e->getMessage(),
												'class' => __METHOD__, 
												'status' => 'error', 
												'backtrace' => Debug::backtrace())); 
			}

			// move position by 30px to the right
			$x += $w;

			//if we have reached the end of the line, move down a level
			if ($x >= $this->box_width) {
				$y += $h;
				$x = 0;
			}

			//if we have reached greater than the height of the box, we're done
			if ($y >= $this->box_height) {
				break; // done
			}
		}

		return $this;
	}


	/**
	* write the file from the options previously set
	*
	* @param filename string The filepath to save the image to, if different from the default
	* @param options array Additional options that will be merged with the defaults
	*
	* @return object
	*/
	function save($filename = null, $options = array()) {

		//are we using the cached image
		if ($this->use_cache) {
			return Cache::pathToUri($this->save_filename);
		}	

		//merge our options with those passed to the function
		if (! empty($options)) {
			$this->options = $options + $this->options;
		}

		//check if we have a filename specified
		if (! is_null($filename)) {
			$this->save_filename = $filename;
		}	

		//if we are creating a thumbnail, 'apply' and return
		if ($this->is_thumb) {
			$this->transform->apply($this->imagine->open($this->filename)); 
			return Cache::pathToUri($this->thumb_filename); 
		}

		try{
			//save our image and return the location of the image
			$this->image->save($this->save_filename, $this->options);

		} catch(Exception $e) {
			\Meagr\Debug::init('log')->add(array('message' => $e->getMessage(),
											'class' => __METHOD__, 
											'status' => 'error', 
											'backtrace' => Debug::backtrace())); 
		}

		return Cache::pathToUri($this->save_filename); 
	}


	/**
	* function set the cache limit or set bool false for caching off, or true to always use cache
	*
	* @param timelimit mixed[bool|int]
	*
	* @return mixed[object|string]
	*/   
	function cache($timelimit = false) {

		//set our cache time out or bool
	   $this->cache = $timelimit; 

	   //check the cache
	   $this->checkCache();

	   //return object
	   return $this;
	}


	/* ================ effects ================= */


	/**
	* invert the current photo
	*
	* @return object
	*/ 
	function negative() {

		//we cant use effects without imagick
		if (! $this->is_imagick) {
			return $this; 
		}

		$this->image->effects()->negative();
		return $this;
	}


	/**
	* black and white the image
	*
	* @return object
	*/ 
	function grayscale() {
		
		//we cant use effects without imagick
		if (! $this->is_imagick) {
			return $this; 
		}

		$this->image->effects()->grayscale();
		return $this;
	}	


	/**
	* function set the cache limit or set bool false for caching off, or true to always use cache
	*
	* @param gamma string 
	*
	* @return object
	*/   
	function gamma($gamma = '1.3') {
		
		//we cant use effects without imagick
		if (! $this->is_imagick) {
			return $this; 
		}

		$this->image->effects()->gamma($gamma);
		return $this;
	}


	/**
	* colourise the image, by passing a hex number
	*
	* @param hex string The hex number of the colour to be used
	*
	* @return object
	*/   
	function colourise($hex) {

		//we cant use effects without imagick
		if (! $this->is_imagick) {
			return $this; 
		}

		//make sure the hex has hash 
		if (! strpos($hex, '#')) {
			$hex = '#' . $hex;
		}

		$this->image->effects()->colorize(new \Imagine\Image\Color($hex));
		return $this;
	}


	/* ================ helpers ================= */


	/**
	* cache our images and check the created dates against any input cache timeout
	*
	* @return void
	*/
	private function checkCache() {

		//if there is no destination filename, we have nothing to cache
		if (! isset($this->save_filename)) {
			$this->use_cache = false;
		}

		//if the file exists and we want to do some form of caching
		if (file_exists($this->save_filename) and (bool)$this->cache === true)  { 

			//if cache is an int and has a valid timestamp relative to the duration set in self::cache()
			if (is_int($this->cache) and Cache::cacheValid($this->save_filename, $this->cache)) { 

				//set the flag
				$this->use_cache = true;
				return;
			}  

			//if cache is bool true, we want to cache the file full stop
			if ($this->cache === true) { 

				//set the flag
				$this->use_cache = true;
				return;
			}          
		}

		//otherwise set caching to false and exit
		$this->use_cache = false;
		return;	
	}	


	/**
	* check if the cache dir exists
	*
	* @return string
	*/
	private function chkdir($dir) {

		//if the instance cache dir is missing
		if (! is_dir($dir)) { 

			//make it...
			mkdir($dir); 

			//...and set the mode
			chmod($dir, 0755);
		}

		substr(sprintf('%o', fileperms($dir)), -4); 
		return $dir;
	}


	/**
	* set options for the rendering of images
	*
	* @param key string The key to set
	* @param value string The value to set
	*
	* @return void
	*/
	private function setOption($key, $value) {
		$this->options[$key] = $value;
	}


	/**
	* set the save name for the file
	*
	* @param filenaame string The name / path of where the data should be saved
	*
	* @return object
	*/
	public function setSaveFileName($filename) {
		$this->save_filename = $filename;
		return $this;
	}


	/**
	* MAGIC
	*  
	* turn our class into an echo'able variable
	*
	* @return string
	*/    
	public function __toString() {
		if (isset($this->save_filename)) {
			return $this->pathToUri($this->save_filename);
		}

		return $this->pathToUri($this->filename);
	}
}