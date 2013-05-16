<?

/**
* Router
*
*
* @package Meagr
* @version 2.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Router {

    //our instance wrapper
    private static $instance;

    //our matching route
    public $route;    

    //our routes array will contain all of our route objects
    public $routes = array();

    //the current uri segments
    public $uri_segments = array(); 

    //our current uri
    public $uri; 

    //our route tags and their matching keyword
    public $route_map = array();

    //our matched route
    public $matched_routes = array(); 

    //is it 404?
    public $is_404 = false;

    //our homepage / root level flag
    public $is_root = false;

    //the type of request we're deaing with (usually POST or GET)
    public $request_type; 

    //additional arguments, passed on to the function call
    public $arguments;

    /**
    * singleton instantiation
    *
    * @return object
    */  
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }   


    /**
    * setup the class
    *
    * @return void
    */
    private function __construct() {
        //get the current uri
        $this->uri_segments = $this->arguments = Uri::segments();

        //get the current uri
        $this->uri = rtrim(Uri::full(), '/');

        $this->request_type = Input::server('request_method');      

        //get the route map from the config
        $this->route_map = Config::settings('routeMap');

        //add any additional routes, adding them to the route map
        $this->routeMap(); 
    }


    /**
    * add our routes to the routes array as instances of the Route object
    *
    * @return object
    */
    public function addRoutes($array = null) {

        //get all of our routes from the config (plus all environments)
        $routes = Config::settings('routes');

        //incase more routes where added merge the arrays
        if (! is_null($array)) {
            $routes = $routes + $array;
        }

        //loop though our routes and add them new route instances
        foreach($routes as $route) { 

            //create our new Route instance and store
            $this->addRoute(new Route($route['uri'], $route['pattern'], $route['filter']));
        }    

        return $this;   
    }


    /**
    * add a route to our routes array, must be a Route instance
    *
    * @return object
    */    
    public function addRoute(Route $route) {
        $this->routes[] = $route;
        return $this;
    }   


    /**
    * translate our routes into something useable
    *
    * @return object
    */  
    function mapRoutes() {

        //run the method on all routes currently registered
        array_map(array($this, 'translatePattern'), $this->getRoutes()); 
        array_map(array($this, 'translateUri'), $this->getRoutes()); 

        //run through the objects for filter functions 
        array_map(array($this, 'filterRoute'), $this->getRoutes());  

        return $this;    
    }


    /**
    * map individual route Uri's
    *
    * @return mixed[object|void]
    */     
    function translateUri(Route $object) {

        //empty and move on
        if (empty($object)) {
            return $object;
        }

        //check for our special terms __HOME__, __404__ etc
        // the / states the start and end of the pattern
        // ^_{2} states the string must start with 2 underscores
        // ([a-zA-Z0-9])+ one or more alphanumeric
        // _{2}$ the string must end with 2 underscores
        // the / denotes the end of our string
        if (preg_match('/^_{2}([a-zA-Z0-9])+_{2}$/', $object->getUri(), $m)) {

            //if we find one, move on, __KEYWORDS__ are dealt with seperately
            $object->is_special = true;
        }  

        $uri = $object->getUri();

        //loop through our route map array and switch values in both uri and pattern strings
        foreach($this->getRouteMap() as $key => $value) { 

            //look for any matches within our uri
            if (stripos($uri, $key) !== false and $object->is_special === false) {

                //make our uri - if value is empty, add a slash to key to be removed
                $uri = str_ireplace(($value ? $key : $key . '/'), $value, $uri);
            }  

             //look for any matches within our pattern
            if (stripos($pattern, $key) !== false) { 
                
                //if we have a key called '{method}' and its empty, we're at the root level, so add 'index'
                $value = (in_array($key, array('{method}', '{submethod}')) and $value == '') ? 'index' : $value;

                //update our pattern variable
                $pattern = str_ireplace($key, $value, $pattern);
            }  
        }

        //prepend the request type to the method, if we have a normal route
        if (strpos($pattern, '::') and $object->is_special === false) {

            //explode $pattern into $class and $method
            list($class, $method) = explode('::', $pattern); 

            //put back together
            $pattern = $class . '::' . $this->request_type . '_' . $method;
        }

        //update the mapped pattern, keeping the original pattern
        $object->setMappedUri($uri);

        return $object;
    }  

    /**
    * map individual route patterns
    *
    * @return mixed[object|void]
    */     
    function translatePattern(Route $object) {

        //empty and move on
        if (empty($object)) {
            return $object;
        }

        $pattern = $object->getPattern();

        //loop through our route map array and switch values in both uri and pattern strings
        foreach($this->getrouteMap() as $key => $value) { 

             //look for any matches within our pattern
            if (stripos($pattern, $key) !== false) { 
                
                //if we have a key called '{method}' and its empty, we're at the root level, so add 'index'
                $value = (in_array($key, array('{method}', '{submethod}')) and $value == '') ? 'index' : $value;

                //update our pattern variable
                $pattern = str_ireplace($key, $value, $pattern);
            }  
        }

        //prepend the request type to the method, if we have a normal route
        if (strpos($pattern, '::') and $object->is_special === false) {

            //explode $pattern into $class and $method
            list($class, $method) = explode('::', $pattern); 

            //put back together - and check we dont add the request type to the method twice
            $pattern = $class . '::' . (! strstr($method, $this->request_type) ? $this->request_type . '_' : '') . $method;
        }

        //update the mapped pattern, keeping the original pattern
        $object->setMappedPattern($pattern);

        return $object;
    }      


    /**
    * map individual routes
    *
    * @return mixed[object|void]
    */     
    function filterRoute(Route $object) {

        //now we have everything in place, run any closures passed from the config
        $object = $object->filter(Uri::segments(), null); 
        return $object;  
    } 


    /**
    * check if a mapped pattern exists in the current setup 
    *
    * @return bool
    */
    public function patternExists(Route $route) {
        $route = self::namespaceRoutePattern($route);
        $segments = explode('::', $route->getMappedPattern()); 
        return method_exists($segments[0], $segments[1]); 
    }

    /**
    * loop our routes and see if we have a match
    *
    * @return object
    */
    function matchRoutes() {

        //if we're dont have any routes, move on
        if (empty($this->routes)) {
            return $this;
        }

        //loop our routes
        foreach($this->routes as $route) { 

            //if we have a special route, move on
            if (is_null($route->getMappedUri())) {
                continue;
            }

            //if we have a matching route which matches an available class
            if (rtrim($route->getMappedUri(), '/') == $this->uri and $this->patternExists($route)) { 

                $route->route_exists = true;

                //store it
                $this->matched_routes[] = $route; 
                continue;
            }

            //check that our args arnt preventing a match
            $args = $this->route_map['{args}']; 
            if (str_replace($args . '/', '', $route->getMappedUri()) == str_replace($args . '/', '', $this->uri) and $this->patternExists($route)) {
            
                $route->route_exists = true;

                 //store it
                $this->matched_routes[] = $route;      
                continue;          
            }
        }

        //if we didnt find any matching routes
        if (empty($this->matched_routes)) {

            //update our flag
            $this->is_404 = true;
        }

        return $this;
    }


    /**
    * check for the existance of matching controllers for the matched routes
    *
    * @return object
    */
    function getMatchedRoute() {

        //if we know we're at the root level
        if ($this->is_root) {

            //assign the correct route
            $this->route = $this->getSpecialRoute('__HOME__');

            //make sure the string is a valid, capitalised Meagr namespace
            $this->route = self::namespaceRoutePattern($this->route);

            //make sure the 404 flag is unset
            $this->is_404 = false;

            //set the matched route 
            $this->matched_routes = $this->route;

            //and move on
            return $this;
        }        

        //check if we have a 404 or no matched routes
        if ($this->is_404() === true or empty($this->matched_routes)) {

            //return the 404 route object
            $this->route = $this->getSpecialRoute('__404__');
            return $this;
        }

        //check through our matched routes array 
        foreach($this->matched_routes as $route) {

            $route = self::namespaceRoutePattern($route);

            //get our class pattern by seperating the string at the '::'
            list($class, $method) = explode('::', $route->pattern_mapped); 

            //check for a method/class which exist
            if (is_callable(array($class, $method))) {

                //if they found, store it...
                $this->route = $route;

                //we have a match, so confirm the 404 flag is false
                $this->is_404 = false;

                // ...and break straight out
                return $this;
            }   
        }   

        //if we got here, no matching class/method was found, so return 404
        $this->route = $this->getSpecialRoute('__404__');
        $this->is_404 = true;

        return $this;
    }


    /**
    * internal function to translate the tags from the config route map adding additional items
    *
    * @return void
    */
    function routeMap() {
        $map_segments = array();

        //check if we have anything to work with
        if (empty($this->uri_segments)) {
            return false;
        }

        //define our segments
        $map_segments['{modules}'] = 'modules';
        $map_segments['{controllers}'] = 'controllers';
        $map_segments['{domain}'] = SITE_DOMAIN;
        $map_segments['{class}'] = $this->uri_segments[0];
        $map_segments['{module}'] = $this->uri_segments[0];
        $map_segments['{subclass}'] = $this->uri_segments[0] . '_' . $this->uri_segments[1];
        $map_segments['{method}'] = $this->uri_segments[1];
        $map_segments['{submethod}'] = $this->uri_segments[2];

        //check for a subdomain
        $url = parse_url('http://' . \Meagr\Input::server('http_host'));
        $url['host'] = explode('.', $url['host']); 

        //if the first host segment is not www 
        if ($url['host'][0] !== 'www' and ! stripos(SITE_URL, $url['host'][0])) {

            //set the sites subdomain 
            $map_segments['{subdomain}'] = $url['host'][0];

            //define for other site wide usage
            define('SITE_SUBDOMAIN', $url['host'][0]);

            //ammend our other map segments
            $map_segments['{modules}'] .= '/' . $map_segments['{subdomain}'];
        } 

        //are we at the root level (home page)
        if ($this->uri_segments[0] == '/' and ! defined('SITE_SUBDOMAIN')) {
            $this->is_root = true;
            $this->uri_segments[0] = '';
        }     

        //clear the segments we have already used
        unset($this->uri_segments[1], $this->uri_segments[0]);

        //check if we have additional arguments
        if (! empty($this->uri_segments)) { 
            $map_segments['{args}'] = implode('/', $this->uri_segments);

            //keep them for passing to the func call later
            $this->arguments = $this->uri_segments;
        }            

        //combine our array with the existing route map
        $this->route_map = $this->route_map + $map_segments;

        return;
    }


    /**
    * return our routes
    *
    * @return array
    */  
    public function getRoutes() {
        return $this->routes;
    }     


    /**
    * helper function to capitalise our route pattern 
    *
    * @param route object A Route object instance to be used 
    *
    * @return void
    */
    public static function namespaceRoutePattern(Route $route) {

        //get our class pattern by seperating the string at the '::'
        list($class, $method) = explode('::', $route->pattern_mapped); 

        //make sure our namespace is capitalised properly
        if (strpos($class, '\\') !== false) { 

            //clean up any remaining / and replace with \
            $class = '\\' . str_replace('/', '\\', $class);

            //explode array and filter empty slots
            $sections = array_filter(explode('\\', $class)); 

            //reset our new array
            $array = array();

            //capitalise each work
            foreach($sections as $key => $section) {

                //if we have a / skip this one
                if (trim($section) == '/')  {
                    continue;
                }

                //add to our new array
                $array[$key] = ucwords($section);
            }

            //create our new class name from the sections
            $class = implode('\\', $array); 

            //update our mapped route
            $route->setMappedPattern($class . '::' . $method);
        }

        return $route;
    }


    /**
    * get the 404 / HOME route
    *
    * @param route object A Route object instance to be used 
    *
    * @return mixed[object|bool]
    */
    function getSpecialRoute($route_key) {

        //reverse the routes so we get our environment configs first
        $routes = array_reverse($this->getRoutes());

        //run through our routes ...
        foreach($routes as $route) {

            //...and find the __404__
            if ($route->getUri() == $route_key) {

                //return it
                return $route;
            }
        }     

        return false;
    }


    /**
    * easy access to if we have a 404
    *
    * @return bool
    */
    function is_404() {
        return $this->is_404;
    }   


    /**
    * return just the matched routes
    *
    * @return array
    */
    function getMatchedRoutes() {
        return $this->matched_routes;
    }


    /**
    * returns the value of the objects route_map key
    *
    * @param key string The key of the route map array
    * 
    * @return mixed[string|array]
    */
    function getRouteMap($key = null) {
        if (is_null($key)) {
            return $this->route_map;
        }

        return $this->route_map[$key];
    }       


    /**
    * set the value of the objects route_map key
    *
    * @param key string The key of the route map array
    * 
    * @return mixed[string|array]
    */
    function setRouteMap($key, $value = '') {
        return $this->route_map[$key] = $value;
    }  
    
    /**
    * misc redirect function
    *
    * @return array
    */
    public static function redirect($location = false, $redirect = true) {
        if (! $location) {
            return false;
        }

        if (! $redirect) {
            return SITE_URL . $location;
        }
        
        header("Location: " . SITE_URL . $location);
        die;
    }       
}