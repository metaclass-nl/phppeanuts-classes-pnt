<?php
// Copyright (c) MetaClass Groningen, 2003-2012

Gen::includeClass('TestDbObject', 'pnt/test/db');
/** Class for testing inheritance and polymorhic retrieval 
* @package pnt/test/db 
*/
class TestDbSub extends TestDbObject {

	public $testDbSubId = 0;
	public $subOnlyStringField;

	public $persistentFieldPropNames = 'id clsId stringField dateField timestampField doubleField memoField booleanField selectField identifiedOptionId testDbObjectId anotherDbObjectId subOnlyStringField testDbSubId';
	public $singleValuePropNames = 'label classDescriptor id oid clsId stringField dateField timestampField doubleField memoField booleanField selectField identifiedOptionId identifiedOption testDbObjectId testDbObject anotherDbObjectId anotherDbObject subOnlyStringField testDbSubId testDbSub';
	public $multiValuePropNames = 'children multiSubs';

	/** @static for testing polymorphic retrieval
	* @return String the name of the database table the instances are stored in
	*/
	static function getTableName() 
	{
		return 'testdbsubs';
	}
	
	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();
		
		$this->addFieldProp('subOnlyStringField', 'string');
		$this->addFieldProp('testDbSubId', 'number');
		$this->addDerivedProp('testDbSub', 'TestDbSub', false); 

		$this->addMultiValueProp('multiSubs', 'TestDbSub');
		
		$prop = $this->getPropertyDescriptor('children');
		$prop->setTwinName('testDbObject'); //necessary because of polymorphism
	}

	/** @static 
	* @param string $itemType itemType for the sort (may be the sort will be for a subclass)
	* @return PntSqlSort that specifies the sql for sorting the instance records by label
	*/
	static function getLabelSort($itemType)
	{
		Gen::includeClass('PntSqlSort', 'pnt/db/query');
		$sort = new PntSqlSort('label', $itemType);
		$sort->addSortSpec('stringField');
		return $sort;
	}
	
	function getLabel() {
		return $this->stringField;
	}
	
}
?>