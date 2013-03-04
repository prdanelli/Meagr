<?

/**
* Schema
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Schema extends Database {

	//the class name
	public $class; 

	//the database table name
	public $table;

	//the database name
	public static $db; 

	//a list of default database column types
	public static $defaults = array(
		'type_length' => array(				
				'TINYINT' => '3',
				'SMALLINT' => '5',
				'MEDIUMINT' => '8',
				'INT' => '10',
				'BIGINT' => '20',
				'FLOAT' => '24 ',
				'DATE' => '',
				'DATETIME' => '',
				'TIMESTAMP' => '',
				'TIME' => '',
				'YEAR' => '',
				'CHAR' => '3',
				'VARCHAR' => '255',
				'TINYBLOB' => '',
				'TINYTEXT' => '',
				'BLOB' => '',
				'TEXT' => '',
				'MEDIUMBLOB' => '',
				'MEDIUMTEXT' => '',
				'LONGBLOB' => '',
				'LONGTEXT' => '',
				'ENUM' => '65535'
			)
		);

	/**
	* create a new instance 
	*
	* @param class string The name of the class that we are checking the database stucture for
	*
	* @return object
	*/
	static public function init($class = null) {      
        return new static($class);
	}


	/**
	* check the db table for the need to update it
	*
	* @return void
	*/
	function check() {
		$class = $this->class;
		$this->table = $class::$table;

		//db config
		$connect = Config::settings('database');

		//check for the existance of the table
		$table_check = static::$db->query("SHOW TABLES LIKE '" . $this->table . "' ")->fetch();

		//if we dont find the table, make it
		if ($table_check[0] !== $this->table) {
			return $this->forgeModel();
		}

		//if we have the table, check if we want to alter if
		return $this->alterTable();
	}


	/**
	* push the model into the db
	*
	* @return void
	*/
	function forgeModel() {

		$class = $this->class; 
		$columns = $class::$columns; 

		//incase we're using the raw model columns will be empty, but 
		//we still need to continue, so just return here and continue execution
		if (empty($columns)) {
			return false;
		}

		$table = explode('\\', $class::$table); 
		$table = $table[count($table)-1];

		if (! $columns) {
			throw new MeagrException('Columns data not found for ' . __METHOD__);
		}

		$string = '';
		$count = count($columns);
		$i=1;
		foreach($columns as $column) {
			$desc = $column['database'];
			$string .= $desc['id'] . ' ' . $desc['type'] . ' ';

			//null or not..
			if (!isset($desc['null']) or $desc['null'] === false) {
				$string .= 'NOT NULL ';
			} else {
				$string .= 'NULL ';
			}

			//add auto increment if required
			if (isset($desc['auto_increment']) and $desc['auto_increment'] === true) {
				$string .= 'AUTO_INCREMENT ';
			}	
		
			//seperate our columns
			if ($i !== $count) {
				$string .= ', ';
			}

			//add the primary key if required
			if (isset($desc['primary']) and $desc['primary'] === true) {
				$string .= 'PRIMARY KEY('. $desc['id'] .'), ';
			}	

			$i++; 
		}

		//get rid of the empty brackets caused by no content type length 
		$string = str_replace('()', '', $string);
		$string = str_replace(' , ', ', ', $string);

		$sql = "CREATE TABLE " . $table . " (" . $string . ") ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
		$result = static::$db->prepare($sql); 

		return $result->execute();
	}


	/**
	* add columns to the table if the model is changed
	*
	* @return void
	*/
	function alterTable() {
		$class = $this->class;

		//if the table exists, check the schema is correct
		$schema_sql = "SHOW COLUMNS FROM " . $this->table;
		$schema_check = static::$db->query($schema_sql)->fetchAll(\PDO::FETCH_ASSOC);
		if (count($schema_check) > 0) {
			//lets all the field name as a key
			foreach($schema_check as $table) {
				$current[strtolower($table['Field'])] = array_change_key_case($table);
			}
		}

		//get the model column data and format correctly
		foreach($class::$columns as $column_data) {
			$id = $column_data['database']['id']; 

			//format our column structure exactly how we want it (inline with the showtables export)
			$model[$id]['field'] = $column_data['database']['id'];
			$model[$id]['type'] = $column_data['database']['type'];
			$model[$id]['null'] = ($column_data['database']['null'] === false) ? 'NO' : 'YES';
			$model[$id]['key'] = ($column_data['database']['primary'] === true) ? 'PRI' : '';
			$model[$id]['default'] = $column_data['database']['default'];
			$model[$id]['extra'] = ($column_data['database']['auto_increment'] === true) ? 'auto_increment' : '';		
		}

		//now we can check for any differences
		$diff = array_diff_assoc($model, $current);
		if (is_array($diff) and ! empty($diff)) {
			$count = count($diff);
			$i=1;
			if ($count > 0) {
				//start to build our sql
				$sql = "ALTER TABLE " . $this->table . " ";
				foreach($diff as $new) {
					//add the basics
					$sql .= "ADD " . $new['field'] . " " . strtoupper($new['type']) . " ";
					//add NULL / NOT
					$sql .= ($new['null'] == 'YES') ? ' ' : 'NOT NULL ';
					//add default value
					if (trim($new['default']) !== '') {
						$sql .= "DEFAULT '" . $new['default'] . "' ";
					}			

					//add the seperating comma if not the last element
					if ($count !== $i) {
						$sql .= ', ';
					}
					$i++;
				}

				//run the query and exit via return
				$result = static::$db->prepare($sql); 
				return $result->execute();
			}			
		}
	}
}