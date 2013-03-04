<?

/**
* Timer
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr;

class Timer {

	//our instance key
	public $name = 'default';

	//our instance wrapper
	private static $instance = array();

	//the start time
	protected $start;

	//the end time
	protected $stop; 

	//the current interval 
	protected $diff;

	//the system time
	public $sys_time = 0;


	/**
	* our init method, checks for an existing instance, starts the clock and returns 
	*
	* @param name string The instance key
	* @param auto_start bool Should the instance timer be started immediately
	*
	* @return void
	*/
	public static function init($name = 'default', $auto_start = true) {
		if (!isset(self::$instance[$name])) {
			self::$instance[$name] = new self($name);
		}

		if (! isset(self::$instance[$name]->start) and $auto_start === true) {
			self::$instance[$name]->start(); 
		}

		return self::$instance[$name];
	}

	/**
	* record the current time within self::sys_time 
	*
	* @param name string The instance key
	*
	* @return void
	*/
	protected function __construct($name) { 
		$this->sys_time = self::mtime();
		$this->name = $name;	
	}
	
	/**
	* start the timer
	*
	* @return $this
	*/
	public function start() {
		$this->start = self::mtime(); 
		return $this;
	}		


	/** 
	* Get the time the timer was started, 
	* if the timer has been started, if not, starts and returns
	*
	* @return start mixed[int|float]
	*/
	public function getStart() {
		if (! isset($this->start) or empty($this->start)) {
			$this->start();
		}

		return $this->start;
	}


	/**
	* stop the timer
	*
	* @return $this
	*/
	public function stop() {
		$this->stop = self::mtime(); 
		return $this;
	}


	/** 
	* Get the time the timer was stopped, 
	* if the timer has been stopped, if not, stops and returns
	*
	* @return start mixed[int|float]
	*/
	public function getStop() {
		if (! isset($this->stop) or empty($this->stop)) {
			$this->stop();
		}

		return $this->stop;
	}


	/**
	* returns the difference between the start and finish times 
	*
	* @return $this
	*/
	public function diff($return_raw = false) {
		if (! isset($this->stop)) {
			$this->stop();
		}

		if ($return_raw) {
			$this->diff = $this->getStop() - $this->getStart();
		} else {
			$this->diff = round($this->stop - $this->start, 5) . ' seconds';
		}
		
		return $this->diff;
	}


	/**
	* take the microtime and make it useable 
	*
	* @return float
	*/
	public static function mtime() {
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}


	/**
	* convert the time into a human readable format
	*
	* @param time mixed[string|int|float]
	* 
	* @return string
	*/
	static public function convert($time) {

		if ($time < 1) {
			//turn from micro to milli seconds
			$timelabel = ($time * 1000) . " ms";
		} else {
			$timelabel = sprintf("%0.2f", $time)." s";
		}

		return $timelabel;
	}	
}