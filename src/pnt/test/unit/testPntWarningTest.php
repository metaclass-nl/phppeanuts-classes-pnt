   <?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
   Gen::includeClass('PntTestCase', 'pnt/test');

/**
 * @package pnt/test/unit
 */
class PntWarningTest extends PntTestCase {

	function testWarning()
	{
		trigger_error('This warning was triggered deliberately', E_USER_WARNING);
	}	

}

return 'PntWarningTest';
?>
