<?

/**
* S3
*
*
* @package Meagr
* @version 1.0.0
* @author Paul Whitehead
*/

namespace Meagr; 

class s3 {

    private static $instance;

    //holds our s3 instance
    private $s3; 

    //the bucket name
    public $bucket_name = ''; 

    //s3 key
    private $key = ''; 

    //s3 secret
    private $secret = ''; 

    // 'private', 'public-read', 'public-read-write', 'authenticated-read'   
    private $acl = 'public-read'; 

    //the default location
    private $location = 'EU'; 

    //array of s3 endpoints
    private $endpoints = array( 'default' => 's3.amazonaws.com',
                                'us-west-2' => 's3-us-west-2.amazonaws.com',
                                'us-west-1' => 's3-us-west-1.amazonaws.com',
                                'EU' => 's3-eu-west-1.amazonaws.com',
                                'ap-southeast-1' => 's3-ap-southeast-1.amazonaws.com',
                                'ap-southeast-2' => 's3-ap-southeast-2.amazonaws.com',
                                'ap-northeast-1' => 's3-ap-northeast-1.amazonaws.com',
                                'sa-east-1' => 's3-sa-east-1.amazonaws.com');

    //our class wide buckets cache
    private $buckets = null; 

    //our bucket contents cache
    private $bucket_contents = array(); 
	

    /**
    * create our instance, depending on the bucket name 
    *
    * @return object
    */    
	public static function init() {

        //create a multuton instance
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
    * our construct
    * setup config details and instantiate the s3 instance
    *
    * @return void
    */  
    private function __construct() {

        //get our s3 connection details
        $s3_config = Config::settings('s3'); 

        //if the bucket name isnt provided, use the default site bucket from the config
        if (is_null($bucket_name)) {  
            $bucket_name = $s3_config['bucket_name'];
        }

        //set our instance variables including our required bucket
        $this->bucket_name = $bucket_name; 

        //set our keys
        $this->key = $s3_config['key'];
        $this->secret = $s3_config['secret'];

        //create and cache our new instance
        $this->s3 = new \Meagr\Vender\S3\S3($this->key, $this->secret); 

        //set the default endpoint
        $this->setEndpoint($this->endpoints[$this->location]);
    }


    /**
    * check if a bucket exists 
    *
    * @param bucket_name string The name of the bucket to be checked
    *
    * @return bool
    */ 
    public function bucketExists($bucket_name = null) {

        //if we have an existing bucket
        if (is_null($bucket_name)) {
            $bucket_name = $this->bucket_name; 
        }        

        //check if we have an already 'safe' name
        if (! strpos($bucket_name, '--')) {
            $bucket_name = $this->encodeBucketName($bucket_name);
        }

        //get our existing buckets and cache if required
        $this->buckets = $this->listBuckets(); 

        //check for the buckets existance
        if (is_array($this->buckets) and in_array($bucket_name, $this->buckets)) {
            return true;
        }

        //else return false
        return false;
    }


    /**
    * set the acl (access control level) of the file or folder 
    *
    * @param level string The acl level required for this instance/file/bucket/connection
    *
    * @return object
    */ 
    public function setAcl($level) {
        $this->acl = $level; 
        return $this; 
    }


    /**
    * set the location of a bucket or a file
    *
    * @param location string the location that we wish to send files to
    *
    * @return object
    */ 
    public function setLocation($location = 'default') {
        $this->location = $location;
        $this->setEndpoint($this->endpoints[$this->location]);
        return $this;
    }

   
    /**
    * change the default endpoint
    *
    * @param host string The endpoint host which will be then used to create a new end point
    *
    * @return object
    */ 
    public function setEndpoint($host) {
        $this->s3->setEndpoint($host);
        return $this;
    }    


    /**
    * list all buckets
    *
    * @return array
    */ 
    public function listBuckets() {
        
        if (is_null($this->buckets)) {
            $this->buckets = $this->s3->listBuckets();
        }
        
        return $this->buckets;
    }


    /**
    * retreive the contents of a bucket
    * 
    * @param bucket_name string The name of the bucket to be checked
    *
    * @return string
    */ 
    public function getBucketLocation($bucket_name = null) {

        if (is_null($bucket_name)) {
            $bucket_name = $this->bucket_name; 
        }

        return $this->s3->getBucketLocation($this->encodeBucketName($bucket_name)); 
    }
   

    
    /**
    * delete a bucket
    * 
    * @param bucket_name string The name of the bucket to be deleted
    *
    * @return void
    */
    public function deleteBucket($bucket_name = null) {

        if (is_null($bucket_name)) {
            $bucket_name = $this->bucket_name; 
        }

        return $this->s3->deleteBucket($this->encodeBucketName($bucket_name)); 
    }


    /**
    * retreive the contents of a bucket
    * 
    * @param bucket_name string The name of the bucket to be retrieved
    *
    * @return array
    */   
    public function getBucket($bucket_name = null) {

        if (is_null($bucket_name)) {
            $bucket_name = $this->bucket_name; 
        }

        $this->bucket_contents[$bucket_name] = $this->s3->getBucket($this->encodeBucketName($bucket_name)); 
        return $this->bucket_contents[$bucket_name]; 
    }


    /**
    * create a bucket
    * 
    * @param bucket_name string The name of the bucket to be added
    *
    * @return array
    */  
    public function addBucket($bucket_name = null) {

        if (is_null($bucket_name)) {
            $bucket_name = $this->bucket_name; 
        }
        return $this->s3->putBucket($this->encodeBucketName($bucket_name), $this->acl, $this->location);
    }    


    /**
    * add a file to a bucket
    *
    * @param file string The path of the file to be uploaded
    * @param bucket_name string The name of the bucket to be added
    * @param acl string Additional access controls for this file
    *
    * @return array
    */    
    public function addFile($file, $bucket_name = null, $acl = null) {

        //check for our bucket
        if (is_null($bucket_name)) {
            $bucket_name = $this->bucket_name; 
        }

        //set the acl for each file or use the default
        if (is_null($acl)) {
            $acl = $this->acl;
        }

        //create a handle for the upload
        return $this->s3->putObject($this->s3->inputFile($file, false), $this->encodeBucketName($bucket_name), basename($file), $acl);
    }

 
    /**
    * get a file from s3
    * 
    * @param filename string The path of the file to be uploaded
    * @param bucket_name string The name of the bucket to be added
    *
    * @return array
    */ 
    public function getFile($filename, $bucket_name = null) {
        //check for our bucket
        if (is_null($bucket_name)) {
            $bucket_name = $this->bucket_name; 
        }

        $file = $this->s3->getObject($this->encodeBucketName($bucket_name), $filename);
        if ($file->code == '200') {
            return $file->body;
        }

        return $file->error; 
    }


    /**
    * delete a file from s3
    * 
    * @param file string The path of the file to be deleted
    * @param bucket_name string The name of the bucket to be the file is in
    *
    * @return void
    */ 
    public function deleteFile($file, $bucket_name = null) {

        //check for our bucket
        if (is_null($bucket_name)) {
            $bucket_name = $this->bucket_name; 
        }

        return $this->s3->deleteObject($this->encodeBucketName($bucket_name), $file);
    }
   

   /**
    * copy a file from one bucket to another
    * 
    * @param new_file string The new path 
    * @param bucket_name string The name of the bucket of the new bucket
    * @param file string The path of the exiting file 
    * @param bucket_name string The name of the bucket of the existing file
    *
    * @return bool
    */ 
    public function copyFile($new_file, $new_bucket, $file, $bucket_name = null, $metaHeaders = array(), $requestHeaders = array()) {

        //check for our bucket
        if (is_null($bucket_name)) {
            $bucket_name = $this->bucket_name; 
        }

        $copy = $this->s3->copyObject($this->encodeBucketName($bucket_name), 
                                    $file,
                                    $this->encodeBucketName($new_bucket), 
                                    $new_file,  
                                    $metaHeaders, 
                                    $requestHeaders);

        return (is_array($copy)) ? : false;
    }


    /**
    * helper function to ensure unique bucket names in the global aws cloud
    * 
    * @param bucket_name string The name of the bucket to be encoded
    *
    * @return string
    */  
    public function encodeBucketName($bucket_name) {
        //make sure our buckets are always unique 
        //and always repclicable by adding a prefix
        
        //trim whitespace
        $bucket_name = trim($bucket_name);
        //removing any _ replacing with -
        $bucket_name = str_replace('_', '-', $bucket_name);
         //seperating our name and the md5 suffix with -- 
        $bucket_name .= '--' . md5($bucket_name); 
        //remove any . for loads of reasons
        $bucket_name = str_replace('.', '', $bucket_name);
        //removing prefix and suffix -
        $bucket_name = trim($bucket_name, '/');
        //make it all lowercase
        return strtolower($bucket_name); 
    }

    
    /**
    * helper to return just the normal part of the anem
    * 
    * @param bucket_name string The name of the bucket to be decoded
    *
    * @return string
    */
    public function decodeBucketName($bucket_name) {
        $name = explode('--', $bucket_name);
        return $name[0];  
    }
}