<?

/**
* URI
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Uri {

	
	/**
	* return a string of the current uri
	*
	* @return string
	*/
	static function current() {
		return Input::server('request_uri'); 
	}


	/**
	* return the full url including all query string arguments
	*
	* @return string
	*/
	static function full() {
		return Input::server('http_host') . Input::server('request_uri') . Input::server('query_string'); 
	}


	/**
	* return just the segments of the uri as an array
	*
	* @param num mixed[string|int] The segment number to be returned
	*
	* @return array
	*/
	static function segments($num = null) {

		//get the current url
		$url = parse_url(trim(Uri::current(), '/')); 

		//explode it into segments seperated by the /
		$segments = array_filter(explode('/', $url['path']));

		//if num is given, use non-zero indexed 
		if (! is_null($num)) {
			return $segments[$num-1];
		}

		//return either the segments of an array containing / for the root - this means we can always loop
		return ($segments) ? : array('/');
	}
}