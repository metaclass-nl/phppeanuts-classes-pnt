<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/** @package pnt/test */
class PntValueValidatorTest extends PntTestCase {
	
	public $validator;
	public $infiniteBig=1.79e308;
	public $infiniteSmall=-1.79e308;
	
	function setUp() {
		Gen::includeClass('PntValueValidator', 'pnt');
		$this->validator = new PntValueValidator();
		$this->validator->type = "string";
		$this->validator->readOnly = false;
	}

	function test_statics()
	{
		$this->assertEquals(
			$this->infiniteBig	
			, PntValueValidator::getInfiniteBig()
			, 'infiniteBig');
		$this->assertEquals(
			$this->infiniteSmall	
			, PntValueValidator::getInfiniteSmall()
			, 'infiniteSmall');
		$this->assertEquals(
			"Y-m-d"	
			, PntValueValidator::getInternalDateFormat()
			, 'getInternalDateFormat');
		$this->assertEquals(
			"Y-m-d H:i:s"	
			, PntValueValidator::getInternalTimestampFormat()
			, 'getInternalTimestampFormat');
		$this->assertEquals(
			'.'	
			, PntValueValidator::getInternalDecimalSeparator()
			, 'getInternalDecimalSeparator');
	}

	function test_getDecimalPrecision()
	{
		$this->assertNull(
			$this->validator->getDecimalPrecision(null)
			, "no maxLength");
			
		$this->validator->decimal = ',';
			
		$this->assertEquals(
			12
			, $this->validator->getDecimalPrecision('3,12')
			, "maxLength 3,12");

		$this->assertEquals(
			0
			, $this->validator->getDecimalPrecision('3')
			, "maxLength 3");

		$this->assertNull(
			$this->validator->getDecimalPrecision('')
			, "maxLength ''");
		$this->assertNull(
			$this->validator->getDecimalPrecision(5.6)
			, "maxLength 5.6");
	}



	function test_getNumberMaxValue()
	{
		$this->assertEquals(
			$this->infiniteBig
			, $this->validator->getNumberMaxValue()
			, "no maxLength, no maxValue");
		$this->validator->maxLength = '3,4';
		$this->assertEquals(
			999.9999
			, $this->validator->getNumberMaxValue()
			, "no maxLength 3,4; no maxValue"
			, 0.00001);
		$this->validator->maxValue = 10000;
		$this->assertEquals(
			10000
			, $this->validator->getNumberMaxValue()
			, "no maxLength 3,4; maxValue 10000"
			, 0.00001);
	}

	function test_getNumberMinValue()
	{
		$this->assertEquals(
			$this->infiniteSmall
			, $this->validator->getNumberMinValue()
			, "no maxLength, no minValue");
		$this->validator->maxLength = '3,4';
		$this->assertEquals(
			-999.9999
			, $this->validator->getNumberMinValue()
			, "no maxLength 3,4; no maxValue"
			, 0.00001);
		$this->validator->minValue = -10;
		$this->assertEquals(
			-10
			, $this->validator->getNumberMinValue()
			, "no maxLength 3,4; minValue - 10"
			, 0.00001);
	}

	function test_validateNumber() 
	{
		$this->validator->type = 'number';
		$value = 10000;
		$this->assertNull(
			$this->validator->validate($value) , $value
		);
		$value = -10001;
		$this->assertNull(
			$this->validator->validate($value), $value
		);

		$this->validator->maxLength = "3,4";
		$value = 900;
		$this->assertNull(
			$this->validator->validate($value) , $value
		);
		$value = -901;
		$this->assertNull(
			$this->validator->validate($value), $value
		);
		$value = 10002;
		$this->assertSame(
			'too high, max: 999.9999',
			$this->validator->validate($value), $value
		);
		$value = -10002;
		$this->assertSame(
			'too low, min: -999.9999',
			$this->validator->validate($value), $value
		);

		$this->validator->maxValue = 6000;
		$value = 10003;
		$this->assertSame(
			'too high, max: '.$this->validator->maxValue,
			$this->validator->validate($value), $value
		);
		$value = 5999;
		$this->assertNull(
			$this->validator->validate($value) , $value
		);

		$this->validator->minValue = -3000;
		$value = -10004;
		$this->assertSame(
			'too low, min: '.$this->validator->minValue,
			$this->validator->validate($value), $value
		);
		$value = -2999.999;
		$this->assertNull(
			$this->validator->validate($value) , $value
		);
	}

	function test_validateDate() 
	{
		$this->validator->type = 'date';
		$value = '2002-01-15';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);

		$this->validator->maxValue = '2000-01-01';
		$this->assertSame(
			'too high, max: '.$this->validator->maxValue,
			$this->validator->validate($value), $value
		);
		$value = '1999-12-31';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);

		$value = null;
		$this->assertNull(
			$this->validator->validate($value) , "should allow null"
		);
		$this->validator->minLength = 1;
		$this->assertSame(
			'too short, min: 1',
			$this->validator->validate($value), "should not allow null"
		);
		

		$this->validator->minValue = '1990-12-31';
		$value = '1990-12-30';
		$this->assertSame(
			'too low, min: '.$this->validator->minValue,
			$this->validator->validate($value), $value
		);
		$value = '1990-12-31';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);


	}

	function test_validateTime() 
	{
		$this->validator->type = 'time';
		$value = '10:15:00';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);

		$this->validator->maxValue = '10:12:00';
		$this->assertSame(
			'too high, max: '.$this->validator->maxValue,
			$this->validator->validate($value), $value
		);
		$value = '10:12:00';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);

		$this->validator->minValue = '10:11:59';
		$value = '10:11:58';
		$this->assertSame(
			'too low, min: '.$this->validator->minValue,
			$this->validator->validate($value), $value
		);
		$value = '10:11:59';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);
	}

	function test_validateTimestamp() 
	{
		$this->validator->type = 'timestamp';
		$value = '2002-01-15 00:00:00';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);

		$this->validator->maxValue = '2000-01-01 00:00:00';
		$this->assertSame(
			'too high, max: '.$this->validator->maxValue,
			$this->validator->validate($value), $value
		);
		$value = '1999-12-31 00:00:00';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);

		$this->validator->minValue = '1990-12-31 00:00:00';
		$value = '1990-12-30';
		$this->assertSame(
			'too low, min: '.$this->validator->minValue,
			$this->validator->validate($value), $value
		);
		$value = '1990-12-31 00:00:00';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);
	}

	function test_validateString() 
	{
		$this->validator->type = 'string';
		$value = 'lkslkjsdflkjsfd fdlkjsafdlk jasdlkjasd aslk jas lkj asdlkj asdlkj';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);
		$value = '';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);

		$this->validator->maxLength = 8;
		$value = '123456789';
		$this->assertSame(
			'too long, max: '.$this->validator->maxLength,
			$this->validator->validate($value), $value
		);
		$value = '12345678';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);

		$this->validator->minLength = 3;
		$value = '12';
		$this->assertSame(
			'too short, min: '.$this->validator->minLength,
			$this->validator->validate($value), $value
		);
		$value = '123';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);
	}

	function test_validateEmail() 
	{
		$this->validator->type = 'email';
		$value = 'henk2@metaclass.nl';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);
		$value = '1@m1.nl';
		$this->assertNull(
			$this->validator->validate($value) , $value
		);

		$value = '123456789';
		$this->assertSame(
			'invalid email address',
			$this->validator->validate($value), $value
		);
	}

	function test_initFromProp()
	{
		Gen::includeClass('TestPropsObject', 'pnt/test/meta');
		$obj1 = new TestPropsObject();
		$clsDes = $obj1->getClassDescriptor();
		$prop = $clsDes->getPropertyDescriptor('field2');
		
		$this->validator->initFromProp($prop);

		$this->assertSame($this->validator->type, $prop->getType(),'type');
		$this->assertSame($this->validator->readOnly, $prop->getReadOnly(),'readOnly');
		$this->assertSame($this->validator->minValue, $prop->getMinValue(),'minValue');
		$this->assertSame($this->validator->maxValue, $prop->getMaxValue(),'maxValue');
		$this->assertSame($this->validator->minLength, $prop->getMinLength(),'minLength');
		$this->assertSame($this->validator->maxLength, $prop->getMaxLength(),'maxLength');
	}

	
	/** In order for PntSqlFilter::evaluateValue to work properly with Comparator '=',
	 * PntValueValidator::equal must work properly
	 * Obsolete test, may be used later in an other form
	 */
	function test_primsEqual() {
		Assert::true(PntValueValidator::equal(1, '1', 'string'), "1, '1', string");
		Assert::true(PntValueValidator::equal(1, '1', 'boolan'), "1, '1', boolean");
		Assert::true(PntValueValidator::equal('1', 1, 'number'), "'1', 1, number");
		Assert::true(PntValueValidator::equal(123, true, 'boolean'), "123, true, boolean");   
		Assert::true(PntValueValidator::equal(123, '1', 'boolean'), "123, '1', boolean");    
		Assert::true(PntValueValidator::equal(123, 1, 'boolean'), "123, 1, boolean");    
		Assert::true(PntValueValidator::equal(null, null, 'any'), "null, null, 'any");
		Assert::true(PntValueValidator::equal(0, false, 'boolean'), "0, false, boolean");
		Assert::true(PntValueValidator::equal('0', false, 'boolean'), "'0', false, boolean");
		Assert::true(PntValueValidator::equal('0', 0, 'number'), "'0', 0, number");
		Assert::true(PntValueValidator::equal('0', '0', 'number'), "'0', '0', number");
		Assert::true(PntValueValidator::equal('0', '0', 'boolean'), "'0', '0', boolean");
		Assert::true(PntValueValidator::equal('0.0', 0, 'number'), "'0.0', 0, number");
		Assert::true(PntValueValidator::equal('0.0', '0', 'number'), "'0.0', '0', number");
		Assert::true(PntValueValidator::equal('0.0', '', 'number'), "'0.0', '', number");
		
		Assert::false(PntValueValidator::equal(null, 'a', 'string'), "null, 'a', string");
		Assert::false(PntValueValidator::equal(null, 0, 'number'), "null, 0, number");
		Assert::false(PntValueValidator::equal(null, '', 'string'), "null, '', string");
		Assert::false(PntValueValidator::equal(null, false, 'boolean'), "null, false, boolean");
		Assert::false(PntValueValidator::equal(null, '0', 'string'), "null, '0', string");

		Assert::false(PntValueValidator::equal(0, 'a', 'string'), "0, 'a', string");
		Assert::false(PntValueValidator::equal(0, 'a 0', 'string'), "0, 'a0', string");
		Assert::false(PntValueValidator::equal(0, '0 a', 'string'), "0, '0a', string");
		Assert::false(PntValueValidator::equal(0, '', 'string'), "0, '', string");
		Assert::false(PntValueValidator::equal(0, null, 'any'), "0, null, any");
		
		Assert::false(PntValueValidator::equal('0', 'a', 'string'), "'0', 'a', string");
		Assert::false(PntValueValidator::equal('0', '', 'string'), "'0', '', string");
		Assert::false(PntValueValidator::equal('0', null, 'string'), "'0', null, string");
		Assert::false(PntValueValidator::equal('0', '0.0', 'string'), "'0', '0.0, string");
		
		Assert::false(PntValueValidator::equal(true, 'true', 'string'), "true, 'true', string");
		Assert::false(PntValueValidator::equal(true, null, 'any'), "true, null, any");
		Assert::false(PntValueValidator::equal('false', false, 'boolean'), "'false', false, boolean");
		Assert::false(PntValueValidator::equal(false, 'false', 'string'), "false, 'false', string");
		Assert::false(PntValueValidator::equal(false, null, 'any'), "false, null, any");
		Assert::true(PntValueValidator::equal('true', true, 'boolean'), "'true', true, booelan");
		
		Assert::false(PntValueValidator::equal('', null, 'any'), "'', null, any");
		Assert::true(PntValueValidator::equal('', 0, 'number'), "'', 0, number");
		Assert::true(PntValueValidator::equal('', 0.0, 'number'), "'', 0.0, number");
		Assert::true(PntValueValidator::equal('', 0, 'boolean'), "'', 0, boolean");
		Assert::true(PntValueValidator::equal('', '0', 'boolean'), "'', '0', boolean");
		Assert::true(PntValueValidator::equal('', false, 'boolean'), "'', false, boolean");
		Assert::true(PntValueValidator::equal('', '0', 'number'), "'', '0', number");
		Assert::true(PntValueValidator::equal('', '0.0', 'number'), "'', '0.0', number");
	}
	
}

return 'PntValueValidatorTest';

?>