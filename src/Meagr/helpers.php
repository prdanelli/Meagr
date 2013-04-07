<?

//register our language function
function __($string, $default = null) {
	return \Meagr\Language::init(LANG_CODE)->get($string, $default);
}


if (! function_exists('p')) {
	function p() {
		foreach(func_get_args() as $array) {
			echo '<pre style="font-size: .8em; line-height: 1.3em; letter-spacing: 0px; font-family: monaco, arial, sans-serif;">';
			print_r($array);
			echo '</pre>';
		}
	}
}

function pd() {
	foreach(func_get_args() as $array) {
		echo '<pre style="font-size: .8em; line-height: 1.3em; letter-spacing: 0px; font-family: monaco, arial, sans-serif;">';
		print_r($array);
		echo '</pre>';
	}

	die();
}

function v() {
	foreach(func_get_args() as $array) {
		echo '<pre style="font-size: .8em; line-height: 1.3em; letter-spacing: 0px; font-family: monaco, arial, sans-serif;">';
		var_dump($array);
		echo '</pre>';
	}
}

function vd() {
	foreach(func_get_args() as $array) {
		echo '<pre style="font-size: .8em; line-height: 1.3em; letter-spacing: 0px; font-family: monaco, arial, sans-serif;">';
		var_dump($array);
		echo '</pre>';
	}
	die();
}


/* ========= browser detection ========= */

function is_firefox(){
	
	if(\Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_FIREFOX) return TRUE;	
}

function firefox_version(){
	
	if(\Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_FIREFOX) return (int)\Meagr\Browser::init()->getVersion();
}

function is_ie8(){
	
	if( \Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_IE && \Meagr\Browser::init()->getVersion() >= 8 ) return TRUE;
}

function is_ie(){
	
	if(\Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_IE) return TRUE;
}

function is_ie67() {
	
	if(\Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_IE && \Meagr\Browser::init()->getVersion() < 8 ) return TRUE;
}

function is_ie6() {
	
	if(\Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_IE && \Meagr\Browser::init()->getVersion() < 7 ) return TRUE;
}

function is_opera(){
	
	if(\Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_OPERA) return TRUE;		
}

function is_webkit(){
	
	if(\Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_SAFARI || \Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_CHROME) return TRUE;		
}

function is_safarit(){
	
	if(\Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_SAFARI) return TRUE;		
}

function is_chrome(){
	
	if(\Meagr\Browser::init()->getBrowser() == \Meagr\Browser::BROWSER_CHROME) return TRUE;		
}


/**
 * Combine two objects / arrays and return them
 *
 * @author Paul Whitehead via wordpress
 * @return string
 **/
function parseArgs($args, $defaults) {
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