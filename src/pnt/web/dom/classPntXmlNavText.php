<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntXmlTextPart', 'pnt/web/dom');
Gen::includeClass('PntNavigation', 'pnt/meta');

/** @package pnt/web/dom
 * Component of a Document Object Model (not w3c compatible) 
 * ::getMarkupWith merges a template 
 * with the result of a navigation that has been converted to HTML.
 *  
 * Used by PntTablePart to specify columns and retrieve their values, 
 * and by DetailsParts for representing and (help) producing the details.
*/
class PntXmlNavText extends PntXmlTextPart {

	public $navigation;

	/** @throws PntReflectionError */
	function __construct($whole, $itemType='Array', $path=null) {
		parent::__construct($whole);
		$this->setPath($path, $itemType);
	}

	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();

		$this->addFieldProp('navigation', 'PntNavigation');
	}
	
	static function formKeyFrom($path) {
		return str_replace('.', '-', $path);
	}

	/** @throws PntReflectionError */
	function setPath($path, $itemType=null) {
		$nav = $this->getNavigation();
		if ($nav && !$itemType)
			$itemType = $nav->getItemType();
		if ($nav && !$path)
			$path = $nav->getPath();
		$nav = PntNavigation::getInstance($path, $itemType);
		$this->setNavigation($nav);
	}
	
	function getPath() {
		$nav = $this->getNavigation();
		return $nav->getPath();
	}

	function getContentType() {
		$nav = $this->getNavigation();
		return $nav->getResultType();
	}

	function getDecimalPrecision() {
		$nav = $this->getNavigation();
		$prop = $nav->getLastProp();
		return $prop
			? ValueValidator::getDecimalPrecision($prop->getMaxLength())
			: parent::getDecimalPrecision();
	}
	
	function getNavigation() {
		return $this->navigation;
	}
	
	function setNavigation($value) {
		$this->navigation = $value;
		if ($this->converter) $this->initConverter($this->converter);
	}
	
	function getLabel() {
		$nav = $this->getNavigation();
		return $nav ? $nav->getLabel() : '';
	}

	function getPathLabel() {
		if (isSet($this->pathLabel)) return $this->pathLabel;
		$nav = $this->getNavigation();
		return $nav->getPathLabel(); 
	}

	function setPathLabel($value) {
		$this->pathLabel = $value;
	}
	
	/** @return string must be according to checkAlphaNumeric 
	 * 		Key that can be used as value for the name attribute of an input tag in a form 
	 */ 
	function getFormKey() {
		if (isSet($this->formKey)) return $this->formKey;
		return $this->formKeyFrom($this->getPath());
	}
	
	function initConverter($conv) {
		$nav = $this->getNavigation();
		$prop = $nav->getLastProp();
		if ($prop)  
			$conv->initFromProp($prop);
		return $conv;
	}
	
	/** @return the content that will be merged
	* @throws PntError */
	function getContentWith($item) {
		$nav = $this->getNavigation();
		$this->content = $nav->evaluate($item);
		return $this->content;
	}
}
?>