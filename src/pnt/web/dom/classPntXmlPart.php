<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObject', 'pnt');

/** @package pnt/web/dom
 * Component of a Document Object Model (not w3c compatible) 
 * that defines that content may consist of several elements.
 * ::getMarkupWith produces a string holding markup according to these elements.
*/
class PntXmlPart extends PntObject {

	public $elements;
	public $converter;

	function __construct($whole) {
		parent::__construct();
		$this->elements = array();
		if ($whole!==null)
			$whole->addElement($this);
	}

	/** @static 
	* @return String the name of the database table the instances are stored in
	* @abstract - override for each subclass
	*/
	function initPropertyDescriptors() {
		// only to be called once

		parent::initPropertyDescriptors();

		$this->addFieldProp('converter', 'PntStringConverter');
		$this->addMultiValueProp('parts', 'PntXmlPart'); // or string
		$this->addMultiValueProp('elements', 'PntXmlPart'); // or string
		$this->addDerivedProp('markup', 'string');
	}

	function initFrom($whole) 
	{
		if ($whole && $this->converter===null && $whole->converter !== null)
			$this->setConverter(clone $whole->converter);
	}
	
	function getConverter() {
		if ($this->converter===null) { //returning by value caused dereference problem
			$result = $this->getConverterDefault();
			return $result;
		} else
			return $this->converter;
	}

	function getConverterDefault() {
		return new StringConverter();
	}

	function setConverter($value) {
		$this->converter = $value;
		$this->initParts();
	}

	function addElement($value) {
		$elements =& $this->getElements();
		if (is_object($value))
			$value->initFrom($this);
		$elements[] = $value;
	}

	function addElements(&$arr) {
		reset($arr);
		foreach ($arr as $element)
			$this->addElement($element);
	}

	/** Must return reference to array so that other methods can add elements */
	function &getElements() {
		return $this->elements;
	}


	function getParts() {
		return $this->getElements();
	}
	
	//must return ref for compatibility with PntXmlElement
	function &getAttributes() {
		$result = array();
		return $result;
	}
	
	function initParts() 
	{
		$parts = $this->getParts();
		if (!empty($parts)) {
		    foreach ($parts as $part) {
				if (is_object($part))
					$part->initFrom($this);
			}
		}
	}

	function getMarkupWith($item) 
	{
		return $this->getMarkupContent($item);
	}


	function getMarkupContent($item) 
	{
		$result = '';
		if (!empty($this->elements)) {
			$elements = $this->getElements();
			reset($elements);
			foreach ($elements as $element) {
				if (is_object($element))
					$result .= $element->getMarkupWith($item);
				else
					$result .= $element;
			}
		}
		return $result;
	}

	function getMarkup() {
		return $this->getMarkupWith(null);
	}
}
?>