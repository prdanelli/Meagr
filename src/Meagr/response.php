<?

/**
* Response
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Response {

    //our static instance
	private static $instance;

    //the router instance passed to us from the Router class
    private $router; 

    //our 404 flag
    private $is_404 = false;

    //our generated body
    private $body;

    private $cache_exists = false;

    //our response statuses and codes
    public static $statuses = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded'
    );

    
    
    /**
    * define out sington instance
    *
    * @param router object A Router object instance prefilled with translated routes
    *
    * @return object
    */
    public static function init(Router $router) {
        if (is_null(self::$instance)) {
            self::$instance = new self($router);
        }

        return self::$instance;
    }		

    
    /**
    * prevent normal instantiation
    *
    * @param router object A Router object instance prefilled with translated routes
    *
    * @return void
    */    
    private function __construct(Router $router) {

        //our class wide router instance
        $this->router = $router;

        //set default headers
        $this->setHeader(200);

        //Set no caching
        if (IS_DEBUG === true) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
            header("Cache-Control: no-store, no-cache, must-revalidate"); 
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");            
        }

        //short hand
        $this->is_404 = $this->router->is_404; 
    }


    /**
    * set our headers
    *
    * @param headers array An array of additional header codes to be set for this request response
    *
    * @return object
    */
    function headers($headers = null) {

        //set any default headers first
        if ($this->router->is_404) {
            $this->setHeader(404);
        }

        if (is_null($headers) or empty($headers)) {
            return $this;
        }

        //now set any headers passed into the method
        foreach($headers as $header) {

            //set headers
            $this->setHeader($header);
        }

        //return instance
    	return $this;
    }

    
    /**
    * worker function, sets headers from a given code
    *
    * @param $code mixed[string|int] A code which matches a status from the static self::$statuses array
    *
    * @return void
    */
    private function setHeader($code) { 
       
       //if we were not passed a legit code
        if (! isset(self::$statuses[$code])) {
            return false;
        } 

        //create our string
        $header_string = Input::server('server_protocol') . ' ' . $code . ' ' . self::$statuses[$code];

        //set the header with the protocol version, the code and the status
        header($header_string);

        //set our response code
        http_response_code($code);        
    }    


    /**
    * set the body of content to be sent to the browser, 
    * buffer all output and store in $this->body
    *
    * @return object
    */
    function body() {

        //if we already have content
        if (! empty($this->body) or $this->cache_exists) {

            //return self and move on
            return $this;
        }

        //get our route object
        $route = $this->router->route;

        //properly capitalise our namespace
        Router::namespaceRoutePattern($route);

        //get our arguments
        $args = ($this->router->arguments) ? : array(); 
        
        //get our class and method
        list($class, $method) = explode('::', $route->getMappedPattern());

        //start our buffering
        ob_start();

        //our before function
        call_user_func_array(array($class, '__before'), $args);

        //call our function and class
        call_user_func_array(array($class, $method), $args);

        //our after function
        call_user_func_array(array($class, '__after'), $args);

        //assign the buffer to our body variable
        $this->body = ob_get_contents(); 

        //finish buffering and clean the output
        ob_end_clean();

        //allow for chaining
    	return $this;
    }


    /**
    * cache our output if required
    *
    * @return object
    */
    function cache($duration = null) {

        if ($duration === false) {
            return $this;
        }

        //if no value was passed into the function
        if (is_null($duration)) {

            //use the config value
            $config = Config::settings('cache');
            $duration = $config['duration'];
        }

        //init with the key
        $cache = Cache::init(); 

        //if cache is available and within the time limit
        if ($cache->setDuration($duration)->exists()) { 
            
            //get the cache
            $this->body = $cache->get();

            //set the flag
            $this->cache_exists = true;

        } else {

            //run the body method to get the content before caching
            $this->body();

            //now we have a body set, set the cache with the time limit
            $cache->set($this->body);
        }         

        return $this;
    }


    /**
    * process the body of the content 
    *
    * @param callback mixed[closure|string|array] The method by which the body is to be filterd/manipulated
    *
    * @return object
    */
    function filter($callback = null) {

        //if the body isnt set yet, set it
        if (empty($this->body)) {
            $this->body();
        }

        // if the closure is null, then just return
        if (is_null($callback)) {
            return $this;
        }

        //if we've been passed a function name as a string
        if (is_string($callback) and is_callable($callback)) {
            $this->body = $callback($this->body);
            return $this;
        }

        //if we've been passed a closure function
        if ($callback instanceof \Closure) {
            $this->body = $callback($this->body);
        }

        //if we've been passed an array of class and method names
        if (is_array($callback) and is_callable($callback)) {
            $this->body = call_user_func_array($callback, $this->body);
        }

        //return
    	return $this;
    }


    /**
    * our execute function
    *
    * @return string
    */
    function go() {

        //print our body to the screen
    	echo $this->body;
    }
}