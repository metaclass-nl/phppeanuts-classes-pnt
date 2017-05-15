<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/** @package pnt/test/meta */
class PntNavigationsTests extends PntTestCase {
	
	public $obj1;
	public $clsDes;
	public $arr;
	
	function setUp() {
		Gen::includeClass('TestPropsObject', 'pnt/test/meta');
		Gen::includeClass('PntObjectNavigation', 'pnt/meta');
		$this->obj1 = new TestPropsObject();
		$this->clsDes = $this->obj1->getClassDescriptor();
		$this->arr['obj1'] = $this->obj1;
		$this->arr[2][1] = 21;
		$this->arr[2][2] = $this->obj1;
	}

	function test_createPntNavigation() {
		
		$nav = PntNavigation::_getInstance('field1', 'Nonsense');
		$this->assertEquals(
			"Nonsense>>field1 unknown itemType"
			, $nav->getLabel()
			, 'Nonsense');
	
		$nav = PntNavigation::_getInstance('field1');
		$this->assertEquals(
			'pntnavigation'
			, strToLower(get_class($nav))
			, 'untyped');

		$nav = PntNavigation::_getInstance('field1', 'Array');
		$this->assertEquals(
			'pntnavigation'
			, strToLower(get_class($nav))
			, 'Array');
		$this->assertEquals(
			'Array'
			, $nav->getItemType()
			, 'Array itemtype');
		$this->assertNull(
			$nav->getResultType()
			, 'field1 getResultType');
			
		
		$nav = PntNavigation::_getInstance('field1', 'PntDao');
		$this->assertEquals(
			'pntnavigation'
			, strToLower(get_class($nav))
			, 'Object');

		$nav = PntNavigation::_getInstance('field1', 'TestPropsObject');
		$this->assertEquals(
			'pntobjectnavigation'
			, strToLower(get_class($nav))
			, 'PntObject');
		$this->assertEquals(
			'number'
			, $nav->getResultType()
			, 'field1 getResultType');

	}

	function test_accessPath() {

		$nav = PntNavigation::_getInstance('step1.step2');
		$this->assertEquals(
			'pntnavigation'
			, strToLower(get_class($nav))
			, 'step1');
		$this->assertEquals(
			'step1'
			, $nav->getKey()
			, 'step1. key');

		$nav2 = $nav->getNext();
		$this->assertEquals(
			'pntnavigation'
			, strToLower(get_class($nav2))
			, 'step2');
		$this->assertEquals(
			'step2'
			, $nav2->getKey()
			, 'step2 key');
		$this->assertEquals(
			'step1.step2'
			, $nav->getPath()
			, 'step1 path');
	}

	function test_pathReflection() {

		$nav = PntNavigation::_getInstance('derived3.field1', 'TestPropsObject');
		$this->assertEquals(
			'pntobjectnavigation'
			, strToLower(get_class($nav))
			, 'derived3');
		$this->assertEquals(
			'derived3'
			, $nav->getKey()
			, 'derived3 key');

		$nav2 = $nav->getNext();
		$this->assertEquals(
			'pntobjectnavigation'
			, strToLower(get_class($nav2))
			, 'field1');
		$this->assertEquals(
			'field1'
			, $nav2->getKey()
			, 'field1 key');
		$this->assertEquals(
			'TestPropsObject'
			, $nav2->getItemType()
			, 'field1 itemtype');

		$this->assertEquals(
			'derived3.field1'
			, $nav->getPath()
			, 'derived3 path');
		$this->assertEquals(
			'number'
			, $nav->getResultType()
			, 'derived3 getResultType');
	}



	function test_getSetField1Value() 
	{
		$value = 123;
		$this->obj1->set('field1', $value);

		$nav = PntNavigation::_getInstance('field1');
		$this->assertSame(
			$value,
			$nav->_step($this->obj1)
			, 'field1 step untyped'
		);
		$this->assertSame(
			$value,
			$nav->_evaluate($this->obj1)
			, 'field1 eval untyped'
		);

		$nav = PntNavigation::_getInstance('field1', 'TestPropsObject');
		$this->assertSame(
			$value,
			$nav->_step($this->obj1)
			, 'field1 step'
		);
		$this->assertSame(
			$value,
			$nav->_evaluate($this->obj1)
			, 'field1 eval'
		);

		$nav = PntNavigation::_getInstance('obj1.field1', 'Array');
		$this->assertSame(
			$this->obj1,
			$nav->_step($this->arr)
			, 'obj1.field1 eval Array'
		);
		$this->assertSame(
			$value,
			$nav->_evaluate($this->arr)
			, 'obj1.field1 eval Array'
		);

	}

	function test_21() {
		$value = 21;
		$nav = PntNavigation::_getInstance('2.1');
		$this->assertSame(
			$this->arr[2]
			, $nav->_step($this->arr)
			, '2.1 step untyped'
		);
		$this->assertSame(
			$value
			, $nav->_evaluate($this->arr)
			, '2.1 eval untyped'
		);
	}

	function test_pntNavigationField2() 
	{
		$nav = PntNavigation::_getInstance('field2');
		$prop = $this->obj1->getPropertyDescriptor('field2');
		$obj2 = new TestPropsObject();
		$prop->_setValue_for( $obj2, $this->obj1 );
		$obj2->undefinedField = 'make obj2 differen';
		$this->assertSame(
			$obj2,
			$nav->_step($this->obj1)
			, 'field2 via step'
		);
		$this->assertSame(
			$obj2,
			$nav->_evaluate($this->obj1)
			, 'field2 via eval'
		);

		$nav = PntNavigation::_getInstance('field2.field2');
		$value = new PntObject();
		$prop->_setValue_for( $value, $obj2 );
		$value->undefinedField = 'make value different';
		$this->assertSame(
			$value,
			$nav->_evaluate($this->obj1)
			, 'field2.field2'
		);
	}


	function test_pntObjectNavigationField2() 
	{
		$nav = PntNavigation::_getInstance('field2', 'TestPropsObject');
		$prop = $this->obj1->getPropertyDescriptor('field2');
		$obj2 = new TestPropsObject();
		$prop->_setValue_for( $obj2, $this->obj1 );
		$obj2->undefinedField = 'make obj2 differen';
		$this->assertSame(
			$obj2,
			$nav->_step($this->obj1)
			, 'field2 via step'
		);
		$this->assertSame(
			$obj2,
			$nav->_evaluate($this->obj1)
			, 'field2 via eval'
		);

		$nav = PntNavigation::_getInstance('field2.field2', 'TestPropsObject');
		$value = new PntObject();
		$prop->_setValue_for( $value, $obj2 );
		$value->undefinedField = 'make value different';
		$this->assertSame(
			$value,
			$nav->_evaluate($this->obj1)
			, 'field2.field2'
		);
	}


}

return 'PntNavigationsTests';
?>
