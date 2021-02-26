<?php
/* Copyright (c) MetaClass, 2003-2017

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

/**
 * Utility class 
 * @package pnt
 */
class PntGen {

    static $CLASS_MAX_LENGTH = 246; //linux 255 -  'class.php' ;  eCryptFS 143 - 9

	/** Returns phpPeanuts version identifier 
	@return String */
	static function getPntVersion() {
		return "2.3.0.alpha";
	}
	
	/** Tries to include a class whose file name follows the pnt class file name rule:
	* $fileName = "class$className.php" relative to the ../classes directory
	* Registers included classes so that their names in correct case
	* can be retrieved by classname in lowercase (=result of get_class() )
	* Each class file is only (try)included once, so if you dynamically generate class files
	* during the same request that should include them, only use this service if you understand its implementation
	*
	* WARNING: Do not use this with parameters that may be manipulated or corrupted
	* especially not with data from the request or the session. For performance reasons this
	* does not check its parameters to prevent the inclusion of
	* code from outside the classes folder 
	*
	* @param String $className The name of the class in correct case
	* @param String $dirPath the pathName of the directory relative to the current one
	* @return boolean Wheater the include file was found and included
	*/
	static function tryIncludeClass($className, $dirPath='') {
		if (class_exists($className)) return true;
	
		if ($dirPath && substr($dirPath, -1) != '/')
			$dirPath .= '/';
	
		$result = Gen::tryIncludeOnce("../classes/$dirPath"."class$className.php"); //warning in method comment
		return $result;
	}
	
	/** @see tryIncludeClass(), same method, but triggers a warning if class file is not found.
	* each class file is only (try)included once 
	* We do use include instead of require because the use of a method like this one will
	* unlike require, not tell the line number where it was called from when a file is not found.
	* letting the execution continue may cause an error that reveals more about 
	* the caller that tried to include the class that was not found.
	*
	* WARNING: Do not use this method with parameters that may be manipulated or corrupted
	* especially not with data from the request or the session. For performance reasons this
	* method does not check its parameters to prevent the inclusion of
	* code from outside the classes folder. PntRequestHandler::useClass does check.
	*/
	static function includeClass($className, $dirPath='', $debug=false) {

		if (class_exists($className)) return true;
	
		if ($dirPath && substr($dirPath, -1) != '/')
			$dirPath .= '/';
	
		$result = include_once("../classes/".$dirPath."class$className.php"); //warning in method comment
		if ($debug) {
			print ("Included: ../classes/$dirPath"."class$className.php<BR>");	
		}
		
		return $result;
	}
	
	/** Replacement of php4's is_a that has been depricated in php5 :-((( 
	*/
	static function is_a($obj, $className) {
		return is_object($obj) && class_exists($className) && $obj instanceof $className;
	}
	
	/** 
	 * @param String $className name of the class
	 * @param String $methodName method name in correct case
	 * @return boolean Wheather the class defines or inherits the method 
	 * 	and it is in the scope of the potential caller 
	*/
	static function class_hasMethod($className, $methodName) {
		return in_array(
			$methodName
			, get_class_methods($className)
		);
	}

	/** Tries to include a file once. Returns wheater the file 
	* was included or included before.  Does not trigger warnings.
	* SECURITY WARNING: This method may be exploited to include code obtained from 
	* arbitrary servers using network protocols.
	*/
	static function tryIncludeOnce($filePath) {
		if ( isSet($GLOBALS['PntIncluded'][$filePath]) )
			//inclusion has been tried before, return cached result
			return $GLOBALS['PntIncluded'][$filePath];

        $found = file_exists($filePath);
		$GLOBALS['PntIncluded'][$filePath] = $found;
		if ($found) 
			include_once($filePath); //tryIncludeOnce added to riskyFnctions check
	
		return $found;
	}

	/** @return boolean wheather the browser is Microsoft Internet Explorer */
	static function isBrowserIE() {
		return isSet($_SERVER['HTTP_USER_AGENT']) && 
			preg_match("/MSIE /i", $_SERVER['HTTP_USER_AGENT']);
	}

	/** Returns wheather the supllied value is within the specified type
	* for objects is_a is used, for $type = number is_numeric.
	* otherwise type == get_type 
	* PS: is_numeric is locale dependent,
	* this is consistent with implicit type conversion
	*
	* @param mixed $value
	* @param String $type
	* @return Boolean
	*/
	static function is_ofType($value, $type) {
		if (is_object($value)) 
			return Gen::is_a($value, $type);
		
		$typeOfValue = gettype($value);
		return $typeOfValue == $type
			|| ($type == 'number'
				&& is_numeric($value)
			);
	}
	
	/** For debugging purposes, for user interface string use StringConverter
	 * @return string from which the value van be recognized
	 * @param mixed $value
	 * @param int $max the maximum number of array elements to be shown. 
	 * 		If not all array elements are shown, ' ..' is added after the last element */
	static function toString($value, $max=4) {
		
		if (!is_array($value))
			return Gen::valueToString($value);
	
		//array
		$result = "array(";
		$result .= implode(", ", Gen::assocsToStrings($value, $max));
		if ($max && count($value) > $max)
			$result .= " ..";
		$result .= ")";
		return $result;
	}
	
	/** For debugging purposes, for user interface string use StringConverter
	 * @return string from which the value van be recognized
	 * @param mixed $value anything but an array */
	static function valueToString($value) {
		
		if ($value===null) 
			return 'NULL';
		if ( is_bool($value) ) 
			return ($value ? 'true' : 'false');
		if ( is_string($value) )
			return "'$value'";
		if (Gen::is_ofType($value, 'Exception'))
			return Gen::exceptionToString($value);
		if ( is_object($value)) {
		    try {
                if (method_exists($value, '__toString')) {
                    return $value->__toString();
                } //had trouble with cast not calling __toString()
                if (method_exists($value, 'toString')) {
                    return $value->toString();
                } //depricated support
            } catch (Exception $e) {
		        return get_class($e). ' '. $e->getMessage();
            }
			return 'a '.get_class($value);
		}
		if (is_array($value)) return 'Array';
		return (string) $value;
	}
	
	/** For debugging purposes, for user interface string use StringConverter
	 * @return string from which the value van be recognized
	 * @param Exception $value  */
	static function exceptionToString($e) {
		return get_class($e). '('. $e->getCode(). ' '. $e->getMessage(). ')';
	}

	/** 
	* @param Array $array an associative array
	* @param int $max maximim number of elements to include. If null all are included and 
	*   Gen::toString is used on the values instead of Gen::valueToString.
	* $return An array with strings representing the associations
	*/
	static function assocsToStrings($array, $max=null) {
		$result = array();
		$count = 0;
		foreach ($array as $key => $value) {
		    if ($max!==null && $count > $max) break;
			$result[] = Gen::valueToString($key).'=>'
			    . ($max===null ? Gen::toString($array[$key]) : Gen::valueToString($array[$key]) );
			$count++;
		}
		return $result;
	}

	/** 
	* @param object $value 
	* $return string from which the object can be recognized within its class,
	* 	if not available ::valueToString is used
	*/
	static function labelFromObject($value) {
		if (method_exists($value, 'getLabel') )
			return $value->getLabel();
		
		return Gen::valueToString($value);
	}
	
	/** 
	* @param mixed $value anything but an array 
	* $return string from which the value can be recognized within its class,
	* 	if not an object, the value is casted to string
	*/
	static function labelFrom($value) {
		if (is_object($value))
			return Gen::labelFromObject($value);
		else
			return (string) $value;
	}

	/** Prints a backtrace in html.
	 * @param array $traceArray array from php's debug_backtrace method
	 */
	static function printBacktrace($traceArray) {
		print "\n<TABLE border=1 cellspacing=0>
		<THEAD>
			<TR bgcolor='#CCCCCC'>
				<TD>obj/cls</TD><TD>func</TD><TD>line</TD><TD>file</TD>
			</TR>
		</THEAD>
		<TBODY>";
		$deepest = count($traceArray);
		for ($i=0; $i <= $deepest ; $i++) {
			if (isSet($traceArray[$i]))
				$frame = $traceArray[$i];
			else
				$frame = array('func'.'tion' => '-', 'args' => array() );
			print "\n<TR vAlign=top><TD>";
			if (isSet($frame['object']) && !Gen::is_ofType($frame['object'], 'Exception')) 
				print Gen::toString($frame['object']);
			else 
				print isSet($frame['cl'.'ass']) ? $frame['cl'.'ass'] : '-';
			print "</TD><TD>";
			print $frame['func'.'tion'];
			print "</TD><TD>";
			print isSet($prev['line']) ? $prev['line'] : '&nbsp;';
			print "</TD><TD>";
			print isSet($prev['file']) ? Gen::getRelativePath($prev['file']) : '&nbsp;';
			print "</TD></TR>";
			if (isSet($frame['args']) && count($frame['args']) > 0) {
				print "\n<TR><TD colspan=4><PRE>";
				for ($j=0; $j < count($frame['args']); $j++) {
					print "\n	". htmlEntities(Gen::toString($frame['args'][$j]));
				}
				print " </PRE></TD></TR>";
			}
			$prev = $frame ;
		}
		print "
	</TBODY>
</TABLE>";
	}

	/** @return the path relative to the script
	 * @param string $path to a http or file resource
	 */
	static function getRelativePath($path) {
		$scriptPathArr = explode('/', $_SERVER['SCRIPT_FILENAME']);
		if (!isSet($scriptPathArr[count($scriptPathArr) - 2]) )
			return $path;
		
		$pntBasePath = implode(	'/', array_slice($scriptPathArr, 0, count($scriptPathArr) - 2) );
		return subStr($path, strLen($pntBasePath) );
	}

	/** @return string file system specific direcotry separator */
	static function pntGetFsSeparator() {
		return strpos ( strToLower(php_uname('s')), 'windows') === false 
			? '/' : '\\';
	}
	
	/** Like array_seach, but uses case insensitive stringcompare
	* @param mixed $needle
	* @param array haystack 
	* [@param bool strict]
	*/
	static function array_searchCaseInsensitive($needle, $haystack, $strict=false) {
		$needleLwr = strToLower(Gen::labelFrom($needle));
		$keys = array_keys($haystack);
		for ($i=0; $i<count($keys); $i++) {
			$key = $keys[$i];
			$value = $haystack[$key];
			if ( $strict && !Gen::is_typeEqual($needle, $value) )
				break;
			if ( $needleLwr == strToLower(Gen::labelFrom($value)) )
				return $key;
			
		}
		return false;
	}
	
	/** @return wheather the type of both parameters is equal
	 * @param mixed $first
	 * @param mixed $second
	 */
	static function is_typeEqual($first, $second) {
		if (is_object($first) && is_object($second))
			return get_class($first) == get_class($second);
		else
			return getType($first) == getType($second);
	}
	
	/** substring between the start marker and the end marker, 
	* not including the markers.
	* @param String $oString may contain the substring
	* @param String $sString marks the start
	* @param String $eString marks the end
	* @param number $sPos position to start searching
	* @return String The substring
	*/
	static function getSubstr($oString, $sString, $eString, $sPos=0) {
		if ((strlen($oString)==0) or (strlen($sString)==0) or (strlen($eString)==0)) 
			return "";
		$beginPos=strpos($oString, $sString, $sPos);
		if ($beginPos===false)
			return "";
		$beginPos += strlen($sString);
		$oString=substr($oString, $beginPos, strlen($oString)-$beginPos);
		$eindPos=strpos($oString, $eString);
		if ($eindPos===false)
			return "";
		return substr($oString, 0, $eindPos);
	}
	
	/*** @return string with qualified strings separated by a separator 
	 * @param array $array to be imploded
	 * @param string $separator by which the elements are to be seperated
	 * @param string $qualifier by wich the elements are surrounded. 
	 *   The qualifier itself is doubled. */
	static function toCsvString($array, $separator=';', $qualifier='"') {
		$result = '';
		reset($array);
		$firstKey = key($array);
		$dblQf = "$qualifier$qualifier";
		foreach ($array as $key=> $value) {
			if ($key !== $firstKey)
				$result .= $separator;
			$result .= $qualifier;
			$result .= str_replace($qualifier, $dblQf, Gen::toString($value));
			$result .= $qualifier;
		}
		return $result;
	}
	
	// 
	/** explodes csv string 
	 * only to be used if strings are qualified, if not, use normal explode
	 * @param string $str with qualified strings separated by a separator
	 * @param string $separator by which the elements are seperated
	 * @param string $qualifier by wich the elements are surrounded. 
	 *   The qualifier itself is expected to be doubled witing the qualified string.
	 * $return array of string  
	 */
	static function fromCsvString($str, $separator=';', $qualifier='"') {
		$arr = explode($separator, $str);
		$result = array();
		$quoteMode = false;
		$quoteKey = ord($qualifier);
		$dblQf = "$qualifier$qualifier";
		for ($i=0; $i<count($arr); $i++) {
			$unQualified = str_replace($dblQf, $qualifier, $arr[$i]);
			if ($quoteMode)
				$result[count($result) - 1] .= $separator. $unQualified;
			else
				$result[] = $unQualified;
			$counts = count_chars($arr[$i], 1);
			if(isSet($counts[$quoteKey]) && $counts[$quoteKey] & 1) // odd
				$quoteMode = !$quoteMode;
		}
		for ($i=0; $i<count($result); $i++) 
			if (subStr($result[$i], 0, 1) == $qualifier)
				$result[$i] = subStr($result[$i], 1, strLen($result[$i]) - 2);
		return $result;
	}
	
	/** @return array with the directory path at index 0 and the file name at the index 1. 
	 * @param string $filePath a file name or a file path separated by forward slashes
	 */
	static function splitFilePath($filePath) {
		$posPathEnd = strrpos ($filePath, '/');
		if ($posPathEnd === false) {
			return array('', $filePath);
		} else {
			return array(subStr($filePath, 0, $posPathEnd)
				, subStr($filePath, $posPathEnd + 1));
		}
	}
	
	/** Return the sum of the values of the property 
	* for the objects in the array
	* @throws PntError
	*/
	static function sum_from( $propertyName, &$arr) {
		$totaal = 0;
		if (count($arr) == 0)
			return 0;
	
		//using propDesc takes a little more code but is faster then get() when repeated
		reset($arr);
		$any = $arr[key($arr)];
		$propDesc = $any->getPropertyDescriptor($propertyName);
			
		if (!$propDesc)
			throw new PntError("$any no propertydescriptor for: $propertyName");
		foreach ($arr as $key => $value) {
			$value = $propDesc->getValueFor($arr[$key]);
			$totaal += $value;
		}
		return $totaal;
	}
	
	/** Strip the query parameter (with its value).
	* Does not work with funky parameters, does work with funky urls 
	* to strip from the non-funky additional query string part
	* becuase lookup in associative arrays like $REQUEST are case senistive,
	* query parameter stripping is also case sensitive.
	* @param String $url Url, uri or query string. 
	* $param String parameter name without ?, = or & in correct case
	* @return String stripped $url
	*/
	static function stripQueryParam($url, $paramName) {
		
		$paramStart = strPos($url, "$paramName=");
		if ($paramStart) {
			$paramEnd = strPos($url, '&', $paramStart);
			return $paramEnd
				? subStr($url, 0, $paramStart). subStr($url, $paramEnd + 1)
				: subStr($url, 0, $paramStart - 1);
		} else { //param not found
			return $url; 
		}
	}

	/** Convert the argument to int, but if it === null or an empty string, return null */
	static function asInt($value) {
		return ($value === null || $value === '') ? null : (int) $value;
	}

	/** The baseUrl is the the url to the folder that holds all applications folders
	* and the classes folder etc. Normally this is the parent folder of the folder where the
	* current script is running. This method is used for the initialization of
	* the baseUrl field of Site if it has not been set. With normal urls, 
	* the implementation assumes one folder and one scriptname between the basUrl and the 
	* question mark or the end of the url. With funky urls the implementation
	* assumes the baseUrl to end where the $funkyPattern starts.
	* It these assumptions are incorrect this method should be overridden or not be used.
	* @param array $serverVars with validated variables from $_SERVER 
	* @param string $funkyPattern for splitting funkyUrl or null if normal url
	* @return String The baseUrl
	*/
	static function getBaseUrl($serverVars, $funkyPattern=null) {
			if ( isSet($serverVars['HTTPS']) && strtolower($serverVars['HTTPS']) == 'on' ) {
				$protocol = 'https' ; $port = 443;
			} else {
				$protocol = 'http'; $port = 80;
			}
			$server = isSet($serverVars['HTTP_HOST']) ? $serverVars['HTTP_HOST'] : $serverVars['SERVER_NAME'];
			$portPiece = ''; //HTTP_HOST already includes portPiece. May not be OK if using SERVER_NAME
			if (!isSet($serverVars['HTTP_HOST']))
				$portPiece = (isSet($serverVars['SERVER_PORT']) && $serverVars['SERVER_PORT'] != $port)
					? (':'.$serverVars['SERVER_PORT']) : '';
			
			$slashPos = $funkyPattern
				? strPos($serverVars['REQUEST_URI'], $funkyPattern)
				: false;
			if ($slashPos===false) {
				$qmPos = strPos($serverVars['REQUEST_URI'], '?');
				$noQuery = $qmPos===false ? $serverVars['REQUEST_URI'] : subStr($serverVars['REQUEST_URI'], 0, $qmPos);
				$slashPos = strrpos( $noQuery, '/');
				$slashPos = strrpos( subStr($noQuery, 0, $slashPos), '/'); 
			}
			$path = subStr($serverVars['REQUEST_URI'], 0, $slashPos+1);
			
			return "$protocol://$server$portPiece$path";	
	}

	/**
	 * Adds the arguments to a date, time or datetime
	 *
	 * @param string $dateAndOrTime containing date, time or timestamp in ValueValidator internal format
	 * @return string same type as $dateAndOrTime
	 */
	static function dt_add($dateAndOrTime, $years=0, $months=0, $days=0, $hours=0, $minutes=0, $seconds=0) {
		if (strPos($dateAndOrTime,'-')) {
			$format = strPos($dateAndOrTime,':')
				? ValueValidator::getInternalTimestampFormat()
				: ValueValidator::getInternalDateFormat();
		} else {
			if (strPos($dateAndOrTime,':')) { 
				$format = ValueValidator::getInternalTimeFormat();
			} else {
				Gen::includeClass('PntError', 'pnt');
				throw new PntError("invalid date/time: '$dateAndOrTime'");
			}
		}
		$tm = strtotime($dateAndOrTime);
		
		return date($format, 	
			mktime(date('G',$tm)+$hours,date('i',$tm)+$minutes,date('s',$tm)+$seconds,date('m',$tm)+$months,date('d',$tm)+$days,date('Y',$tm)+$years) 
		);
	}
	
	/** @return boolean wheather the first param is larger then the second.
	 * @param string $value with date, time or timestamp
	 * @param string $ref with date, time or timestamp, but same type as $value
	 * @param boolean $incl wheather equal should result in true
	 */
	static function dt_largerOrNone($value, $ref, $incl=false) {
		if ($value && !$ref) return false;
		if (($ref && !$value) || ($incl && !$ref && !$value)) return true;
		
		if ($incl) return $value >= $ref;
		return $value > $ref;
	}
	
} // END OF CLASS PntGen --------------------------------------------------

/** The following are not moved to PntGen nor depricated */

/** Returns whether specified class is 
* a subclass or is the specified parentClass
* !! php5 version is case sensitive !!
*
* @param String childClassName
* @param String parentClassName
* @return Boolean
*/
if (version_compare(phpversion(), '5.0.3', '<')) {
	function is_subclassOr($childClassName, $parentClassName) { 
		if ($childClassName == $parentClassName) return true;
		if(!class_exists($childClassName))return false;
		do { 
     		$childClassName = get_parent_class($childClassName);
     		if ($childClassName==$parentClassName) return true; 
   		} while (!empty($childClassName)); 

   		return false; 
	}
} else {
	function is_subclassOr($childClassName, $parentClassName) { 
		if ($childClassName == $parentClassName) return true;
		if(!class_exists($childClassName))return false;
		return is_subclass_of($childClassName, $parentClassName);
	}	
}

/** Limited version: only two arguments.
* PEAR Package PHP_Compat (@see http://pear.php.net/package/PHP_Compat) 
* has a complete version, 
* Furthermore, documentation is not clear in which version this 
* fnction exists, so here we define our own version if it
* does not yet exist 
*/
if (!function_exists('array_diff_key')) {
	function array_diff_key(&$original, $reference) {
		$result = array();
		forEach(array_keys($original) as $key)
			if (!isSet($reference[$key]))
				$result[$key] =& $original[$key];
		return $result;
	}
}

/** Safer then eval or call_user_func because it can not be used to call 
* normal (arbitrary) fnctions */
function pntCallStaticMethod($className, $methodName, $param1=null, $param2=null) {
	return call_user_func(array($className, $methodName), $param1, $param2); //pntCallStaticMethod implementation
}
	
/** Only to prevent url include, inclusion from outside basedir may still be possible if
* not prevented by php */
if (version_compare(phpversion(), '5.2.0', '<') 
		?  ini_get('allow_url_fopen')
		: ini_get('allow_url_include')            ) {
	function pntCheckIncludePath($localPath) {
		if (preg_match("'[^A-Za-z0-9_/]'", $localPath))
			trigger_error('Improper characters in local include path: '. $localPath, E_USER_ERROR);
	}
} else {
	function pntCheckIncludePath($localPath) {
		//ignore
	}
}

/** Older versions of php give warning if setcookie with $httponly parameter */
if (version_compare(phpversion(), '5.2.0', '<')) {
	function pntSetCookie($name, $value, $expire=0, $path=null, $domain=null, $secure=false, $httponly=false) {
		setcookie($name, $value, $expire, $path, $domain, $secure);
	}
} else {
	function pntSetCookie($name, $value, $expire=0, $path=null, $domain=null, $secure=false, $httponly=false) {
		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}
}

if (!function_exists('lcFirst')) {
	function lcFirst($string) {
		$len = strLen($string);
		if ($len < 2)
			return strToLower($string);
			
		return strToLower(subStr($string,0, 1)).subStr($string,1);
	}
}

//UNCHECKED: if array_key_exists does not see private members then use from 5.1 property_exists (for slightly better incorrect case comatibility)
/** @param object $obj 
 * @param string $memberName must be correct case */
if (version_compare(phpversion(), '5.3.0', '<')) {
	function pntMember_exists($obj, $memberName) {
		return array_key_exists($memberName, $obj);
	}
} else {
	function pntMember_exists($obj, $memberName) {
		return property_exists($obj, $memberName);
	}
}

?>