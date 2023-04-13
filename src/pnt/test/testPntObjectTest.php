<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/** @package pnt/test */
class PntObjectTest extends PntTestCase {
	
	public $obj1;
	public $clsDes;
	
	function setUp() {
		//Gen::includeClass('PntDerivedPropertyDescriptor', 'pnt/meta');
		Gen::includeClass('PntObject', 'pnt');
		$this->obj1 = new PntObject;
		$this->clsDes = $this->obj1->getClassDescriptor();
	}

	function test_getClassDescriptor() {
		$this->assertNotNull($this->clsDes);
		
		$this->clsDes->label = 'label for testing';
		$this->assertSame(
			$this->clsDes
			, $this->obj1->getClassDescriptor()
		);
	}

	function test_getLabel() {
		$this->assertSame($this->obj1->getLabel(), $this->obj1->basicGetLabel(), 'pntObject label against basicLabel');
		
		Gen::includeClass('TestPropsObject', 'pnt/test/meta');
		$obj = new TestPropsObject();
		$value = 10;
		$obj->set('field1', $value);
		$this->assertSame($value, $obj->getLabel(), 'testPropsObject label against value set');
	}
/*
    	$this->assertEquals('yes', 123, 'assertEquals');
		$this->assertNotNull(null, 'assertNotNull');
		$this->assertNull(123, 'assertNull');
		$this->assertSame('12', 12, 'assertSame');
		$this->assertNotSame($this->obj1, $this->obj1, 'assertNotSame');
     	$this->assertTrue(false, 'assertTrue');
		$this->assertFalse(true, 'assertFalse');
		$this->assertRegExp('~.php~', 'myFile.txt', 'assertRegExp');
 */   	
}

return 'PntObjectTest';
?>
