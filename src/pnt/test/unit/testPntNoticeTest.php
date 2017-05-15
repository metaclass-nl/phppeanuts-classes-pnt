<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/**
 * @package pnt/test/unit
 */
class PntNoticeTest extends PntTestCase {

	function testNotice()
	{
		trigger_error('This notice was triggered deliberately', E_USER_NOTICE);
	}
	
}

return 'PntNoticeTest';
?>
