<?php
// Copyright (c) MetaClass, 2003-2012

/**
 * @package pnt/test
 * @author  Henk Verhoeven, MetaClass <henk@phpPeanuts.org>
 */
class PntTest {
	
	public $tstCase;
	public $methodName;
	
	function __construct($testCase, $methodName)
	{
		$this->tstCase = $testCase;
		$this->methodName = $methodName;
	}
	
	function getCase()
	{
		return $this->tstCase;
	}
	
	function getMethodName()
	{
		return $this->methodName;
	}
	
	function execute()
	{
		$mth = $this->methodName;
		$this->tstCase->$mth();
	}
}
?>