<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/**
 * @package pnt/test/unit
 */
 
class PntEStrictTest extends PntTestCase {

	function testEstrict() {
		//we can not use trigger_error to trigger E_STRICT, it results in a warning
		//assign value by reference
		$test =& $this->returnsByValue();
	}
	
	function returnsByValue() {
		return 'a value';
	}
	
}

return 'PntEStrictTest';
?>
