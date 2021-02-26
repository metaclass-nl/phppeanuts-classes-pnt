<?php
// Copyright (c) MetaClass Groningen, 2003-2012

global $cfgCommonClassDirs;
require_once((isSet($cfgCommonClassDirs->pnt) ? $cfgCommonClassDirs->pnt : '../classes'). '/pnt/test/db/testCaseDbObject.php'); 
Gen::includeClass('TestPropsObject', 'pnt/test/meta');
Gen::includeClass('TestSecurityManager', 'pnt/test/auth');
Gen::includeClass('TestSecurityController', 'pnt/test/auth');
Gen::includeClass('PntFormMtoNRelValue', 'pnt/web/dom');

/** Testcase for testing how RequestHandlers respond to 
* check methods on SecurityManager
* implementation and overrides of PntPage::handleRequest
* must be verified manually. 
* @package pnt/test/auth
*/
class CaseHandlerSecurity extends PntTestCase {
	
	public $obj1;
	
	function setUp() {
		$this->req = array('pntType' => 'TestDbObject');
		$this->sm = new TestSecurityManager('no baseUrl', 'testTokenSalt');
		$this->testContr = new TestSecurityController('pnt/test/db'); //assigning by value gives anomaly in php4
		$this->testContr->controller = $this->testContr;
		$this->testContr->securityManager = $this->sm;
		
		$this->obj = new PntObject();
		$this->clsDesObj = PntClassDescriptor::getInstance('PntObject');
		$this->propsObj = new TestPropsObject(1234);
		$this->clsDesPropsObj = PntClassDescriptor::getInstance('TestPropsObject');
		$this->propDes = $this->clsDesPropsObj->getPropertyDescriptor('derived2');
	}

	function test_CreateTables() {
		$this->caseDbObj = new CaseDbObject();
		$this->caseDbObj->setUp();
		$this->caseDbObj->test_CreateTables();
		$this->caseDbObj->test_insert_retrieve();
	}

	function testPntPage() {
		Gen::includeClass('PntPage', 'pnt/web/pages');
		$handler = new PntPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewClass');
	}

	function testPntErrorPage() {
		Gen::includeClass('PntErrorPage', 'pnt/web/pages');
		$handler = new PntErrorPage($this->testContr, $this->req);

		$this->sm->checkViewClass = 'ViewClass';
		$handler->checkAccess();
		Assert::null($this->testContr->getAdError(), 'checkViewClass');

		$this->sm->checkAccessApp = 'AccessApp';
		$handler->checkAccess();
	}
	function testPntIndexPage() {
		Gen::includeClass('PntIndexPage', 'pnt/web/pages');
		$handler = new PntIndexPage($this->testContr, $this->req);

		$this->sm->checkViewClass = 'ViewClass';
		$handler->checkAccess();
		Assert::null($this->testContr->getAdError(), 'checkViewClass');

		$this->sm->checkAccessApp = 'AccessApp';
		$handler->checkAccess();
		Assert::equals($this->sm->checkAccessApp, $this->testContr->getAdError(), 'checkAccessApp');
	}
	function testPntObjectSortDialog() {
		Gen::includeClass('PntObjectSortDialog', 'pnt/web/dialogs');
		$handler = new PntObjectSortDialog($this->testContr, $this->req);

		$this->sm->checkViewClass = 'ViewClass';
		$handler->checkAccess();
		Assert::null($this->testContr->getAdError(), 'checkViewClass');

		$this->sm->checkAccessApp = 'AccessApp';
		$handler->checkAccess();
		Assert::equals($this->sm->checkAccessApp, $this->testContr->getAdError(), 'checkAccessApp');
	}
	

	function testPntObjectIndexPage() {
		Gen::includeClass('PntObjectIndexPage', 'pnt/web/pages');
		$handler = new PntObjectIndexPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewClass');
	}
	function testPntObjectSearchPage() {
		Gen::includeClass('PntObjectSearchPage', 'pnt/web/pages');
		$handler = new PntObjectSearchPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewClass');
	}
	
	function testPntObjectSelectionReportPage() {
		Gen::includeClass('PntObjectSelectionReportPage', 'pnt/web/pages');
		$handler = new PntObjectSelectionReportPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewClass');
	}
	function PntObjectSelectionDetailsReportPage() {
		Gen::includeClass('PntObjectSelectionDetailsReportPage', 'pnt/web/pages');
		$handler = new PntObjectSelectionDetailsReportPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewClass');
	}
	function testPntHorSelReportPage() {
		Gen::includeClass('PntHorSelReportPage', 'pnt/web/pages');
		$handler = new PntHorSelReportPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewClass');
	}
	
	function testPntObjectDetailsPage() {
		Gen::includeClass('PntObjectDetailsPage', 'pnt/web/pages');
		$handler = new PntObjectDetailsPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewObject');
	}
	function testPntObjectEditDetailsPage() {
		Gen::includeClass('PntObjectEditDetailsPage', 'pnt/web/pages');
		$handler = new PntObjectEditDetailsPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewObject');
	}
	function testPntObjectReportPage() {
		Gen::includeClass('PntObjectReportPage', 'pnt/web/pages');
		$handler = new PntObjectReportPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewObject');
	}
	function testPntObjectEditDetailsDialog() {
		Gen::includeClass('PntObjectEditDetailsDialog', 'pnt/web/dialogs');
		$handler = new PntObjectEditDetailsDialog($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewObject');
	}

	function testPntObjectPropertyPage() {
		$this->req['pntProperty'] = 'testDbObject';
		Gen::includeClass('PntObjectPropertyPage', 'pnt/web/pages');
		$handler = new PntObjectPropertyPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'ViewProperty');
		$this->checkPageAccess($handler, 'ViewObject');
	}

	function testPntObjectMtoNPropertyPage() {
		$this->req['pntProperty'] = 'testDbObject';
		Gen::includeClass('PntObjectMtoNPropertyPage', 'pnt/web/pages');
		$handler = new PntObjectMtoNPropertyPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'EditProperty');
		$this->checkPageAccess($handler, 'ViewObject');
	}

	function testPntObjectMtoNSearchPage() {
		$this->req['pntProperty'] = 'testDbObject';
		Gen::includeClass('PntObjectMtoNSearchPage', 'pnt/web/pages');
		$handler = new PntObjectMtoNSearchPage($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'SelectProperty');
	}
	function testPntObjectDialog() {
		$this->req['pntProperty'] = 'testDbObject';
		Gen::includeClass('PntObjectDialog', 'pnt/web/dialogs');
		$handler = new PntObjectDialog($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'SelectProperty');
	}
	function testPntObjectMtoNDialog() {
		$this->req['pntProperty'] = 'testDbObject';
		Gen::includeClass('PntObjectMtoNDialog', 'pnt/web/dialogs');
		$handler = new PntObjectMtoNDialog($this->testContr, $this->req);

		$this->checkPageAccess($handler, 'SelectProperty');
	}

//TODO: Actions
	function testPntObjectDeleteAction() {
		$this->req['id'] = $this->caseDbObj->obj1->get('id');
		Gen::includeClass('PntObjectDeleteAction', 'pnt/web/actions');
		$handler = new PntObjectDeleteAction($this->testContr, $this->req);

		$this->checkActionAccess($handler, 'DeleteObject');
	}
	function testPntObjectDeleteMarkedAction() {
		Gen::includeClass('PntObjectDeleteMarkedAction', 'pnt/web/actions');
		$handler = new PntObjectDeleteMarkedAction($this->testContr, $this->req);
		$scout = $this->testContr->getScout();
		$fpUris =& $scout->sessionVar('pntFpUris');
		$fpUris = null; //otherwise action may redirect to context
		$this->checkActionAccess($handler, 'DeleteClass');
	}
	function testPntObjectSaveActionCreate() {
		$this->req['id'] = 0;
		$this->addSaveReqValues($this->req);
		Gen::includeClass('PntObjectSaveAction', 'pnt/web/actions');
		$handler = new PntObjectSaveAction($this->testContr, $this->req);

		$this->checkActionAccess($handler, 'CreateObject');
	}
	function addSaveReqValues(&$req) {
		$req['stringField']='';
		$req['dateField']='';
		$req['timestampField']='';
		$req['doubleField']='';
		$req['memoField']='';
		$req['selectField']='';
		$req['identifiedOptionId']='';
		$req['testDbObjectId']='';
		$req['anotherDbObjectId']='';
	}
	function testPntObjectSaveActionUpdate() {
		$this->req['id'] = $this->caseDbObj->obj1->get('id');
		$this->addSaveReqValues($this->req);

		$null = null;
		$formText = new PntFormMtoNRelValue($null, 'TestDbObject', 'testDbObject');
		$this->req[$formText->getFormKey()] = 'bad data';

		Gen::includeClass('PntObjectSaveAction', 'pnt/web/actions');
		$handler = new PntObjectSaveAction($this->testContr, $this->req);

		//check editProperty
		$handler->initialize();
		$handler->formTexts[$formText->getFormKey()] = $formText; //including MtoNRel
		$this->sm->checkEditProperty = 'EditProperty';
		$handler->handleRequest();
		forEach($handler->formTexts as $toCheck)
			Assert::equals($this->sm->checkEditProperty, $toCheck->error, 'EditProperty');
		unSet($this->sm->checkEditProperty);
		$this->checkActionAccess($handler, 'EditObject');
	}

	function test_dropTables() {
		$caseDbObj = new CaseDbObject();
		$caseDbObj->test_dropTables();
	}

	function checkPageAccess($handler, $accessType) {
		$checkFunc = "check$accessType";
		$this->sm->$checkFunc = $accessType;
		$handler->checkAccess();
		Assert::equals($accessType, $this->testContr->getAdError(), $accessType);
		unSet($this->sm->$checkFunc);

		$this->sm->checkAccessApp = 'AccessApp';
		$handler->checkAccess();
		Assert::equals($this->sm->checkAccessApp, $this->testContr->getAdError(), 'checkAccessApp');
		unSet($this->sm->checkAccessApp);
	}
	
	function checkActionAccess($handler, $accessType) {
		$checkFunc = "check$accessType";
		$this->sm->$checkFunc = $accessType;
		$handler->handleRequest();
		Assert::equals(1, count($handler->errors), 'one error');
		Assert::equals($accessType, $handler->errors[0], $accessType);
		unSet($this->sm->$checkFunc);
		
		$this->sm->checkAccessApp = 'AccessApp';
		$handler->handleRequest();
		Assert::equals(2, count($handler->errors), 'one error');
		Assert::equals($this->sm->checkAccessApp, $handler->errors[1], 'checkAccessApp');
		unSet($this->sm->checkAccessApp);
	}
}

?>
