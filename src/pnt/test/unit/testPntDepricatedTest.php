<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/**
 * @package pnt/test/unit
 */
class PntDepricatedTest extends PntTestCase {

	function testDepricated() {
		//only works in 5.3.0 and above
		split('x', '');
	}
	
	function testUserDepricated() {
		trigger_error('This deprecated event was triggered deliberately', E_USER_DEPRECATED);
	}
	
}

return 'PntDepricatedTest';
?>
