<?php
// Copyright (c) MetaClass Groningen, 2003-2012


Gen::includeClass('PntTestCase', 'pnt/test');
global $cfgCommonClassDirs;
require_once((isSet($cfgCommonClassDirs->pnt) ? $cfgCommonClassDirs->pnt : '../classes'). '/pnt/test/db/testCaseDbObject.php'); 
Gen::includeClass('TestDbSub', 'pnt/test/db');

/** @package pnt/test/db */
class CaseDbPolymorphic extends PntTestCase {
	
	public $obj1;
	public $childObj1;
	public $incremental = true;  	
	
	function setUp() {
		$this->clsDes = PntClassDescriptor::getInstance('TestDbSub');
		$this->parentClsDes = PntClassDescriptor::getInstance('TestDbObject');
		$this->caseDbObj = new CaseDbObject();
		
		if (!$this->obj1)
			$this->obj1 = new TestDbSub();

	}
	
	function test_CreateTables() {
		//utilize the undocumented order of the TestSuite run to 
		//have this initialization only run once for all the tests
		//in the testcase. 
		
		$qh = TestDBSub::newQueryHandler();
		$qh->query = 'DROP TABLE testdbsubs';
		$qh->_runQuery();
		
		$this->caseDbObj->test_CreateTables();

		$mth = 'getCreateTableSql'.$qh->getDbmsName();
		$qh->query = pntCallStaticMethod('CaseDbPolymorphic', $mth);
		
		$qh->_runQuery();
		$this->assertNull($qh->getError(),'create table testdbsubs');	
	}

	static function getCreateTableSqlMySQL() {
		return "CREATE TABLE testdbsubs (
			id int(6) NOT NULL,
			subOnlyStringField varchar(40) default NULL,
			testDbSubId int(6) NOT NULL default 0,
			PRIMARY KEY  (id),
			UNIQUE KEY id (id)
			)  ENGINE=MyISAM  DEFAULT CHARSET=latin1";
	}

	static function getCreateTableSqlSQLite() {
		return "CREATE TABLE testdbsubs (
			id int(8) NOT NULL PRIMARY KEY,
			subOnlyStringField varchar(40) default NULL,
			testDbSubId int(6) NOT NULL default 0
			) ";
	}

	function test_getSingleValuePropertyDescriptors() {
		$props = $this->clsDes->getSingleValuePropertyDescriptors();
		$this->assertEquals(
			explode(' ', $this->obj1->singleValuePropNames)
			, array_keys($props)
		);
	}

	function test_getMultiValuePropertyDescriptors() {
		$props = $this->clsDes->getMultiValuePropertyDescriptors();
		$nameString = $this->obj1->multiValuePropNames;
		$this->assertEquals(
			($nameString ? explode(' ', $nameString) : array())
			, array_keys($props)
		);
	}

	function test_getPersistentFieldPropertyDescriptors() {
		$props = $this->clsDes->getPersistentFieldPropertyDescriptors();
		$this->assertEquals(
			explode(' ', $this->obj1->persistentFieldPropNames)
			, array_keys($props)
		);
	}

	function test_insert_retrieve() {
		// setUp sets id to 1 to retrieve obj1 if it has been saved.
		// right now we need to insert obj1 and therefore we need id to be 0
		$this->obj1->set('id', 0);
		
		$this->obj1->set('stringField', 'zomaar een String ~!@#$%^&*()_+-={}[]:;"<,>.?/|\~`'."'");
		//date and timestamp are actually represented as strings
		$this->obj1->set('dateField', date('Y-m-d', time()) );
		$this->obj1->set('timestampField', date('Y-m-d H:i:s', time()) );
		$this->obj1->set('doubleField', 12345.67);
		$this->obj1->set('memoField', 'memo' );

		$this->obj1->save();
		$this->assertTrue($this->obj1->get('id'), 'obj1 id after save');
		
		$this->retrieveObjAssertEqual($this->obj1, $this->clsDes);
		$this->retrieveObjAssertEqual($this->obj1, $this->parentClsDes);
	}
	
	function test_update_retrieve() {
		$this->obj1->set('stringField', 'een andere string 1234567890');
		$this->obj1->set('dateField', date('Y-m-d', time()) );
		$this->obj1->set('timestampField', date('Y-m-d H:i:s', time()) );
		$this->obj1->set('doubleField', 76543.21);
		$this->obj1->set('memoField', 'Websites met Nanotubes Content Management Systeem
Dit product bestaat wederom uit een vormgeving, echter dit maal kan de inhoud van de website op zeer eenvoudige wijze door u zelf geschreven en gewijzigd worden.
Het grote voordeel hiervan is dat u kosten bespaart wanneer u de website wilt wijzigen. U kunt het namelijk nu zelf doen, er hoeft niet meer iemand met kennis van HTML, het internetformaat, ingehuurd te worden om de teksten aan te passen.
Bij de website wordt het door Metaclass ontwikkelde unieke Nanotubes content management systeem geleverd. Dit is een afgeschermd deel van de site dat geschreven is om uw site te beheren.
Het Nanotubes content management systeem is ook leverbaar met een WYSIWYG- (what you see is what you get) editor. Dit is een klein tekstverwerkertje dat vrijwel gelijk aan de standaard Windows tekstverwerkers werkt. Dit maakt het bewerken van teksten op de website voor de ondernemer een vertrouwde bezigheid.
Klik hier voor een demonstratie van het CMS.');

		$this->obj1->save();

		$this->assertNull($this->obj1->get('testDbObject'), 'obj1 testDbObject');
		
		$this->retrieveObjAssertEqual($this->obj1, $this->clsDes);
		$this->retrieveObjAssertEqual($this->obj1, $this->parentClsDes);
	}

	function test_insertChild() {
		$this->childObj1 = new TestDbSub();
		
		$this->childObj1->set('stringField', 'this is the child');
		$this->childObj1->set('dateField', date('Y-m-d', time()) );
		$this->childObj1->set('timestampField', date('Y-m-d H:i:s', time()) );
		$this->childObj1->set('doubleField', 9999.99);
		$this->childObj1->set('memoField', 'memo of the child' );
		$this->childObj1->set('subOnlyStringField', 'child is a TestDbSub');

		//print "<BR>$this->obj1->get('id') ".$this->obj1->get('id');
		
		$this->childObj1->set('testDbObject', $this->obj1);
		$this->assertEquals(
			$this->obj1->get('id')
			, $this->childObj1->get('testDbObjectId')
			, 'testDbObjectId before save'
			);

		$this->childObj1->save();
		$this->assertTrue($this->childObj1->get('id'), 'childObj1 id after save');
		
		$this->obj1->children = null; //reset children cache on obj1

//		$this->retrieveObjAssertEqual($this->childObj1, $this->clsDes);
		$this->retrieveObjAssertEqual($this->childObj1, $this->parentClsDes);
		
		$children = $this->obj1->get('children');
		reset($children);
		$this->assertTrue(
			$this->childObj1 == $children[key($children)] //assertEquals too strict
			, 'childObj1 == child'
			);
		$this->assertFalse(
			$this->obj1 == $children[key($children)] 
			, 'obj1 == child'
			);
		
	}
	
	function testIsIdProperty()
	{
		$prop = $this->clsDes->getPropertyDescriptor('id');
		$this->assertTrue($prop->isIdProperty(), "id");
		
		$prop = $this->clsDes->getPropertyDescriptor('testDbObjectId');
		$this->assertTrue($prop->isIdProperty(), "testDbObjectId");
		
		$prop = $this->clsDes->getPropertyDescriptor('stringField');
		$this->assertFalse($prop->isIdProperty(), "stringField");
	}

	function testDeleteObj1 () {
		$this->assertTrue($this->obj1->get('id'));
		$this->obj1->delete();
		
		$this->assertNull(
			$this->clsDes->_getPeanutWithId('1')
			, 'retrieve after delete'
		);
	}

	function test_dropTables() {
		//utilize the undocumented order of the TestSuite run to 
		//have this finalization only run once for all the tests
		//in the testcase. 
		$this->caseDbObj->test_dropTables();
		
		$qh = TestDBSub::newQueryHandler();
		$qh->query = 'DROP TABLE testdbsubs'; 
		$qh->_runQuery();
		$this->assertNull($qh->getError(),'drop table testdbsubs');	
	}

	function retrieveObjAssertEqual($obj1, $clsDes) {

		$obj1ClsDes = $obj1->getClassDescriptor();
		unSet($obj1ClsDes->peanutsById[$obj1->get('id')]);
		
		$obj2 = $clsDes->_getPeanutWithId($obj1->get('id'));
		Assert::propertiesEqual($obj1, $obj2, 'retrieved object');
	}

}

return 'CaseDbPolymorphic';
?>
