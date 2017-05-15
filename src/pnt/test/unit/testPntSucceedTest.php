<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');
Gen::includeClass('ObjectToTest', 'pnt/test/unit');

/**
 * @package pnt/test/unit
 */
class PntSucceedTest extends PntTestCase {
	
	public $obj1;
	public $obj2;
	
	function setUp() {
		$this->obj1 = new ObjectToTest();
    	$this->obj2 = new ObjectToTest();
    	$this->obj2->var1 = 'value of obj2->var1 explicitly set by testSucceed';
	}

    function testSucceed() {

    	$this->assertEquals('123.0', 123, 'mixed');
    	$this->assertEquals(2, 2, 'with numbers');
    	$this->assertEquals('yes', 'yes', 'with Strings');
    	$this->assertEquals($this->obj1, $this->obj1, 'with objects');

		$this->assertNull(null, 'null');
		$this->assertNotNull(123, '123');

		$this->assertNotSame('12', 12, '12');
		$this->assertSame($this->obj1, $this->obj1, '$this->obj1');

     	$this->assertFalse(false, 'false');
		$this->assertTrue(true, 'true');

		$this->assertRegExp('~.php~', 'myFile.php', 'RegExp');
		
		Assert::ofType('integer', 123);
		Assert::ofType('string', '123');
		Assert::ofType('number', 123);
		Assert::ofType('number', '123');
		Assert::ofType('NULL', null);
		Assert::ofType('ObjectToTest', $this->obj1);
		
		Assert::ofAnyType(array('integer', 'boolean'), false);
		
    }
    
}

return 'PntSucceedTest';
?>
