<?

/**
* Debug
* 
* Debug::init('file')->add(array('class' => __CLASS__, 'status' => 'success', 'time' => microtime()));
* Debug::init('file')->print();
* Debug::init('file')->clear();
* Debug::init('file')->remove();
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr;

class Debug {

	//the instance wrapper
	private static $instance;

	//an array of backtraces
	public $debug_backtrace = array();

	//the current memory usage
	public $memory;

	//the type of debug
	public $type; 

	//the current log
	public $log = array(); 

	//how many places do we want to record the time to
	public $time_precision = 6;

	// the debug output column order
	public $order = array('timestamp', 'class', 'message', 'status', 'memory', 'interval');

	//the current timestamp in milliseconds
	public $microtime;


	/**
	* init our multiton
	*
	* @param type string The type of debug we are recording
	*
	* @return object
	*/
	static function init($type = 'default') {
        if (is_null(self::$instance[$type])) {
            self::$instance[$type] = new self($type);
        }

        return self::$instance[$type];
	}


	/**
	* set instance variables - cannot be instantiated
	*
	* @param type string The type of debug we are recording
	*
	* @return void
	*/
	private function __construct($type) { 	
		
		//set the type of debug we want to record
		$this->type = $type;

		//start the instance with the initial memory usage - this is a backup for the index.php init
		$this->memory = defined('MEAGR_MEMORY') ? (int) MEAGR_MEMORY : memory_get_usage(); 
		$this->microtime = defined('MEAGR_TIMER') ? MEAGR_TIMER : Timer::mtime();
	}


	/**
	* set an order for the instance and override the defaults
	*
	* @param array array The order which we want to order our columns, is merged with the defaults
	*
	* @return void
	*/
	function order($array = null) {
		if (! is_null($array)) {

			//combine
			$this->order = $array + $this->order;
		}
	}


	/**
	* current memory usage
	*
	* @return string
	*/
	function memory() {
		
		//cache the current memory usage
		$current_mem = memory_get_usage();
		
		//get the amount used since the last time we checked
		if ($current_mem > $this->memory) {
			$used = $current_mem - $this->memory; 
		} else {
			$used = $this->memory - $current_mem; 
		}
		
		//change the cached system memory
		$this->memory = $current_mem;

		//return the converted, readable memory
		return $this->convert(round($used, 5));
	}


	/**
	* return the total memory used up until this point
	*
	* @return string
	*/
	function appMemory() {
		return $this->convert(memory_get_usage() - MEAGR_MEMORY);
	}


	/**
	* return the total execution time up until this point
	*
	* @return string
	*/
	function appTime() { 
		return Timer::convert(round(Timer::mtime() - MEAGR_TIME, $this->time_precision));
	}	


	/**
	* add our data to log
	*
	* @return string
	*/
	function add($array) {
		$array['timestamp'] = date('H:i:s d/m');
		$array['memory'] = $this->memory();
		$array['interval'] = Timer::convert(round(Timer::mtime() - $this->microtime, $this->time_precision));
		$this->time = Timer::mtime();

		$this->log[] = $array; 
	}


	/**
	* our backtrace, which if we are not in debug mode, returns nothing (saves tones of memory)
	*
	* @return array
	*/
	static function backtrace() {
		if (IS_DEBUG === false) {
			return false;
		}

		return debug_backtrace();
	}


	/**
	* output our log in tabular form
	*
	* @param status array Which log entries we want to return, ie only errors
	*
	* @return string
	*/
	public function output($status = array('success', 'error', 'info')) { 

		//check we have a log
		if (empty($this->log)) {
			return false;
		}

		//incase we were passed a string status instead of an array
		if (! is_array($status) and trim($status) !== '') {
			$status = array($status);
		}

		$string = '';
		$string .= '<table class="debug-table table table-striped table-bordered">';
		$string .= '<thead>';
		$string .= '<tr>';	

		//get our headers from the array keys
		$headers = array_keys($this->log[0]);

		//merge the header order and remove duplicate rows
		$headers = array_unique($this->order + $headers);

		//if we have an array
		if (count($headers) > 0) {

			//loop through and set our table headers
			foreach($headers as $header) {
				$string .= '<th>'. ucwords(str_replace(array('-', '_'), ' ', $header)) .'</th>';
			}
		}

		$string .= '</tr>';
		$string .= '</thead>';
		$string .= '<tbody>';

		$count_headers = count($headers);

		foreach($this->log as $entry) {

			//make sure we only display the status's we are asked for
			if (! in_array($entry['status'], $status)) {
				continue; 
			}

			$string .= '<tr>';
			foreach($headers as $order) {
				$string .= '<td>'. $entry[$order] .'</td>';
			}

			$string .= '</tr>';		
		}

		$string .= '<tr>'; 
		$string .= '<td colspan="2">Total app memory usage '.$this->appMemory().'</td>';
		$string .= '<td colspan="2">Total app execution time '.$this->appTime().'</td>';
		$string .= '<td colspan="'. ($count_headers - 4) .'">&nbsp;</td>';
		$string .= '</tr>';

		$string .= '</tbody>';
		$string .= '</table>';
		return $string;
	}	


	/**
	* convert our memory / files to a readable format
	*
	* @param size mixed[int|float] The filesize in bytes
	* @param precision int The number of decimal places to round to
	*
	* @return string
	*/
	private function convert($size, $precision = 0) {

		//our units 
		$unit = array('b','kb','mb','gb','tb','pb');

		//format our number
		return @round($size / pow(1024, ($i = floor(log($size, 1024)))), $precision) . ' ' . $unit[$i];
	}	
}