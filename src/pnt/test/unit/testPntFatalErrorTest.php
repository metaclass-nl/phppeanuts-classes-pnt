<?php
// Copyright (c) MetaClass Groningen, 2003-2012

Gen::includeClass('PntTestCase', 'pnt/test');

/** 
 * @package pnt/test/unit
 */
class PntFatalErrorTest extends PntTestCase {

    function testFatalError() {
    	$null = null;
    	$null->doesNotExist();
	}
	
}

return 'PntFatalErrorTest';
?>
