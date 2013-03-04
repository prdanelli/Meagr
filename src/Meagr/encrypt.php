<?

/**
* Encrypt
*
* @usage
*  
* $plaintext = "this is a test";
* $key = Core\encrypt::gen_base64_key();
* $iv = Core\encrypt::gen_base64_ivector();
* $cyphertext = Core\encrypt::encrypt_text($plaintext, $key, $iv);
* $decyphertext = Core\encrypt::decrypt_text($cyphertext, $key, $iv);
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class Encrypt {
    
    // const EXAMPLEKEY1 = "UBeChsDHZOYdbITKz/LXceb3XMPVFOAP";
    // const EXAMPLEIV1 = "vsrpN/tmchipHza9jldMLVNCowLpkse5WOU8mVTnSOo=";
 

    /**
    * generate our base64 key from the number of bytes entered 
    *
    * @param bytes int the number of bytes to be used in our key
    * @param strong bool
    *
    * @return string
    */   
    public static function gen_base64_key($bytes = 24, &$strong = null) {
        return base64_encode(openssl_random_pseudo_bytes($bytes, $strong));
    }
 

    /**
    * 
    *
    * @return string
    */   
    public static function gen_base64_ivector() {
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size);
        return base64_encode($iv);
    }



    /**
    * Encode a string from the values that have been generated
    *
    * @param text string The string to be encoded
    * 
    * @return string
    */
    public static function encrypt($text) {
        $config = Config::settings('encrypt');
        $base64key = $config['key'];
        $base64ivector = $config['iv'];

        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, base64_decode($base64key), bzcompress(trim($text)), MCRYPT_MODE_CBC, base64_decode($base64ivector)));
    }

    /**
    * Decode a string from the values that have been generated
    *
    * @param text string The string that has been encoded
    * 
    * @return string
    */
    public static function decrypt($text) {
        $config = Config::settings('encrypt');
        $base64key = $config['key'];
        $base64ivector = $config['iv'];

        return bzdecompress(trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, base64_decode($base64key), base64_decode($text), MCRYPT_MODE_CBC, base64_decode($base64ivector))));
    }
}