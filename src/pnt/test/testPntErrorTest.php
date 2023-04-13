<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/** @package pnt/test */
class PntErrorTest extends PntTestCase {
	
	public $obj1;
	public $obj1Message = 'message of original error';
	public $obj2;
	public $obj2Message = 'message of resulting error';
	public $obj3;
	
	
	function setUp() {
		Gen::includeClass('PntError', 'pnt');
		$this->obj1 = new PntError($this->obj1Message);
//no longer supported: $this->obj2 = new PntError($this->obj2Message, 0, $this->obj1Message);
		$this->obj3 = new PntError($this->obj2Message, 0, $this->obj1);
	}

	function __toString() {
		return 'TestPntError';
	}

	function test_getMessage()
	{
		$this->assertTrue($this->obj1Message, 'obj1Message');
		$this->assertEquals(
			$this->obj1Message
			, $this->obj1->getMessage()
			, 'obj1->getMessage()'
		);
		$errorClass = 'PntError';
		$errorNoMessage = new $errorClass();
		$this->assertEquals(
			''
			, $errorNoMessage->getMessage()
			, 'errorNoMessage->getMessage()'
		);
	}
	
	function test_getCause()
	{
		$this->assertSame(
			$this->obj1
			, $this->obj3->getCause()
			, 'obj3->getCause()'
		);
	}

	function test_getCauseDescription()
	{
		$this->assertEquals(
			$this->obj1Message
			, $this->obj3->getCauseDescription()
			, 'obj3->getCauseDescription()'
		);
	}

	function test_getLabel()
	{
		$this->assertEquals(
			$this->obj2Message
				.' because: '
				. $this->obj1Message
			, $this->obj3->getLabel()
			, 'obj3->getLabel()'
		);
	}

}

return 'PntErrorTest';
?>
