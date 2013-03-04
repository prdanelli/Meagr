<?

/**
* Database
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr;
use \Meagr\Modules\Config as ModulesConfig;  

class Database implements \Iterator, \ArrayAccess, \Countable {

    //make this db var static so its singleton
	public static $db = null;
    public static $schema; 

    //our model class name and namespace
    public $class; 
    
    //the table name the model is representing - or the table we want to query
    public $table;
    
    //if we want to alias our table 'SELECT * FROM news n'
    private $table_alias;
    
    //the query we'll be building
    public $query;
    
    //the columns we are selecting, * by default
    private $select = '*';
    
    //an array to hold our joins - processed first in first out
    private $join = array(); 
    
    //any WHERE, OR or AND conditions, processed first in first out
    private $where = array(); 
    
    //the order of the results DESC or ASC
    private $order; 
    
    //our result offset
    private $offset; 
    
    //any grouping
    private $group_by;
    
    //the result limit
    private $limit;   
    
    //bool for if we want distinct results or not  
    private $is_distinct = false;
    
    //are we performing a delete query
    private $is_delete = false;
    
    //we'll be passing this back, so lets keep it public for now
    public $results; 
    
    //for SPL iterator
    private $iterator_offset = 0; 
    
    //flag to know if we've pushed the model to the db in debug
    private $push_model = false;


   /**
    * enable static create of new instances - singleton only
    *
    * @param class string The name of the model we want to deal with
    *
    * @return void
    */
    static function init($class = null) {
        if (is_null($class)) {
            $class = get_called_class();
        }
        
        return new static($class);
    }
 
    /**
    * create our instance and fill in all the class wide variables
    *
    * @param class string The name of the model we want to deal with
    *
    * @return void
    */
	function __construct($class = null) { 

        if (! is_null(static::$db)) {
            return;
        }

        //pass our class if its present
        if (! is_null($class)) {
            $this->class = $class; 

            //if we have a class, we should have a table too...
            if (property_exists($class, 'table')) {
                $this->table = $class::$table; 
            } else {
                throw new MeagrException('No table property found');
            }
        }

        //get our merged config
		$connect = Config::settings('database'); 
        
        //try to connect
        try {
            static::$db = new \PDO("mysql:host=" . $connect['host'] . ";dbname=" . $connect['dbname'], $connect['username'], $connect['password']); 
            static::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e) {
            // Log::add('Failed to connect to database: ' . $connect['host'] . ' ' . $e->getMessage(), __METHOD__, 'error');
        }			
	}


    /**
    * destroy the connection
    *
    * @return void
    */
    function __destruct() {
        static::$db = null; 
    }


    /**
    * Our select statement 
    * 
    * SELECT column.table as colu_mn.table
    *
    * @param condition string Our select statement
    *
    * @return object
    */
    function select($condition) {
        $this->select = $condition;
        return $this; 
    }    


    /**
    * Should our query be distinct 
    *
    * @return object
    */
    function distinct() {
        $this->is_distinct = true;
        return $this; 
    }


    /**
    * Should the table selected be aliased to another name
    *
    * @param alias string The alias
    *
    * @return object
    */
    function tableAlias($alias) {
        $this->table_alias = $alias;
        $this->table .= ' ' . $alias . ' ';

        return $this;
    }


    /**
    * Add a where statement to our query
    * 
    * WHERE column (column.table) $condition (!=) $value
    *
    * @param alias string The alias
    *
    * @return object
    */
    function where($column, $value, $condition = '=', $apply_marks = true) {

        //incase we're doing coolness, dont always quote
        $quote = ($apply_marks === true) ? '"' : ''; 

        //check for previous WHERE statements and use AND if found
        $this->where[] = (count($this->where) > 0 ? ' AND ' : ' WHERE ') . $column . ' ' . $condition . ' ' . $quote . $value . $quote; 
        return $this; 
    }

 
    /**
    * Join two tables together
    * 
    * $direction $table ON $condition
    *
    * @param table string The name of the table to join
    * @param condition string The condition upon which the join should occure
    * @param direct string left or right
    *
    * @return object
    */
    function join($table, $condition, $direction = '') {
        $this->join[] = $direction . ' JOIN ' . $table . ' ON ' . $condition;
        return $this; 
    }


    /**
    * Left Join two tables together
    * 
    * uses self::join()
    *
    * @param table string The name of the table to join
    * @param condition string The condition upon which the join should occure
    *
    * @return object
    */
    function leftJoin($table, $condition) {
        return $this->join($table, $condition, "LEFT");
    }


    /**
    * Right Join two tables together
    * 
    * uses self::join()
    *
    * @param table string The name of the table to join
    * @param condition string The condition upon which the join should occure
    *
    * @return object
    */
    function rightJoin($table, $condition) {
        return $this->join($table, $condition, "RIGHT");
    }


    /**
    * apply an offset to the query
    *
    * @param num mixed[string|int] the number by which the query is to be offset
    *
    * @return object
    */
    function offset($num = null) {
        $this->offset = ' OFFSET ' . $num;

        if (is_null($num)) {
            $this->offset = ''; 
        }
        return $this; 
    }


    /**
    * The order the query results are to be sorted by 
    *
    * @param array array A list of columns that the query results are to be ordered by
    * @param  direction string ASC|DESC
    *
    * @return object
    */
    function order($array = array(), $direction = 'ASC') {
        if (! empty($array)) {
            $this->order = ' ORDER BY ' . implode(', ', $array) . ' ' . $direction;
        }
        
        return $this; 
    } 


    /**
    * Order the query DESC
    *
    * uses self::order()
    *
    * @return object
    */
    function orderDesc() {
        $args = func_get_args();
        return $this->order($args, 'DESC');
    }


    /**
    * Order the query ASC
    *
    * uses self::order()
    *
    * @return object
    */
    function orderAsc($column) {
        $args = func_get_args();
        return $this->order($args, 'ASC');
    }


    /**
    * Set the limit of the number of query results
    *
    * @param num int The number of results to be returned
    *
    * @return object
    */
    function limit($num = 0) {
        if ((int) $num > 0) {
            $this->limit = ' LIMIT ' . $num; 
        } else {
            $this->limit = '';
        }

        return $this; 
    }


    /**
    * Insert results into the table
    *
    * @param table string The name of the table
    * @param array array An array of key => value pairs
    *
    * @return object
    */
    function insert($table, $array) {
        //we shouldnt have this, so lets remove it here
        unset($array['id']);

        //get the count
        $count=count($array); 
        $i=1; 
        //reset our string vars
        $query_keys = ''; 
        $query_vals = '';

        //create key and : prefixed values
        foreach ($array as $key => $val) {
            $query_keys .= $key.(($i<$count) ? ', ' : '');
            $query_vals .= ':'.$key.(($i<$count) ? ', ' : '');
            $i++;
        }

        //create our string with the keys and values
        $query_string = "INSERT INTO ".$table." (" . $query_keys . ") VALUES (" . $query_vals . ")"; 
        
        //start monitoring
        // $this->_transStart();

        try {
            //lots happening here
            //prep the string to escape it, which returns a PDO Stamtent object, 
            //then run the execute method and pass in our array values which are translated
            //start monitoring
            $this->_prep($query_string)->execute($array);
            //if we got to here, we're doing ok, so commit
            // $this->_transCommit();
            
            //return the last inserted id
            return static::$db->lastInsertId();           

        //we dont want to be here, so deal    
        } catch(PDOException $e) {
            //bad things happen to good people, so rollback
            // $this->_transRollback();
        }
    }


   /**
    * update results in the table
    *
    * @param table string The name of the table
    * @param array array An array of key => value pairs
    *
    * @return object
    */
    function update($table, $array) { 
        //get the count
        $count=count($array); 
        $i=1; 
        //reset our string vars
        $query_keys = ''; 
        $query_vals = '';

        //create key and : prefixed values
        foreach ($array as $key => $val) {
            $query_keys .= $key.(($i<$count) ? ', ' : '');
            $query_vals .= ':'.$key.(($i<$count) ? ', ' : '');
            $i++;
        }

        //create our string with the keys and values
        $query_string = "UPDATE " . $table . " SET " . $set_vals . " WHERE id=:id"; 

        //start monitoring
        // $this->_transStart();

        try {
            //do pdo magic - explained in the insert method
            $this->_prep($query_string)->execute($array);
            //if we got to here, we're doing ok, so commit
            // $this->_transCommit();
            
            //return the id
            return $array['id'];              

        //we dont want to be here, so deal    
        } catch(PDOException $e) {
            //bad things happen to good people, so rollback
            // $this->_transRollback();
        }
    }


   /**
    * delete results from the table
    *
    * @return object
    */
    public function delete() {
        $this->is_delete = true; 
        return $this;
    }


   /**
    * go function... do stuff!
    * if $return_index is not null, give back that index from the results array (NOT zero indexed)
    * so ->go(1) would return the first result from the db in index 0
    *
    * @param table string The name of the table
    * @param array array An array of key => value pairs
    *
    * @return object
    */
    public function go($return_index = null) {
        $class = $this->class; 
        //confirm db table is present and available
        if (IS_DEBUG === true) { 
            Schema::init($class)->check();  
        }  

        //dont do the query if we already have results
        if (! empty($this->results)) {

            //if we want a specific result (probably the first)
            //might be a good idea to alter $this->limit at this point to avoid wasting resources
            if (! is_null($return_index)) {
                return $this->results[$return_index-1];
            }
            return $this->results; 
        }

        //if we dont have results already, run the query 
        $result = $this->_query($this->_buildQuery());
        //again check for a specific index
        if (! is_null($return_index)) {
            return $result[$return_index-1];
        }

        //else run the query with our desired query sql
        return $result;
    }


   /**
    * Prepare a the query and make sure its escaped
    *
    * @param query string The query to be escaped and prepared
    *
    * @return object
    */
    protected function _prep($query) {
        return self::$db->prepare($query);
    }


   /**
    * build query into an sql string
    *
    * @return object
    */
    protected function _buildQuery() {

        //loop through our array segments and create strings
        $keys = array('where', 'join'); 
        foreach($keys as $key) {
            $db_keys = $this->{$key};
            //do where
            if (! empty($db_keys) and count($db_keys) > 0) { 
                $db_keys = implode(' ', $db_keys); 
            } else {
                //cant unset or we get errors, so just set to ''
                $db_keys = '';
            }   

            $this->{$key} = $db_keys;        
        }

        //are we doing a delete or a select statement
        $select_prefix = ($this->is_delete) ? 'DELETE' : 'SELECT';
        //do we only want distinct results
        $distinct = ($this->is_distinct) ? ' DISTINCT' : '';
        // Format strings into a select query
        $this->query = sprintf("%s %s %s FROM %s%s%s%s%s%s%s",
            $select_prefix,
            $distinct,
            $this->select,
            $this->table,
            $this->join,
            $this->where,
            $this->group_by,
            $this->order,
            $this->limit, 
            $this->offset
        );

        return stripslashes(mysql_real_escape_string($this->query)); 
    }


   /**
    * run the query we have been building
    *
    * @return object
    */
    protected function _query($query) {
        //reset
        $this->results = array();
        //generate our statement
        $statement = self::$db->query($query);
        //grab the rows one at a time and create an instance of the calling class
        while ($row = $statement->fetchObject($this->class, array('class' => $this->class))) {
            $this->results[] = $row; 
        }  

        //return the rows, or false
        return $this;     
    }   


   /**
    * return the raw sql
    *
    * @return object
    */
    public function returnQuery() {
        return $this->_buildQuery();
    }     


   /**
     * Transactions function wrappers
     * 
     */

    private function _transStart() {
        self::$db->beginTransaction();
    }

    private function _transRollback() {
        self::$db->rollback();
    }

    private function _transCommit() {
        self::$db->commit();
    }


    /**
     * Iterator Methods
     * Automagically used by PHP
     */

    function current() {
        $this->go();
        return $this->results[$this->iterator_offset];
    }
    
    function key() {
        return $this->iterator_offset;
    }
    
    function next() {
        ++$this->iterator_offset;
    }
    
    function rewind() {
        $this->iterator_offset = 0;
    }
    
    function valid() {
        $this->go();
        return isset($this->results[$this->iterator_offset]);
    }
    
    
    /**
     * ArrayAccess Methods
     * Automagically used by PHP
     */
    
    function offsetExists($offset) {
        $this->go();
        return isset($this->results[$offset]);
    }
    
    function offsetGet($offset) {
        $this->go();
        return $this->results[$offset];
    }
    
    function offsetSet($offset, $value) {
        $this->go();
        if (is_null($offset)) {
            $this->results[] = $value;
        } else {
            $this->results[$offset] = $value;
        }
    }
    
    function offsetUnset($offset) {
        $this->go();
        unset($this->results[$offset]);
    }
    
    
    /**
     * Countable Methods
     * Automagically used by PHP
     */
    function count() {
        $this->go();
        return count($this->results);   
    }
}