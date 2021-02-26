<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/**
 * @package pnt/test/unit
 */
class PntDepricatedTest extends PntTestCase {

	function testDepricated() {
		//only works in 7.2-4
		$this->assertTrue(function_exists('each'), 'function each required for this test');
		if (!function_exists('each')) return;

        $arr = [1];
		each($arr);

	}
	
	function testUserDepricated() {
		trigger_error('This deprecated event was triggered deliberately', E_USER_DEPRECATED);
	}
	
}

return 'PntDepricatedTest';
?>
