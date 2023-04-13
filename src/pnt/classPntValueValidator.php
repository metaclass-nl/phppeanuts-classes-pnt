<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

	Gen::includeClass('PntValueValidator', 'pnt');

/**  An object that checks values against its constraint settings and returns error messages.
* @see http://www.phppeanuts.org/site/index_php/Pagina/131
* 
* This abstract superclass provides behavior for the concrete
* subclass ValueValidator in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @package pnt
*/
class PntValueValidator {

	public $errorReadOnly='no changes allowed';
	public $errorTooHigh='too high, max: ';
	public $errorTooLow='too low, min: ';
	public $errorTooShort='too short, min: ';
	public $errorTooLong='too long, max: ';

	public $errorInvalidEmail='invalid email address';
	public $errorInvalidType='invalid type: ';
	public $errorNotOfType='value must be a: ';
	
	public $type;
	public $readOnly;
	public $minValue;
	public $maxValue;
	public $minLength;
	public $maxLength;
    public $decimal;
	
	
	/** @return String the character encoding of domain model strings.
	 * The naming is to be compatible with http request header "content-type: text/html; charset= "
	 * You should override this method on ValueValidator if you want to use a different encoding. If you do:
	 * Be aware that the database must support the same encoding.
	 * Be aware that length validation needs to be overridden on ValueValidator for multi byte character sets. 
	 * Be aware that for character sets that can contain illegal characters, 
	 * like UTF-8, you must extend either Site, StringConverter or ValueValidator to sanitized/validate all incoming characters
	 * otherwise your application may be vurnerable to injection (SQL, CSS) and
	 * string php's and the database's string functions may not work properly
	 * (some of them never work properly with multi byte characters unless you use mbstrings).
	 */
	static function getInternalCharset() {
		return 'ISO-8859-1';
	}
	
	static function getInfiniteBig() {
		return 1.79e308;
	}

	static function getInfiniteSmall() {
		return-1.79e308;
	}
	
	static function getInfiniteDate() {
		return '9999-12-31';
	}
	
	static function getZeroDate() {
		return '0000-00-00';
	}
	
	static function getInfinity($type) {
		if ($type=='number') return ValueValidator::getInfiniteBig();
		if ($type=='date') return ValueValidator::getInfiniteDate();
		
		//no infinite available for $type
		return null;
	}
	
	/** @return string the internal format used in properties, fields and the database */
	static function getInternalDateFormat() {
		return "Y-m-d";
	}

	/** @return string the internal format used in properties, fields and the database.
	* this is compatible with MySql datetime */
	static function getInternalTimestampFormat() {
		return "Y-m-d H:i:s";
	}

	/* @return string the internal format used in properties, fields and the database */
	static function getInternalTimeFormat() {
		return "H:i:s";
	}
	
	/** @return the internal format used in properties, fields and the database
	* internal representation is created without thousends separator */
	static function getInternalDecimalSeparator() {
		return '.';
	}

	/** only if type = 'number'.
	 * @return number of decimals after the decimal separater, or null if none
	 * @param mixed $maxLength int or string like '5,2' */ 
	static function getDecimalPrecision($maxLength) 
	{
		if (!is_string($maxLength) || strlen($maxLength) == 0)
			return null;
		$arr = explode(',', $maxLength);

		if (isSet($arr[1]))
			return $arr[1];
		else
			return 0;
	}

	/** @return wheather the first primitive value is equal to the reference primitive value.
	 * Php's == operator sees null, false, '' and 0 as equal, but most databases don't. 
	 * Php's === operater is so strict that automatic type conversion becomes useless.
	 * This function should return the same as a case sentitive = on database-stored values. 
	 * expects a boolean or number 
	 * @param mixed $value the value to compare
	 * @param mixed $reference the value to compare to
	 */
	static function equal($value, $reference, $type) {
		if (is_null($value) != is_null($reference)) return false;
		if ($type=='string')
			return ((string) $value) === ((string) $reference);
		if ($type=='boolean')
			return ((bool) $value) === ((bool) $reference);
		if ($type=='number' && 
			($value === '' && $reference == 0 || $value == 0 && $reference === '') ) return true;
		
		if (class_exists($type) && method_exists($reference, 'equals'))
			return $reference->equals($value);
		return $value == $reference;
	}

	static function getDerivedChangeConflictMessage() {
		return "Entered value conflicts with derived value change";
	}
	
	
//---------- instance methods ------------------------

	/** Initialize this from the supplied PropertyDescriptor 
	 * @param PntPropertyDescriptor $prop
	 */
	function initFromProp($prop) {
		$this->type = $prop->getType();
		$this->readOnly = $prop->getReadOnly();
		$this->minValue = $prop->getMinValue();
		$this->maxValue = $prop->getMaxValue();
		$this->minLength = $prop->getMinLength();
		$this->maxLength = $prop->getMaxLength();
	}

	function getNumberMaxValue() 
	{
		if ($this->maxValue !== null)
			return $this->maxValue;
			
		if (!is_string($this->maxLength))
			return $this->getInfiniteBig();
		
		return $this->getMaxValueFromMaxLength();
	}
	
	function getNumberMinValue() 
	{
		if ($this->minValue !== null)
			return $this->minValue;
			
		if (!is_string($this->maxLength))
			return $this->getInfiniteSmall();
		
		return -$this->getMaxValueFromMaxLength();
	}

	function getMaxValueFromMaxLength()
	{
		$arr = explode(',', $this->maxLength);
		$max = '';
		for ($i=0;$i<$arr[0];$i++)
			$max .= '9';
		if ($arr[1]) {
			$max .= '.';
			for ($i=0;$i<$arr[1];$i++)
				$max .= '9';
			return (float)$max;
		}
		return (int)$max;
	}

	function getErrorInvalidNumber() {
		$prec = $this->getDecimalPrecision($this->maxLength);
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

// validation methods ----------------------------------------------

	/** @result string error message if invalid, or null if valid
	 * If more types have to be added to the validation this method must be overridden.
	 * @param mixed $value to be validated
	 * @param boolean $validateReadOnly wheather to validate that readOnly propeties are not set
	 */
	function validate($value, $validateReadOnly=true) 
	{
		if ($validateReadOnly && $this->readOnly) 
			return $this->errorReadOnly;

		switch ($this->type) {
			case "boolean":
				return $this->validateBoolean($value);
			case "number":
				return $this->validateNumber($value);
			case "string":
				return $this->validateString($value);
			case "date":
				return $this->validateDate($value);
			case "timestamp":
				return $this->validateTimestamp($value);
			case "time":
				return $this->validateTime($value);
			case "email":
				return $this->validateEmail($value);
			case "html":
				return $this->validateString($value);
			case "currency":
				return $this->validateNumber($value);
			default:
				if (class_exists($this->type))
					return $this->validateObject($value);
					
			//can not validate values of unknown type
			return $this->errorInvalidType. $type;
		}
			
	}

	function validate_min_max($value, $minValue, $maxValue) {
		if ($value < $minValue) 
			return $this->errorTooLow.$minValue;
		
		if ($value > $maxValue)
			return $this->errorTooHigh.$maxValue;
		
		return null;
	}
		
	function validateBoolean($value) {
		//StringConverter only produces booleans valid for PHP
		//You may want to override this if your database can not handle some
		return null;
	}

	function validateNumber($value) {
		$minValue=$this->getNumberMinValue();
		$maxValue=$this->getNumberMaxValue();
		
		if ($value === null && $this->minLength > 0)
			return $this->errorTooShort.$this->minLength;
		
		if ($value === null) return null;
		
		return $this->validate_min_max($value, $minValue, $maxValue);
	}

	/** 
	 * string $value holding date in internal date format. Does not have to be valid for strToTime.
	 */
	function validateDate($value) 
	{
		$minValue = ($this->minValue==null)? "0000-00-00": $this->minValue;
		$maxValue = ($this->maxValue==null)? "9999-12-31": $this->maxValue;

		if ($value === null && $this->minLength > 0)
			return $this->errorTooShort.$this->minLength;
			
		if ($value === null) return null;

		return 	$this->validate_min_max($value, $minValue, $maxValue);
	}
	
	/** 
	 * string $value holding time in internal time format. Does not have to be valid for strToTime.
	 */
	function validateTime($value) {
		$minValue = ($this->minValue==null)? "00:00:00": $this->minValue;
		$maxValue = ($this->maxValue==null)? "23:59:59": $this->maxValue;
		
		if ($value === null && $this->minLength > 0)
			return $this->errorTooShort.$this->minLength;
			
		if ($value === null) return null;

		return 	$this->validate_min_max($value, $minValue, $maxValue);
	}

	/** 
	 * string $value holding timestamp in internal timestamp format. Does not have to be valid for strToTime.
	 */
	function validateTimestamp($value) {
		$minValue = ($this->minValue==null)? "0000-00-00 00:00:00": $this->minValue;
		$maxValue = ($this->maxValue==null)? "9999-12-31 23:59:59": $this->maxValue;
		
		if ($value === null && $this->minLength > 0)
			return $this->errorTooShort.$this->minLength;
			
		if ($value === null) return null;

		return $this->validate_min_max($value, $minValue, $maxValue);
	}

    /** RFC2822 defines the format for email addresses. But new RFCs (http://tools.ietf.org/html/rfc5321 and http://tools.ietf.org/html/rfc5322) replace 2821 and 2822. 
    * This validation is much more permissive then RFC's, except for the domain part, 
    * where it does not allow address literals or comments. This choice was based on the comments
    * of mcv and Bear on http://haacked.com/archive/2007/08/21/i-knew-how-to-validate-an-email-address-until-i.aspx
	* Tested Myself: e-mail adress with space in local part worked with catchall on a linux server
	* if you need stricter validation you van override this method on ValueValidator.
	* Consider the PHP function filter_var($email, FILTER_VALIDATE_EMAIL) (PHP 5.2.0 and up)
    * or use its regexp, see http://svn.php.net/viewvc/php/php-src/trunk/ext/filter/logical_filters.c
	*/
	function validateEmail($value) {
		//not too long to fit in the property? 
		$error = $this->validateString($value);
		if ($error) return $error;

		if ($value === null) return null;

		//requires @ with at least one character before, checks the domain name 
		//not allowing address literals nor comments 
		if (!preg_match('/^.+' //you may use '/^(?:"(?:\\\\.|[^"])*"|[^ ]+)' if you do not want to  allow whitespace outside double quoted
			. "@[\w0-9\-.]+\.\w{2,4}$/" //allows subdomains and numers. "@[\w-]+(\.\w{2,3})*\.\w{2,4}$/" allows no numbers and intends to diallow subdomains (does not for 2/3 letter domains like ibm.com)
			, $value)) return $this->errorInvalidEmail;
			
		//Look up domain name in DNS
		//by default this is not checked because not all apps have access to DNS.
		//Furthermore, the domain may be taken off line anyway while te e-mail address is in the database.
		//$lastApe = strrpos($value, '@');
		//$host = subStr($value, $lastApe+1);
		//if (!checkdnsrr($host, "MX")) return $this->errorInvalidEmail; //with PHP<5.3 this does not work on windows
					
		//You should send a confirmation email, but that is beyond the scope of this function
		
		return null;
	}
	

	function validateString($value)
	{
		if ($this->minLength==null) $this->minLength=0;
		if ($this->maxLength==null) $this->maxLength=$this->getInfiniteBig();
		$len = $value===null ? 0 : strlen($value);
		if ($len < $this->minLength) 
			return $this->errorTooShort.$this->minLength;
		if ($len > $this->maxLength)
			return $this->errorTooLong.$this->maxLength;
		return null;
	}

	function validateObject($value) {
		if ($value === null && $this->minLength > 0)
			return $this->errorTooShort.$this->minLength;
		if ($value === null) return null;

		if (Gen::is_ofType($value, $this->type)) {
			if (method_exists($value, 'validate'))
				return $value->validate($value, $this); //static method
			else
				return $this->validateString(Gen::labelFromObject($value));
			}
		//class should be a subclass of the specified type
		//interfaces are not (yet) supported
		return $this->errorNotOfType. $this->type;
	}
}
?>