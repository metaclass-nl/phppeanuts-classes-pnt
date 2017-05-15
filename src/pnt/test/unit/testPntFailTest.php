<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');
Gen::includeClass('ObjectToTest', 'pnt/test/unit');

/**
 * @package pnt/test/unit
 */
class PntFailTest extends PntTestCase {
	
	public $obj1;
	public $obj2;
	
	function setUp() {
		$this->obj1 = new ObjectToTest();
    	$this->obj2 = new ObjectToTest();
    	$this->obj2->var1 = 'value of obj2->var1 explicitly set by testFail';
		
	}

    function testFail() {

    	$this->assertEquals('yes', 123, 'mixed');
    	$this->assertEquals(2, 4, 'with numbers');
    	$this->assertEquals('yes', 'no', 'with Strings');
    	$this->assertEquals($this->obj1, $this->obj2, 'with objects');

		$this->assertNotNull(null, 'null');
		$this->assertNull(123, '123');

		$this->assertSame('12', 12, '12');
		$this->assertNotSame($this->obj1, $this->obj1, '$this->obj1');

     	$this->assertTrue(false, 'false');
		$this->assertFalse(true, 'true');

		$this->assertRegExp('~.php~', 'myFile.txt', 'RegExp');
     	
		Assert::ofType('boolean', 1);
		Assert::ofType('string', 123);
		Assert::ofType('number', '123,45');
		Assert::ofType('integer', null);
		Assert::ofType('object', $this->obj1);
		Assert::ofType('ObjectToTest', $this);
		
		Assert::ofAnyType(array('integer', 'boolean'), '12');
    }
    
}

return 'PntFailTest';
?>
