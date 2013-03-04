<?

/**
* FTP
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr;

Class FTP {

	//maintain multiton instances
	private static $instance = array();

	//connection variable
	private $connection;

	//login check
	private $login_check = false;

	//connection host name / ip
	private $host; 

	//connection username
	private $username;

	//connection password 
	private $password; 

	//our port number
	private $port;
	
	//passive connection
	private $passive = false;

	//an array of file types that require an ascii transfer type
	public $ascii = array('ajx', 'am', 'asa', 'asc', 'asp', 'aspx', 'awk', 'bat', 'c', 'cdf', 'cf', 'cfg', 'cfm', 'cgi', 'cnf', 'conf', 'cpp', 'css', 'csv', 'ctl', 'dat', 'dhtml', 'diz', 'file', 'forward', 'grp', 'h', 'hpp', 'hqx', 'hta', 'htaccess', 'htc', 'htm', 'html', 'htpasswd', 'htt', 'htx', 'in', 'inc', 'info', 'ini', 'ink', 'java', 'js', 'jsp', 'log', 'logfile', 'm3u', 'm4', 'm4a', 'mak', 'map', 'model', 'msg', 'nfo', 'nsi', 'info', 'old', 'pas', 'patch', 'perl', 'php', 'php2', 'php3', 'php4', 'php5', 'php6', 'phtml', 'pix', 'pl', 'pm', 'po', 'pwd', 'py', 'qmail', 'rb', 'rbl', 'rbw', 'readme', 'reg', 'rss', 'rtf', 'ruby', 'session', 'setup', 'sh', 'shtm', 'shtml', 'sql', 'ssh', 'stm', 'style', 'svg', 'tcl', 'text', 'threads', 'tmpl', 'tpl', 'txt', 'ubb', 'vbs', 'xhtml', 'xml', 'xrc', 'xsl');

	//the our directory cache
	public $ls = array();

	//present working diretory
	public $pwd = DS; 

	//the current directory contents
	public $pwd_contents; 

	//execute funcitons on the server
	private $exec = false; 

	/**
	* create a new instance from the connection name passed
	*
	* @param connection_name	string 	Inorder to maintain different connections and instances
	*
	* @return object
	*/
	public static function init($connection_name = 'default') {

		//if we do not have an instance for thi connection
		if (! isset(self::$instance[$connection_name])) {

			//instantiate one anew
            self::$instance[$connection_name] = new self($connection_name);
        }

        //return our instance
        return self::$instance[$connection_name];
	}


	/**
	* refuse normal instantiation
	*
	* @param connection_name 	string 	Inorder to maintain different connections and instances
	*
	* @return void
	*/
	private function __construct($connection_name) {	

		//get the ftp config 
		$config = Config::settings('ftp');

		//if we have a set of connection details for the required connection
		if (isset($config[$connection_name]) and is_array($config[$connection_name])) { 

			//loop through our config and assign to instance variables
			foreach($config[$connection_name] as $k => $v) {
				$this->{$k} = $v; 
			}
		}
	}


	/**
	* connect to the ftp server with the details provided
	*
	* @return object
	*/	
	public function connect() {

		// *** Set up basic connection
		$this->connection = ftp_connect($this->host);

		// *** Login with username and password
		$login_result = ftp_login($this->connection, $this->username, $this->password);

		// sets passive mode on/off 
		ftp_pasv($this->connection, $this->passive);

		// check connection
		if (($this->connection === false) || ($login_result === false)) {
			Log::add('FTP connection has failed! ' . $this->host . ' for user ' . $this->username, __METHOD__, 'error');

			throw new MeagrException('Attempted to connect to ' . $this->host . ' for user ' . $this->username);
			return false;
		} 

		Log::add('Connected to ' . $this->host . ', for user ' . $this->username, __METHOD__, 'success');
		$this->login_check = true;
		return $this;		
	}


	/**
	* create a directory on the server
	*
	* @param $directory   string 	The name of the directory to be created
	* 
	* @return object
	*/
	public function mkdir($directory) {

		//if the directory could not be successfully created
		if (! ftp_mkdir($this->connection, $directory)) {

			//log the error
			Log::add('Failed creating directory "' . $directory . '"', __METHOD__, 'error');

			//throw a new exception
			throw new MeagrException('Failed creating directory "' . $directory . '"');
			return false;
		} 
			
		Log::add('Directory "' . $directory . '" created successfully', __METHOD__, 'error');
		return $this;
	}


	/**
	* delete a directory on the server
	*
	* @param $directory   string 	The name of the directory to be deleted
	* 
	* @return object
	*/
	public function rmDir($directory) {

	}


	/**
	* delete a file / directory on the server
	*
	* @param $directory   string 	The name of the file / directory to be deleted
	* 
	* @return object
	*/
	public function rmFile($file) {

		//check if we have a filename but not a file path
		if (! strpos($file, DS)) {
		
			//if so, use the pwd
			$file_form = $this->pwd . DS . $file; 
		}

		//check if our upload failed
		if (! ftp_delete($this->connection, $file)) {

			//log the error
			Log::add('The file delete "'. $file .'" failed', __METHOD__, 'error');

			//throw a new exception
			throw new MeagrException('The file delete "'. $file.'" failed');
			return false;
		} 

		Log::add('Deleted "' . $file . '"', __METHOD__, 'success');
		return $this;		
	}

	//move a file
	public function mv($file_from, $file_to) {

	}


	/**
	* execute commands on the server, ie. ls -al >files.txt
	*
	* @param $command   string 	The command to be executed
	* 
	* @return object
	*/
	public function exec($command) {

		//check if exec has been enabled by the config
		if ($this->exec === false) {
			throw new MeagrException('Exec is currently disabled, tried to execute: "'. $command .'"');
			return false;
		}

		//see if the command was executed
		if (! ftp_exec($this->connection, $command)) {
			throw new MeagrException('Exec command "' . $command . '" could not be executed');
			return false;
		}

		return $this;
	}


	/**
	* return the size of a file
	*
	* @param $file   string 	The absolute path to the file
	* 
	* @return int
	*/
	public function size($file) {

		//check if we have a filename but not a file path
		if (! strpos($file, DS)) {
		
			//if so, use the pwd
			$file_form = $this->pwd . DS . $file; 
		}		

		return ftp_size($this->connection, $file); 
	}


	/**
	* change the file permissions
	*
	* @param $file   string 	The absolute path to the file on the server
	* @param $mode 	 string 	The mode that is to be set 
	* 
	* @return int
	*/
	public function chmod($file, $mode = '0755') {

		//check if we have a filename but not a file path
		if (! strpos($file, DS)) {
		
			//if so, use the pwd
			$file_form = $this->pwd . DS . $file; 
		}

		//force a string
		if (! is_string($mode)) {
			$string = (string) $mode; 
		}

		//check if our upload failed
		if (! ftp_chmod($this->connection, octdec(str_pad($mode, 4, '0', STR_PAD_LEFT)), $file)) {

			//log the error
			Log::add('The file "'. $file .'" could not have it permissions changed', __METHOD__, 'error');

			//throw a new exception
			throw new MeagrException('The file permissions for "'. $file.'" could not be updated');
			return false;
		} 

		Log::add('chmod "' . $file . '" to ' . $mode, __METHOD__, 'success');
		return $this;			
	}


	/**
	* remname a file
	*
	* @param $file   string 	The absolute path to the file on the server
	* @param $new_file 	 string 	The new name / location on the server
	* 
	* @return void
	*/
	public function rename($file, $new_file) {

		//check if we have a filename but not a file path
		if (! strpos($file, DS)) {
		
			//if so, use the pwd
			$file = $this->pwd . DS . $file; 
		}

		//check if we have a filename but not a file path
		if (! strpos($new_file, DS)) {
		
			//if so, use the pwd
			$new_file = $this->pwd . DS . $new_file; 
		}

		if (! ftp_rename($this->connection, $file, $new_file)) {

			//log the error
			Log::add('The file "'. $file .'" could not be renamed to "'. $new_file .'"', __METHOD__, 'error');

			//throw a new exception
			throw new MeagrException('The file "'. $file .'" could not be renamed to "'. $new_file .'"');			
		}
	}


	/**
	* upload a file
	*
	* @param $file_from   string 	The absolute path to the file on the local server
	* @param $file_to 	 string 	The location on the server
	* 
	* @return void
	*/
	public function putFile($file_from, $file_to = null) {

		//if only a source file is passed
		if (is_null($file_to)) {

			//use the current pwd and sorce filename
			$file_to = $this->pwd . DS . basename($file_from);
		}

		//set the detault file type (binary)
		$mode = FTP_BINARY;

		//check if we have text file that requires ascii
		$extension = @end(explode('.', $file_from)); 
		if (in_array($extension, $this->ascii)) {
			$mode = FTP_ASCII;		
		} 

		//check if our upload failed
		if (! ftp_put($this->connection, $file_to, $file_from, $mode)) {

			//log the error
			Log::add('The file upload "'. $file_from .'" failed', __METHOD__, 'error');

			//throw a new exception
			throw new MeagrException('The file upload "'. $file_from .'" failed');
			return false;
		} 

		Log::add('Uploaded "' . $file_from . '" as "' . $file_to, __METHOD__, 'success');
		return $this;
	}


	/**
	* chnage directory
	*
	* @param $directory 	string 	The directory to change to
	* 
	* @return object
	*/	
	public function cd($directory) {
		if (! ftp_chdir($this->connection, $directory)) {
			Log::add('Couldn\'t change to directory "'. $directory .'"', __METHOD__, 'success');
			throw new MeagrException('Couldn\'t change to directory "'. $directory .'"');

			return false;
		} 

		Log::add('Current directory is now: ' . ftp_pwd($this->connection), __METHOD__, 'error');
		$this->pwd = $directory;
		return $this;		
	}


	/**
	* get a directory listing from the connection
	*
	* @param $directory 	string 	The directory to list
	* 
	* @return object
	*/		
	public function ls($directory = null) {

		if (is_null($directory)) {
			$directory = $this->pwd; 
		}

		// get an array of contents for the current directory
		$this->ls[$this->pwd] = ftp_nlist($this->connection, $directory);

		//store the contents of the last listing for east access
		$this->pwd_contents = $this->ls[$this->pwd];
		return $this;
	}


	/**
	* return the contents of just the pwd
	* 
	* @return array
	*/	
	public function lsPwd() {

		//check the pwd has a listing
		if (! isset($this->ls[$this->pwd])) {

			//if not, fill it
			$this->ls();
		}

		//return only the pwd listing
		return $this->ls[$this->pwd];
	}	


	/**
	* return the pwd (present working directory - where we are)
	* 
	* @return array
	*/
	public function pwd() {
		return $this->pwd;
	}


	/**
	* return the contents of just the pwd
	*
	* @param $file 	string 	The name of the file to be looked for
	* @param $directory 	string 	The directory to to check
	*
	* @return array
	*/
	public function exists($file, $directory = null) {

		//if the directory wasnt passed, use the pwd
		if (is_null($directory)) {
			$directory = $this->pwd; 
		}

		//check if the cache has the directory listing
		if (! isset($this->ls[$directory])) {

			//if not run the ls on the directory
			$this->ls($directory);
		}

		//check if the file is now in the directory listing array
		if(in_array($file, $this->ls[$directory])) {
			return true; 
		}

		return false;
	}


	/**
	* download a file form the server
	*
	* @param $file_from 	string 	The name of the file / absolute location on the remote server
	* @param $file_to 	string 	The location to move download the file to on the local server
	* @param $assume_name 	bool 	Should be just assume the filename is to be the same
	*
	* @return mixed[bool|object]
	*/
	public function getFile($file_from, $file_to, $assume_name = true) {

		//check if we have a filename but not a file path
		if (! strpos($file_from, DS)) {
		
			//if so, use the pwd
			$file_form = $this->pwd . DS . $file_from; 
		}

		//if the flag is true, we want to maintain the filename, which means only a download path is required
		if ($assume_name === true) {
			$file_to .= DS . basename($file_from); 
		}

		//set the detault file type (binary)
		$mode = FTP_BINARY;

		//check if we have text file that requires ascii
		$extension = @end(explode('.', $file_from));
		if (in_array($extension, $this->ascii)) {
			$mode = FTP_ASCII;		
		} 

		// try to download $remote_file and save it to $handle
		if (! ftp_get($this->connection, $file_to, $file_from, $mode, 0)) {
			throw new MeagrException('There was an error downloading file "' . $file_from . '" to "' . $file_to . '"');

			return false;
		} 

		return $this;
	}


	//helper functions 

	/**
	* Set the host before connect
	*
	* @param $host 	string 	The host to be set
	*
	* @return object
	*/
	public function addHost($host) {
		$this->host = $host; 
		return $this;
	}


	/**
	* Set the username before connect
	*
	* @param $username 	string 	The username to be set
	*
	* @return object
	*/
	public function addUsername($username) {
		$this->username = $username; 
		return $this;
	}


	/**
	* Set the password before connect
	*
	* @param $password 	string 	The password to be set
	*
	* @return object
	*/
	public function addPassword($password) {
		$this->password = $password; 
		return $this;
	}


	/**
	* Set the port before connect
	*
	* @param $port 	string 	The port to be set
	*
	* @return object
	*/	
	public function addPort($port) {
		$this->port = $port; 
		return $this;
	}		


	/**
	* close the current connection upon reaching the end of the script
	*
	* @return void
	*/
	public function __destruct() {
		ftp_close($this->connection);
	}		
}