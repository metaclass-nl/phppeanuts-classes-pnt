<?php
// Copyright (c) MetaClass Groningen, 2003-2012

Gen::includeClass('PntDbObject', 'pnt/db');

/** @package pnt/test/db */
class TestDbObject extends PntDbObject {

	public $obj1;
	public $clsDes;
	public $testDbObjectId = 0; // otherwise asserEquals will complain, though usually the behavior will be the same if no initial value is set
	public $anotherDbObjectId = 0;
	public $booleanField;
	public $selectField;
	public $identifiedOptionId;
	public $persistentFieldPropNames = 'id clsId stringField dateField timestampField doubleField memoField booleanField selectField identifiedOptionId testDbObjectId anotherDbObjectId';
	public $singleValuePropNames = 'label classDescriptor id oid clsId stringField dateField timestampField doubleField memoField booleanField selectField identifiedOptionId identifiedOption testDbObjectId testDbObject anotherDbObjectId anotherDbObject';
	public $multiValuePropNames = 'children';
	public $propsForCheckOptions = 'id clsId stringField dateField timestampField doubleField memoField booleanField selectField identifiedOptionId identifiedOption testDbObjectId testDbObject anotherDbObjectId anotherDbObject';
	
	function __construct($id=null)
	{
		parent::__construct($id);
		$this->clsId = $this->getClass();
	}

	/** @static 
	* @return String the name of the database table the instances are stored in
	*/
	static function getTableName() 
	{
		return 'testdbobjects';
	}

	/** Returns the classFolder
	* @static
	* @return String
	*/
	static function getClassDir()
	{
		return 'pnt/test/db';
	}
	
	function initPropertyDescriptors() {
		//activate polymorphism support. Must be done befora any property descriptor is added 
 		$clsDes = $this->getClassDescriptor();
		$clsDes->setPolymorphismPropName('clsId');

		parent::initPropertyDescriptors(); 		//adds 'inherited' propertydescriptors
		
		$this->addFieldProp('clsId', 'string');
		$this->addFieldProp('stringField', 'string');
		$this->addFieldProp('dateField', 'date');
		$this->addFieldProp('timestampField', 'timestamp');
		$this->addFieldProp('doubleField', 'number');
		$this->addFieldProp('memoField', 'string');
		$this->addFieldProp('booleanField', 'boolean');
		$this->addFieldProp('selectField', 'string');
		$this->addFieldProp('identifiedOptionId', 'number');
		$this->addDerivedProp('identifiedOption', 'TestIdentifiedOption', false); //not readOnly
		
		$this->addFieldProp('testDbObjectId', 'string');
		$this->addDerivedProp('testDbObject', 'TestDbObject', false); //not readOnly
		$this->addFieldProp('anotherDbObjectId', 'number');
		$this->addDerivedProp('anotherDbObject', 'TestDbObject', false); 
		
		$prop = $this->addMultiValueProp('children', 'TestDbObject');
		$prop->setOnDelete('v');
	}

	function getSelectFieldOptions() {
		return array('one', 'two', 'three');
	}	
}
?>