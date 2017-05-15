<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/** @package pnt/test/db */
class SqlJoinFilterTest extends PntTestCase {
	
	public $dbObjectTest;
	public $clsDes;
	public $obj1;

	function setUp() {
		Gen::includeClass('TestDbSub', 'pnt/test/db');
		global $cfgCommonClassDirs;
		require_once((isSet($cfgCommonClassDirs->pnt) ? $cfgCommonClassDirs->pnt : '../classes'). '/pnt/test/db/testCaseDbPolymorphic.php'); 
		Gen::includeClass('PntSqlJoinFilter', 'pnt/db/query');

		$this->dbObjectTest = new CaseDbObject();
		$this->dbObjectTest->setUp();
		$this->clsDes = PntClassDescriptor::getInstance('TestDbObject');
		$this->obj1 = $this->clsDes->_getPeanutWithId('1');
		$this->filter1 = new PntSqlJoinFilter();
		$this->filter2 = new PntSqlFilter();
		$this->filter1->setNext($this->filter2);
		Gen::includeClass('PntMysqlDao', 'pnt/db/dao');
		$this->qh = new PntMysqlDao();
	}

	function testCreateTableAndObjects() 	{
		$this->dbObjectTest->test_CreateTables();
		$this->dbObjectTest->test_insert_retrieve();
	}
	
	function testWithKey() {
		$this->filter1->set('key', 'testDbObject');
		$this->filter1->set('itemType', 'TestDbObject');
		$this->filter2->set('key', 'doubleField');
		$this->filter2->set('itemType', 'TestDbObject');
		$this->filter2->set('valueType', 'number');

		$this->filter1->set('comparatorId', '=');
		$this->filter1->set('value1', 76543.21);
		
		$this->assertEquals(
			'TestDbObject'
			, $this->filter1->get('itemType')
			, "itemType");
		$this->assertEquals(
			'testDbObject.doubleField'
			, $this->filter1->get('label')
			, "label");
		$this->assertEquals(
			'number'
			, $this->filter1->get('valueType')
			, "valueType");
			
		$this->assertEquals(
			"\n LEFT JOIN testdbobjects AS AL_1 ON testdbobjects.testDbObjectId = AL_1.id"
			, $this->filter1->getSqlForJoin()
			, 'sqlForJoin'
			);
/*		$this->assertEquals(
			"testdbobjects.testDbObjectId IN 
		(SELECT AL_1.id FROM testdbobjects AL_1 WHERE (AL_1.doubleField = ?))"
			, $this->filter1->get('sql')
			, "sql"); */
		$this->assertEquals(
			"(AL_1.doubleField = ?)"
			, $this->filter1->get('sql')
			, "sql");
		$this->filter1->addParamsTo($this->qh);
		$this->assertEquals(
			array(76543.21)
			, $this->qh->parameters
			, 'parameters');
		
		$found = $this->clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::isEmpty($found, 'doubleField = 76543.21 no ref');
		$this->filter1->set('value1', 12345.67);
		$found = $this->clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::isEmpty($found, 'doubleField = 12345.67 no ref');

		$found = $this->clsDes->getPeanutsWith('doubleField', 12345.67);
		$this->dbObjectTest->obj1 = $found[0];
		$this->dbObjectTest->test_insertChild();
		
		$found = $this->clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::notEmpty($found, 'doubleField = 12345.67 with ref');
		$child = $found[0];
		Assert::equals('this is the child', $child->get('stringField'), 'stringField');

		$this->filter1->set('value1', 76543.21);
		$found = $this->clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::isEmpty($found, 'doubleField = 76543.21 with ref');
		
		$child->delete();
	}

	function test_getInstance()
	{
		$this->filter1 = PntSqlFilter::getInstance('TestDbObject', 'testDbObject.doubleField');
		$this->assertNotNull($this->filter1, "getInstance('TestDbObject', 'testDbObject.doubleField')" );

		$this->filter2 = $this->filter1->getNext();
		$this->assertNotNull($this->filter2, "filter1->getNext()" );

		
		$this->assertEquals(
			'testDbObject'
			, $this->filter1->get('key')
			, "key");
		$this->assertEquals(
			'TestDbObject'
			, $this->filter1->get('itemType')
			, "itemType");
		$this->assertEquals(
			'testDbObject.doubleField'
			, $this->filter1->get('label')
			, "label");
		$this->assertEquals(
			'number'
			, $this->filter1->get('valueType')
			, "valueType");

		$this->assertEquals(
			'doubleField'
			, $this->filter2->get('key')
			, "key");
		$this->assertEquals(
			'TestDbObject'
			, $this->filter2->get('itemType')
			, "itemType");

	}

	function testCreateTableAndDbSub()
	{
		$this->dbObjectTest->test_dropTables();
		unSet($this->clsDes->peanutsById[1]);
		
		$dbObjectTest = new CaseDbPolymorphic();
		$dbObjectTest->setUp();
		$dbObjectTest->test_CreateTables();
		$dbObjectTest->test_insert_retrieve();

		$subClsDes = PntClassDescriptor::getInstance('TestDbSub');
		unSet($subClsDes->peanutsById[1]);
		$dbObjectTest->test_insertChild();
	}

	function test_SelectEvaluateSuperclassRelationSingleValue()
	{
		$this->filter1 = PntSqlFilter::getInstance('TestDbSub', 'testDbObject.doubleField');
		$this->assertNotNull($this->filter1, "getInstance('TestDbSub', 'testDbObject.doubleField')" );

		$this->filter2 = $this->filter1->getNext();
		$this->assertNotNull($this->filter2, "filter1->getNext()" );
		$this->filter2->set('comparatorId', '=');
		$value = 1;
		$this->filter2->set('value1', $value);
		$sub = $this->clsDes->_getPeanutWithId(2);
		$super = $sub->get('testDbObject');

		Assert::false($this->filter2->evaluate($super), "super->doubleField=$value"); 
		Assert::false($this->filter1->evaluate($sub), "sub->testDbObject.doubleField=$value"); 
		
		$value = 12345.67;
		$this->filter2->set('value1', $value);
		Assert::true($this->filter1->evaluate($sub), "sub->testDbObject.doubleField=$value"); 
	
	}

	function test_superclassRelationSingleValue() {
		$clsDes = PntClassDescriptor::getInstance('TestDbSub');
		$this->filter1 = PntSqlFilter::getInstance('TestDbSub', 'testDbObject.doubleField');
		$this->assertNotNull($this->filter1, "getInstance('TestDbSub', 'testDbObject.doubleField')" );

		$this->filter2 = $this->filter1->getNext();
		$this->assertNotNull($this->filter2, "filter1->getNext()" );

		
		$this->assertEquals(
			'testDbObject'
			, $this->filter1->get('key')
			, "key");
		$this->assertEquals(
			'TestDbSub'
			, $this->filter1->get('itemType')
			, "itemType");
		$this->assertEquals(
			'testDbObject.doubleField'
			, $this->filter1->get('label')
			, "label");
		$this->assertEquals(
			'number'
			, $this->filter1->get('valueType')
			, "valueType");

		$this->assertEquals(
			'doubleField'
			, $this->filter2->get('key')
			, "key");
		$this->assertEquals(
			'TestDbObject'
			, $this->filter2->get('itemType')
			, "itemType");

		$value = 12345.67;
		$this->filter1->by('=', $value);
		Assert::equals("\n LEFT JOIN testdbobjects AS AL_1 ON testdbobjects.testDbObjectId = AL_1.id"
			, $this->filter1->getSqlForJoin(), 'sqlForJoin');
		Assert::equals("(AL_1.doubleField = ?)"
			, $this->filter1->getSql(), 'sql');
/*		Assert::equals("testdbobjects.testDbObjectId IN 
		(SELECT AL_1.id FROM testdbobjects AL_1 WHERE (AL_1.doubleField = ?))"
			, $this->filter1->getSql(), 'sql'); */
		$this->filter1->addParamsTo($this->qh);
		$this->assertEquals(
			array($value)
			, $this->qh->parameters
			, 'parameters');
			
		$found = $clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::notEmpty($found, 'doubleField = 12345.67 with ref');
		$child = $found[0];
		Assert::equals('this is the child', $child->get('stringField'), 'stringField');
		
		//now with the parents id directly set
		$id = $child->get('testDbObjectId');
		$this->filter1 = PntSqlFilter::getInstance('TestDbSub', 'testDbObject', '=', $id);
		Assert::equals(""
			, $this->filter1->getSqlForJoin(), 'sqlForJoin from parents directly set');
		Assert::equals("(testdbobjects.testDbObjectId = ?)"
			, $this->filter1->getSql(), 'sql from parents directly set');
		$this->qh->clearParams();
		$this->filter1->addParamsTo($this->qh);
		$this->assertEquals(
			array($id)
			, $this->qh->parameters
			, 'parameters');
			
		$found = $clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::notEmpty($found, "testDbObjectId = $id");
		$child = $found[0];
		Assert::equals('this is the child', $child->get('stringField'), 'stringField');
	}

	function test_SelectEvaluateSuperclassRelationMultiValue() {
		$this->filter1 = PntSqlFilter::getInstance('TestDbSub', 'children.doubleField');
		$this->assertNotNull($this->filter1, "getInstance('TestDbSub', 'children.doubleField')" );

		$this->filter2 = $this->filter1->getNext();
		$this->assertNotNull($this->filter2, "filter1->getNext()" );
		$this->filter2->set('comparatorId', '=');
		$value = 1;
		$this->filter2->set('value1', $value);
		$super = $this->clsDes->_getPeanutWithId(1);
		$subs = $super->get('children');

		$result = $this->filter2->evaluate($subs[0]);
		Assert::false($result, '$subs[0]->doubleField='.$value); 
		Assert::false($this->filter1->evaluate($super), 'super->children.doubleField='.$value); 

		$value = 9999.99;
		$this->filter2->set('value1', $value);
		Assert::true($this->filter2->evaluate($subs[0]), '$subs[0]->doubleField='.$value); 
		Assert::true($this->filter1->evaluate($super), 'super->children.doubleField='.$value); 
	}

	function test_superclassRelationMultiValue() {
		$clsDes = PntClassDescriptor::getInstance('TestDbSub');
		$this->filter1 = PntSqlFilter::getInstance('TestDbSub', 'children.doubleField');
		$this->assertNotNull($this->filter1, "getInstance('TestDbSub', 'children.doubleField')" );

		$this->filter2 = $this->filter1->getNext();
		$this->assertNotNull($this->filter2, "filter1->getNext()" );

		$this->assertEquals(
			'children'
			, $this->filter1->get('key')
			, "key");
		$this->assertEquals(
			'TestDbSub'
			, $this->filter1->get('itemType')
			, "itemType");
		$this->assertEquals(
			'children.doubleField'
			, $this->filter1->get('label')
			, "label");
		$this->assertEquals(
			'number'
			, $this->filter1->get('valueType')
			, "valueType");

		$this->assertEquals(
			'doubleField'
			, $this->filter2->get('key')
			, "key");
		$this->assertEquals(
			'TestDbObject'
			, $this->filter2->get('itemType')
			, "itemType");

		$value = 9999.99;
		$this->filter1->by('=', $value);
		Assert::equals("\n LEFT JOIN testdbobjects AS AL_1 ON testdbsubs.id = AL_1.testDbObjectId"
			, $this->filter1->getSqlForJoin(), 'sqlForJoin by doubleField');
		Assert::equals("(AL_1.doubleField = ?)"
			, $this->filter1->getSql(), 'sql by doubleField');
/*		Assert::equals("testdbsubs.id IN 
		(SELECT AL_1.testDbObjectId FROM testdbobjects AL_1 WHERE (AL_1.doubleField = ?))"
			, $this->filter1->getSql(), 'sql by doubleField'); */
		$this->filter1->addParamsTo($this->qh);
		$this->assertEquals(
			array(9999.99)
			, $this->qh->parameters
			, 'parameters by doubleField');
			
		$found = $clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::notEmpty($found, 'doubleField = 9999.99');
		$parent = $found[0];
		Assert::equals('zomaar een String', subStr($parent->get('stringField'),0,17) , 'stringField');

		//now with the childs id directly set
		$children = $parent->get('children');
		$id = $children[0]->get('id');
		$this->filter1 = PntSqlFilter::getInstance('TestDbSub', 'children', '=', $id);
		Assert::equals("\n LEFT JOIN testdbobjects AS AL_1 ON testdbsubs.id = AL_1.testDbObjectId"
			, $this->filter1->getSqlForJoin(), 'sqlForJoin by id');
		Assert::equals("(AL_1.id = ?)"
			, $this->filter1->getSql(), 'sql by children');
/*		Assert::equals("testdbsubs.id IN 
		(SELECT AL_1.testDbObjectId FROM testdbobjects AL_1 WHERE (AL_1.id = ?))"
			, $this->filter1->getSql(), 'sql by id'); */
		$this->qh->clearParams();
		$this->filter1->addParamsTo($this->qh);
		$this->assertEquals(
			array($id)
			, $this->qh->parameters
			, 'parameters by children');
		
		$found = $clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::notEmpty($found, "child id = $id");
		$parent = $found[0];
		Assert::equals('zomaar een String', subStr($parent->get('stringField'),0,17) , 'stringField');
	}

	function test_superclassRelation_subclassfield() {
		$clsDes = PntClassDescriptor::getInstance('TestDbSub');
		$this->filter1 = PntSqlFilter::getInstance('TestDbSub', 'children');
		$this->assertNotNull($this->filter1, "getInstance('TestDbSub', 'children')" );

		$this->assertEquals(
			'children'
			, $this->filter1->get('key')
			, "key");
		$this->assertEquals(
			'TestDbSub'
			, $this->filter1->get('itemType')
			, "itemType");

		//according to the metadata children.subOnlyStringField is not possible, this is a trick 
		//of course the queery will give wrong results if any of the children is a TestDbObject
		$this->filter2 = PntSqlFilter::getInstance('TestDbSub', 'subOnlyStringField');
		$this->assertNotNull($this->filter2, "filter1->getNext()" );

		$this->filter1->setNext($this->filter2);		
		$this->assertEquals(
			'TestDbSub'
			, $this->filter2->get('itemType')
			, "itemType");
		$this->assertEquals(
			'children.subOnlyStringField'
			, $this->filter1->get('label')
			, "label");
		$this->assertEquals(
			'string'
			, $this->filter1->get('valueType')
			, "valueType");
			
		$value = 'child is a TestDbSub';
		$this->filter1->by('=', $value);
		Assert::equals("\n LEFT JOIN testdbobjects AS AL_1 ON testdbsubs.id = AL_1.testDbObjectId"
			."\n LEFT JOIN testdbsubs AS AL_2 ON AL_1.id = AL_2.id"
			, $this->filter1->getSqlForJoin(), 'sqlForJoin by subOnlyStringField');
		Assert::equals("(AL_2.subOnlyStringField = ?)"
			, $this->filter1->getSql(), 'sql by subOnlyStringField');
/*		Assert::equals("testdbsubs.id IN 
		(SELECT AL_1.testDbObjectId FROM testdbobjects AL_1 WHERE AL_1.id IN 
		(SELECT AL_2.id FROM testdbsubs AL_2 WHERE (AL_2.subOnlyStringField = 'child is a TestDbSub')))"
			, $this->filter1->getSql(), 'sql by subOnlyStringField'); */
		$this->filter1->addParamsTo($this->qh);
		$this->assertEquals(
			array($value)
			, $this->qh->parameters
			, 'parameters by subOnlyStringField');
			
		$found = $clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::notEmpty($found, 'doubleField = 9999.99');
		$parent = $found[0];
		Assert::equals('zomaar een String', subStr($parent->get('stringField'),0,17) , 'stringField');
	}
	
	function test_subclassRelation_superclassfield() {
		$clsDes = PntClassDescriptor::getInstance('TestDbSub');
		$child = $clsDes->getPeanutWithId(2);
		$child->set('testDbSubId', $child->get('testDbObjectId'));
		$child->set('testDbObjectId', null);
		$child->save();
		
		$this->filter1 = PntSqlFilter::getInstance('TestDbSub', 'multiSubs.doubleField');
		$this->assertNotNull($this->filter1, "getInstance('TestDbSub', 'multiSubs.doubleField')" );

		$this->filter2 = $this->filter1->getNext();
		$this->assertNotNull($this->filter2, "filter1->getNext()" );
		
		$this->assertEquals(
			'multiSubs'
			, $this->filter1->get('key')
			, "key");
		$this->assertEquals(
			'TestDbSub'
			, $this->filter1->get('itemType')
			, "itemType");
		$this->assertEquals(
			'multiSubs.doubleField'
			, $this->filter1->get('label')
			, "label");
		$this->assertEquals(
			'number'
			, $this->filter1->get('valueType')
			, "valueType");

		$this->assertEquals(
			'doubleField'
			, $this->filter2->get('key')
			, "key");
		$this->assertEquals(
			'TestDbSub'
			, $this->filter2->get('itemType')
			, "itemType");
			
		$value = 9999.99;
		$this->filter1->by('=', $value);
		Assert::equals("\n LEFT JOIN testdbsubs AS AL_1 ON testdbsubs.id = AL_1.testDbSubId"
			. "\n LEFT JOIN testdbobjects AS AL_2 ON AL_1.id = AL_2.id"
			, $this->filter1->getSqlForJoin(), 'sqlForJoin by doubleField');
		Assert::equals("(AL_2.doubleField = ?)"
			, $this->filter1->getSql(), 'sql by doubleField');
/*		Assert::equals("testdbsubs.id IN 
		(SELECT AL_1.testDbSubId FROM testdbsubs AL_1 WHERE AL_1.id IN 
		(SELECT AL_2.id FROM testdbobjects AL_2 WHERE (AL_2.doubleField = ?)))"
			, $this->filter1->getSql(), 'sql by doubleField'); */
		$this->filter1->addParamsTo($this->qh);
		$this->assertEquals(
			array($value)
			, $this->qh->parameters
			, 'parameters by doubleField');
			
		$found = $clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::notEmpty($found, 'doubleField = 9999.99');
		if (!$found) return;
		$parent = $found[0];
		Assert::equals('zomaar een String', subStr($parent->get('stringField'),0,17) , 'stringField');
	}

	function test_subclassRelation_subclassfield() {
		$clsDes = PntClassDescriptor::getInstance('TestDbSub');
		
		$this->filter1 = PntSqlFilter::getInstance('TestDbSub', 'multiSubs.subOnlyStringField');
		$this->assertNotNull($this->filter1, "getInstance('TestDbSub', 'multiSubs.subOnlyStringField')" );

		$this->filter2 = $this->filter1->getNext();
		$this->assertNotNull($this->filter2, "filter1->getNext()" );
		
		$this->assertEquals(
			'multiSubs'
			, $this->filter1->get('key')
			, "key");
		$this->assertEquals(
			'TestDbSub'
			, $this->filter1->get('itemType')
			, "itemType");
		$this->assertEquals(
			'multiSubs.subOnlyStringField'
			, $this->filter1->get('label')
			, "label");
		$this->assertEquals(
			'string'
			, $this->filter1->get('valueType')
			, "valueType");

		$this->assertEquals(
			'subOnlyStringField'
			, $this->filter2->get('key')
			, "key");
		$this->assertEquals(
			'TestDbSub'
			, $this->filter2->get('itemType')
			, "itemType");

		$value = 'child is a TestDbSub';
		$this->filter1->by('=', $value);
		Assert::equals("\n LEFT JOIN testdbsubs AS AL_1 ON testdbsubs.id = AL_1.testDbSubId"
			, $this->filter1->getSqlForJoin(), 'getSqlForJoin by subOnlyStringField');
		Assert::equals("(AL_1.subOnlyStringField = ?)"
			, $this->filter1->getSql(), 'sql by subOnlyStringField');
/*		Assert::equals("testdbsubs.id IN 
		(SELECT AL_1.testDbSubId FROM testdbsubs AL_1 WHERE (AL_1.subOnlyStringField = '$value'))"
			, $this->filter1->getSql(), 'sql by subOnlyStringField'); */
		$this->filter1->addParamsTo($this->qh);
		$this->assertEquals(
			array($value)
			, $this->qh->parameters
			, 'parameters by subOnlyStringField');
			
		$found = $clsDes->getPeanutsAccordingTo($this->filter1);
		Assert::notEmpty($found, 'doubleField = 9999.99');
		$parent = $found[0];
		Assert::equals('zomaar een String', subStr($parent->get('stringField'),0,17) , 'stringField');

/*		$joinsData = array();
		$this->filter1->addJoinTableAndConditionByTableAlias($joinsData);
		$condition = 'testdbsubs.testDbSubId = testDbSubALIAStestdbsubs.id'; 
		$this->assertEquals(
			$condition
			, $this->filter1->getJoinCondition('testDbSubALIAStestdbsubs')
			, 'joinCondition');
		$tableAndCondition = $joinsData['testDbSubALIAStestdbsubs'];
		$this->assertNotNull( $tableAndCondition, 'tableAndCondition');
		$this->assertEquals('testdbsubs',  $tableAndCondition[0], 'joinData tableName');
		$this->assertEquals($condition,  $tableAndCondition[1], 'joinData condition'); */
	}

	function test_getPersistArray_FilterWithPresetTemplate()
	{ 
		$this->filter1 = PntSqlFilter::getInstance('TestDbObject', 'testDbObject.doubleField');
		$this->filter1->set('value1', 76543.21);
		$this->filter1->set('comparatorId', '=');
		$this->filter2 = $this->filter1->getNext();
		$array = $this->filter1->getPersistArray();

		$this->assertEquals('PntSqlJoinFilter', $array['clsId'], 'clsId');
		$this->assertEquals('TestDbObject', $array['itemType'], 'itemType');
		$this->assertEquals('testDbObject', $array['key'], 'key');
		$this->assertEquals('testDbObject', $array['propLabel'], 'propLabel');
		$this->assertEquals('=', $array['comparatorId'], 'comparatorId');
//		$this->assertEquals('string', $array['valueType'], 'valueType'); field not used
		$this->assertEquals(76543.21, $array['value1'], 'value1');
		$this->assertEquals(7, count($array), 'array count');
		
		$nextArray = $array['next'];
		$this->assertEquals('PntSqlFilter', $nextArray['clsId'], 'next clsId');
		$this->assertEquals('TestDbObject', $nextArray['itemType'], 'next itemType');
		$this->assertEquals('doubleField', $nextArray['key'], 'next key');
		$this->assertEquals('doubleField', $nextArray['propLabel'], 'propLabel');
		$this->assertEquals('=', $nextArray['comparatorId'], 'next comparatorId');
		$this->assertEquals('number', $nextArray['valueType'], 'next valueType'); 
		$this->assertEquals(76543.21, $nextArray['value1'], 'next value1');
		$this->assertEquals(7, count($nextArray), 'next array count');
	}

	function test_instanceFromPersistArray()
	{
		$this->filter1 = PntSqlFilter::getInstance('TestDbObject', 'testDbObject.doubleField');
		$this->filter1->set('comparatorId', '=');
		$this->filter1->set('value1', 76543.21);
		$this->filter2 = $this->filter1->getNext();
		$array = $this->filter1->getPersistArray();

		$filterFromArray = PntSqlFilter::instanceFromPersistArray($array);
		Assert::propertiesEqual($this->filter1, $filterFromArray);
	}

	function test_dropTables()
	{
		$dbObjectTest = new CaseDbPolymorphic();
		$dbObjectTest->setUp();
		$dbObjectTest->test_dropTables();
	}

}

return 'SqlJoinFilterTest';
?>
