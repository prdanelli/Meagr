<?

/**
* Model
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

//need to do delete

namespace Meagr; 

class Model {

	//the table we will be using
	public static $table;

	//the columns wihtin the table
	public static $columns = array();


	/**
	* handle class instantiation through $model::init(); 
	*
	* @return object
	*/	
	public static function init(){
		$class = get_called_class(); 
		return new Database($class);
	}	


	/**
	* create an instance and check our required values
	*
	* @return void
	*/	
	function __construct($class = null){

		//check for our required variables and properties and throw exceptions
		if (is_null($class)) {
			$class = get_called_class();
		}

		if (! property_exists($class, 'columns')) {
			throw new MeagrException('The $columns property of ' . $class . ' could not be found or was empty');
		}

		if (! property_exists($class, 'table')) {
			throw new MeagrException('The $table propetry of ' . $class . ' could not be found or was empty');
		}
	}


	/**
	* update/insert row 
	*
	* @param array array The array of key => value pairs to be inserted into the database
	*
	* @return object
	*/
	public function save($array = null){

		//get our class
		$class = get_called_class(); 
		
		//get our table column / field names
		$fields = array_keys($class::$columns); 
		
		//get our table name
		$table = $class::$table; 

		//if we're passed an array of key => value pairs, use those
		//and override the other values that were passed in
		if (! is_null($array) and is_array($array)) {
			foreach($array as $key => $value) {
				$this->{$key} = $value;
			}
		}

		//loop through out fields... 
		foreach ($fields as $key) {

			//for ease or access, store this
			$value = $this->{$key}; 
			
			//auto-validate
			$validated = Validate::validate($class, $key, $value);

			//check the validate didnt return false (failing)
			if ($validated === false) {
				//if it did, throw an exception
				throw new MeagrException('The value "' . $value . '" was given for "' . $key . '" breaking one or more validate rules "' . implode('", "', $class::$columns[$key]['validate']) . '". ');
				continue;
			}

			//if we passed validate
			if ((isset($value) and !empty($value)) and $validated !== false)  {
				$data[$key] = $validated;

			//if the value was empty or not set, use the default value
			} elseif (! empty($class::$columns[$key]['database']['default'])) {
				$data[$key] = $class::$columns[$key]['database']['default'];

			//we shouldnt really get here, but if we do, use empty string
			} else {
				$data[$key] = '';
			}
		}

		//get the db instance
		$db = new Database; 

		//update
		if (isset($data['id']) and !empty($data['id'])) {

			//set the updated time
			$data['updated_at'] = date('Y-m-d H:i:s');

			return $db->update($table, $data);

		//insert
		} else {

			//set our time stamps
			$data['created_at'] = date('Y-m-d H:i:s');
			$data['updated_at'] = date('Y-m-d H:i:s');

			return $db->insert($table, $data);
		}
	}


	/**
	* validate functions
	*
	* @param value string 
	*
	* @return string
	*/
	static function create_slug($value) {
		return strrev($value);
	}
}