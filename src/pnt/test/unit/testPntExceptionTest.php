<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');
Gen::includeClass('PntError', 'pnt');

/**
 * @package pnt/test/unit
 */
class PntExceptionTest extends PntTestCase {

	function testException() {
		throw new Exception('this exception was thrown deliberately', 123);
	}
	
	function testPntException() {
		try {
			throw new Exception('wrapped exception', 456);
		} catch (Exception $prev) {
			throw new PntError('wrapping exception', 789, $prev);			
		}
	}
	
	function testDebugTraceNotStored() {
		PntError::storeDebugTrace(false);
		$exc = new PntError();
		Assert::false(isSet($exc->debugTrace), 'field debugTrace');
		$trace = $exc->getDebugTrace();
		Assert::true(isSet($trace[0]['class']), 'debugtrace class set');
		Assert::false(isSet($trace[0]['object']), 'debugtrace object not set');
		Assert::null($exc->getOrigin(), 'exception origin');
	}
	
	function testDebugTraceStored() {
		PntError::storeDebugTrace(true);
		Assert::true(PntError::storeDebugTrace(), 'storeDebugTrace true');
		$exc = new PntError();
		Assert::true(isSet($exc->debugTrace), 'field debugTrace isSet');
		$trace = $exc->getDebugTrace();
		Assert::true(isSet($trace[1]['class']), 'debugtrace class set');
		Assert::equals($this, $trace[1]['object'], 'debugtrace object');
		Assert::equals($this, $exc->getOrigin(), 'exception origin');
	}
}

return 'PntExceptionTest';
?>
