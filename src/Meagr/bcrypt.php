<?

/**
* Bcrypt
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr;

class Bcrypt {

	//our random bites
	private $randomState;

	//the number of encryption rounds to perform
	private $rounds;

	//our salting prefix
	private $salt = null;


	/**
	* create the object and check for blowfish support 
	*
	* @param rounds int The number of encryption rounds to perform
	*
	* @return void
	*/
	public function __construct($rounds = 10) {

		//make sure the system has blowfish extension installed
		if(CRYPT_BLOWFISH != 1) {
			throw new Exception("bcrypt not supported in this installation. See http://php.net/crypt");
		}

		//set the class wide number of encryoption rounds to perform for blowfish
		$this->rounds = $rounds;
		//set our instance wide salt on init
		$this->salt = $this->getSalt();		
	}


	/**
	* create our hash of the input password / text string etc
	*
	* @param input string The content that is to be encrypted
	*
	* @return mixed[string|bool]
	*/
	public function hash($input) {
		//crete the input hash from the current class wide salt (passed in or created)
		$hash = crypt($input, $this->getSalt()); 

		if(strlen($hash) > 13) {
			return $hash;
		}

		return false;
	}


	/**
	* return the bool evalution of the comparison
	*
	* @param input string The string which is to be checked
	* @param existing_hash string The hash which is to be used to check our input for its validity
	*
	* @return bool
	*/
	public function verify($input, $existing_hash) {
		// Wait for a random time to protect against timing attacks
		usleep(mt_rand(0, 500));

		//compare our current hashed input to the existing value and return bool
		return $this->hash($input) === $existing_hash;
	}


	/**
	* generate our new additional salt string
	*
	* @return bool
	*/
	public function getSalt() {
		
		//if we need to use a user salt, pass that in
		if (! is_null($this->salt)) {
			return $this->salt; 
		}

		//otherwise create a nwe one and return
		//pre . 
		//create our blowfish
		//post .
		//create the new salt additional text
		$this->salt = sprintf('$2a$%02d$', $this->rounds) . $this->encodeBytes($this->getRandomBytes(16)); 
		return $this->salt;
	}



	/**
	* so we can pass in our unique user salts
	*
	* @param salt string Set the object salt 
	*
	* @return object
	*/
	public function setSalt($salt) {
		$this->salt = $salt; 
		return $this;
	}


	/**
	* generate our random byte which is used during the salting process
	*
	* @param count int The number of times to perform the check
	*
	* @return 
	*/
	private function getRandomBytes($count) {
		$bytes = '';

		// OpenSSL slow on Win
		if(function_exists('openssl_random_pseudo_bytes') && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) { 
			$bytes = openssl_random_pseudo_bytes($count);
		}

		if($bytes === '' && is_readable('/dev/urandom') &&
			($hRand = @fopen('/dev/urandom', 'rb')) !== FALSE) {
			$bytes = fread($hRand, $count);
			fclose($hRand);
		}

		if(strlen($bytes) < $count) {
			$bytes = '';

			if($this->randomState === null) {
				$this->randomState = microtime();

				if(function_exists('getmypid')) {
					$this->randomState .= getmypid();
				}
			}

			for($i = 0; $i < $count; $i += 16) {
				$this->randomState = md5(microtime() . $this->randomState);

				if (PHP_VERSION >= '5') {
					$bytes .= md5($this->randomState, true);
				} else {
					$bytes .= pack('H*', md5($this->randomState));
				}
			}
			$bytes = substr($bytes, 0, $count);
		}
		return $bytes;
	}


	/**
	* The following is code from the PHP Password Hashing Framework
	*
	* @param input string The input to be used for extra hashing and random byte generation
	*
	* @return string
	*/
	private function encodeBytes($input) {	
		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$output = '';
		$i = 0;
		do {
			$c1 = ord($input[$i++]);
			$output .= $itoa64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $itoa64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $itoa64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $itoa64[$c1];
			$output .= $itoa64[$c2 & 0x3f];
		} while (1);

		return $output;
	}
}