<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntXmlPart', 'pnt/web/dom');

/** @package pnt/web/dom
 * Component of a Document Object Model (not w3c compatible) 
 * that has a start tag, content and end tag and is represented in object form. 
 * The start tag may have attributes, which are represented in an associatice array.  
 * ::getMarkupWith produces the complete representation of all these in a string.
*/
class PntXmlElement extends PntXmlPart {

	public $tag;
	public $attributes;

	function __construct($whole, $tag=null) 	{
		$this->attributes = array();
		parent::__construct($whole);
		$this->setTag($tag);
		
	}

	function initPropertyDescriptors() {
		// only to be called once

		parent::initPropertyDescriptors();

		$this->addFieldProp('tag', 'string');
		$this->addMultiValueProp('attributes', 'string'); //key as attribute name, value may be a PntXmlNavValue too
	}

	function initFrom($whole) 
	{
		if ($this->converter===null && $whole->converter !== null)
			$this->setConverter($whole->converter);
	}
	
	function getParts() {
		return array_merge(
			$this->getElements()
			, $this->getAttributes()
		);
	}

	function getLabel() {
		return $this->getTag();
	}

	function getTag() {
		return $this->tag;
	}
	
	function setTag($value) {
		$this->tag = $value;
	}

	/* Must return reference to array so that other methods can add attributes */
	function &getAttributes() {
		return $this->attributes;
	}
	
	function setAttribute($name, $value)
	{
		$atts =& $this->getAttributes();
		$atts[$name] = $value;
	}

	function setAttributes($assocArray)
	{
		$this->attributes = $assocArray;
	}

	function getMarkupStartTag($item) { 
		$result = "\n	<"; 
		$result .= $this->getTag();
		$result .= $this->getMarkupAttributes($item);
		$result .= ">";

		return $result;
	}
	
	function getMarkupEndTag() { 
		$result = "</"; 
		$result .= $this->getTag();
		$result .= ">\n";

		return $result;
	}

	function getMarkupAttributes($item) { 
		$atts = $this->getAttributes();
		if (empty($atts)) 
			return '';
			
		$conv = $this->getConverter();
		$result = '';
		reset($atts);
		while (list($key, ) = each($atts)) {
			
			if (is_object($atts[$key])) {							
				if (($atts[$key]->isAlwaysVisible()) || ($atts[$key]->getContentWith($item)==true)) {
					
					$result .= ' ';
					$result .= $key;
					$result .= '="';
					$result .= $atts[$key]->getMarkupWith($item);
				} 
			}
			else {
				$result .= ' ';
				$result .= $key;
				$result .= '="';
				$result .= $conv->toHtml($atts[$key]);
			}
			$result .= '"';
		}
		return $result;
	}


	function getMarkupWith($item) 
	{
		$result = $this->getMarkupStartTag($item); 
		$result .= $this->getMarkupContent($item); 
		$result .= $this->getMarkupEndTag(); 

		return $result;
	}

}
?>