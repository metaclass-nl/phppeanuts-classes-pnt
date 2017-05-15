<?php
// Copyright (c) MetaClass Groningen, 2003-2012

Gen::includeClass('PntObject', 'pnt');

/** @package pnt/test/meta */
class TestPropsObject extends PntObject {

	public $field1;
	
	public $singleValuePropNames = 'label classDescriptor derived1 derived2 derived3 field1 field2 derived3Id id';
	public $multiValuePropNames = 'multi1 multi2';
	public $persistentFieldPropNames = 'field1 derived3Id id';
	public $uiColumnPaths = 'derived1 derived2 derived3 field1 field2';

	function __construct($id=null) {
		parent::__construct();
		$this->id = $id;
	}

	/** Returns the directory of the class file
	* @static
	* @return String 
	*/
	static function getClassDir() 
	{
		return 'pnt/test/meta';
	}

	function initPropertyDescriptors() {
		// only to be called once

		parent::initPropertyDescriptors();
		
		$this->addDerivedProp('derived1', 'email');
		$this->addDerivedProp('derived2', 'PntObject', false, 'lowest', 'highest', 1, 2, 'pnt');
		$this->addDerivedProp('derived3', 'TestPropsObject', false, 'lowest', 'highest', 1, 2);
		
		$this->addFieldProp('field1', 'number');
		$this->addFieldProp('field2', 'TestPropsObject', false, 'lowest', 'highest', 1, 2, null, false);
		$this->addFieldProp('derived3Id', 'number');
		$this->addFieldProp('id', 'number');
		
		$this->addMultiValueProp('multi1', 'string');
		$this->addMultiValueProp('multi2', 'PntObject', false);
		
	}

	function getField1Options()
	{
		return array(1, 2, 4, 8, 16);
	}
	
	function getDerived3Options()
	{
		// keys must be equal to ids
		return array(
			1 => new TestPropsObject(1)
			, 2 => new TestPropsObject(2)
			, 3 => new TestPropsObject(3)
			);
	}

	function getDerived2() {
		return new TestPropsObject(73947);
	}
	
	function getLabel() {
		return $this->field1;
	}
}
?>