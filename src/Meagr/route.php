<?

/**
* Route
*
* allow for setting and getting of route values
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Route {

	//our external uri - /admin/member/login/4
	public $uri; 

	//our matching internal pattern - the class/method/args etc
	public $pattern; 

	//our translated uri
	public $pattern_mapped;

	//our translated uri
	public $uri_mapped;

	//our closure to manipulate the route further
	public $filter;

	//whether this route is a system route
	public $is_special = false;

	//does this route object exist
	public $route_exists = false;


	/**
	* instantiate and pass our route array which should look like this: 
	* array('{subdomain}.{domain}/{class}/{method}/{arg}/' => '\{Modules}\Controllers\{Class}::{Method}') 
	*
	* @param uri string The URI of the route
	* @param pattern string The internal class/method pattern of the route
	* @param filter closure	The closure function to manipulate additional routes
	*
	* @return void
	*/
	public function __construct($uri, $pattern, $filter = null) {
		$this->uri = $uri;
		$this->pattern = $pattern; 	
		$this->filter = $filter;
	}

	/* === getters and setters === */

	/**
	* return the route pattern 
	*
	* @return string
	*/
	public function getPattern() {
		return $this->pattern;
	}


	/**
	* set the route pattern 
	*
	* @param new_pattern string The new pattern to add to this route
	*
	* @return object
	*/
	public function setPattern($new_pattern) {
		$this->pattern = $new_pattern;
		return $this;
	}


	/**
	* return the routes translated pattern 
	*
	* @return string
	*/
	public function getMappedPattern() {
		return $this->pattern_mapped;
	}	


	/**
	* set the routes tralsated pattern 
	*
	* @param pattern_mapped The new translated pattern
	*
	* @return object
	*/
	public function setMappedPattern($pattern_mapped) {
		$this->pattern_mapped = $pattern_mapped;
		return $this;
	}


	/**
	* return the route URI
	*
	* @return string
	*/
	public function getUri() {
		return $this->uri;
	}


	/**
	* set the route URI 
	*
	* @param new_uri string The new URI to add to this route
	*
	* @return object
	*/
	public function setUri($new_uri) {
		$this->uri = $new_uri;
		return $this;
	}	


	/**
	* return the translated URI
	*
	* @return string
	*/
	public function getMappedUri() {
		return $this->uri_mapped;
	}	
	

	/**
	* run a function on the closure
	*
	* @return mixed
	*/
	public function filter($args = null) {
		$filter = $this->filter; 

		if (! is_callable($filter)) {
			return false;
		}

		return $filter($this, $args);
	}


	/**
	* set the newly translated route uri 
	*
	* @param uri_mapped string The newly translated uri to add to this route
	*
	* @return object
	*/
	public function setMappedUri($uri_mapped) {
		$this->uri_mapped = $uri_mapped;
		return $this;
	}


	/**
	* returns the value of the objects route_map key
	*
	* @param key string The key of the route map array
	* 
	* @return mixed[string|array]
	*/
	public function routeMapKeyExists($key) { 
		//get our singleton object
		$router = Router::init();
		return (trim($router->getRouteMap($key)) !== '' ? true : false);
	}		


}