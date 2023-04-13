<?php
// Copyright (c) MetaClass Groningen, 2003-2012

 Gen::includeClass('PntTestCase', 'pnt/test');

/** @package pnt/test/db */
class CaseSqlCombiFilter extends PntTestCase {
	
	public $dbObjectTest;
	public $clsDes;
	public $obj1;
	public $filter1;
	public $filter2;
	public $qh;
	public $combiFilter;

	function setUp() {
		Gen::includeClass('TestDbSub', 'pnt/test/db');
		global $cfgCommonClassDirs;
		require_once((isSet($cfgCommonClassDirs->pnt) ? $cfgCommonClassDirs->pnt : '../classes'). '/pnt/test/db/testCaseDbPolymorphic.php'); 
		Gen::includeClass('PntSqlCombiFilter', 'pnt/db/query');

		$this->dbObjectTest = new CaseDbObject();
		$this->clsDes = PntClassDescriptor::getInstance('TestDbObject');
		$this->obj1 = $this->clsDes->_getPeanutWithId('1');

		$this->filter1 = PntSqlFilter::getInstance('TestDbObject', 'doubleField');
		$this->filter1->set('comparatorId', '=');
		$this->filter1->set('value1', 76543.21);
		$this->filter2 = PntSqlFilter::getInstance('TestDbObject', 'stringField');
		$this->filter2->set('comparatorId', '>');
		$this->filter2->set('value1', 'zomaar');
		$this->combiFilter = new PntSqlCombiFilter();
		$this->combiFilter->addPart($this->filter1);
		$this->combiFilter->addPart($this->filter2);
		
		Gen::includeClass('PntMysqlDao', 'pnt/db/dao');
		$this->qh = new PntMysqlDao();
	}

	function testCreateTableAndDbObjects() {
		$this->dbObjectTest->test_CreateTables();
		$this->dbObjectTest->setUp();
		$this->dbObjectTest->test_insert_retrieve();
		$this->dbObjectTest->test_insertChild();
	}
	
	function testCombiFilterParts() {
		$this->assertEquals(
			"(testdbobjects.doubleField = ?)"
			, $this->filter1->getSql()
			, 'sql filter1'
			);
		$this->filter1->addParamsTo($this->qh);
		$this->assertEquals(
			array(76543.21)
			, $this->qh->parameters
			, 'parameters filter1');
		$this->filter1->queryHandler =null; //can not be serialized
		$this->assertSame(
			$this->filter1
			, $this->combiFilter->parts[0]
			, 'part[doubleField] === $this->filter1');

		$this->assertEquals(
			"(testdbobjects.stringField > ?)"
			, $this->filter2->getSql()
			, 'sql filter2'
			);
		$this->qh->clearParams();
		$this->filter2->addParamsTo($this->qh);
		$this->assertEquals(
			array('zomaar')
			, $this->qh->parameters
			, 'parameters filter2');
		$this->filter2->queryHandler =null; //can not be serialized
		$this->assertSame(
			$this->filter2
			, $this->combiFilter->parts[1]
			, 'part[stringField] === $this->filter2');
	}

	function testEvaluate() {
		Assert::false($this->combiFilter->evaluate($this->obj1), "inital");
		$value = 1;
		$this->filter1->set('value1', $value);
		Assert::false($this->combiFilter->evaluate($this->obj1), "obj1->doubleField=$value && obj1>stringField > 'zomaar' "); 
		$value = 12345.67;
		$this->filter1->set('value1', $value);
//		Assert::true($this->filter1->evaluateValue($value), "evaluateValue $value");
		Assert::true($this->filter1->evaluate($this->obj1), "obj1->doubleField=$value && obj1>stringField > 'zomaar' "); 

		$this->combiFilter->set('combinator', 'OR');
		Assert::true($this->combiFilter->evaluate($this->obj1), "obj1->doubleField=$value || obj1>stringField > 'zomaar' "); 
		$value = 1;
		$this->filter1->set('value1', $value);
		Assert::true($this->combiFilter->evaluate($this->obj1), "obj1->doubleField=$value || obj1>stringField > 'zomaar' ");
		$this->filter2->set('comparatorId', '=');
		$this->filter2->comparator = null;
		Assert::false($this->combiFilter->evaluate($this->obj1), "obj1->doubleField=$value || obj1>stringField = 'zomaar' "); 
	}

	function testSqlAndCombinatorVariants() {
		$this->assertEquals(
			"((testdbobjects.doubleField = ?) AND (testdbobjects.stringField > ?))"
			, $this->combiFilter->get('sql')
			, "AND");
		$this->qh->clearParams();
		$this->combiFilter->addParamsTo($this->qh);
		$this->assertEquals(
			array(76543.21, 'zomaar')
			, $this->qh->parameters
			, 'parameters AND');
			
		$this->combiFilter->set('combinator', 'OR');
		$this->assertEquals(
			"((testdbobjects.doubleField = ?) OR (testdbobjects.stringField > ?))"
			, $this->combiFilter->get('sql')
			, "OR");		
		$this->qh->clearParams();
		$this->combiFilter->addParamsTo($this->qh);
		$this->assertEquals(
			array(76543.21, 'zomaar')
			, $this->qh->parameters
			, 'parameters OR');
	}

	//TO DO: test combination of JOIN conditions

	function test_dropTables()
	{
		$this->dbObjectTest->test_dropTables();
	}

}

?>
