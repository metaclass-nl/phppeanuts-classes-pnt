<?php
// Copyright (c) MetaClass Groningen, 2003-2012

Gen::includeClass('Assert');
Gen::includeClass('PntTest', 'pnt/test');

/**
 * @package pntUnit
 */
class PntTestCase {
	
	public $tests;
	public $incremental = false;  //compatible with sUnit, PhpUnit etc.
	public $class;
	public $filePath;
	
	function __construct() {
		$this->initTests();
	}
	
	function isIncremental()
	{
		return $this->incremental;
	}
	
	function abortIncrementalOnError()
	{
		return true;
	}

	function getClass()
	{
		if ($this->class) return $this->class;
		
		return get_class($this);
	}
	
	function setClass($name)
	{
		$this->class = $name;
	}

	function __toString() {
		return $this->getClass();
	}
	
	/** @depricated */
	function toString() {
		return (string) $this;
	}

	function getFilePath()
	{
		return $this->filePath;
	}
	
	function setFilePath($string)
	{
		$this->filePath = $string;
	}

	function initTests()
	{
		$this->tests = array();
		$methods = get_class_methods(get_class($this));

		foreach ($methods as $methodName)
			if (strPos($methodName, 'test') === 0 && $methodName != get_class($this))
				$this->addNewTestFor($methodName);
	}
	
	function addNewTestFor($methodName)
	{
		$this->tests[] = new PntTest($this, $methodName);
	}

	function getTests() {
		return $this->tests;
	}
	
// PHPUnit compatible interface (semantics are somewhat different!)	
	
	function setUp()
	{
		
	}
	
	function tearDown()
	{
		
	}
	
	/** Check the equality of $reference and $toCheck.
	* if $precision specified, signalAssertionFailure if 
	*   abs(difference) > $precision
	* otherwise, signalAssertionFailure if not equal.
	* if $precision specified, assertNumeric $reference and $toCheck
	* @param mixed $reference value known to be correct
	* @param mixed $toCheck value to check
	* @param string $label To recognise the asstertion from
	* @param float @precision maximum difference for numeric values
	*/
	function assertEquals($reference, $toCheck, $label = null, $precision = null) 
	{
		Assert::equals($reference, $toCheck, $label, $precision);
	}
	
	function assertSame($reference, $toCheck, $label = null)
	{
		Assert::same($reference, $toCheck, $label);
	}
		
	function assertNotSame($reference, $toCheck, $label = null)
	{
		Assert::notSame($reference, $toCheck, $label);
	}
	
	function assertNull($toCheck, $label = null)
	{
		Assert::null($toCheck, $label);
	}
	
	function assertNotNull($toCheck, $label = null)
	{
		Assert::notNull($toCheck, $label);
	}
	
	function assertTrue($toCheck, $label = null)
	{
		Assert::true($toCheck, $label);
	}
	
	function assertFalse($toCheck, $label = null)
	{
		Assert::false($toCheck, $label);
	}
	
	function assertRegExp($expression, $toCheck, $label = null)
	{
		Assert::preg_match($expression, $toCheck, $label);
	}

}
?>