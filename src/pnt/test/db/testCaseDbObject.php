<?php
// Copyright (c) MetaClass Groningen, 2003-2012

Gen::includeClass('PntTestCase', 'pnt/test');
Gen::includeClass('TestDbObject', 'pnt/test/db');

/** @package pnt/test/db */
class CaseDbObject extends PntTestCase {
	
	public $incremental = true;  	
	public $obj1;
	public $childObj1;
	public $clsDes;
	
	function setUp() {
		$this->clsDes = PntClassDescriptor::getInstance('TestDbObject');
		
		if (!$this->obj1)
			$this->obj1 = new TestDbObject();
	}
	
	function test_CreateTables() {
		//utilize the undocumented order of the TestSuite run to 
		//have this initialization only run once for all the tests
		//in the testcase. 
		
		$qh = TestDBObject::newQueryHandler();
		$qh->query = 'DROP TABLE testdbobjects';
		$qh->_runQuery();

		$mth = 'getCreateTableSql'.$qh->getDbmsName();
		$qh->query = pntCallStaticMethod('CaseDbObject', $mth);
		$qh->runQuery();
		$this->assertNull($qh->getError(),'create table testdbobjects');
	}

	static function getCreateTableSqlmysql() {
		return "CREATE TABLE testdbobjects(
			id int(6) NOT NULL auto_increment,
			clsId varchar(80) default NULL,
			stringField varchar(80) default NULL,
			dateField date NOT NULL default '0000-00-00',
			timestampField datetime NOT NULL default '0000-00-00 00:00',
			doubleField double(7,2) NOT NULL default 0.00,
			booleanField tinyint,
			selectField varchar(20) NULL,
			identifiedOptionId int(6) NULL,
			memoField mediumtext,
			testDbObjectId int(6) NOT NULL default 0,
			anotherDbObjectId int(6) NOT NULL default 0,
			PRIMARY KEY  (id),
			UNIQUE KEY id (id)
			) ENGINE=MyISAM  DEFAULT CHARSET=latin1";
	}

	function getCreateTableSqlsqlite() {
		return "CREATE TABLE testdbobjects(
			id INTEGER NOT NULL PRIMARY KEY,
			clsId varchar(80) default NULL,
			stringField varchar(80) default NULL,
			dateField date NOT NULL default '0000-00-00',
			timestampField datetime NOT NULL default '0000-00-00 00:00',
			doubleField double(7,2) NOT NULL default 0.00,
			memoField text,
			booleanField int(3),
			selectField varchar(20) NULL,
			identifiedOptionId int(6) NULL,
			testDbObjectId int(6) NOT NULL default 0,
			anotherDbObjectId int(6) NOT NULL default 0
			) ENGINE=MyISAM  DEFAULT CHARSET=latin1";
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

	function test_getPropsForCheckOptions() {
		$props = $this->obj1->getPropsForCheckOptions();
		$this->assertEquals(
			explode(' ', $this->obj1->propsForCheckOptions)
			, array_keys($props)
		);
	}

	function test_getLabelSort() {
		$sort = $this->clsDes->getLabelSort();
		$paths = $sort->getSortPaths();
		$this->assertEquals(1, count($paths), 'sortpath count');
		$this->assertEquals('stringField', $paths[0], 'sortpath at 0');
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

		Assert::equals($this->obj1->get('stringField'), $this->obj1->getLabel(), 'label versus stringField');

		$this->obj1->save();
		$this->assertTrue($this->obj1->get('id'), 'obj1 id after save');
		
		$this->retrieveObjAssertEqual($this->obj1, $this->clsDes);
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
	}

	function testSqlDateTimeFormat() {
		$qh = TestDBObject::newQueryHandler();
		$qh->query = "SELECT * FROM testdbobjects where id = 1 ";
		$qh->runQuery();
		$row = $qh->getAssocRow();
		
		$this->assertTrue(is_string($row['timestampField']));
		$this->assertSame( date('Y-m-d H:i:s', time()), $row['timestampField']);
		
//		print is_string($row['timestampField']) ? 'true' : 'false';
//		print " ";
//		print $row['timestampField'];
// true 2004-10-24 23:43:17

		$this->assertTrue(is_string($row['dateField']));
		$this->assertSame( date('Y-m-d', time()), $row['dateField']);
		
//		print is_string($row['dateField']) ? 'true' : 'false';
//		print " ";
//		print $row['dateField'];
// true 2004-10-24


	}

	function test_insertChild() {
		$this->childObj1 = new TestDbObject();
		
		$this->childObj1->set('stringField', 'this is the child');
		$this->childObj1->set('dateField', date('Y-m-d', time()) );
		$this->childObj1->set('timestampField', date('Y-m-d H:i:s', time()) );
		$this->childObj1->set('doubleField', 9999.99);
		$this->childObj1->set('memoField', 'memo of the child' );

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
		
		$this->retrieveObjAssertEqual($this->childObj1, $this->clsDes);
		
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
	
	function test_getValueNoOptionErrorMessage() {
		$prop = $this->clsDes->getPropertyDescriptor('testDbObject');
		$message = $this->childObj1->getValueNoOptionErrorMessage($prop, $this->childObj1->get('testDbObject'));
		Assert::equals("'een andere string 1234567890' is no option for testDbObject", $message, 'childObj1');
	}
	
	function test_checkValueInOptions() {
		$prop = $this->clsDes->getPropertyDescriptor('testDbObject');
		$errs = array();
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(empty($errs), 'empty', array(), $errs, 'obj1>>testDbObject');
		
		$errs = array();
		$this->childObj1->checkValueInOptions($prop, $errs);
		Assert::that(empty($errs), 'empty', array(), $errs, 'childObj1>>testDbObject');

		$prop = $this->clsDes->getPropertyDescriptor('anotherDbObject');
		$errs = array();
		$this->obj1->set('anotherDbObjectId', 98765); //id that is not in the db
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(!empty($errs), 'not empty', array(), $errs, 'obj1>>anotherDbObject');
		Assert::equals('value with id 98765 is no option for anotherDbObject', $errs[0], 'obj1>>anotherDbObject');

		$prop = $this->clsDes->getPropertyDescriptor('booleanField');
		$errs = array();
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(empty($errs), 'empty', array(), $errs, 'obj1>>booleanField null');
		$errs = array();
		$this->obj1->set('booleanField', true);
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(empty($errs), 'empty', array(), $errs, 'obj1>>booleanField true');
		$errs = array();
		$this->obj1->set('booleanField', false);
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(empty($errs), 'empty', array(), $errs, 'obj1>>booleanField false');
		$errs = array();
		$this->obj1->set('booleanField', 2);
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(empty($errs), 'empty', array(), $errs, 'obj1>>booleanField 2');
		//boolean options are not checked, but value validation should fail

		$prop = $this->clsDes->getPropertyDescriptor('selectField');
		$errs = array();
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(empty($errs), 'empty', array(), $errs, 'obj1>>selectField null');
		$errs = array();
		$this->obj1->set('selectField', 'two');
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(empty($errs), 'empty', array(), $errs, 'obj1>>selectField two');
		$errs = array();
		$this->obj1->set('selectField', 'bad');
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(!empty($errs), 'not empty', array(), $errs, 'obj1>>selectField bad');
		Assert::equals("'bad' is no option for selectField", $errs[0], 'obj1>>selectField bad');

		$prop = $this->clsDes->getPropertyDescriptor('identifiedOption');
		$errs = array();
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(empty($errs), 'empty', array(), $errs, 'obj1>>identifiedOption null');
		$errs = array();
		$clsDesc = PntClassDescriptor::getInstance('TestIdentifiedOption');
		$option = $clsDesc->getPeanutWithId(2);
		$this->obj1->set('identifiedOption', $option);
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(empty($errs), 'empty', array(), $errs, 'obj1>>identifiedOption seconds option');
		$errs = array();
		$option = new TestIdentifiedOption(999, 'Bad');
		$this->obj1->set('identifiedOption', $option);
		$this->obj1->checkValueInOptions($prop, $errs);
		Assert::that(!empty($errs), 'not empty', array(), $errs, 'obj1>>identifiedOption bad');
		Assert::equals("value with id 999 is no option for identifiedOption", $errs[0], 'obj1>>identifiedOption bad');

	}

	function testIsIdProperty() {
		$prop = $this->clsDes->getPropertyDescriptor('id');
		$this->assertTrue($prop->isIdProperty(), "id");
		
		$prop = $this->clsDes->getPropertyDescriptor('testDbObjectId');
		$this->assertTrue($prop->isIdProperty(), "testDbObjectId");
		
		$prop = $this->clsDes->getPropertyDescriptor('stringField');
		$this->assertFalse($prop->isIdProperty(), "stringField");
	}
	
	function testCopyFrom() {
		$copy = clone $this->obj1;
		$copy->set('stringField', 'dit is een kopie');
		$copy->copyFrom($this->obj1);
		$copy->children = null; //clear chache
		
		Assert::that($copy->get('id') != $this->obj1->get('id'), '!=', $this->obj1->get('id'), $copy->get('id'), 'ids');
		$cpch = $copy->get('children');
		Assert::equals($this->childObj1->get('stringField'), $cpch[0]->get('stringField'), 'childCopy stringfield');
		Assert::equals($cpch[0]->get('testDbObjectId'), $copy->get('id'), 'childCopy id');
	}

	function testDeleteObj1 () {
		$this->assertTrue($this->obj1->get('id'));
		$this->obj1->delete();
		
		$this->assertNull(
			$this->clsDes->getPeanutWithId('1')
			, 'retrieve obj1 after delete'
		);
		$this->assertNull(
			$this->clsDes->getPeanutWithId($this->childObj1->id)
			, 'retrieve childObj1 after delete'
		);		
	}

	function test_dropTables() {
		//utilize the undocumented order of the TestSuite run to 
		//have this finalization only run once for all the tests
		//in the testcase. 
		$qh = TestDBObject::newQueryHandler();
		$qh->query = 'DROP TABLE testdbobjects'; 
		$qh->_runQuery();
		$this->assertNull($qh->getError(),'drop table testdbobjects');	
	}

	function retrieveObjAssertEqual($obj1, $clsDes) {

		$obj1ClsDes = $obj1->getClassDescriptor();
		unSet($obj1ClsDes->peanutsById[$obj1->get('id')]);
		
		$obj2 = $clsDes->getPeanutWithId($obj1->get('id'));
		Assert::propertiesEqual($obj1, $obj2, 'retrieved object');
	}

}

return 'CaseDbObject';
?>
