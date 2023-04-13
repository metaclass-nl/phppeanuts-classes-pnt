<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/** @package pnt/test/meta */
class PntPropertyDescriptorsTest extends PntTestCase {
	
	public $obj1;
	public $clsDes;
	
	function setUp() {
		Gen::includeClass('TestPropsObject', 'pnt/test/meta');
		$this->obj1 = new TestPropsObject();
		$this->clsDes = $this->obj1->getClassDescriptor();
	}

	function test_getPropertyDescriptors() {

		$this->assertTrue(
			$this->clsDes->getPropertyDescriptors()
		);		
	}

	function test_getPropertyDescriptor() {
		$prop = $this->obj1->getPropertyDescriptor('derived1');
		$this->assertNotNull($prop, 'derived1');
		
		$undefined = $this->clsDes->getPropertyDescriptor('undefined');
		$this->assertNull(
			$undefined
			, 'undefined');
		
		$prop->label = 'not empty';
		$this->assertSame(
			$prop
			, $this->clsDes->getPropertyDescriptor('derived1')
		);
		
	}

	function test_hasPropertyDescriptor() {
		$this->assertTrue(
			$this->clsDes->hasPropertyDescriptor('derived1')
			, 'derived1'
		);
		$this->assertFalse(
			$this->clsDes->hasPropertyDescriptor('undefined')
			, 'undefined'
		);
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
		$this->assertEquals(
			explode(' ', $this->obj1->multiValuePropNames)
			, array_keys($props)
		);
	}

	function test_getUiColumnPaths() {
		$paths = $this->clsDes->getUiColumnPaths();
		$this->assertEquals(
			explode(' ', $this->obj1->uiColumnPaths)
			, $paths
		);
	}


	function testDerived1PropProps() {
		
		$prop = $this->clsDes->getPropertyDescriptor('derived1');
		$this->assertEquals('derived1', $prop->getName(), 'name');
		$this->assertEquals('email', $prop->getType(), 'type');
		$this->assertTrue($prop->getReadOnly(), 'readOnly');
		$this->assertNull($prop->getMinValue(), 'minValue');
		$this->assertNull($prop->getMaxValue(), 'maxValue');
		$this->assertSame(0, $prop->getMinLength(), 'minLength');
		$this->assertNull($prop->getMaxLength(), 'maxLength');
		$this->assertFalse($prop->getPersistent(), 'persistent');
		
		$this->assertTrue($prop->isDerived(), 'isDerived');
		$this->assertFalse($prop->isFieldProperty(), 'isFieldProperty');
		$this->assertFalse($prop->isMultiValue(), 'isMultiValue');
		$this->assertEquals('pnt/test/meta', $prop->getClassDir(), 'classDir');

	}
	
	function testDerived2PropProps() {
		
		$prop = $this->clsDes->getPropertyDescriptor('derived2');
		$this->assertEquals('derived2', $prop->getName(), 'name');
		$this->assertEquals('PntObject', $prop->getType(), 'type');
		$this->assertFalse($prop->getReadOnly(), 'readOnly');
		$this->assertSame('lowest', $prop->getMinValue(), 'minValue');
		$this->assertSame('highest', $prop->getMaxValue(), 'maxValue');
		$this->assertSame(1, $prop->getMinLength(), 'minLength');
		$this->assertSame(2, $prop->getMaxLength(), 'maxLength');
		$this->assertFalse($prop->getPersistent(), 'persistent');
		$this->assertEquals('pnt', $prop->getClassDir(), 'classDir');

	}

	function testFieldProp1Props() {
		
		$prop = $this->clsDes->getPropertyDescriptor('field1');
		$this->assertEquals('field1', $prop->getName(), 'name');
		$this->assertEquals('number', $prop->getType(), 'type');
		$this->assertFalse($prop->getReadOnly(), 'readOnly');
		$this->assertNull($prop->getMinValue(), 'minValue');
		$this->assertNull($prop->getMaxValue(), 'maxValue');
		$this->assertSame(0, $prop->getMinLength(), 'minLength');
		$this->assertNull($prop->getMaxLength(), 'maxLength');
		$this->assertTrue($prop->getPersistent(), 'persistent');

		$this->assertFalse($prop->isDerived(), 'isDerived');
		$this->assertTrue($prop->isFieldProperty(), 'isFieldProperty');
		$this->assertFalse($prop->isMultiValue(), 'isMultiValue');

	}

	function testFieldProp1DepricatedSupport() {
		$prop = $this->clsDes->getPropertyDescriptor('field1');
		$arr = $prop->getFieldProperties();
		$this->assertTrue(is_array($arr), 'is array');
		$this->assertEquals('number', $arr['type'], 'type');
		$this->assertFalse($arr['readOnly'], 'readOnly');
		$this->assertSame('', $arr['minValue'], 'minValue');
		$this->assertSame('', $arr['maxValue'], 'maxValue');
		$this->assertSame(0, $arr['minLength'], 'minLength');
		$this->assertSame('', $arr['maxLength'], 'maxLength');
		
	}

	function testFieldProp2Props() {
		
		$prop = $this->clsDes->getPropertyDescriptor('field2');
		$this->assertEquals('field2', $prop->getName(), 'name');
		$this->assertEquals('TestPropsObject', $prop->getType(), 'type');
		$this->assertFalse($prop->getReadOnly(), 'readOnly');
		$this->assertSame('lowest', $prop->getMinValue(), 'minValue');
		$this->assertSame('highest', $prop->getMaxValue(), 'maxValue');
		$this->assertSame(1, $prop->getMinLength(), 'minLength');
		$this->assertSame(2, $prop->getMaxLength(), 'maxLength');
		$this->assertFalse($prop->getPersistent(), 'persistent');
	}

	function testFieldProp2DepricatedSupport() {
		$prop = $this->clsDes->getPropertyDescriptor('field2');
		$arr = $prop->getFieldProperties();
		$this->assertTrue(is_array($arr), 'is array');
		$this->assertEquals('TestPropsObject', $arr['type'], 'type');
		$this->assertFalse($arr['readOnly'], 'readOnly');
		$this->assertSame('lowest', $arr['minValue'], 'minValue');
		$this->assertSame('highest', $arr['maxValue'], 'maxValue');
		$this->assertSame(1, $arr['minLength'], 'minLength');
		$this->assertSame(2, $arr['maxLength'], 'maxLength');
		
	}

	function testMultiValuePropProps() {
		
		$prop = $this->clsDes->getPropertyDescriptor('multi1');
		$this->assertEquals('multi1', $prop->getName(), 'name');
		$this->assertEquals('string', $prop->getType(), 'type');
		$this->assertTrue($prop->getReadOnly(), 'readOnly');
		$this->assertNull($prop->getMinValue(), 'minValue');
		$this->assertNull($prop->getMaxValue(), 'maxValue');
		$this->assertSame(0, $prop->getMinLength(), 'minLength');
		$this->assertNull($prop->getMaxLength(), 'maxLength');
		$this->assertFalse($prop->getPersistent(), 'persistent');

		$this->assertTrue($prop->isDerived(), 'isDerived');
		$this->assertFalse($prop->isFieldProperty(), 'isFieldProperty');
		$this->assertTrue($prop->isMultiValue(), 'isMultiValue');

	}

	function test_getSetField1_2Value() 
	{
		$value = 123;
		$this->obj1->set('field1', $value);
		$this->assertSame(
			$value,
			$this->obj1->get('field1')
			, 'field1'
		);

		$prop = $this->obj1->getPropertyDescriptor('field2');
		$value = new PntObject();
		$prop->_setValue_for( $value, $this->obj1 );
		$value->undefinedField = 'make the original different from eventual copy';
		$this->assertSame(
			$value,
			$prop->_getValueFor($this->obj1)
			, 'field2 via prop'
		);


		$value = new PntObject();
		$this->obj1->set('field2', $value); // copies the value
		$this->assertEquals(
			$value,
			$this->obj1->get('field2')
			, 'field2 via obj'
		);
	}
	
	function testField1Options()
	{
		$prop = $this->clsDes->getPropertyDescriptor('field1');
		$this->assertEquals(
			$this->obj1->getField1Options()
			, $prop->_getOptionsFor($this->obj1)
		);

	}

	function test_getSetDerived1Value() {
		$prop = $this->obj1->getPropertyDescriptor('derived1');

		$result = $prop->_getValueFor($this->obj1 );
		// assume PntError
		$this->assertEquals(
			'TestPropsObject>>derived1 unable to derive value: no getter and type is not a class'
			, $result->getlabel()
			, 'derived1 getValueFor error message'
		);

		$value = 'indifferent@somedomain.nl';
		$result = $prop->_setValue_for( $value, $this->obj1 );
		// assume PntError
		$this->assertEquals(
			'TestPropsObject>>derived1 unable to propagate value: no setter and type is not a class'
			, $result->getlabel()
			, 'derived1 setValue_for error message'
		);
	}
	
	function test_getSetDerived2Value() 
	{
		$prop = $this->obj1->getPropertyDescriptor('derived2');

		$result = $prop->_getValueFor($this->obj1);
		// assume PntError
		$this->assertEquals(
			new TestPropsObject(73947)
			, $result
			, 'derived2 getValueFor result'
		);

		$value = new PntObject();
		$result = $prop->_setValue_for( $value, $this->obj1 );
		// assume PntError
		$this->assertEquals(
			'TestPropsObject>>derived2 Unable to propagate value: no setter and no id-property'
			, $result->getlabel()
			, 'derived2 setValue_for error message'
		);
	}

	function test_getSetDerived3Value() 
	{
		$prop = $this->obj1->getPropertyDescriptor('derived3');

		$value = new PntObject();
		$result = $prop->_setValue_for( $value, $this->obj1 );
		// assume PntError
		$this->assertEquals(
			'TestPropsObject>>derived3 Unable to propagate value: no setter and value has no id-property'
			, $result->getlabel()
			, 'derived3 setValue_for error message'
		);
		
		$value = new TestPropsObject();
		$valueId = 3456;
		$value->set('id', $valueId);
		$result = $prop->_setValue_for( $value, $this->obj1 );
		$this->assertTrue($result, 'set value: TestPropsObject');
		
		$this->assertEquals(
			$valueId
			, $this->obj1->get('derived3Id')
			, 'derived3Id after set value: TestPropsObject'
		);

		$result = $prop->_getValueFor($this->obj1 );
		// assume PntError
		$this->assertEquals(
			'TestPropsObject>>derived3 Unable to derive value: no option with id: 3456'
			, $result->getlabel()
			, 'derived3 getValueFor error message'
		);
		
		$this->obj1->set('derived3Id', 2);
		$result = $prop->_getValueFor($this->obj1 );
		$options = $this->obj1->getDerived3Options(); //instantiates new options
		$this->assertEquals(
			$options[2]
			, $result
			, 'derived3 getValueFor = option 2'
		);
		
	}

	function test_getSetMulti1Value() 
	{
		$prop = $this->obj1->getPropertyDescriptor('multi1');

		$result = $prop->_getValueFor($this->obj1 );
		// assume PntError
		$this->assertEquals(
			'TestPropsObject>>multi1 unable to derive value: no getter and type is not a class'
			, $result->getlabel()
			, 'multi1 getValueFor error message'
		);

		$value = new PntObject();
		$result = $prop->_setValue_for( $value, $this->obj1 );
		// assume PntError
		$this->assertEquals(
			'TestPropsObject>>multi1 unable to propagate value: no setter and type is not a class'
			, $result->getlabel()
			, 'multi1 setValue_for error message'
		);
	}

	function test_getSetMulti2Value() {
		$this->obj1->set('id', 92034832); //without an id the property assumes it is a new object that can not be referenced
		
		$prop = $this->obj1->getPropertyDescriptor('multi2');

		$result = $prop->_getValueFor($this->obj1 );

		// assume PntError
		$this->assertEquals(
			'TestPropsObject>>multi2 Unable to derive value: no getter and no id-property: '.lcFirst($prop->ownerName).'Id'
			, $result->getlabel()
			, 'multi2 getValueFor error message'
		);

		$value = new PntObject();
		$result = $prop->_setValue_for( $value, $this->obj1 );
		// assume PntError
		$this->assertEquals(
			'TestPropsObject>>multi2 Unable to propagate value: no setter and no id-property'
			, $result->getlabel()
			, 'multi2 setValue_for error message'
		);
	}

	//TODO: test Options references behavior, 
}

return 'PntPropertyDescriptorsTest';
?>
