<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/** @package pnt/test/db */
class SqlSortTest extends PntTestCase {
	
	public $dbObjectTest;
	public $clsDes;
	public $obj1;
	public $filter1;
	public $filter2;
	public $filter3;
	public $qh;
	public $sort1;

	function setUp() {
		Gen::includeClass('TestDbSub', 'pnt/test/db');
		global $cfgCommonClassDirs;
		require_once((isSet($cfgCommonClassDirs->pnt) ? $cfgCommonClassDirs->pnt : '../classes'). '/pnt/test/db/testCaseDbPolymorphic.php'); 
		Gen::includeClass('PntSqlJoinFilter', 'pnt/db/query');
		Gen::includeClass('PntSqlSort', 'pnt/db/query');

		$this->dbObjectTest = new CaseDbObject();
		$this->clsDes = PntClassDescriptor::getInstance('TestDbObject');
		$this->obj1 = $this->clsDes->_getPeanutWithId('1');
		
		$this->filter1 = PntSqlFilter::getInstance('TestDbObject', 'testDbObject.doubleField');
		$this->filter2 = PntSqlFilter::getInstance('TestDbObject', 'dateField');
		$this->filter3 = PntSqlFilter::getInstance('TestDbObject', 'anotherDbObject.stringField');
		
		$this->sort1 = new PntSqlSort('testsort1');
		$this->sort1->setFilter($this->filter1);
		$this->sort1->addSortSpecFilter($this->filter2);
		$this->sort1->addSortSpecFilter($this->filter3);
	}

	function testCreateTableAndObjects()
	{
		$this->dbObjectTest->test_CreateTables();
		$this->dbObjectTest->setUp();
		$this->dbObjectTest->test_insert_retrieve();
	}

	function test_getExtraSelectExpressions() {
		$filter4 = PntSqlFilter::getInstance('TestDbObject', 'anotherDbObject.testDbObject.doubleField');
		$this->sort1->addSortSpecFilter($filter4);
		$this->assertEquals(str_replace(["\n", "\r", "\t", "   "], "", "
	, (SELECT AL_1.stringField FROM testdbobjects AL_1 
    WHERE AL_1.id = testdbobjects.anotherDbObjectId  LIMIT 1) AS pntSort1
	, (SELECT AL_3.doubleField FROM testdbobjects AL_2 \n     JOIN testdbobjects AS AL_3 ON AL_2.testDbObjectId = AL_3.id
    WHERE AL_2.id = testdbobjects.anotherDbObjectId  LIMIT 1) AS pntSort2")
			, str_replace(["\n", "\r", "\t", "   "], "", $this->sort1->getExtraSelectExpressions())
			, "getExtraSelectExpressions");
	}
	
	function test_getOrderBySql() {
		$this->sort1->getExtraSelectExpressions();
		$this->assertEquals(
			'testdbobjects.dateField ASC, pntSort1 ASC'
			, $this->sort1->getOrderBySql()
			, "sql ascending");
		$this->filter2->comparatorId = '>';
			$this->assertEquals(
			'testdbobjects.dateField DESC, pntSort1 ASC'
			, $this->sort1->getOrderBySql()
			, "sql filter2 descending");
		$this->filter3->comparatorId = '>';
		$this->assertEquals(
			'testdbobjects.dateField DESC, pntSort1 DESC'
			, $this->sort1->getOrderBySql()
			, "sql filter3 descending");
	}
	
	function test_getSqlForJoin() {
		//depricated; sorting uses extra JOIN
		$this->assertEquals(
			"\n LEFT JOIN testdbobjects AS AL_1 ON testdbobjects.anotherDbObjectId = AL_1.id"
			. "\n LEFT JOIN testdbobjects AS AL_2 ON testdbobjects.testDbObjectId = AL_2.id"
			, $this->sort1->getSqlForJoin()
			, "getSqlForJoin");
	}

	function test_getOrderByJoinedSql() {
		$this->sort1->getSqlForJoin();
		$this->assertEquals(
			'testdbobjects.dateField ASC, AL_1.stringField ASC'
			, $this->sort1->getOrderBySql()
			, "sql ascending");
		$this->filter2->comparatorId = '>';
		$this->assertEquals(
			'testdbobjects.dateField DESC, AL_1.stringField ASC'
			, $this->sort1->getOrderBySql()
			, "sql filter2 descending");
		$this->filter3->comparatorId = '>';
		$this->assertEquals(
			'testdbobjects.dateField DESC, AL_1.stringField DESC'
			, $this->sort1->getOrderBySql()
			, "sql filter3 descending");
	}

	function test_addSortSpec()
	{
		$this->sort1 = new PntSqlSort('test_addSortSpec', 'TestDbObject');
		$this->sort1->addSortSpec('dateField');
		$this->sort1->addSortSpec('anotherDbObject.stringField', 'DESC');
		$sortSpecFilters = $this->sort1->sortSpecFilters;

		$ssf = $sortSpecFilters['dateField'];
		$this->assertEquals(
				'dateField'
				, $ssf->get('label')
				, "addSortSpec 0 label");
		$this->assertEquals(
				'TestDbObject'
				, $ssf->get('itemType')
				, "addSortSpec 0 itemType");
		
		$ssf = $sortSpecFilters['anotherDbObject.stringField'];
		$this->assertEquals(
				'anotherDbObject.stringField'
				, $ssf->get('label')
				, "addSortSpec 1 label");
		$this->assertEquals(
				'TestDbObject'
				, $ssf->get('itemType')
				, "addSortSpec 1 itemType");
	}
	
	function testLabelSort()
	{
		$sort = $this->obj1->getLabelSort('TestDbObject');
		$sortSpecFilters = $sort->sortSpecFilters;
		$ssf = $sortSpecFilters['stringField'];
		$this->assertEquals(
				'stringField'
				, $ssf->get('label')
				, "spec label");
		$this->assertEquals(
				'TestDbObject'
				, $ssf->get('itemType')
				, "spec itemType");
	}

	function testCreateTableAndDbSub()
	{
		$this->dbObjectTest->test_dropTables();
		unSet($this->clsDes->peanutsById[1]);
		
		$dbObjectTest = new CaseDbPolymorphic();
		$dbObjectTest->setUp();
		$dbObjectTest->test_CreateTables();
		$dbObjectTest->test_insert_retrieve();
	}

	function test_dbsub_LookupAndTypes() {
		$this->sort1 = new PntSqlSort('test_dbsub', 'TestDbSub');
		$this->sort1->addSortSpec('dateField');
		$this->sort1->addSortSpec('anotherDbObject.stringField', 'DESC');
		$this->sort1->addSortSpec('subOnlyStringField');
		$this->sort1->addSortSpec('testDbSub.doubleField', 'ASC');
		$sortSpecFilters = $this->sort1->sortSpecFilters;

		$ssf = $sortSpecFilters['dateField'];
		$this->assertEquals(
				'dateField'
				, $ssf->get('label')
				, "addSortSpec 0 label");
		$this->assertEquals(
				'TestDbSub'
				, $ssf->get('itemType')
				, "addSortSpec 0 itemType");
		
		$ssf = $sortSpecFilters['anotherDbObject.stringField'];
		$this->assertEquals(
				'anotherDbObject.stringField'
				, $ssf->get('label')
				, "addSortSpec 1 label");
		$this->assertEquals(
				'TestDbSub'
				, $ssf->get('itemType')
				, "addSortSpec 1 itemType");

		$ssf = $sortSpecFilters['subOnlyStringField'];
		$this->assertEquals(
				'subOnlyStringField'
				, $ssf->get('label')
				, "addSortSpec 2 label");
		$this->assertEquals(
				'TestDbSub'
				, $ssf->get('itemType')
				, "addSortSpec 2 itemType");

		$ssf = $sortSpecFilters['testDbSub.doubleField'];
		$this->assertEquals(
				'testDbSub.doubleField'
				, $ssf->get('label')
				, "addSortSpec 3 label");
		$this->assertEquals(
				'TestDbSub'
				, $ssf->get('itemType')
				, "addSortSpec 3 itemType");
	}

	function test_dbsub_Sql() {
		$this->sort1 = new PntSqlSort('test_dbsub', 'TestDbSub');
		$this->sort1->addSortSpec('subOnlyStringField');
		$this->sort1->addSortSpec('testDbSub.doubleField', 'ASC');
		$this->sort1->addSortSpec('testDbSub.subOnlyStringField', 'ASC');

		$this->assertEquals(str_replace(["\n", "\r"], " ", '
, (SELECT AL_1.doubleField FROM testdbobjects AL_1 
WHERE AL_1.id = testdbsubs.testDbSubId  LIMIT 1) AS pntSort1
, (SELECT AL_2.subOnlyStringField FROM testdbsubs AL_2 
WHERE AL_2.id = testdbsubs.testDbSubId  LIMIT 1) AS pntSort2')
			, str_replace(["\n", "\r", "\t", "   "], " ", $this->sort1->getExtraSelectExpressions())
			, "getExtraSelectExpressions");
		$this->assertEquals(
			'testdbsubs.subOnlyStringField ASC, pntSort1 ASC, pntSort2 ASC'
			, $this->sort1->getOrderBySql()
			, "order by sql");
	}
	
	function test_dbsub_Multitable() {
		//sorting by multi value props is not supported, but let's test the SQL anyway
		$this->sort1 = new PntSqlSort('test_dbsub', 'TestDbSub');
		$this->sort1->addSortSpec('multiSubs.doubleField');
		
		$this->assertEquals(str_replace(["\n", "\r"], " ","
, (SELECT AL_2.doubleField FROM testdbsubs AL_1 \n JOIN testdbobjects AS AL_2 ON AL_1.id = AL_2.id
WHERE AL_1.testDbSubId = testdbsubs.id  LIMIT 1) AS pntSort1")
			, str_replace(["\n", "\r", "\t", "   "], " ", $this->sort1->getExtraSelectExpressions())
			, "getExtraSelectExpressions");
	}	
	
	function test_dropTables() {
		$dbObjectTest = new CaseDbPolymorphic();
		$dbObjectTest->setUp();
		$dbObjectTest->test_dropTables();
	}

}

return 'SqlSortTest';
?>
