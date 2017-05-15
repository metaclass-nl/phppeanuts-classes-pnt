<?php
// Copyright (c) MetaClass Groningen, 2003-2012

Gen::includeClass('PntIdentifiedOption', 'pnt');

/** Class for testing IdentifiedOptions, @see PntIdentifiedOption
 * @package pnt/test/db 
 */
class TestIdentifiedOption extends PntIdentifiedOption {

	static function getInstances() {
		return array(
			1 => new TestIdentifiedOption(1, 'first option')
		,	2 => new TestIdentifiedOption(2, 'second options')
		,	3 => new TestIdentifiedOption(3, 'third options')
		);
	}
}
?>