<?

/**
* Email
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Email {

	//the location of our header file
	public $header; 

	//the location of our footer file
	public $footer; 

	//our email content
	public $content; 

	//if we want to present the debug information and not send the email
	public $debug = false; 

	//a container for our debug backtrace for the debug content
	public $debug_backtrace; 

	//our PHPMailer object container
	public $email; 
	
	//for debugging//an array of addresses (for debugging)
	public $addresses; 

	//an array of bcc addresses (for debugging)
	public $bcc;  

	//an array of cc addresses (for debugging)
	public $cc;  

	//the arguments passed to the content callback (for debuggin)
	public $content_args; 


	/**
	* create our instance and setup class wide variables 
	*
	* @return void
	*/
	function __construct() {

		//get our debug callback and store it incase of debugging
		$this->debug_backtrace = Debug::backtrace();

		//keep just the second element sa that tells us where the calling class is
		$this->debug_backtrace = $this->debug_backtrace[1];  

		//get our config
		$config = Config::settings('email');
		$this->header = $config['header']; 
		$this->footer = $config['footer'];

		//init our email instance
		$this->email = new PHPMailer(true);

		//check if we want to use smtp and set all our required values from the config
		if ($config['smtp'] === true) {

			// telling the class to use SMTP
			$this->email->IsSMTP(); 

			// set the host address
			$this->email->Host = $config['smtp-host'];

			// enable SMTP authentication
			$this->email->SMTPAuth = true; 

			// SMTP connection will not close after each email sent
			$this->email->SMTPKeepAlive = true; 

			// set the SMTP port for the GMAIL server
			$this->email->Port = $config['smtp-port']; 

			// SMTP account username
			$this->email->Username = $config['smtp-username']; 

			// SMTP account password	
			$this->email->Password = $config['smtp-password']; 	
		}

		//add the default from address
		$this->addFrom($config['from-address'], $config['fromt-name']);
	}


	/**
	* incase we want to use this method of instantiation
	*
	* @return object
	*/
	static function init() { 
		return new self; 
	}


	/**
	* add a single address
	*
	* @param email string The email address of the user
	* @param name string The name of the user
	*
	* @return object
	*/
	function addAddress($email, $name = '') {
		//pass the address to PHPMailer
		$this->email->AddAddress($email, $name);

		//keep a copy of the addresses for debugging
		$this->addresses[] = array('name' => $name, 'email' => $email);
		return $this;	
	}

	/**
	* add alot of pairs of address details
	*
	* @param array array An array of names and email addreses
	*
	* @return object
	*/
	function addAddresses($array = array()) {
		if (! empty($array)) {
			foreach($array as $addr) {

				//pass the address to PHPMailer
				$this->email->AddAddress($addr['email'], $addr['name']);
				
				//keep a copy for the debugging
				$this->addresses[] = array('name' => $addr['name'], 'email' => $addr['email']);
			}
		}
		return $this;
	}


	/**
	* Add a BCC to the email
	*
	* @param email string The email address of the user
	* @param name string The name of the user
	*
	* @return object
	*/
	function addBCC($email, $name = '') {
		$this->email->AddBCC($email, $name);
		$this->bcc[] = array('name' => $name, 'email' => $email);
		return $this;
	}


	/**
	* Add a CC to the email
	*
	* @param email string The email address of the user
	* @param name string The name of the user
	*
	* @return object
	*/
	function addCC($email, $name = '') {
		$this->email->AddCC($email, $name);
		$this->cc[] = array('name' => $name, 'email' => $email);
		return $this;
	}		


	/**
	* Add an Attachment to the email
	*
	* @param filepath string The absolute path to the file on the local server
	*
	* @return object
	*/
	function addAttachment($filepath) {
		 $this->email->AddAttachment($filepath);
		 $this->attachment[] = $filepath;
		 return $this;
	}


	/**
	* Add a reply to address to the email
	*
	* @param email string The email address of the user
	* @param name string The name of the user
	*
	* @return object
	*/
	function addReplyTo($email, $name = '') {
		$this->email->AddReplyTo($email, $name);
		return $this;
	}


	/**
	* Add a subject to the email
	*
	* @param subject string The subject to be added to the email
	*
	* @return object
	*/
	function addSubject($subject) {
		$this->email->Subject = $subject;
		return $this;
	}


	/**
	* Add a from address to the email
	*
	* @param email string The email address of the user
	* @param name string The name of the user
	*
	* @return object
	*/
	function addFrom($email, $name = '') {
		$this->email->SetFrom($email, $name);
		return $this;		
	}


	/**
	* Add a header to the the html template
	*
	* @param header string The absolute location of the header on the server
	*
	* @return object
	*/
	function addHeader($header) {
		$this->header = $header; 
		return $this;
	}

	/**
	* Add a footer to the the html template
	*
	* @param footer string The absolute location of the footer on the server
	*
	* @return object
	*/
	function addFooter($footer) {
		$this->footer = $footer;
		return $this; 
	}

	function debug($debug) {
		$this->debug = $debug;
		return $this;
	}


	/**
	* Add the main content section to the email, the body
	*
	* @param callback array An array of class (fully namespaced) and method names 
	* @param args array Any additional arguments that should be passed to the callback
	*
	* @return object
	*/
	function addContent($callback, $args = array()) { 

		list($class, $method) = $callback; 

		if (class_exists($class) and is_callable(array(new $class, $method))) {
			
			//start our output buffer
			ob_start(); 

			//get the result of our callback
			call_user_func_array(array(new $class, $method), compact('args'));

			//capture the output
			$content = ob_get_clean(); 

			//add the alt body here before we add the header and footer buffers
			$this->email->AltBody = strip_tags($content, '<p><a><img><span><bold><ul><li>');

			//add the header
			$this->content = View::partial($this->header); 

			//add the body
			$this->content .= $content;

			//add the footer
			$this->content .= View::partial($this->footer);		
		}

		//for the debug
		$this->content_args = $args; 

		$this->email->MsgHTML($this->content);

		//return object for chaining
		return $this;
	}


	/**
	* Internal function to view the email debug information
	*
	* @return html
	*/
	private function outputDebug() { 
		echo View::partial('email-debug', array('object' => $this));
	}


	/**
	* execute the current email (send or debug)
	*
	* @return object
	*/
	function go() {
		if ($this->debug === true) {
			return $this->outputDebug();
		}

		try {
			$this->email->Send();
		} catch (phpmailerException $e) {
			echo $e->errorMessage(); 
		} catch (Exception $e) {
			echo $e->getMessage(); 
		}		

		return $this;
	}
}