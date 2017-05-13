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
 * for merging a value into markup where the markup is internally represented as a string
 * if no template ::getMarkupWith returns the value itself   
*/
class PntXmlTextPart extends PntXmlPart {

	public $content;
	public $contentLabel;
	public $markup;
	public $preformat = false;

	function __construct( $whole, $markup=null, $content=null, $contentType='string', $decimalPrecision=2) {
		parent::__construct($whole);
		
		$this->setMarkup($markup);
		$this->setContent($content);
		$this->setContentType($contentType);
		$this->setDecimalPrecision($decimalPrecision);
	}

	/** @static 
	* @return String the name of the database table the instances are stored in
	* @abstract - override for each subclass
	*/
	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();

		$this->addFieldProp('content', 'string');
		$this->addFieldProp('markup', 'string');

	}

	function getLabel() {
		return $this->getMarkup();
	}

	function getContent() {
		return $this->content;
	}

	function getContentWith($item) {
		if ($this->content==null)
			return $item;
		else
			return $this->content;
	}
	
	function setContent($value) {
		$this->content = $value;
	}

	/** Merges the template in $this->markup with the contenLabel got from the content */
	function getMarkupWith($item) {
		$contentLabel = $this->getContentLabelWith($item);
		$result = $this->merge($this->markup, $contentLabel);
		$result .= $this->getMarkupContent($item); //add elements markup?
		return $result;
	}

	function getContentLabelWith($item) {
		if ($this->contentLabel !== null) 
			return $this->contentLabel;
		
		$content = $this->getContentWith($item);
		$conv = $this->getConverter();

		return $conv->toLabel($content, $this->getContentType());
	}
	
	function getConverterDefault() {
		return $this->initConverter(parent::getConverterDefault());
	}
	
	function setConverter($conv) {
		parent::setConverter($conv);
		$this->initConverter($conv);
	}
	
	function initConverter($conv) {
		$conv->decimalPrecision = $this->getDecimalPrecision();
		$conv->type = $this->getContentType();
		return $conv;
	}
	
	function setContentLabel($string) {
		$this->contentLabel = $string;
	}

	function setRequestString($requestString) {
		$this->setContentLabel($this->getConverter()->fromRequestData($requestString));
	}

	
/*	function getMarkupContent($item) 
	{
		return " ". get_class($this) . count($this->getElements()). " elements ";
	}
*/	
	function setMarkup($value) {
		$this->markup = $value;
	}

	/** Sets wheather and how the content should be converted as preformatted. 
	* @param integer $value the number of spaces per tab, or 0 for no preformat
	*/
	function setPreformat($value) {
		$this->preformat = $value;
	}

	function merge($template, $content) {
		$conv = $this->getConverter();
		$contentHtml = $conv->toHtml($content, $this->preformat, $this->preformat);
		
		if (!empty($template))
			return str_replace('$content', $contentHtml, $template);
		else
			return $contentHtml;
	}
	
	function getContentType() {
		return $this->contentType;
	}
	
	function setContentType($value) {
		$this->contentType = $value;
	}
	
	function getDecimalPrecision() {
		return $this->decimalPrecision;
	}
	
	function setDecimalPrecision($value) {
		$this->decimalPrecision = $value;
	}
}
?>