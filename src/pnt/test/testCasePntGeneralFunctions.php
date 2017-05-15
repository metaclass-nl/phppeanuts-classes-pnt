<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/** @package pnt/test */
class CasePntGeneralFunctions extends PntTestCase {
	
	public $obj1;
	
	function setUp() {
	}

	function test_array_diff_key() {
		$original = array(1, 'two', 'threeKey' => 'three');
		$reference = array(1 => 'xxx', 'threekey' => 'yyy');
		Assert::equals(
			array_diff_key($original, $reference),
			array(0 => 1, 'threeKey' => 'three'), 
			'second numeric key and wrong case third key gives second numeric key only ');
		$original = array(1, 'two', 'three');
		$reference = array(1 => 'xxx', 3=>'yyy');
		Assert::equals(
			array_diff_key($original, $reference),
			array(0 => 1, 2 => 'three'), 
			'last numeric key only');
			
	}

	function test_is_subclassOr() {
		Assert::true(
			is_subclassOr('PntTestCase', 'PntTestCase'),
			'is: PntTestCase subclassOr: PntTestCase'
		);
		Assert::true(
			is_subclassOr('CasePntGeneralFunctions', 'PntTestCase'),
			'is: CasePntGeneralFunctions subclassOr: PntTestCase'
		);
		Assert::false(
			is_subclassOr('PntTestCase', 'CasePntGeneralFunctions'),
			'is: PntTestCase subclassOr: CasePntGeneralFunctions'
		);
		Assert::false(
			is_subclassOr('NoClass', 'PntTestCase'),
			'is: NoClass subclassOr: PntTestCase'
		);
		Assert::false(
			is_subclassOr('PntTestCase', 'NoClass'),
			'is: PntTestCase subclassOr: NoClass'
		);
	}

/*
    	Assert::equals('yes', 123, 'assertEquals');
		Assert::notNull(null, 'assertNotNull');
		Assert::null(123, 'assertNull');
		Assert::same('12', 12, 'assertSame');
		Assert::notSame($this->obj1, $this->obj1, 'assertNotSame');
     	Assert::true(false, 'assertTrue');
		Assert::false(true, 'assertFalse');
		Assert::regExp('~.php~', 'myFile.txt', 'assertRegExp');
 */   	
}

return 'CasePntGeneralFunctions';
?>
