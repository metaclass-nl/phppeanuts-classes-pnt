<?php
// Copyright (c) MetaClass Groningen, 2003-2012

   	
Gen::includeClass('PntTestCase', 'pnt/test');

/** @package pnt/test/web */
class PntXmlElementTest extends PntTestCase {
	
	public $root;
	
	function setUp() {
		Gen::includeClass('PntXmlElement', 'pnt/web/dom');
		Gen::includeClass('PntXmlNavValue', 'pnt/web/dom');
		$this->root = new PntXmlElement(null,'ROOT');
	}

	function test_root() {
		
		$this->assertEquals(
			'ROOT'
			, $this->root->getTag()
			, 'tag');
		$this->assertEquals(
			'ROOT'
			, $this->root->get('label')
			, 'label');
		$this->assertEquals(
			'stringconverter'
			, strToLower(get_class($this->root->getConverter()))
			, 'converter class');
			
		$this->assertEquals(
			"\n	<ROOT></ROOT>\n"
			, $this->root->getMarkup()
			, 'html');

	}
	
	function test_attributes() {
		$atts =& $this->root->getAttributes();

		$atts['class'] = 'pntNormal';
		$this->assertEquals(
			"\n	<ROOT class=\"pntNormal\"></ROOT>\n"
			, $this->root->getMarkup()
			, 'html with class');
			
		$atts['HREF'] = 'http://www.metaclass.nl';
		$this->assertEquals(
			"\n	<ROOT class=\"pntNormal\" HREF=\"http://www.metaclass.nl\"></ROOT>\n"
			, $this->root->getMarkup()
			, 'html with class and HREF');
	}
	
	function test_part1() {
		//must in php4 be assigned by reference because $this->root will reference it, 
		//if we are talking to a copy $this->root will not be affected
		$this->part1 = new PntXmlElement($this->root,'PART1'); 

		$this->part1Text = 'HTMLTEKST VAN PART1';
		$this->part1->addElement($this->part1Text);
		$this->assertEquals(
			"\n	<PART1>$this->part1Text</PART1>\n"
			, $this->part1->getMarkup()
			, 'part1 html');
		//print $this->root->getMarkup();

		$part1Html = $this->part1->getMarkup();
		$this->assertEquals(
			"\n	<ROOT>$part1Html</ROOT>\n"
			, $this->root->getMarkup()
			, 'root html');
		$this->assertEquals(
			"\n	<PART1>$this->part1Text</PART1>\n"
			, $this->part1->getMarkup()
			, 'part1 html to check parts iterator reset');

	}

	function test_part12() {
		//must in php4 be assigned by reference because $this->root will reference it, 
		//if we are talking to a copy $this->root will not be affected
		$this->part1 = new PntXmlElement($this->root,'PART1');
		$this->part2 = new PntXmlElement($this->root,'PART2');

		$part1Html = $this->part1->getMarkup();
		$part2Html = $this->part2->getMarkup();
		$this->assertEquals(
			"\n	<ROOT>$part1Html$part2Html</ROOT>\n"
			, $this->root->getMarkup()
			, 'root html');
	}

	function test_textPart() {
		//must in php4 be assigned by reference because $this->root will reference it, 
		//if we are talking to a copy $this->root will not be affected
		$this->part1 = new PntXmlTextPart($this->root,'HTMLTEKST');
		$this->assertEquals(
			"HTMLTEKST"
			, $this->part1->getMarkup()
			, 'part1 html');
		$this->part1->setContent(">>>");
		$this->assertEquals(
			"HTMLTEKST"
			, $this->part1->getMarkup()
			, 'part1 html content ignoored');
		$this->part1->setMarkup(null);
		$this->assertEquals(
			'&gt;&gt;&gt;'
			, $this->part1->getMarkup()
			, 'part1 html from content');
		$this->part1->setMarkup('<P>$content</P>');
		$this->assertEquals(
			'<P>&gt;&gt;&gt;</P>'
			, $this->part1->getMarkup()
			, 'part1 html from content using html as template');
		
		$part1Html = $this->part1->getMarkup();
		$this->assertEquals(
			"\n	<ROOT>$part1Html</ROOT>\n"
			, $this->root->getMarkup()
			, 'root html');
	}
	
	function test_navtext() {
		Gen::includeClass('TestPropsObject', 'pnt/test/meta');
		$obj1 = new TestPropsObject();
		$obj1->set('field1',123);
		//must in php4 be assigned by reference because $this->root will reference it, 
		//if we are talking to a copy $this->root will not be affected
		$this->part1 = new PntXmlNavText($this->root, 'TestPropsObject', 'field1');

		$this->assertEquals(
			123
			, $this->part1->getContentWith($obj1)
			, 'part1 field1 content');
		$this->assertEquals(
			'123'
			, $this->part1->getContentLabelWith($obj1)
			, 'part1 field1 contentLabel');
			$this->assertEquals(
			'123'
			, $this->part1->getMarkupWith($obj1)
			, 'part1 field1 html');

		$obj2 = new TestPropsObject();
		$obj2->set('id', 777);
		$obj2->set('field1', 'label of obj2');
		$this->part1->setPath('field2');
		$obj1->set('field2',$obj2);
		$this->assertEquals(
			'TestPropsObject'
			, $this->part1->getContentType()
			, 'part1 field2 contentType');
		$this->assertEquals(
			$obj2->getLabel()
			, $this->part1->getContentLabelWith($obj1)
			, 'part1 field2 content');
		$this->assertEquals(
			'label of obj2'
			, $this->part1->getMarkupWith($obj1)
			, 'part1 field2 html');

		$this->part1->setPath('field2.id');
		$this->assertEquals(
			'field2.id'
			, $this->part1->getPath()
			, 'part1 field2.id path');		
		$this->assertEquals(
			'777'
			, $this->part1->getMarkupWith($obj1)
			, 'part1 field2.id html');
	}
	
	
/*
    	$this->assertEquals('yes', 123, 'assertEquals');
		$this->assertNotNull(null, 'assertNotNull');
		$this->assertNull(123, 'assertNull');
		$this->assertSame('12', 12, 'assertSame');
		$this->assertNotSame($this->obj1, $this->obj1, 'assertNotSame');
     	$this->assertTrue(false, 'assertTrue');
		$this->assertFalse(true, 'assertFalse');
		$this->assertRegExp('~.php~', 'myFile.txt', 'assertRegExp');
 */   	
}

return 'PntXmlElementTest';
?>
