<?php
/* Copyright (c) MetaClass, 2012-2017

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

See the License, http://www.gnu.org/licenses/agpl.txt */
 
Gen::includeClass('PntValidationException', 'pnt/secu');

/** Http request validator. Logs validation warnings for bad input. Returns only valid input.
 * Unlike ValueValidator, who expects the characters to be encoded in ValueValidator::getInternalCharset,
 * this validator expects characters to be encoded as in the http request. 
 * StringConverter may convert from the request encoding to the internal encoding 
 * 		(but by default it does no conversion of character encoding)
 * In this default implementation:
 * - keys are validated to hold only alphanumeric character, dasches and underscores 
 * - Http header values are validated to hold visible ASCII characters. Some are validated to a
 * specific character whitelist or preg pattern  
 * - PHP_AUTH_USER and 'PHP_AUTH_PW characters are expected to be valid (like with ISO-8859-1) #
 * - requestData and cookies: all characters are expected to be valid (like with ISO-8859-1) #
 * - other server variable are not validated. They are expected to come from http server settings
 *   or other reliable sources.
 * # To be overridden on subclass HttpValidator to validate/sanitize input using other character set(s) 
 * like UTF-8, as this implementarion will NOT adapt automatically to a change in StringConverter::getLabelCharset  
 * May be overridden to do (more) sanitization.
 * 
 * Unlike the OWASP ESAPI SafeRequest class this class does not do canonalization and 
 * does not explcitly use mbstrings functions. Its behavior with multi byte strings has not been tested 
 * and may be different depending on ini settings for mbstring.func_overload and mbstring.encoding_translation
 * 
 * This class does not delegate to ValueValidator because ValueValidator must work with the
 * character set it defines in ::getInternalCharset and return user error messages, 
 * while most of the validations here are specific to ASCII and the error messages are for logging
 * to be evaluated later by the application administrator.
* @package pnt/web
*/
class PntHttpRequest {

	public $serverVarValidationFatal; //value set overrides constructor parameter
	public $gpcValidationFatal;  //value set overrides constructor parameter
	public $pcre_backtrack_limit = 100000; //default limit
	
	//language dependent strings, may be overridden on HttpValidator
	public $tooShort = 'too short';
	public $tooLong = 'too long';
	public $tooLow = 'too low';
	public $tooHigh = 'too high';
	public $invalid = 'invalid';
	public $serverVarValidationFailed = 'Server variable validation failed for';
	public $gpcValidationFailed = 'Gpc validation failed for';
	
	/** result of ::validateServerVars kept as a context for ::validateGpc */
	public $serverVars; 
	public $cookies;
	public $get;
	public $post;
	
	/************************************************************************************
	 * preg and char patterns @copyright 2007-2010 The OWASP Foundation as part of the 
	 * OWASP Enterprise Security API (ESAPI) (SafeRequest class)
	 * @author    jah <jah@jahboite.co.uk>
	 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD license
	 * @version   SVN: espi4php-1.0a
	 * @link      http://www.owasp.org/index.php/ESAPI
	 * LICENSE: These patterns are subject to the New BSD license.  You should read
	 * and accept the LICENSE before you use, modify, and/or redistribute this software.
	*/
	//pattern delimiters and D added by MetaClass
    public $serverPatterns = array(
			'REQUEST_METHOD' => '~^(GET|HEAD|POST|TRACE|OPTIONS|PUT|DELETE)$~D' //PUT|DELETE added by MetaClass for restful web services
	    ,	'AUTH_TYPE' => '~^([dD][iI][gG][eE][sS][tT]|[bB][aA][sS][iI][cC])$~D'
	    ,	'REMOTE_HOST' => '~^((?:(?:[0-9a-zA-Z][0-9a-zA-Z\-]{0,61}[0-9a-zA-Z])\.)*[a-zA-Z]{2,4}|[0-9a-zA-Z][0-9a-zA-Z\-]{0,61}[0-9a-zA-Z])$~D'
		);
	//REMOTE_ADDR, SERVER_ADDR
	public $ipV4Pattern = '~^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$~D';
	//generic preg character class pieces, backslashes for escaping added by MetaClass 
	public $httpCookieNamePat = '\\-_'; // \\- added by MetaClass because errors are too frequent and do not seem malicious
	public $headerNameCp  = '\\-_'; //actually $_SERVER has no names with -
	public $headerValueCp = '!"#$%&\'()*+,\\-./\\\\;:<=>?@[\\]\\^_`{|}\\~ '; //"\t" added in constructor //basically all visible ASCII characters and tab
	//generic preg character class pieces, backslashes for escaping added by MetaClass 
	public $serverCps = array(
    		'QUERY_STRING' => ' &()*+,\\-./;:=?_%!' 
				//% added by metaclass so that url encoded octets can get through
				//! added by metaclass because js encodeURIComponent does not encode it
//    	,	'HTTP_HOST'     => '\\-._' //strange, there is a specific pattern too, to look up in saferequest
    	,	'REMOTE_USER'  => '!#$%&\'*+\\-.\\^_`|\\~'
    	,	'SCRIPT_NAME' => '!$%&\'()*+\\-,./:=@_\\~' //and REQUEST_URI with '?' added
    	);
    public $filePathCp = ' !#$%&\'()+,-./=@[\\]\\^_`{}\\~\\\\'; //PATH_TRANSLATED
	
    //modified by MetaClass to require eventual sign to be at the start
	public $integerPattern = '/^(\\+|\\-)?[0-9]+$/'; //CONTENT_LENGTH
	
	public $minLengths = array(
			'REQUEST_METHOD' => 3
		,	'SCRIPT_NAME' => 1
		);
	public $maxLengths = array(
			'AUTH_TYPE' => 6
		,	'CONTENT_TYPE' => 4096
		,	'PATH_INFO' => 4096
		,	'PATH_TRANSLATED' => 4096
		,	'QUERY_STRING' => 4096
		,	'REMOTE_HOST' => 255
		,	'REMOTE_USER' => 255
		,	'REQUEST_METHOD' => 7
		,	'SERVER_NAME' => 255
		,	'REMOTE_ADDR' => 15
		,	'SERVER_ADDR' => 15
		,	'SERVER_PROTOCOL' => 8
		);
	public $maxValues = array(
			'CONTENT_LENGTH' => 2147483647 //PHP_INT_MAX on 32 bits
		,	'SERVER_PORT' => 65535
	);
	
	/***************************************************************************
	* the rest of this file is copyright (c) MetaClass, 2012  */ 
	
	public $sessionIdCp = ',\-'; //to be overridden if non-standard session ids are used
	
	/** 
	 * @param PntErrorHandler $logger error logger
	 * @param String $gpcCharset the encoding of GET, POST, COOKIE (and Authentication?) data
	 * @param boolean $fatal wheater to throw a PntValidationException if validation fails (value set on subclass overrides this param) 
	 */  
	function __construct($logger, $gpcCharset, $fatal=true) {
		$this->logger = $logger;
		$this->gpcCharset = $gpcCharset;
		if (!isSet($this->serverVarValidationFatal)) $this->serverVarValidationFatal = $fatal; 
		if (!isSet($this->gpcValidationFatal)) $this->gpcValidationFatal = $fatal;  
		
		$this->nullChar = chr(0);
		$this->headerValueCp .= "\t";
		$this->serverCps['REQUEST_URI']  = $this->serverCps['SCRIPT_NAME']. '?'; //added by MetaClass
		if (subStr(php_uname('s'), 0, 7) == 'Windows')
			$this->serverPatterns['PATH_TRANSLATED'] = "~^([a-zA-Z]:)?[a-zA-Z0-9{$this->filePathCp}]+$~D";
		else 
			$this->serverCps['PATH_TRANSLATED'] = $this->filePathCp;
		
		$this->serverPatterns['CONTENT_LENGTH'] = $this->integerPattern; 
		$this->serverPatterns['SERVER_PORT'] = $this->integerPattern;
		//this may only work for IP4:
	    $this->serverPatterns['REMOTE_ADDR'] = $this->ipV4Pattern;
	    $this->serverPatterns['SERVER_PROTOCOL'] = '~^HTTP\\/[1-2]\.[0-9]$~'; //officially hugher numers are allowed, but they do not yet occur

		$ini_value = ini_get('pcre.backtrack_limit'); //Available since PHP 5.2.0.
		if ($ini_value) $this->pcre_backtrack_limit = (int) $ini_value;
	}
	
	/** @param string $funkyAlias or null if no funkyUrls */
	function initHttpData($funkyAlias) {
		$this->serverVars = $this->validateServerVars($_SERVER); //must be done before getFunkyRequestData
		$this->cookies = $this->validateGpc($_COOKIE, true);
		$this->get = $this->validateGpc($this->getFunkyRequestData($funkyAlias)); //does not include cookies
		$this->post = $this->validateGpc($_POST);
	}

	/** @return validated or eventually sanitized value from $_SERVER or null if not present or sanitation failed
	 * @param string $name key in $_SERVER 
	 * @see HttpValidator and pnt.web.PntHttpValidator 
	 */ 
	function getServerValue($name) {
		return isSet($this->serverVars[$name]) ? $this->serverVars[$name] : null;
	}
		
	/** @return validated or eventually sanitized value from $_COOKIE or null if not present or sanitation failed
	 * @param string $name key in $_COOKIE
	 * @see HttpValidator and pnt.web.PntHttpValidator 
	 */ 
	function getCookie($name) {
		return isSet($this->cookies[$name]) ? $this->cookies[$name] : null;
	}
	
	/** @return value of request parameter as if magic_quotes_gpc is OFF,
	 * 		validated or eventually sanitized with respect to character encoding 
	 * 		or null if the parameter does not exist or sanitation failed.
	 * @param string $name key in $_REQUEST (without cookies) 
	 */ 
	function getRequestParam($key) {
		return isSet($this->post[$key]) 
			? $this->post[$key] 
			: (isSet($this->get[$key]) ? $this->get[$key]: null);
	}
	
	function getRequestData() {
		return array_merge($this->get, $this->post);
	}
	
	/** @return array requestdata  
	* all components from '/$this->getDir()/$alias' up to one slash before the ? are interpreted
	* as pntType/id/key/value/key/value etc.  
	* For normal urls while an alias is passed, this method returns the script name as parameter key, 
	* so one should not use the script name as the name of a parameter in the query string
	* since phpPeanuts 2.1 no longer includes params from $_POST
	* if Funky Urls are used, either the server root must be equal to the phpPeanuts base folder, 
	* or $this->baseUrl must be set (for example from classes/scriptMakeSettings.php)
	* or $this->getBaseUrl() must be overridden to properly initialize $this->baseUrl
	*/
	function getFunkyRequestData($alias=null, $uriParam=null) {
		$uri = $uriParam ? $uriParam : $this->getServerValue('REQUEST_URI');
		if ($uriParam || $alias ) {
			$requestData = array(); 
			$pAndQ = explode('?', $uri);
			$p = $pAndQ[0];
			if (isSet($pAndQ[1])) 
				parse_str($pAndQ[1], $requestData); //!adds slashes if magic_quotes_gpc
		} else { //no uriParam and normal urls or POST
			$requestData = $_GET;
			$p = $uri;
		}
		if (!$alias) return $requestData;

		//funky urls, find the funky piece of the request uri
		$pos = strPos($p, $alias);	
		if ($pos === false) return $requestData; //can not find the start of the funky piece 
		$funkyPiece = substr($p,$pos+strLen($alias));
		if (strLen($funkyPiece) == 0) return $requestData; // funky piece is empty
		
		//parse the funky piece of the request uri
		$funkyData = array();
		$kvArr = explode("/",$funkyPiece);
		$funkyData['pntType'] = urlDecode($kvArr[0]);
		if (isSet($kvArr[1]))
			$funkyData['id'] = urlDecode($kvArr[1]);

		if (count($kvArr) > 2)
			for ($i=2; $i < count($kvArr)-1; $i += 2)
				$funkyData[urlDecode($kvArr[$i])] = urlDecode($kvArr[$i+1]);
//printDebug($funkyData);
		//ErrorHandlers normal query string must override funkyData
		return array_merge($funkyData, $requestData);
	}
	
	function noMagicQuotesGpc($data) {
	    // Removed in PHP 5.4.0., should not be called
        trigger_error('noMagicQuotesGpc', E_USER_DEPRECATED);
		return $data;
	}
	
	//========================= VALIDATION methods =======================
	
	/** Main method for validating GET, POST and COOKIE data.
	 * To be called AFTER validateServerVars so that $this->validServerVars can be used as a context
	 * (like for browser specific sanitization)
	 * @param array $data, if magic_quotes_gpc slashes must be stripped beforehand
	 * @throws PntValidationException if $this->gpcValidationFatal with message about the last validation that failed
	 * @return array with valid data 
	 */
	function validateGpc($data, $cookies=false, $context='') {
		$result = array();
		$this->error = false;
    	forEach($data as $key => $value) {
    		$errorMessage = $cookies 
    			? $this->validateCookieName($key)
    			: $this->validateParamName($key);
    		if ($errorMessage) {
    			$this->error = $cookies ? 'cookie name' : 'param name';
    			$this->logValidationWarning($this->error, $context.$key, $errorMessage);
    			continue; //do not return the param or cookie with the invalid name
    		}
    		if (is_array($value)) {
    			$result[$key] = $this->validateGpc($value, $cookies, $context.$key.'.');
    		} else {
	    		$sanitizedValue = $this->sanitizeGpc($key, $value);
	    		$errorMessage = $this->validateGpcValue($key, $sanitizedValue);
	    		if ($errorMessage) {
	    			$this->logValidationWarning($context.$key, $sanitizedValue, $errorMessage);
	    			$this->error = $context.$key;
	    		} else {
	    			$result[$key] = $sanitizedValue;
	    		}
    		}
    	}
    	if ($this->error && $this->gpcValidationFatal)
    		throw new PntValidationException("$this->gpcValidationFailed $this->error");
    	
		return $result;
	}

	function validateCookieName($name) {
    	return (preg_match($this->getCpPattern($this->httpCookieNamePat), $name)) 
    		? "cookie name $this->invalid"
    		: null;
    }
    
	/** 
	 * @param string $value to be checked to be valid for $this->gpcCharset
	 * @return string validation error message or null if valid
	 */ 
    function validateParamName($name) {
    	return $this->validateGpcValue('param name', $name);
	}
	
	/** Sanitizes value for Get, Post and Cookie
	 * must call ::logValidationWarning if replacing some character(s) that raise security suspicion 
	 * default implementation is no sanitization.  
	 * May be overridden on HttpValidator to do actual sanitization with respect to character encoding and browser issues.
	 * Type-specific sanitization is to be done on StringConverter
	 * @return string sanitized value 
	 * @param string value
	 */
	function sanitizeGpc($key, $value) {
		return $value;
	}
	
	/** In case invalid character encoding is possible, this method shoud be overridden to validate the character encodings of $value.
	 * @param string $name properly encoded in $this->gpcCharset, or 'param name'
	 * @param string $value to be checked to be valid for $this->gpcCharset
	 * @return string validation error message or null if valid
	 */ 
	function validateGpcValue($name, $value) {
		return session_name() === $name
			? $this->validateSessionId($name, $value) 
			: $this->validateForNullChar($name, $value);
	}
		
	function validateForNullChar($name, $value) {
		if (strPos($value, $this->nullChar) !== false)
			return "$name invalid: null byte";
		return null;
	}
	
	/** To be overridden if non-standard session ids are used */ 
	function validateSessionId($name, $value) {
		return $this->pregValidate($name, $value, $this->getCpPattern($this->sessionIdCp), 1, 2147483647);
	}
	
	/** Main method for validating $_SERVER data 
	 * @param array $serverData
	 * @throws PntValidationException if $this->serverVarValidationFatal with message about the last validation that failed
	 * @return array with valid server data 
	 */
    function validateServerVars($serverData) {
		$this->validServerVars = array();
		$error = false;
    	forEach($serverData as $key => $value) {
    		$errorMessage = $this->validateServerVarName($key);
    		if ($errorMessage) {
				$error = 'server var name';
    			$this->logValidationWarning('server var name', $key, $errorMessage);
    			continue;
    		}
    		
    		$sanitizedValue = $key == 'PHP_AUTH_USER' || $key == 'PHP_AUTH_PW' 
				? $this->sanitizePhpAuth($key, $value) 
				: $this->sanitizeServerValue($key, $value);
    		$errorMessage = $this->validateServerValue($key, $sanitizedValue);
    		if ($errorMessage) {
    			$this->logValidationWarning($key, $sanitizedValue, $errorMessage);
				$error = $key;
    		} else {
    			$this->validServerVars[$key] = $sanitizedValue;
			}
    	}
    	if ($error && $this->serverVarValidationFatal)
    		throw new PntValidationException("$this->serverVarValidationFailed $error");
    	
		return $this->validServerVars;
    }
    
    function validateServerVarName($name) {
    	return $this->pregValidate('server var name', $name, $this->getCpPattern($this->headerNameCp), 1, 2147483647);
	}
    
	/** Sanitizes invalid value for PHP_AUTH_USER and PHP_AUTH_PW 
	 * must call ::logValidationWarning if replacing some character(s) that raise security suspicion 
	 * default implementation is no sanitization.  
	 * May be overridden on HttpValidator to do actual sanitization.
	 * @return string sanitized and validated value or null
	 * @param string value to sanitize
	 * */
	function sanitizePhpAuth($name, $value) {
		return $value; 
	}
	
	/** Validates 'PHP_AUTH_USER' and 'PHP_AUTH_PW'
	 * @return string error message or null if valid
	 * Default implementation is for single byte character encodings, 
	 * all characters are expected to be valid (like with ISO-8859-1).
	 * Should be overridden for UTF-8 and other character encodings for which 
	 * invalid characters can occur.
	 */
	function validatePhpAuth($name, $value) {
		return $this->validateForNullChar($name, $value);
	}
	
	/** Sanitizes invalid server var value, except PHP_AUTH_USER and PHP_AUTH_PW 
	 * must call ::logValidationWarning if replacing some character(s) that raise security suspicion 
	 * default implementation is no sanitization.  
	 * May be overridden on HttpValidator to do actual sanitization.
	 * @return mixed sanitized and validated value or null
	 * @param string value that has failed validation 
	 * @trhows PntValidationException if validation fails after sanitization
	 * */
	function sanitizeServerValue($name, $value) {
		return $value; 
	}
	
	/** Validates values from $_SERVER
	 * @return string error message or null if valid
	 */
	function validateServerValue($name, $value) {
		$minLength = isSet($this->minLengths[$name]) ? $this->minLengths[$name] : 0;
		$maxLength = isSet($this->maxLengths[$name]) ? $this->maxLengths[$name] : $this->pcre_backtrack_limit;
		
		if ($name == 'SERVER_NAME') {
			$errorMessage = $this->pregValidate($name, $value, $this->serverPatterns['REMOTE_ADDR'], $minLength, $maxLength, 1);
			if (!$errorMessage) return null;
			
			return $this->pregValidate($name, $value, $this->serverPatterns['REMOTE_HOST'], $minLength, $maxLength, 1);
		}
		
		if (isSet($this->serverPatterns[$name])) {
			$errorMessage =  $this->pregValidate($name, $value, $this->serverPatterns[$name], $minLength, $maxLength, 1);
			if ($errorMessage) return $errorMessage;
			
			return isSet($this->maxValues[$name]) 
				? $this->validateMinMaxValue($name, $value)
				: null;
		}
		
		if (isSet($this->serverCps[$name])) 
			return $this->pregValidate($name, $value, $this->getCpPattern($this->serverCps[$name]), $minLength, $maxLength);	
		
		if ($name == 'PHP_AUTH_USER' || $name == 'PHP_AUTH_PW' ) 
			return $this->validatePhpAuth($name, $value);
			
		if (substr($name, 0, 5) == 'HTTP_' || isSet($this->maxLengths[$name]))
			return $this->pregValidate($name, $value, $this->getCpPattern($this->headerValueCp), 0, $maxLength);

		//!! remaining values are NOT VALIDATED!
	}
	
	function validateMinMaxValue($name, $value) {
    	if ($value < 0)
    		return "$name $this->tooLow: $value";
    	if ($value > $this->maxValues[$name])
    		return "$name $this->tooHigh: $value";
		}
	
	/** @return string preg character class pattern */
    function getCpPattern($classPiece) {
    	return "~[^a-zA-Z0-9{$classPiece}]~";
    }
    
	function pregValidate($description, $value, $pattern, $minLength, $maxLength, $expected=0) {
		$found = array();
    	$length = strlen($value);
    	if ($length < $minLength)
    		return "$description $this->tooShort: $length";
    	if ($length > $maxLength)
    		return "$description $this->tooLong: $length";
    	$matchResult = preg_match($pattern, $value, $found);

    	if ($matchResult === false) 
    		return "error in pattern: $pattern";
    	if ($matchResult != $expected) 
			return "$description $this->invalid"
				. ($expected ? '' : ": ". implode(' ', $found) );
    	
		return null; //valid
	}
    
	function logValidationWarning($key, $value, $errorMessage) {
		$timeStamp = date(ValueValidator::getInternalTimestampFormat(), time());
		$this->logger->logError($key, $errorMessage, 'HttpValidator', $value, $timeStamp, array(), 'HttpValidationWarning');
	}
}

?>