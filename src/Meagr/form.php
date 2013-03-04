<?

/**
 * Form
 *
 *
 * @package Meagr
 * @version 1.0.0
 * @author Paul Whitehead
 */	

namespace Meagr; 
use Meagr\Core as Core; 

class Form {

	private static $attribute_ignore = array('validate', 'label');

 	/**
 	 * form attributes defaults
 	 * 
     * @access public
     * @var array 
     */
	public $form_defaults = array(

			//the form ID
			'id' => 'form', 
		
			//any required form classes
			'class' => '',
		
			//horizonal (inline - no label or control tags) or vertical layout
			'horizonal' => false, 
		
			//the request type get / post
			'method' => 'get', 
		
			//the location to pass the form data
			'action' => '', 
		
			//the for NONCE to protect against bad people
			'_nonce' => 'form'
		);


 	/**
 	 * default input array setup
 	 * 
     * @access public
     * @var array 
     */
	public $input_defaults = array(

			//element ID
			'id' => '', 
		
			//element name
			'name' => '', 
		
			//any classes to be added, space seperated
			'class' => 'input-large', 
		
			//the label for the element
			'label' => '',
		
			//if the input should be hoizonal
			'inline' => false,
		
			//for selects only 
			'multiple' => false,
		
			//editable or not
			'disabled' => false,
		
			//any help / additional text to add after the input
			'help' => '',
		
			//the placeholder text
			'placeholder' => '', 
		
			//the type of input [text, checkbox, select, textarea, email, etc]
			'type' => 'text',
		
			//an array of functions to perform in order to know the value is valid
			'validate' => array(), 
		
			//the value which will be used and likely stored
			'value' => '', 
		
			//the default value to use if the value key is empty
			'default' => '',
		
			//any errors found during validation should be added here
			'errors' => array()
		);


 	/**
 	 * default addon array setup
 	 * 
     * @access public
     * @var array 
     */
	public $addon_defaults = array(
			//the value / title of the addon (displayed in the button / span)
			'value' => '', 
			//the type of addon, [button, span]
			'type' => 'span', 
			//any options, will create drop down if provided. multidimensional array required. 
			//inner arrays should have [id, title, href] keys, or plain line of text to be echoed in place
			'options' => array(),
			//the type => class array - not really used i dont think
			'type_classes' => array(
				'button' => 'btn', 
				'span' => 'add-on'
			)
		);


 	/**
 	 * form_gather array, will hold our form setup. added to by the addFields() methods
 	 * 
     * @access private
     * @var array 
     */
	private $form_gather = array();


 	/**
 	 * instance wrapper
 	 * 
     * @access private
     * @var array 
     */	
	private static $instance = array();


	 /**
 	 * build form html container array, which will be imploded prior to returning to view
 	 * 
     * @access private
     * @var string 
     */	
	private $form_build = ''; 


	 /**
 	 * is the form horizonal
 	 *  
     * @access private
     * @var bool 
     */	
	private $horizonal = false; 


	/**
	 * our init method, checks for an existing instance, 
	 * this means we can refer to our instances from anywhere without globals 
	 *
	 * @param init_array 	array 	the array of input items
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	public static function init(array $init_array) {
		
		//make sure we have a form ID provided
		if (! isset($init_array['id'])) {
			throw new MeagrException('Form ID must be provided');
		}

		//check for an existing instance
		if (! isset(self::$instance[$init_array['id']])) {
			self::$instance[$init_array['id']] = new self($init_array);

			\Meagr\Debug::init('log')->add(array('message' => 'Create new form instance: ' . $init_array['id'],
											'class' => __METHOD__, 
											'status' => 'success', 
											'backtrace' => Debug::backtrace()));			
		}

		//return our instance, new or old
		return self::$instance[$init_array['id']];
	}	


	/**
	 * prevent direct instantiation, fill in and parse all input items added to the instance
	 *
	 * @param construct_array
	 *
	 * @author Paul Whitehead
	 * @return void
	 */
	private function __construct($construct_array) {

		//merge our defaults and the passed arguments
		$this->form_attributes = self::parseArgs($construct_array, $this->form_defaults);

		if ($this->form_attributes['horizonal']) {
			$this->form_attributes['class'] .= ' form-inline';

			//change the class wide var
			$this->horizonal = true; 
		}		
	}


	/**
	 * check if our form is valid
	 *
	 * First checks whether the _nonce is valid, then checks through field validate arrays
	 *
	 * @author Paul Whitehead
	 * @return bool 
	 */
	public function valid() { 

		//clear our return array
		$errors_array = array();
		$values_array = array();

		//loop through our registered form elements and check for validation
		foreach($this->form_gather as $input) { 

			// incase of html strings and other form stuff
			if (! isset($input['id'])) {
				continue;
			}

			//clear our errors anray, JIC (just in case)
			$errors = array(); 

			//get our request method from the form setup vars
			$method = $this->form_attributes['method']; 			

			//save our value
			$values_array[$input['id']] = Input::$method($input['id']); 

			//if not set, continue and set flag as passed validation
			if (! isset($input['validate'])) {
				continue;
			}

			//if we have a closure to process
			if (is_callable($input['validate'])) { 

				//if the closure reutrned any errors
				if($closure_errors = $input['validate']()) { 

					//if we received TRUE from the closure
					if ($closure_errors === true) {

						//and skip to next input
						continue;						
					}

					//add them to an array and pass them to our form errors session 
					if (is_array($closure_errors)) {
						//if we recieved an array of errors from the closure
						$errors_array[$input['id']] = $closure_errors;

					//if we just get a string back	
					} else {
						$errors_array[$input['id']][] = $closure_errors;
					}

					//...and skip to next input
					continue;
				}

				//and skip to next input
				continue;
			}

			//make sure we're dealing with an array for looping
			if (! is_array($input['validate'])) {
				$input['validate'] = array($input['validate']);
			}

			//loop through our validation rules and check them individually
			foreach($input['validate'] as $rule => $checks) { 

				//pass in the rule (:is, :not:, :valid), the checks (array('email' => 'message if fail')) and the value to check
				$errors[] = Validate::validate($rule, $checks, Input::$method($input['id']));
			}

			//remove any empty slots
			array_filter($errors);

			//if we got no errors back return true
			if (empty($errors)) {
				return true;
			}

			//loop the errors and add them as form session data
			foreach($errors as $error) { 

				//check for nulls / bool false
				if (! $error) {
					continue;
				}

				//if we have multiple failed rules coming back from validation
				if (count($error) > 1) {

					//loop through them and add them individually
					foreach($error as $e) {
						$errors_array[$input['id']][] = $e;
					}
					continue;
				}

				//everything else gets added to the array 
				$errors_array[$input['id']][] = $error[0];
			}
		}

		//if we have no errors return true
		if (empty($errors_array)) {
			return true;
		}

		//if we have errors, record our values in the session
		Input::session($this->form_attributes['id'] . '.values', $values_array);

		//add the array of errors to the session 
		Input::session($this->form_attributes['id'] . '.errors', $errors_array);

		//and glass is half empty, default to failure
		return false;
	}


	/**
	 * build the form from the array created in the frontend, so an html string
	 *
	 * @param echo 	bool 	Should be return or echo the built content
	 *
	 * @author Paul Whitehead
	 * @return string
	 */
	public function build($echo = true) {

		//confirm we have anything to work with
		if (! count($this->form_gather)) {
			throw new MeagrException('build called on empty form');
		}

		//create our form string
		$this->structureFormOpen();

		//check for our nonce
		if (isset($this->form_attributes['_nonce']) and ! empty($this->form_attributes['_nonce'])) {
			$this->form_build[] = Nonce::input($this->form_attributes['_nonce']);
		}		

		//get the errors for this form if there are any
		$errors = Input::session($this->form_attributes['id'] . '.errors');

		//get the errors for this form if there are any
		$values = Input::session($this->form_attributes['id'] . '.values');

		//walk the form elements array and create our inputs
		array_walk($this->form_gather, function($input, $key) use ($errors, $values) {

			//check for errors for this input
			if (isset($errors[$input['id']])) {

				//assign the errors to the input before build
				$input['errors'] = $errors[$input['id']];

				//delete the session var after use
				Arr::delete($_SESSION[ID][$this->form_attributes['id']]['errors'], $input['id']);
			}

			//check for value for this input
			if (isset($values[$input['id']]) and ! empty($values[$input['id']])) {

				//assign the errors to the input before build
				$input['value'] = $values[$input['id']];

				//delete the session var after use
				Arr::delete($_SESSION[ID][$this->form_attributes['id']]['values'], $input['id']);
			}			

			//check for html first
			if (isset($input['html'])) {

				//add our decoded html string and continue
				$this->form_build[] =  htmlspecialchars_decode($input['html']);
				return;
			}

			//merge our input values with our system defaults
			$input = self::parseArgs($input, $this->input_defaults);

			//create our class input method name, will be like inputText() or inputTextarea()
			$method_name = 'input' . ucwords($input['type']); 
			if (! is_callable(array($this, $method_name))) {
				throw new MeagrException('No input method found for requested type "' . $input['type'] . '" ');
			}

			//assign our clas wide input variable
			$this->input = $input; 

			//run the function
			$this->$method_name();
		});	

		//close the form
		$this->structureFormClose();

		//echo / return
		if ($echo) {
			//use \n for source readability
			echo implode("\n", $this->form_build);
			
		} else {
			return $this->form_build;
		}
	}


	/**
	 * add a single array of input values
	 *
	 * @param $fields_array array 	An array of arrays of input key => value pairs denoting our input type
	 *
	 * @author Paul Whitehead
	 * @return Object
	 */
	public function addFields(array $fields_array) {
		if (empty($fields_array)) {
			throw new MeagrException('Array must be provided');
		}

		foreach($fields_array as $input) { 
			$this->form_gather[$input['id']] = $input;
		}

		//allow for method chaining
		return $this;
	}


	/**
	 * add multiple arrays of input values within a multidimensional array
	 *
	 * @param $fields_array array 	An array of input key => value pairs denoting our input type
	 *
	 * @author Paul Whitehead
	 * @return Object
	 */
	public function addField(array $fields_array) {
		if (empty($fields_array)) {
			throw new MeagrException('Array expected must be provided');
		}

		//add the input array to the form mix
		$this->form_gather[$fields_array['id']] = $fields_array;

		//allow for method chaining
		return $this;
	}


	/**
	 * add straight HTML into the form in particular required places
	 *
	 * @param string 	string 	The string / html that should be inserted at this point in the build
	 *
	 * @author Paul Whitehead
	 * @return Object
	 */
	public function addHTML($string = null) { 

		if (! is_null($string)) {
			//add the string inside an array so we always know we can loop through $form
			//encode it so it doesnt corrupt any of our data or its self
			$this->form_gather[]['html'] = htmlspecialchars(trim($string)); 
		}

		return $this;
	}


/****************************\
	Help text / Errors
\****************************/	

	/**
	 * create a string of 'help-block' text if provided
	 *
	 * @author Paul Whitehead
	 * @return Object
	 */
	private function textHelp() {

		//if the form is horizonal, we dont want to add structure
		if ($this->horizonal) {
			return $this;
		}

		//check for errors first and display inplace of normal help text
		if (isset($this->input['errors']) and ! empty($this->input['errors'])) {
			//loop the errors, create help text for each
			foreach($this->input['errors'] as $error) {
				$this->form_build[] =  '<span class="help-block">' . $error . '</span>';
			}

			return $this;
		}

		//if there are no errors, display the normal help text
		if (isset($this->input['help']) and ! empty($this->input['help'])) {
			$this->form_build[] =  '<span class="help-block">' . $this->input['help'] . '</span>';
		}

		return $this;
	}


/****************************\
	Label
\****************************/	

	/**
	 * open a label tag and add required classes and text
	 *
	 * @author Paul Whitehead
	 * @return Object
	 */
	private function textLabelOpen($label_text = true) {

		//if the form is horizonal, we dont want to add structure
		if ($this->horizonal and ! in_array($this->input['type'], array('checkbox', 'radio'))) {
			return $this;
		}
		
		if (isset($this->input['label']) and $this->input['label'] !== '') { 

			$string = '<label class="control-label ' . ($this->input['inline'] ? 'inline ' : '') . ' ' . $this->input['type'] . '" ';
			$string .= ' for="'. $this->input['id'] .'">'; 

			//check if we want the label text
			if ($label_text) {
				$string .= $this->input['label'];
			}

			$this->form_build[] =  $string;
		}

		return $this;
	}

	/**
	 * close an label tag
	 *
	 * @author Paul Whitehead
	 * @return Object
	 */
	private function textLabelClose() {
		$this->form_build[] =  '</label>';
		return $this;
	}


/****************************\
	Input types
\****************************/	

	/**
	 * create input text / email / password / etc fields
	 *
	 * @author Paul Whitehead
	 * @return Object
	 */
	private function inputText() { 

		//check if we have been passed a closure
		if (is_callable($this->input['value'])) {
			$this->input['value'] = $this->input['value']();
		}

		$this->structureControlGroupOpen();
		$this->textLabelOpen();
		$this->textLabelClose();
		$this->structureControlOpen();				
		$this->structureAddon('prepend');

		//add our input to the form string
		$this->form_build[] =  '<input ' . $this->attrKeyValue($this->input, array('label', 'help', 'multiple', 'inline')) . ' />';

		$this->structureAddon('append');
		$this->structureControlClose();
		//put the help outside the control incase of errors (which wont show otherwise)
		$this->textHelp();
		$this->structureControlGroupClose();

		return $this;
	}


	/**
	 * add extra's, buttons, drop downs, etc. 
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function structureAddon($type = 'prepend') {

		if (! is_array($this->input[$type]) or empty($this->input[$type])) {
			return $this;
		}

		//combine our arugments
		$addon = parseArgs($this->input[$type], $this->addon_defaults);

		//check if we want a drop down box 
		//if options have not been provided
		if (! is_array($this->input[$type]['options']) or empty($this->input[$type]['options'])) {

			//check for the need to do anything
			if (is_array($this->input[$type]) and ! empty($this->input[$type])) {
				$this->form_build[] = '<'. $addon['type'] .' class="add-on">'. $addon['value'] .'</'. $addon['type'] .'>';
			}

		//if we have options				
		} else {

  			$this->form_build[] = '<div class="btn-group">';
			$this->form_build[] = '<button class="btn dropdown-toggle" data-toggle="dropdown">'. $addon['value']; 
			$this->form_build[] = '<span class="caret"></span></button>';
			$this->form_build[] = '<ul class="dropdown-menu">';

			foreach ($this->input[$type]['options'] as $key => $value) {

				//check if a divider is requested
				if ($key == 'divider' and $value === true) {
					$this->form_build[] = '<li class="divider"></li>';
					continue;
				}

				//check is we just have a simple string or value
				if (! is_array($value)) {
					$this->form_build[] = $key;
					continue;
				}

				$this->form_build[] = '<li><a '. $this->attrKeyValue($value, array('icon')) .'>'. $value['title'] .'</a></li>';
				continue;
			}
			$this->form_build[] = '</ul>';
  			$this->form_build[] = '</div>';
		}
		return $this;
	}


	/**
	 * function wrapper
	 *
	 * @author Paul Whitehead
	 * @return void
	 */
	private function inputEmail() { 
		$this->inputText();
	}


	/**
	 * function wrapper
	 *
	 * @author Paul Whitehead
	 * @return void
	 */
	private function inputPassword() {
		$this->inputText();
	}


	/**
	 * create textarea html string
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function inputTextarea() {	
		$this->form_build[] =  '<textarea ' . $this->attrKeyValue($this->input, array('value', 'help', 'label', 'type')) . '>' . $this->input['value'] . '</textarea>';
		return $this;
	}	


	/**
	 * create checkbox form element from data array / closure provided
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function inputCheckbox() {
		//check if we have been passed a closure
		if (is_callable($this->input['options'])) {
			$this->input['options'] = $this->input['options']();
		}

		//if we have no options, leave
		if (! is_array($this->input['options'])) {
			return $this; 
		}

		//start our structure
		$this->structureControlGroupOpen();
		$this->structureControlOpen();
		$this->text($this->input['label']);

		//make sure our input name has the '[]' so allowing for multiple values
		if (! strpos('[]', $this->input['name'])) {
			$this->input['name'] .= '[]'; 
		} 

		//loop options
		foreach($this->input['options'] as $option_key => $option_value) {
			$this->textLabelOpen(false);	
   			//check if we need to check this input
			$checked = (is_array($this->input['value']) and in_array($option_key, $this->input['value'])) ? 'checked="checked" ' : '';	      
			$this->form_build[] =  '<input type="checkbox" value="'. $option_value .'"' . $this->attrKeyValue($this->input, array('value', 'plceholder', 'options', 'help', 'label')) . $checked . ' />' . $option_value;
			$this->textLabelClose();			
		}

		//add help text
		$this->textHelp();
		//close structure
		$this->structureControlClose();
		$this->structureControlGroupClose();

		return $this;
	}	


	/**
	 * create radio form element from data array / closure provided
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function inputRadio() {
		//check if we have been passed a closure
		if (is_callable($this->input['options'])) {
			$this->input['options'] = $this->input['options']();
		}

		//if we have no options, leave
		if (! is_array($this->input['options'])) {
			return $this; 
		}

		//start our structure
		$this->structureControlGroupOpen();
		$this->structureControlOpen();
		$this->text($this->input['label']);

		//loop options
		foreach($this->input['options'] as $option_key => $option_value) {
			//open the label
			$this->textLabelOpen(false);		

			//check if we have a value provided
			if (! isset($this->input['value']) or trim($this->input['value']) == '') {

				//if no input value is found, check for a default value and use that is present
				$checked = (isset($this->input['default']) and $this->input['default'] == $option_key) ? 'checked="checked" ' : '';	 

			//if we DO have a value provided
			} else {
				$checked = ($option_key == $this->input['value']) ? 'checked="checked" ' : '';	      
			}

			//create our input 
			$this->form_build[] =  '<input type="radio" value="'. $option_value .'" ' . $this->attrKeyValue($this->input, array('value', 'options', 'help', 'label', 'type')) . $checked . ' />' . $option_value;

			//close the label
			$this->textLabelClose();			
		}

		//add help text
		$this->textHelp();
		//close structure
		$this->structureControlClose();
		$this->structureControlGroupClose();

		return $this;
	}		


	/**
	 * create select box and multiple select box
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function inputSelect() {

		//check if we have been passed a closure
		if (is_callable($this->input['options'])) {
			$this->input['options'] = $this->input['options']();
		}

		if (! is_array($this->input['options'])) {
			return $this; 
		}

		$this->structureControlGroupOpen();
		$this->structureControlOpen();
		$this->textLabelOpen()->textLabelClose();	

		//make sure multiple isnt always added, check if we want it and add it properly
		if ($this->input['multiple']) {
			$this->input['multiple'] = 'multiple';
		} else {
			unset($this->input['multiple']);
		}

		$this->form_build[] =  '<select ' . $this->attrKeyValue($this->input, array('value', 'options', 'help', 'label')) . '>';
		foreach($this->input['options'] as $option_key => $option_value) {		      
			$this->form_build[] =  '<option value="'. $option_key .'">' . $option_value . '</option>';
		}
		$this->form_build[] =  '</select>';
		
		$this->textHelp();
		$this->structureControlClose();
		$this->structureControlGroupClose();

		return $this;
	}			


	/**
	 * create submit button
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function inputSubmit() {
		if ($this->input['primary']) {
			$this->input['class'] .= ' btn-primary';
		}

		if ($this->input['label']) {
			$this->textLabelOpen();
		}

		$this->form_build[] =  '<button type="submit" class="btn '. $this->input['class'] .'">'. $this->input['value'] .'</button>';

		if ($this->input['label']) {
			$this->textLabelClose();
		}		

		return $this;
	}


	/**
	 * create a link, for cancel buttons etc
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function inputLink() {
		if ($this->input['primary']) {
			$this->input['class'] .= ' btn-primary ';
		}

		if ($this->input['label']) {
			$this->textLabelOpen();
		}

		$this->input['class'] .= ' btn'; 

		$this->form_build[] =  '<a ' . $this->attrKeyValue($this->input, array('value', 'options', 'help', 'label')) .'>'. $this->input['value'] .'</a>';

		if ($this->input['label']) {
			$this->textLabelClose();
		}		

		return $this;
	}	


/****************************\
	Structure
\****************************/

	/**
	 * create opening form tag and assign attributes as required
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function structureFormOpen() {
		$this->form_build[] = '<form '. $this->attrKeyValue($this->form_attributes, array('horizonal', '_nonce')) .'><fieldset>';
		return $this;
	}


	/**
	 * close form tag
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function structureFormClose() {
		$this->form_build[] =  '</fieldset></form>';
		return $this;
	}


	/**
	 * open a control group tag
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function structureControlGroupOpen() {

		//if the form is horizonal, we dont want to add structure
		if ($this->horizonal) {
			return $this;
		}

		if (is_array($this->input['errors']) and ! empty($this->input['errors'])) {
			$class = 'error';
		}

		$this->form_build[] =  '<div class="control-group '. $this->input['id'] . ' ' . $class . '">';
		return $this;
	}


	/**
	 * clsoe a control group tag
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function structureControlGroupClose() {

		//if the form is horizonal, we dont want to add structure
		if ($this->horizonal) {
			return $this;
		}

		$this->form_build[] =  '</div><!--control-group-->';
		return $this;
	}


	/**
	 * open a control tag
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function structureControlOpen() {

		//if the form is horizonal, we dont want to add structure
		if ($this->horizonal) {
			return $this;
		}

		//check for our appended or prepended extra's and add the correct class to a variable if present
		$extras = array('prepend', 'append');
		$class = ''; 
		foreach($extras as $extra) {
			if (is_array($this->input[$extra]) and !empty($this->input[$extra])) {
				$class .= ' input-' . $extra; 
			}
		}

		$this->form_build[] =  '<div class="controls '. $class .'">';	
		return $this;			
	}


	/**
	 * close a control tag
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function structureControlClose() {

		//if the form is horizonal, we dont want to add structure
		if ($this->horizonal) {
			return $this;
		}

		$this->form_build[] =  '</div><!--controls-->';			
		return $this;
	}	


	/**
	 * close a control tag
	 *
	 * @author Paul Whitehead
	 * @return object
	 */
	private function text($string) {

		//if the form is horizonal, we dont want to add structure
		if ($this->horizonal) {
			return $this;
		}

		$this->form_build[] =  $string;			
		return $this;
	}		



/****************************\
	Helpers
\****************************/


	/**
	 * return a string of key="value" attributes, used throughout
	 *
	 * @author Paul Whitehead
	 * @return object
	 */	
	private function attrKeyValue(array $key_vals, $excludes = null) {

		//check for excludes, if null, create empty array
		if (is_null($excludes))  {
			$excludes = array();
		}

		//if the array isnt empty - a string
		if(! is_array($excludes)) {
			//make an array
			$excludes = array($excludes);
		}

		//merge with our class wide ignored attributes
		$excludes = self::parseArgs($excludes, self::$attribute_ignore);

		//clear our blanks
		array_filter($key_vals);

		//create our attributes string
		$attributes = ''; 
		foreach($key_vals as $attr => $value) { 

			//check we arnt ignoring this one
			if (in_array($attr, $excludes)) { 
				continue;
			}

			//incase of append/prepend's
			if (is_array($value)) {
				continue;
			}

			//dont add empty, or bool false values
			if (empty($value)) {
				continue;
			}
			
			//continue making string
			$attributes .= $attr . '="' . $value . '" '; 
		}
		return $attributes;
	}	


	/**
	 * Combine two objects / arrays and return them
	 *
	 * @author Paul Whitehead via wordpress
	 * @return string
	 **/
	static function parseArgs($args, $defaults) {
		if (is_object($args)) {
			$r = get_object_vars( $args );
		} elseif (is_array($args)) {
			$r =& $args;
		}

		if (is_array($defaults)) {
			return array_merge($defaults, $r);
		}
		return $r;
	}	
}