<?php
/* Copyright (c) MetaClass, 2003-2017

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObject', 'pnt');
// ValueValidator included by PntSite 

/** Object of this class convert strings to values and back according to their format settings.
* All user interface String conversions are and should be delegated to StringConverters
* to make override possible. 
* 
* This abstract superclass provides behavior for the concrete
* subclass StringConverter in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @package pnt/web
*/
class PntStringConverter extends PntObject {

	public $labelItemSeparator = ', '; //separates multiple values in arrays
	public $true='true';
	public $false='false';
	public $dateTimezone = null; //if set, overrides date_default_timezone
	public $dateFormat="Y-m-d"; //as to be shown in user interface. 
	// ! Currently not used for parsing date strings from the user interface,
	// promat from locale is used. dateFormat must correspond to locale!
	public $timestampFormat="Y-m-d H:i:s"; //as shown in user interface. Default is same as properties value
	public $timeFormat="H:i:s"; //as shown in user interface. Default is same as properties value
	public $decimal='.'; // decimal separator
	public $thousends=','; //thousends separator
	public $labelCharset = null; //You need to override and add character conversion if you set this to be different from ValueValidator::getInternalCharset 
	public $html_version_ent = 'ENT_HTML401'; //do not set directly after construction; see html_version_entOptions
	public $html_version_flag = 0; //initialized on construction
	public $languageId = 'en'; 
	public $usKbSupport4Uropean = false; //numeric keypad has only a dot key...
	public $emptyToInfinity=false; //used for HistoricalVersion::validUai
	
	public $errorInvalidNumber='invalid number, expected is like: ';
	public $errorInvalidDate='invalid date, expected is like: ';
	public $errorInvalidTimestamp='invalid timestamp, expected is like: ';
	public $errorInvalidTime='invalid time, expected is like: ';
	public $errorInvalidBoolean='invalid boolean, expected: ';
	public $errorInvalidType='invalid type: ';

	public $type;
	public $decimalPrecision = 2;
	public $asId = false; //convert as id
	
	public $error; //if not null an error occurred

	function __construct() {
		$this->setHtml_version_ent($this->html_version_ent); //initalizes html_version_flag
	}
	
	static function html_version_entOptions() {
		return array('ENT_HTML401','ENT_XML1','ENT_XHTML','ENT_HTML5');
	}

	//return supported separators for date, time and timestamp
	static function getTimeStampSeparators() {
		return '- :/.';
	}

	/** works for date, time and timestamp, 
	* only supports numeric date and time elements, 
	* so no monthnames, daynames and no AM/PM
	* @static
	* @param $string String containing date and/or time 
	* @param $format the format $string should be in
	* @param $type String either 'date', 'time' or 'timestamp'.
	* @return String containing date and/or time in the internal format corrensponding to $type
	*/
	static function convertDT($string, $format, $type)
	{
		switch ($type) {
			case 'date':
				$internalFormat = ValueValidator::getInternalDateFormat();
				break;
			case 'timestamp':
				$internalFormat = ValueValidator::getInternalTimestampFormat();
				break;
			case 'time':
				$internalFormat = ValueValidator::getInternalTimeFormat();
				break;
			default:
				trigger_error("unsupported type: $type", E_USER_ERROR);
		}

		$inputArray = StringConverter::splitDT($string, $format);
		foreach ($inputArray as $key => $value) {
			// check numeric components to be numeric
			if (strPos($key, 'aADFlMST') === false && !is_numeric($value))
				return false; 
			//add leading zero's, but check for numbers too large
			$denominator = $key=='Y' ? 10000: 100;
			if (($value % $denominator) != (int) $value)
				return false;
			$resultArray[$key] = substr($value + $denominator, 1);
		}

		//check resultArray content Y-m-d H:i:s
		if ($type != 'time' && !checkdate($resultArray['m'], $resultArray['d'], $resultArray['Y']))
			return false;
		if ($type != 'date' && !StringConverter::checktime($resultArray['H'], $resultArray['i'], $resultArray['s']))
			return false;

		// create internalformat formatted string
		return StringConverter::formatDT($resultArray, $internalFormat);
	}

	/** Split date, time or timeStamp, answer array with keys from format
	* Limitation: only works for formats using Y, m, d, H, i and/or s 
	*    and separators from getTimeStampSeparators()
	* @static
	* @param String date, time or timestamp String
	* @param String $format
	* @return Array of String
	*/
	static function splitDT($value, $format) {
		$expr = '/['.str_replace('/', '\/', StringConverter::getTimeStampSeparators()).']/';
		$formatArray = preg_split($expr, $format);
		
		if (count($formatArray) == 1) {
			$formatArray = array();
			for ($i=0; $i<strLen($format); $i++) 
				$formatArray[] = subStr($format, $i, 1);
			$arr = StringConverter::splitDtNoSeparators($value, $formatArray);
		} else {
			$arr = preg_split($expr, $value);
		}
		for ($i=0; $i<count($formatArray); $i++) 
			$result[$formatArray[$i]] = isSet($arr[$i]) ? $arr[$i] : '00';

		if (isSet($result['d'])) 
			$result['j'] = (int) $result['d'];
		if (isSet($result['m'])) 
			$result['n'] = (int) $result['m'];

		return $result;
	}

	static function addMonthName(&$dateArray, $monthNames, $format='F')	{
		if (isSet($dateArray['n'])) {
			$dateArray[$format] = $monthNames[$dateArray['n']];
		}
	}

	static function splitDtNoSeparators($value, $formatArray) {
		//assume all elements are fixed size, like with YmdHis
		$pos = 0;
		$arr = array();
		for ($i=0; $i<count($formatArray); $i++) {
			$size = ($formatArray[$i] == 'Y') ? 4 : 2;
			$arr[$i] = subStr($value, $pos, $size);
			$pos += $size;
		}
		return $arr;
	}

	/** Format date, time or timestamp String from Array
	* Limitation: only works for formats that require no conversion of
	*   elements, in practice these are only using Y, m, d, H, i and/or s
	* @static
	* @param Array of String $dtArray with keys from format
	* @param String $format
	* @return String
	*/
	static function formatDT($dtArray, $format) {
		$result = $format;
        foreach ($dtArray as $key => $value)
			$result = str_replace($key, $value, $result);
				
		return $result;
	}

	// static
	static function checkTime($hours, $minutes, $seconds) {
		return $hours >= 0 
			&& $hours < 24
			&& $minutes >= 0
			&& $minutes < 60
			&& $seconds >= 0
			&& $seconds < 60;
	}

	function getDateTimezone() {
		if (isSet($this->dateTimezone)) return $this->dateTimezone;
		if (function_exists('date_default_timezone_get')) 
			return date_default_timezone_get();
		return null;
	}
	
	function getErrorInvalidNumber() {
		$prec = $this->decimalPrecision;
		if ($prec === null) 
			$prec = 2;
		$expected = '-4';
		if ($prec > 0) {
			$expected .= $this->decimal;
			for ($i=1; $i<=$prec; $i++)
				$expected .= $i % 10;
		}
		return $this->errorInvalidNumber . $expected;
	}

	function initFromProp($prop) {		
		$this->type = $prop->getType();
		$this->decimalPrecision = ValueValidator::getDecimalPrecision($prop->getMaxLength());
		$this->asId = $prop->getHoldsId();
	}

	/** @return String the character encoding of labels in the user interface. 
	 * requestData and html are assumed to be in this charset. 
	 * You need to override and add character conversion if you set this to be different from ValueValidator::getInternalCharset
	 */
	function getLabelCharset() {
		if (isSet($this->labelCharset)) return $this->labelCharset;
		
		return ValueValidator::getInternalCharset();
	}

	function getLanguageId() {
		return $this->languageId;
	}
	
	/**  
	 * Sets html_version_ent and if applicable, html_version_flag
	 * @param String $value to be set
	 * @trhows PntError if value not in StringConverter::html_version_entOptions()
	 */
	function setHtml_version_ent($value) {
		if (!in_array($value, StringConverter::html_version_entOptions()))
			throw new PntError("html_version_ent not supported: '$value'");
		$this->html_version_ent = $value;
		$this->html_version_flag = defined('ENT_HTML401') 
			 ? constant($this->html_version_ent) : 0;
	}
	
	// methods converting to user interface string ---------------------------
	
	/** Convert a string to HTML. 
	* @param string $string The string to convert
	* @param boolean $breaksForLineFeeds Wheather to convert line feeds to <BR>
	* $param boolean $preformatAndTab if set > 0 convert multiple spaces to non-breaking spaces
		and replace tabs by the specified number of non-breaking spaces
	* @return String with HTML */
	function toHtml($string, $breaksForLineFeeds=false, $preformatAndTab=0) {		
		if ($this->type=='html') return $string;
        if ($string===null) return '';
		$result = htmlspecialchars($string, ENT_QUOTES | $this->html_version_flag, $this->getLabelCharset());
		if ($breaksForLineFeeds) {
			$br = $this->html_version_ent=='ENT_XML1' || $this->html_version_ent=='ENT_XHTML'
				? "<br />\n" : "<br>\n";
			$result = preg_replace("/\r\n|\n\r|\n|\r/", $br, $result);
		}
		if ($preformatAndTab) {
			$result = str_replace("\t", str_pad(' ', $preformatAndTab), $result);
			$result = str_replace("  ", "&nbsp;&nbsp;", $result);
			$result = str_replace("&nbsp; ", "&nbsp;&nbsp;", $result);
		}
		return $result;
	}
	
	/** @return String a javascript literal string 
	 * WARNING: This implementation is NOT OK for UTF-8 and other multi byte charsets!
	 * 		if you use those, you must override this method on StringConverter)*/
	function toJsLiteral($string, $quote="'") {
        if ($string==null) return $quote.$quote;
	    return $quote. str_replace(
	    	array('\\'  , '<'     , '>'     , "\r" , "\n" , '"'    ,"'"     , '&'    ), 
	    	array("\\\\", "\\x3C" , "\\x3E" , "\\r", "\\n", "\\x22", "\\x27", "\\x26"), 
	    	$string). $quote;	
	}
	
	/** @return String encoded for usage in urls. 
	 * WARNING: This implementation may NOT be OK for UTF-8 and other multi byte charsets!
	 *   if you use those, you may need to override this method on StringConverter)
	 *   check the PHP documentation of urlencode to see what to do for the character set you want to use.*/
	function urlEncode($string) {
        if ($string===null) return '';
		return urlencode($string);
	}
	
	function toLabel($value, $type) {
		if ($value === null) return '';
		if (is_array($value)) 
			return $this->arrayToLabel($value, $type);
		
		switch ($type) {
			case "boolean":
				return $this->labelFromBoolean($value);
			case "number":
				return $this->labelFromNumber($value);
			case "currency":
				return $this->labelFromNumber($value);
			case "string":
				return $this->labelFromString($value);
			case "html":
				return $this->labelFromString($value);
			case "date":
				return $this->labelFromDate($value);
			case "timestamp":
				return $this->labelFromTimestamp($value);
			case "email":
				return $this->labelFromString($value);
			case "time":
				return $this->labelFromTime($value);
			default:
				if ( is_object($value) ) 
					return $this->labelFromObject($value);
				else
					return "$value";
		}
	}
	
	function arrayToLabel($array, $type) {
		$labels = array();
		$amparsand = false; //if & in label, add line feeds
		forEach(array_keys($array) as $key) {
			$label = $this->toLabel($array[$key], $type);
			$labels[] = $label;
			if (strPos($label, '&') !== false) $amparsand = true;
		}
		$sep = $amparsand ? "$this->labelItemSeparator\n" : $this->labelItemSeparator;
		return implode($sep, $labels);
	}
	
	function labelFromBoolean($value) {
		if ($value)
			return $this->true;
		else
			return $this->false;
	}

	function labelFromNumber($value) {
		// bring in line with decimal precision
		$prec = $this->decimalPrecision;
		$thousends = $this->asId ? '' : $this->thousends;
		
		if ($prec!==null) {
			$value = round($value, $prec); //otherwise it gets trucated
			return number_format($value, $prec, $this->decimal, $thousends);
		}
		
		$arr =  explode(ValueValidator::getInternalDecimalSeparator(), "$value");
		$string = number_format((float)$arr[0], 0, $this->decimal, $thousends);
		if (isSet($arr[1])) 
			$string .= $this->decimal. $arr[1];
			
		return $string;
	}

	function labelFromDate($value) {
		$arr = $this->splitDT($value, ValueValidator::getInternalDateFormat());
		if ($arr['Y'] == 0 || $value == ValueValidator::getInfiniteDate())
			return '';
		if ($arr['Y'] > 1972 && $arr['Y'] < 2038)  // ;-(((
			return date($this->dateFormat, strtotime($value)); //MC: changing this may break Aurora lastUpdated column (uses internalDormat 'Ymd')
		else
			return $this->formatDT($arr, $this->dateFormat); //has its limitations too, but not the year limit
	}

	function labelFromTime($value) 
	{
		$arr = $this->splitDT($value, ValueValidator::getInternalTimeFormat());
		return date($this->timeFormat, strtotime($value));
	}


	function labelFromTimestamp($value) {
		$time = strtotime($value);
		if ($time != -1 && subStr($value,0,10) != '0000-00-00')
			return date($this->timestampFormat, strtotime($value));
			
		$arr = $this->splitDT($value, ValueValidator::getInternalTimestampFormat());
		return $this->formatDT($arr, $this->timestampFormat);
	}

	function labelFromString($value) {
		return "$value";
	}

	function labelFromObject($value) {
		return Gen::labelFromObject($value);
	}

// methods for conversion from user interface String ----------------------

	/** clean up a request string. 
	* 	@param string $requestString from PntRequestHandler::getRequestParam
	*/
	function fromRequestData($requestString) {
		if ($requestString===null) return null;
		return $this->type == 'html'
			? $this->sanitizeHtml($requestString)
			: $this->sanitizeString($requestString);
	}
	
	static function sanitizeString($txt) {
		//remove tags
		return preg_replace ('/<[^>]*>/', '', $txt);
	}
	
	static function sanitizeHtml($txt) {
        // Remove active content and frames
		return preg_replace(array(
	            '@<script[^>]*?.*?</script>@si',
	            '@<object[^>]*?.*?</object>@si',
	             '@<embed[^>]*?.*?</embed>@si',
	            '@<applet[^>]*?.*?</applet>@si',
	            '@</?((frameset)|(frame)|(iframe))@i'
        	),  array(' ', ' ', ' ', ' ', ' '), $txt ); 
	}
		
	/** converts from user interface string representation to
	* domain model property value. 
	* @return mixed value or null if conversion fails 
	* $this->error will hold an error message if some error occurs */
	function fromLabel($string)
	{
		$this->error = null;

		$value = $this->convert($string); //checks format too
		return $value;
	}

	function convert($string) 
	{
		if ($string === null || strlen($string) == 0)
			return $this->emptyToInfinity ? ValueValidator::getInfinity($this->type) : null;
			
		$this->error = null;
		switch ($this->type) {
			case "boolean":
				return $this->convertToBoolean($string);
			case "number":
				return $this->convertToNumber($string);
			case "currency":
				return $this->convertToNumber($string);
			case "string":
				return $this->convertToString($string);
			case "html":
				return $this->convertToString($string);
			case "date":
				return $this->convertToDate($string);
			case "timestamp":
				return $this->convertToTimestamp($string);
			case "time":
				return $this->convertToTime($string);
			case "email":
				return $this->convertToString($string);
			default:
				if (class_exists($this->type))
					return $this->convertToObject($string); 
				
				$this->error = $this->errorInvalidType . $this->type;
				return null;
		}
	}
	
	function convertToBoolean($string)
	{
		$lower = strtolower($string); 
		if (($lower != $this->true) && ($lower != $this->false))
			$this->error = $this->errorInvalidBoolean . "$this->true/$this->false";
		return ($lower && $lower != $this->false);
	}
	
	function convertToNumber($string) {
		$value = trim($string);
		if ($this->usKbSupport4Uropean)
			$value = $this->usKbConvert4Uropean($value);
		if (!$this->asId)
			$value = str_replace($this->thousends, '', $value);
//		$valid = preg_match("|^[+-]?[0-9]+([$$this->decimal][0-9]+)?\$|", $value);
		$valid = preg_match("|^[+-]?\\d+([$this->decimal]\\d+)?$|U", $value);

		if (!$valid) {
			$this->error = $this->getErrorInvalidNumber();
			return null;
		}
		
		// replace the decimal separator	
		$value = str_replace(
			$this->decimal
			,ValueValidator::getInternalDecimalSeparator()
			, $value
		);

		return $this->decimalPrecision === 0 ? (int)$value: (float)$value;
	}

	function convertToDate($string) {
		$result = $this->convertDT(
			$string, 
			$this->dateFormat, 
			'date'
		);
		
		if ($result === false) {
			$this->error = $this->errorInvalidDate.date($this->dateFormat);
			return null;
		}
		return $result;
	}

	function convertToTime($string) {
		$result = $this->convertDT(
			$string, 
			$this->timeFormat, 
			'time'
		);
		
		if ($result === false) {
			$this->error = $this->errorInvalidTime.date($this->timeFormat);
			return null;
		}
		return $result;
	}

	function convertToTimestamp($string) {
		$result = $this->convertDT(
			$string, 
			$this->timestampFormat, 
			'timestamp'
		);
		
		if ($result === false) {
			$this->error = $this->errorInvalidTimestamp.date($this->timestampFormat);
			return null;
		}
		return $result;
	}
	
	function convertToString($string) {
		return (string)$string;
	}

	function convertToObject($string) {
		if (Gen::class_hasMethod($this->type, 'fromLabel'))
			return pntCallStaticMethod($this->type, 'fromLabel', $string);

		$this->error = $this->errorInvalidType . $this->type;
		return null;
	}

	function usKbConvert4Uropean($string)
	{
		//if decimal separator present, return untouched
		if (strpos($string, $this->decimal) !== false) {
			return $string;
		}
		//convert last dot to decimal separator except when it could be a thousends separator
		$lastDotPos = strrpos($string, '.');
		if ($lastDotPos === false ||
				($this->thousends == '.' && strLen($string) - $lastDotPos == 4))
			return $string;
			
		return substr_replace ($string, $this->decimal, $lastDotPos, 1);
	}
}
?>