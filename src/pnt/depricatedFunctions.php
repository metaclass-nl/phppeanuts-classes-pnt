<?php
/** @depricated functions from 1.x generalFunctions.php
 * and php5Functions.php
 */

/* the following 1.x functionality is removed from phpPeanuts 2.x
 */

/** Print a variable for debugging. 
* Warning, do not use in production, this function does not properly encode 
* the html, so it may make your site vurnerable to cross site scripting attacks.
*/
function printDebug($obj) {
	print "<pre>";
	print "\n\n##################################\n";		
	print_r($obj);
	print "\n##################################\n";
	print "</pre>\n";
}

/** Returns a shallowcopy of the supplied array
* Assigns primitive types and objects to the new array by value.
* Arrays are assigned by reference.
* @param Array $anArray Array to be copied
* @result Array Shallowcopy
*/
function arrayCopy(&$anArray) {
	$copy = array();
	forEach(array_keys($anArray) as $key)
		if (is_array($anArray[$key]))
			$copy[$key] =& $anArray[$key];
		else
			$copy[$key] = $anArray[$key];
	return $copy;
}	

/** @depricated
 * @result array with a reference to the value as its element.
 * Use the key parameter if supplied
 */
function arrayWith(&$value, $key=0) {
	$arr[$key] =& $value;
	return $arr;
}

function array_assocAddAll(&$addTo, $toAdd) {
	reset($toAdd);
	while ( list($key) = each($toAdd) )
		$addTo[$key] = $toAdd[$key];
}

function array_addAll(&$addTo, $toAdd) {
	reset($toAdd);
	while ( list($key) = each($toAdd) )
		$addTo[] = $toAdd[$key];
}

function getBrowser() {
	$b = $_SERVER['HTTP_USER_AGENT'];
	$ie40 = preg_match("/MSIE 4.0/i", $b);
	$ie50 = preg_match("/MSIE 5.0/i", $b);
	$ie55 = preg_match("/MSIE 5.5/i", $b);
	$ie60 = preg_match("/MSIE 6.0/i", $b);
	$ie7 = preg_match("/MSIE 7./i", $b);
	$ie8 = preg_match("/MSIE 8./i", $b);
	$opera = preg_match("/opera/i", $b);
	$ns47 = preg_match("/Mozilla\/4/i", $b);
	$ns6  = preg_match("/Netscape6/i", $b);
	$mz5  = preg_match("/Mozilla\/5/i", $b);
	
	if ($ie40 == 1) {
		$browser = "Internet Explorer 4.0";
	} else if ($ie50 == 1) {
		$browser = "Internet Explorer 5.0";
	} else if ($ie55 == 1) {
		$browser = "Internet Explorer 5.5";
	} else if ($ie60 == 1) {
		$browser = "Internet Explorer 6.0";
	} else if ($ie7 == 1) {
		$browser = "Internet Explorer 7.x";
	} else if ($ie8 == 1) {
		$browser = "Internet Explorer 8.x";
	} else if ($opera == 1) {
		$browser = "Opera";
	} else if ($ns47 == 1) {
		$browser = "Netscape 4.7";
	} else if ($ns6 == 1) {
		$browser = "Netscape 6";
	} else if ($mz5 == 1) {
		$revPos = strPos($b, 'rv:');
		$rev = $revPos !== false ? subStr($b, $revPos + 3, 3) : '';
		$browser = "Mozilla $rev";
	} else {
		$browser = "Not identified";
	}
	return($browser);
}

/* use for array search, which may return false or null depending on php version
*/
function isFalseOrNull($value) {
	return $value === false || $value === null;
}

/** @depricated. Use clone.
* Returns a copy of the object
* The object's class file must be included by includeClass
* and the object must support constructing throuhg a zero argument constructor
*/
function objectCopy($obj) {
	$className = get_class($obj);
	$copy = new $className();
	reset($obj);
	while(list($field) = each($obj))
		$copy->$field = $obj->$field;
	return $copy;
}

/** returns wheater url includes are allowed by php */
function allowUrlInclude() {
	return version_compare(phpversion(), '5.2.0', '<') 
		?  ini_get('allow_url_fopen')
		: ini_get('allow_url_include');
}
	

/* the following functionality no longer needs to be wrapped */

/** portability wrapper for php5's explicit clone keyword
* for portability implicit copying should no longer be used.
* Mind the possible difference in behavior of the versions of 
* this function between php4 and 5:
* In php5 the clone keyword is used, in php4 an implicit copy is made 
* and then an eventual __clone() method is called on the clone.  
* @param Object $obj the original object to clone
* $return Object the clone of the original object 
*/
function pntClone($obj) {
	return clone $obj;
}

/** Alternative portability wrapper that is more reliable (in php4), but slower.
* Requires the object's class to support construction without parameters.
*/
function safeClone($obj) {
	return clone $obj;
}


/** Returns class name in the same case as it was declared in the class definition. 
* Mind the differences with the php4 version:
* - for php5 the first agrument does expect correct case. In php4 this is lower case. 
    In fact both versions expect the result of get_class().
  - the php4 version triggers a warning if the class was never included or 
    not supplied in lower case. The php5 version triggers the warning 
    if the class does not exist.
* @param string $class the class name according to the result of get_class() 
* @param boolean $warnIfMissing trigger a warning if the class does not exist
* @result The class name in proper case
*/
function getOriginalClassName($class, $warnIfMissing=true) {
	if ($warnIfMissing && !class_exists($class) )
		trigger_error("class does not exist: $class", E_USER_WARNING);

	return $class;
}


/* the following functions have been moved to PntGen */

function getPntVersion() {
	return Gen::getPntVersion();
}

function tryIncludeClass($className, $dirPath='') {
	return Gen::tryIncludeClass($className, $dirPath);
}

function includeClass($className, $dirPath='', $debug=false) {
	return Gen::includeClass($className, $dirPath, $debug);
}

function pntIs_a($obj, $className) {
	return Gen::is_a($obj, $className);
}

function class_hasMethod($className, $methodName) {
	return Gen::class_hasMethod($className, $methodName);
}

function tryIncludeOnce($filePath) {
	return Gen::tryIncludeOnce($filePath);
}

function isBrowserIE() {
	return Gen::isBrowserIE();
}

function is_ofType($value, $type) {
	return Gen::is_ofType($value, $type);
}
function pntToString($value, $max=4) {
	return Gen::toString($value, $max);
}

function pntValueToString($value) {
	return Gen::valueToString($value);
}

function assocsToStrings($array, $max=null) {
	return Gen::assocsToStrings($array, $max);
}

function labelFromObject($value) {
	return Gen::labelFromObject($value);
}

function labelFrom($value) {
	return Gen::labelFrom($value);
}

function pntPrintBacktrace($traceArray) {
	return Gen::printBacktrace($traceArray);
}

function pntGetRelativePath($path) {
	return Gen::getRelativePath($path);
}

function pntGetFsSeparator() {
	return Gen::getFsSeparator();
}

function array_searchCaseInsensitive($needle, $haystack, $strict=false) {
	return Gen::array_searchCaseInsensitive($needle, $haystack, $strict);
}

function is_typeEqual($first, $second) {
	return Gen::is_typeEqual($first, $second);
}

function getSubstr($oString, $sString, $eString, $sPos=0) {
	return Gen::getSubstr($oString, $sString, $eString, $sPos);
}

function toCsvString($array, $separator=';', $qualifier='"') {
	return Gen::toCsvString($array, $separator, $qualifier);
}

function fromCsvString($str, $separator=';', $qualifier='"') {
	return Gen::fromCsvString($str, $separator, $qualifier);
}

function splitFilePath($filePath) {
	return Gen::splitFilePath($filePath);
}

function sum_from( $propertyName, &$arr) {
	return Gen::sum_from($propertyName, $arr);
}

function stripQueryParam($url, $paramName) {
	return Gen::stripQueryParam($url, $paramName);
}

function pntAsInt($value) {
	return Gen::asInt($value);
}

/** WARNING: this function uses $_SERVER directly, which may not be secure */
function pntGetBaseUrl($mode=0) {
	if ($mode) trigger_error('mode no longer supported: '. $mode, E_USER_ERROR);
	trigger_error('Direct use of $_SERVER may not be secure', E_USER_NOTICE);
	return Gen::getBaseUrl($_SERVER);
}
?>