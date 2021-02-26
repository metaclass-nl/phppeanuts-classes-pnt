<?php
// Copyright (c) MetaClass Groningen, 2003-2012

Gen::includeClass('PntTestCase', 'pnt/test');
Gen::includeClass('PntHttpRequest', 'pnt/web');

/** @package pnt/test */
class CasePntHttpRequest extends PntTestCase {
	
	public $validator;
	public $alpahNumeric = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	
	function setUp() {
		$logger = $this;
//		global $site;
//		$logger = $site->getErrorHandler();
		$this->validator = new PntHttpRequest($logger, 'ISO-8859-1');	
		$this->loggedErrors = array();	
	}

	function logError($key, $message, $filePath, $value, $timeStamp, $traces=array(), $type=null) {
		$this->loggedErrors[] = array(
			$key, $message, $filePath, $value, $timeStamp, $traces, $type);
	}
	
	function testValidateServerVarName() {
		$valid = $this->alpahNumeric. '-_';
		$this->checkValidateOctets('validateServerVarName', $valid, 'server var name invalid');
	}
	
	function testValidateHeaderValue() {
		Assert::null($this->validator->validateServerValue('HTTP_TEST', ''), 'empty');
		
		$this->validator->pcre_backtrack_limit = 1000;
		Assert::null($this->validator->validateServerValue('HTTP_TEST', str_repeat('A', 1000)), '1000 long');
		Assert::equals('HTTP_TEST too long: 1001'
			, $this->validator->validateServerValue('HTTP_TEST', str_repeat('A', 1001))
			, '1001 long'
		);
		
		$valid = $this->alpahNumeric. ' !"#$%&\'()*+,-./;:<=>?@[\]^_`{|}~'."\t ";
		$name = 'HTTP_TEST';
		$this->checkValidateOctets('validateServerValue', $valid, "$name invalid", $name);
	}
	
	function testValidateAUTH_TYPE() {
		Assert::equals('AUTH_TYPE too long: 7'
			, $this->validator->validateServerValue('AUTH_TYPE', str_repeat('A', 7))
			, '7 long'
		);
		
		Assert::null($this->validator->validateServerValue('AUTH_TYPE', 'DigesT'));
		Assert::null($this->validator->validateServerValue('AUTH_TYPE', 'baSIc'));
		Assert::equals('AUTH_TYPE invalid'
			, $this->validator->validateServerValue('AUTH_TYPE', 'quatsh')
			, 'quatsh'
		);
		Assert::equals('AUTH_TYPE invalid'
			, $this->validator->validateServerValue('AUTH_TYPE', 'BASIC ')
			, 'BASIC with a space at the end'
		);
	}
	
	function testValidateCONTENT_LENGTH() {
		Assert::null($this->validator->validateServerValue('CONTENT_LENGTH', '12345'), 'unsigned');
		Assert::null($this->validator->validateServerValue('CONTENT_LENGTH', '+12345'), 'plus');
		Assert::equals('CONTENT_LENGTH invalid'
			, $this->validator->validateServerValue('CONTENT_LENGTH', 'quatsh')
			, 'quatsh'
		);
		Assert::equals('CONTENT_LENGTH invalid'
			, $this->validator->validateServerValue('CONTENT_LENGTH', '10a1')
			, '10a1'
		);
		Assert::equals('CONTENT_LENGTH invalid'
			, $this->validator->validateServerValue('CONTENT_LENGTH', '10-1')
			, '10-1'
		);
		Assert::equals('CONTENT_LENGTH invalid'
			, $this->validator->validateServerValue('CONTENT_LENGTH', '10+1')
			, '10+1'
		);
		Assert::equals('CONTENT_LENGTH too low: -1'
			, $this->validator->validateServerValue('CONTENT_LENGTH', '-1')
			, '-1'
		);
		Assert::null($this->validator->validateServerValue('CONTENT_LENGTH', 2147483647), 'PHP_INT_MAX on 32 bits');
		Assert::equals('CONTENT_LENGTH too high: '.(2147483647 + 1)
			, $this->validator->validateServerValue('CONTENT_LENGTH', 2147483647 + 1)
			, 'max int + 1'
		);
	}
	
	function testValidateSERVER_PORT() {
		Assert::null($this->validator->validateServerValue('SERVER_PORT', '12345'), 'unsigned');
		Assert::null($this->validator->validateServerValue('SERVER_PORT', '+12345'), 'plus');
		Assert::equals('SERVER_PORT invalid'
			, $this->validator->validateServerValue('SERVER_PORT', 'quatsh')
			, 'quatsh'
		);
		Assert::equals('SERVER_PORT invalid'
			, $this->validator->validateServerValue('SERVER_PORT', '10a1')
			, '10a1'
		);
		Assert::equals('SERVER_PORT invalid'
			, $this->validator->validateServerValue('SERVER_PORT', '10-1')
			, '10-1'
		);
		Assert::equals('SERVER_PORT invalid'
			, $this->validator->validateServerValue('SERVER_PORT', '10+1')
			, '10+1'
		);
		Assert::equals('SERVER_PORT too low: -1'
			, $this->validator->validateServerValue('SERVER_PORT', '-1')
			, '-1'
		);
		Assert::null($this->validator->validateServerValue('SERVER_PORT', '65535'), '65535');
		Assert::equals('SERVER_PORT too high: '. 65536
			, $this->validator->validateServerValue('SERVER_PORT', 65536)
			, '65536'
		);
			}
	
	function testValidateCONTENT_TYPE() {
		Assert::null($this->validator->validateServerValue('CONTENT_TYPE', str_repeat('A', 4096)), '4096 long');
		Assert::equals('CONTENT_TYPE too long: 4097'
			, $this->validator->validateServerValue('CONTENT_TYPE', str_repeat('A', 4097))
			, '4097 long'
		);
	}
		
	function testValidatePATH_INFO() {
		Assert::null($this->validator->validateServerValue('PATH_INFO', str_repeat('A', 4096)), '4096 long');
		Assert::equals('PATH_INFO too long: 4097'
			, $this->validator->validateServerValue('PATH_INFO', str_repeat('A', 4097))
			, '4097 long'
		);
	}
	
	
	function testValidateQUERY_STRING() {
		Assert::null($this->validator->validateServerValue('QUERY_STRING', str_repeat('A', 4096)), '4096 long');
		Assert::equals('QUERY_STRING too long: 4097'
			, $this->validator->validateServerValue('QUERY_STRING', str_repeat('A', 4097))
			, '4097 long'
		);

		$valid = $this->alpahNumeric. ' &()*+,-./;:=?_%!';
		$name = 'QUERY_STRING';
		$this->checkValidateOctets('validateServerValue', $valid, "$name invalid", $name);
	}
	
	function testValidateREMOTE_HOST() {
		$name = 'REMOTE_HOST';
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, '127.0.0.1')
			, '127.0.0.1'
		);
		
		return $this->checkValidateHostName($name);
		
	}
	
	function testValidateSERVER_NAME() {
		$name = 'SERVER_NAME';
		Assert::null($this->validator->validateServerValue($name, '127.0.0.1'), '127.0.0.1');
		
		$this->checkValidateHostName($name);
	}
	
	
	function checkValidateHostName($name) {
		Assert::null($this->validator->validateServerValue($name, 
				str_repeat('A', 63)
					.'.'.str_repeat('A', 63)
					.'.'.str_repeat('A', 63)
					.'.'.str_repeat('A', 4)
					)
			, 'longest valid');
		Assert::equals("$name too long: 256"
			, $this->validator->validateServerValue($name, str_repeat('A', 256))
			, '256 long'
		);
	
		Assert::null($this->validator->validateServerValue($name, 'localhost'), 'localhost');
		Assert::null($this->validator->validateServerValue($name, 'ontwikkeling'), 'ontwikkeling');
		Assert::null($this->validator->validateServerValue($name, 'phppeanuts.org'), 'phppeanuts.org');
		Assert::null($this->validator->validateServerValue($name, 'metaclass.nl'), 'metaclass.nl');
		Assert::null($this->validator->validateServerValue($name, 'my.name'), 'my.name');
		Assert::null($this->validator->validateServerValue($name, 'my.info'), 'my.info');
		Assert::null($this->validator->validateServerValue($name, 'whoever.co.uk'), 'whoever.co.uk');
		Assert::null($this->validator->validateServerValue($name, 'subdomain.whoever.co.uk'), 'subdomain.whoever.co.uk');
		Assert::null($this->validator->validateServerValue($name, 'with-dash.net'), 'with-dash.net');

		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'withéaccent.com')
			, 'withéaccent.com'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'with%percent.com')
			, 'with%percent.com'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'with_underscore.com')
			, 'with_underscore.com'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'with*asterix.com')
			, 'with*asterix.com'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'with$dollar.com')
			, 'with$dollar.com'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'with"doublequote.com')
			, 'with"doublequote.com'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'with:colon.com')
			, 'with:colon.com'
		);
	}

	function testValidateREMOTE_USER() {
		$name = 'REMOTE_USER';
		Assert::null($this->validator->validateServerValue($name, str_repeat('A', 255)), '255 long');
		Assert::equals("$name too long: 256"
			, $this->validator->validateServerValue($name, str_repeat('A', 256)), '256 long'
		);
		$valid = $this->alpahNumeric. '!#$%&\'*+-.^_`|~';
		$this->checkValidateOctets('validateServerValue', $valid, "$name invalid", $name);
	}
	
	function testValidateREQUEST_METHOD() {
		$name = 'REQUEST_METHOD';
		Assert::equals("$name too short: 2"
			, $this->validator->validateServerValue($name, 'AA'), '2 long');
		$name = 'REQUEST_METHOD';
		Assert::equals("$name too long: 8"
			, $this->validator->validateServerValue($name, str_repeat('A', 8)), '8 long');
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, str_repeat('A', 7)), "7 a's");
			
		Assert::null($this->validator->validateServerValue($name, 'GET'), 'GET');
		Assert::null($this->validator->validateServerValue($name, 'POST'), 'POST');
		Assert::null($this->validator->validateServerValue($name, 'HEAD'), 'HEAD');
		Assert::null($this->validator->validateServerValue($name, 'TRACE'), 'TRACE');
		Assert::null($this->validator->validateServerValue($name, 'OPTIONS'), 'OPTIONS');
		
		Assert::null($this->validator->validateServerValue($name, 'PUT'), 'PUT');
		Assert::null($this->validator->validateServerValue($name, 'DELETE'), 'DELETE');
		
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'get'), "get");
	}
	
	function testValidateSCRIPT_NAME() {
		$name = 'SCRIPT_NAME';
		Assert::equals("$name too short: 0"
			, $this->validator->validateServerValue($name, ''), 'empty');
		$valid = $this->alpahNumeric. '!$%&\'()*+-,./:=@_~'; //request uri
		$this->checkValidateOctets('validateServerValue', $valid, "$name invalid", $name);
	}
	
	function testValidateREQUEST_URI() {
		$name = 'REQUEST_URI';
		$valid = $this->alpahNumeric. '!$%&\'()*+-,./:=@_~?';
		$this->checkValidateOctets('validateServerValue', $valid, "$name invalid", $name);
	}
	
	function testValidateREMOTE_ADDR($name='REMOTE_ADDR') {
		Assert::equals("$name too long: 16"
			, $this->validator->validateServerValue($name, '123.123.123.0001')
			, '123.123.123.0001'
		);
		
		Assert::null($this->validator->validateServerValue($name, '127.0.0.1'), '127.0.0.1');
		Assert::null($this->validator->validateServerValue($name, '0.0.0.0'), '0.0.0.0');
		Assert::null($this->validator->validateServerValue($name, '0.0.0.1'), '0.0.0.1');
		Assert::null($this->validator->validateServerValue($name, '1.0.0.0'), '0.0.0.1');
		Assert::null($this->validator->validateServerValue($name, '234.156.089.001'), 'trailing zeros');
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'quatsh')
			, 'quatsh'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'A0.BF.CE.DD')
			, 'A0.BF.CE.DD'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, '0234.123.123.12')
			, 'byte 1 too long'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, '123.123.123')
			, '3 bytes'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, '123.256.121.001')
			, 'byte > 255'
		);
	}
	
	function testValidatePATH_TRANSLATED() {
		//Note: As of PHP 4.3.2, PATH_TRANSLATED is no longer set implicitly under the Apache 2 SAPI
		$name = 'PATH_TRANSLATED';
		Assert::null($this->validator->validateServerValue($name, str_repeat('A', 4096)), '4096 long');
		Assert::equals("$name too long: 4097"
			, $this->validator->validateServerValue($name, str_repeat('A', 4097))
			, '4097 long'
		);
		$special = ' !#$%&\'()+,-./=@[\]^_`{}~';
		$valid = $this->alpahNumeric. $special;
		if (subStr(php_uname('s'), 0, 7) == 'Windows') {
			$this->checkValidateOctets('validateServerValue', $valid, "$name invalid", $name, false);
			
			$value = 'c:\testing\a123.gif';
			Assert::null($this->validator->validateServerValue($name, $value), "$name '$value'");
			$value = 'Z:'. $valid;
			Assert::null($this->validator->validateServerValue($name, $value), "$name '$value'");
			
			$value = 'http:'.  $valid;
			Assert::equals("$name invalid", $this->validator->validateServerValue($name, $value), "$name '$value'");
			$value = ':'.  $valid;
			Assert::equals("$name invalid", $this->validator->validateServerValue($name, $value), "$name '$value'");
			$value = '/:'.  $valid;
			Assert::equals("$name invalid", $this->validator->validateServerValue($name, $value), "$name '$value'");
				
		} else {
			$this->checkValidateOctets('validateServerValue', $valid, "$name invalid", $name, true);
		}
	}
	
	function testValidateSERVER_PROTOCOL() {
		$name = 'SERVER_PROTOCOL';
		Assert::null($this->validator->validateServerValue($name, 'HTTP/1.0'), 'HTTP/1.0');
		Assert::null($this->validator->validateServerValue($name, 'HTTP/2.0'), 'HTTP/2.0');
		Assert::equals("$name too long: 9"
			, $this->validator->validateServerValue($name, str_repeat('A', 9))
			, '9 long'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'HTTX/1.0')
			, 'HTTX/1.0'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'HTTp/1.0')
			, 'HTTp/1.0'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'HTTP\1.0')
			, 'HTTP\1.0'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'HTTP/1,0')
			, 'HTTP/1,0'
		);
		Assert::equals("$name invalid"
			, $this->validator->validateServerValue($name, 'HTTP/1')
			, 'HTTP/1'
		);
	}

//PHP_AUTH_USER PHP_AUTH_PW  

	function testValidateServerVars() {
		$this->validator->serverVarValidationFatal = false;
		$data = array(
				'BAD NAME' => 'whatever'
			,	'HTTP_TEST_OK' => 'OK value'
			,	'HTTP_TEST_BAD' => 'kopieëren'
			,	'UNCHECKED' => "\n" 
			,	'HTTP_TEST_EMPTY' => '' 
			, 	'with null'. chr(0). 'byte' => 'OKvalue'
			, 	'PHP_AUTH_PW' => 'with null'. chr(0). 'byte' 
			, 	'PHP_AUTH_USER' => 'with null'. chr(0). 'byte' 
			);
		$result = $this->validator->validateServerVars($data);
		Assert::equals(array(
				'HTTP_TEST_OK' => 'OK value'
			,	'UNCHECKED' => "\n" 
			,	'HTTP_TEST_EMPTY' => '' 
			), $result, 'validated');
		
		Assert::equals(5, count($this->loggedErrors), 'number of logged errors');
			
		Assert::equals('server var name', $this->loggedErrors[0][0], '0 key');
		Assert::equals('server var name invalid:  ', $this->loggedErrors[0][1], '0 errorMessage');
		Assert::equals('HttpValidator', $this->loggedErrors[0][2], '0 HttpValidator');
		Assert::equals('BAD NAME', $this->loggedErrors[0][3], '0 value');
		Assert::equals('HttpValidationWarning', $this->loggedErrors[0][6], '0 HttpValidationWarning');

		Assert::equals('HTTP_TEST_BAD', $this->loggedErrors[1][0], '1 key');
		Assert::equals('HTTP_TEST_BAD invalid: ë', $this->loggedErrors[1][1], '1 errorMessage');
		Assert::equals('kopieëren', $this->loggedErrors[1][3], '1 value');
				
		Assert::equals('server var name', $this->loggedErrors[2][0], '2 key');
		//strange: space reported instead of null byte
		Assert::equals('server var name invalid:  ', $this->loggedErrors[2][1], '2 errorMessage');
		Assert::equals('with null'. chr(0). 'byte', $this->loggedErrors[2][3], '2 value');

		Assert::equals('PHP_AUTH_PW', $this->loggedErrors[3][0], '3 key');
		Assert::equals('PHP_AUTH_PW invalid: null byte', $this->loggedErrors[3][1], '3 errorMessage');
		Assert::equals('with null'. chr(0). 'byte', $this->loggedErrors[3][3], '3 value');
		
		Assert::equals('PHP_AUTH_USER', $this->loggedErrors[4][0], '4 key');
		Assert::equals('PHP_AUTH_USER invalid: null byte', $this->loggedErrors[4][1], '4 errorMessage');
		Assert::equals('with null'. chr(0). 'byte', $this->loggedErrors[4][3], '4 value');
		
		$this->validator->serverVarValidationFatal = true;
		$e = null;
		try {
			$result = $this->validator->validateServerVars($data);
		} catch (PntError $e) {
			Assert::equals('Server variable validation failed for PHP_AUTH_USER' , $e->getMessage(), 'PntError message');
		}
		Assert::notNull($e, 'PntError thrown');
	}
	
	function checkValidateOctets($methodName, $whitelistString, $errorMessage, $nameParam=null, $specificErr=true) {
		$map = array_flip(unpack('C*', $whitelistString));
		$valid = subStr($whitelistString, 0, 4);
		for($i=0; $i<256; $i++) {
			$value = $valid. chr($i);
			$name = $nameParam ? $nameParam : $value;
			$errorRef = $specificErr ? $errorMessage. ': '. chr($i) : $errorMessage;
			if (isSet($map[$i]))
				Assert::null($this->validator->$methodName($name, $value), "$name '$value'");
			else 
				Assert::equals($errorRef, $this->validator->$methodName($name, $value), "$name '$value'");
			
			$value = chr($i). $valid;
			$name = $nameParam ? $nameParam : $value;
			if (isSet($map[$i]))
				Assert::null($this->validator->$methodName($name, $value), "$name '$value'");
			else 
				Assert::equals($errorRef, $this->validator->$methodName($name, $value), "$name '$value'");
			
			$value = $valid. chr($i). $valid;
			$name = $nameParam ? $nameParam : $value;
			if (isSet($map[$i]))
				Assert::null($this->validator->$methodName($name, $value), "$name '$value'");
			else 
				Assert::equals($errorRef, $this->validator->$methodName($name, $value), "$name '$value'");
		}
	}
	
	function test_validateCookieName() {
		$valid = $this->alpahNumeric. '_-';
		$map = array_flip(unpack('C*', $valid));
		for($i=0; $i<256; $i++) {
			$name = 'name'. chr($i);
			if (isSet($map[$i]))
				Assert::null($this->validator->validateCookieName($name), $name);
			else 
				Assert::equals('cookie name invalid', $this->validator->validateCookieName($name), $name);
			$name = 'na'. chr($i). 'me';
			if (isSet($map[$i]))
				Assert::null($this->validator->validateCookieName($name), $name);
			else 
				Assert::equals('cookie name invalid', $this->validator->validateCookieName($name), $name);
				$name = chr($i). 'name';
			if (isSet($map[$i]))
				Assert::null($this->validator->validateCookieName($name), $name);
			else 
				Assert::equals('cookie name invalid', $this->validator->validateCookieName($name), $name);
		}
	}
	
	function test_validatePhpAuth() {
		Assert::equals('PHP_AUTH_PW invalid: null byte', 
			$this->validator->validatePhpAuth('PHP_AUTH_PW', 'with null'. chr(0). 'byte'), 
			'with null byte');
		Assert::null($this->validator->validatePhpAuth('PHP_AUTH_PW', "OKé\n"), 'some other chars');
	}
	
	function test_validateCookies() {
		$this->validator->gpcValidationFatal = false;
		$data = array(
				'name_underscore' => 'whatever'
			,	'kopieëren' => 'OK value'
			,	'withDiacriticalSign' => 'kopieëren'
			,   'name with spaces' => 'any value'
			,	'withNewline' => "\n" 
			,   'name-dash' => 'value;'
			,	'empty' => '' 
			, 	'with null'. chr(0). 'byte' => 'OK value'
			, 	'nameOk' => 'with null'. chr(0). 'byte' 
			);
		$result = $this->validator->validateGpc($data, true);
		Assert::equals(
			array(
				'name_underscore' => 'whatever'
			,	'withDiacriticalSign' => 'kopieëren'
			,	'withNewline' => "\n"
			,   'name-dash' => 'value;'
			,	'empty' => ''
			), $result, 'validated');
		
		Assert::equals(4, count($this->loggedErrors), 'number of logged errors');
			
		Assert::equals('cookie name', $this->loggedErrors[0][0], '0 key');
		Assert::equals('cookie name invalid', $this->loggedErrors[0][1], '0 errorMessage');
		Assert::equals('kopieëren', $this->loggedErrors[0][3], '0 value');

		Assert::equals('cookie name', $this->loggedErrors[1][0], '1 key');
		Assert::equals('cookie name invalid', $this->loggedErrors[1][1], '1 errorMessage');
		Assert::equals('name with spaces', $this->loggedErrors[1][3], '1 value');
				
		Assert::equals('cookie name', $this->loggedErrors[2][0], '2 key');
		Assert::equals('cookie name invalid', $this->loggedErrors[2][1], '2 errorMessage');
		Assert::equals('with null'. chr(0). 'byte', $this->loggedErrors[2][3], '2 value');
		
		Assert::equals('nameOk', $this->loggedErrors[3][0], '3 key');
		Assert::equals('nameOk invalid: null byte', $this->loggedErrors[3][1], '3 errorMessage');
		Assert::equals('with null'. chr(0). 'byte', $this->loggedErrors[3][3], '3 value');
		
		$this->validator->gpcValidationFatal = true;
		$e = null;
		try {
			$result = $this->validator->validateGpc($data, true);
		} catch (PntError $e) {
			Assert::equals('Gpc validation failed for nameOk' , $e->getMessage(), 'PntError message');
		}
		Assert::notNull($e, 'PntError thrown');
	}
	
	function test_validateParams() {
		$this->validator->gpcValidationFatal = false;
		$data = array(
				'name_underscore' => 'whatever'
			,	'kopieëren' => 'OK value'
			,	'withDiacriticalSign' => 'kopieëren'
			,   'name with spaces' => 'any value'
			,	'withNewline' => "\n" 
			,   'name-dash' => 'value;'
			,	'empty' => '' 
			, 	'with null'. chr(0). 'byte' => 'OK value'
			, 	'name ok' => 'with null'. chr(0). 'byte' 
			);
		$result = $this->validator->validateGpc($data);
		Assert::equals(array(
				'name_underscore' => 'whatever'
			,	'kopieëren' => 'OK value'
			,	'withDiacriticalSign' => 'kopieëren'
			,   'name with spaces' => 'any value'
			,	'withNewline' => "\n" 
			,   'name-dash' => 'value;'
			,	'empty' => '' )
		, $result, 'validated');
		
		Assert::equals(2, count($this->loggedErrors), 'number of logged errors');
		
		Assert::equals('param name', $this->loggedErrors[0][0], '0 key');
		Assert::equals('param name invalid: null byte', $this->loggedErrors[0][1], '0 errorMessage');
		Assert::equals('with null'. chr(0). 'byte', $this->loggedErrors[0][3], '0 value');
		
		Assert::equals('name ok', $this->loggedErrors[1][0], '1 key');
		Assert::equals('name ok invalid: null byte', $this->loggedErrors[1][1], '1 errorMessage');
		Assert::equals('with null'. chr(0). 'byte', $this->loggedErrors[1][3], '1 value');
	}	
}

return 'CasePntHttpRequest';
?>
