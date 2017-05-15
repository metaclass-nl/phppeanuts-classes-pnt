<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/**
 * @package pnt/test/unit
 */
class PntErrorTest extends PntTestCase {

    function testError() {
    	trigger_error('This error was triggered deliberately', E_USER_ERROR);
	}
	
}

return 'PntErrorTest';
?>
