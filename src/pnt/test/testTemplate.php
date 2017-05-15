<?php
// Copyright (c) MetaClass Groningen, 2003-2012

Gen::includeClass('PntTestCase', 'pnt/test');
Gen::includeClass('PntObject', 'pnt');

/** @package pnt/test */
class Template extends PntTestCase {
	
	public $obj1;
	
	function setUp() {
		$this->obj1 = new PntObject();
		
	}

	function testSomething()
	{
		Assert::equals(0, $this->obj1->get('id'), 'comment to recognize assertion from');
	}

/*
    	Assert::Equals('yes', 123, 'assertEquals');
		Assert::notNull(null, 'assertNotNull');
		Assert::null(123, 'assertNull');
		Assert::same('12', 12, 'assertSame');
		Assert::notSame($this->obj1, $this->obj1, 'assertNotSame');
     	Assert::true(false, 'assertTrue');
		Assert::false(true, 'assertFalse');
		Assert::regExp('~.php~', 'myFile.txt', 'assertRegExp');
 */   	
}

return 'Template';
?>
