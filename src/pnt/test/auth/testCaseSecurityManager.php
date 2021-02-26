<?php
// Copyright (c) MetaClass Groningen, 2003-2012

Gen::includeClass('PntTestCase', 'pnt/test');
Gen::includeClass('TestPropsObject', 'pnt/test/meta');
Gen::includeClass('TestSecurityManager', 'pnt/test/auth');

/** Testcase for testing PntSecurityManager default reasoning.
* Also tests TestSecurityManager.
* @package pnt/test/auth
*/
class CaseSecurityManager extends PntTestCase {
	
	public $obj1;
	
	function setUp() {
		$this->sm = new TestSecurityManager('no baseUrl', 'testTokenSalt');
		$this->obj = new PntObject();
		$this->clsDesObj = PntClassDescriptor::getInstance('PntObject');
		$this->propsObj = new TestPropsObject(1234);
		$this->clsDesPropsObj = PntClassDescriptor::getInstance('TestPropsObject');
		$this->propDes = $this->clsDesPropsObj->getPropertyDescriptor('derived2');
	}

	function testAccessApp() {
		$this->sm->checkAccessApp = 'AccessApp';
		Assert::equals($this->sm->checkAccessApp, $this->sm->checkAccessApp('xxx'), 'string set');
		
		unSet($this->sm->checkAccessApp);
		Assert::null($this->sm->checkAccessApp('xxx'), 'unset');
	}

	function testViewInDomainDir() {
		$this->sm->checkViewInDomainDir = 'ViewInDomainDir';
		Assert::equals($this->sm->checkViewInDomainDir, $this->sm->checkViewInDomainDir('xxx'), 'string set');
		
		unSet($this->sm->checkViewInDomainDir);
		Assert::null($this->sm->checkViewInDomainDir('xxx'), 'unset');
	}

	function testModifyInDomainDir() {
		$this->sm->checkModifyInDomainDir = 'ModifyInDomainDir';
		Assert::equals($this->sm->checkModifyInDomainDir, $this->sm->checkModifyInDomainDir('xxx'), 'string set');
		
		unSet($this->sm->checkModifyInDomainDir);
		$this->sm->checkViewInDomainDir = 'ViewInDomainDir';
		Assert::equals($this->sm->checkViewInDomainDir, $this->sm->checkModifyInDomainDir('xxx'), 'default from checkViewInDomainDir');
	}

	
	function testViewClass() {
		$this->sm->checkViewClass = 'ViewClass';
		Assert::equals($this->sm->checkViewClass, $this->sm->checkViewClass(array($this->obj), $this->clsDesObj), 'string set');

		unSet($this->sm->checkViewClass);
		$this->sm->checkViewInDomainDir = 'ViewInDomainDir';
		Assert::equals($this->sm->checkViewInDomainDir, $this->sm->checkViewClass(array($this->obj), $this->clsDesObj), 'default from checkViewInDomainDir');
	}

	function testModifyClass() {
		$this->sm->checkModifyClass = 'ModifyClass';
		Assert::equals($this->sm->checkModifyClass, $this->sm->checkModifyClass(array($this->obj), $this->clsDesObj), 'string set');

		unSet($this->sm->checkModifyClass);
		$this->sm->checkViewClass = 'ViewClass';
		Assert::equals($this->sm->checkViewClass, $this->sm->checkModifyClass(array($this->obj), $this->clsDesObj), 'default from checkViewClass');

		unSet($this->sm->checkViewClass);
		$this->sm->checkModifyInDomainDir = 'ModifyInDomainDir';
		Assert::equals($this->sm->checkModifyInDomainDir, $this->sm->checkModifyClass(array($this->obj), $this->clsDesObj), 'default from checkModifyInDomainDir');
	}

	function testCreateClass() {
		$this->sm->checkCreateClass = 'CreateClass';
		Assert::equals($this->sm->checkCreateClass, $this->sm->checkCreateClass(array($this->obj), $this->clsDesObj), 'string set');

		unSet($this->sm->checkCreateClass);
		$this->sm->checkModifyClass = 'ModifyClass';
		Assert::equals($this->sm->checkModifyClass, $this->sm->checkCreateClass(array($this->obj), $this->clsDesObj), 'default from checkModifyClass');
	}

	function testEditClass() {
		$this->sm->checkEditClass = 'EditClass';
		Assert::equals($this->sm->checkEditClass, $this->sm->checkEditClass(array($this->obj), $this->clsDesObj), 'string set');

		unSet($this->sm->checkEditClass);
		$this->sm->checkModifyClass = 'ModifyClass';
		Assert::equals($this->sm->checkModifyClass, $this->sm->checkEditClass(array($this->obj), $this->clsDesObj), 'default from checkModifyClass');
	}

	function testDeleteClass() {
		$this->sm->checkDeleteClass = 'DeleteClass';
		Assert::equals($this->sm->checkDeleteClass, $this->sm->checkDeleteClass(array($this->obj), $this->clsDesObj), 'string set');

		unSet($this->sm->checkDeleteClass);
		$this->sm->checkModifyClass = 'ModifyClass';
		Assert::equals($this->sm->checkModifyClass, $this->sm->checkDeleteClass(array($this->obj), $this->clsDesObj), 'default from checkModifyClass');
	}

	function testViewObject() {
		$this->sm->checkViewObject = 'ViewObject';
		Assert::equals($this->sm->checkViewObject, $this->sm->checkViewObject($this->obj, $this->clsDesObj), 'string set');

		unSet($this->sm->checkViewObject);
		$this->sm->checkViewClass = 'ViewClass';
		Assert::equals($this->sm->checkViewClass, $this->sm->checkViewObject($this->obj, $this->clsDesObj), 'default from checkViewClass');
		
		$this->sm->checkViewClass = 'class';
		Assert::equals('PntObject', $this->sm->checkViewObject($this->obj, $this->clsDesPropsObj), 'name of classDescriptor from object');
		Assert::equals('TestPropsObject', $this->sm->checkViewObject($this, $this->clsDesPropsObj), 'name of supplied classDescriptor');
	}

	function testCreateObject() {
		$this->sm->checkCreateObject = 'CreateObject';
		Assert::equals($this->sm->checkCreateObject, $this->sm->checkCreateObject($this->obj, $this->clsDesObj), 'string set');

		unSet($this->sm->checkCreateObject);
		$this->sm->checkViewObject = 'ViewObject';
		Assert::equals($this->sm->checkViewObject, $this->sm->checkCreateObject($this->obj, $this->clsDesObj), 'default from checkViewObject');

		unSet($this->sm->checkViewObject);
		$this->sm->checkCreateClass = 'CreateClass';
		Assert::equals($this->sm->checkCreateClass, $this->sm->checkCreateObject($this->obj, $this->clsDesObj), 'default from checkCreateClass');

		unSet($this->sm->checkCreateClass);
		$this->sm->checkViewClass = 'class';
		Assert::equals('PntObject', $this->sm->checkCreateObject($this->obj, $this->clsDesPropsObj), 'name of classDescriptor from object');
		Assert::equals('TestPropsObject', $this->sm->checkCreateObject($this, $this->clsDesPropsObj), 'name of supplied classDescriptor');
	}

	function testEditObject() {
		$this->sm->checkEditObject = 'EditObject';
		Assert::equals($this->sm->checkEditObject, $this->sm->checkEditObject($this->obj, $this->clsDesObj), 'string set');

		unSet($this->sm->checkEditObject);
		$this->sm->checkViewObject = 'ViewObject';
		Assert::equals($this->sm->checkViewObject, $this->sm->checkEditObject($this->obj, $this->clsDesObj), 'default from checkViewObject');

		unSet($this->sm->checkViewObject);
		$this->sm->checkEditClass = 'EditClass';
		Assert::equals($this->sm->checkEditClass, $this->sm->checkEditObject($this->obj, $this->clsDesObj), 'default from checkEditClass');

		unSet($this->sm->checkCreateClass);
		$this->sm->checkViewClass = 'class';
		Assert::equals('PntObject', $this->sm->checkEditObject($this->obj, $this->clsDesPropsObj), 'name of classDescriptor from object');
		Assert::equals('TestPropsObject', $this->sm->checkEditObject($this, $this->clsDesPropsObj), 'name of supplied classDescriptor');
	}

	function testDeleteObject() {
		$this->sm->checkDeleteObject = 'DeleteObject';
		Assert::equals($this->sm->checkDeleteObject, $this->sm->checkDeleteObject($this->obj, $this->clsDesObj), 'string set');

		unSet($this->sm->checkDeleteObject);
		$this->sm->checkViewObject = 'ViewObject';
		Assert::equals($this->sm->checkViewObject, $this->sm->checkDeleteObject($this->obj, $this->clsDesObj), 'default from checkViewObject');

		unSet($this->sm->checkViewObject);
		$this->sm->checkDeleteClass = 'DeleteClass';
		Assert::equals($this->sm->checkDeleteClass, $this->sm->checkDeleteObject($this->obj, $this->clsDesObj), 'default from checkEditClass');

		unSet($this->sm->checkCreateClass);
		$this->sm->checkViewClass = 'class';
		Assert::equals('PntObject', $this->sm->checkDeleteObject($this->obj, $this->clsDesPropsObj), 'name of classDescriptor from object');
		Assert::equals('TestPropsObject', $this->sm->checkDeleteObject($this, $this->clsDesPropsObj), 'name of supplied classDescriptor');
	}


	function testViewProperty() {
		$this->sm->checkViewProperty = 'ViewProperty';
		Assert::equals($this->sm->checkViewProperty, $this->sm->checkViewProperty($this->propsObj, $this->propDes), 'string set');

		unSet($this->sm->checkViewProperty);
		$this->sm->checkViewClass = 'class';
		Assert::equals('PntObject', $this->sm->checkViewProperty($this->propsObj, $this->propDes), 'name of classDescriptor from object');

		$propDes = $this->clsDesPropsObj->getPropertyDescriptor('label');
		Assert::null($this->sm->checkViewProperty($this->propsObj, $propDes), 'string type property');
	}
	
	function testEditProperty() {
		$this->sm->checkEditProperty = 'EditProperty';
		Assert::equals($this->sm->checkEditProperty, $this->sm->checkEditProperty($this->propsObj, $this->propDes), 'string set');

		unSet($this->sm->checkEditProperty);
		$this->sm->checkViewProperty = 'ViewProperty';
		Assert::equals($this->sm->checkViewProperty, $this->sm->checkEditProperty($this->propsObj, $this->propDes), 'name of classDescriptor from object');
	}

	function testSelectProperty() {
		$this->sm->checkSelectProperty = 'SelectProperty';
		$values = array($this->propsObj->get($this->propDes->getName()));
		Assert::equals($this->sm->checkSelectProperty, $this->sm->checkSelectProperty($values, $this->clsDesPropsObj, 'ignored'), 'string set');

		unSet($this->sm->checkSelectProperty);
		$this->sm->checkViewClass = 'class';
		Assert::equals('TestPropsObject', $this->sm->checkSelectProperty($values, $this->clsDesPropsObj, 'ignored'), 'name of classDescriptor from object');
	}

	function testCheckAccessRef() {
		#TODO
	}
}

?>
