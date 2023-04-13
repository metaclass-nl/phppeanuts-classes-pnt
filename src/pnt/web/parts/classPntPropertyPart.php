<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPagePart', 'pnt/web/parts');

/** @package pnt/web/parts */
class PntPropertyPart extends PntPagePart {

	public $printLabel = false;
	public $printIcons = false;
	/** @var string HTML value of the class attribute of the property label div tag */
	public $propLabelCssClass = 'pntMultiPropLabel';
	/** @var string (untrusted) id of the table tag */
	public $itemTableId;
	
	function __construct($whole, $requestData, $propertyName=null) {
		parent::__construct($whole, $requestData);
		$this->initialize($propertyName);
	}
	function initialize($propertyName) {
		$this->propertyName = $propertyName;
	}
	
	function setPropertyName($value) {
		if (!$value) 
			trigger_error("propertyName missing", E_USER_WARNING);
		$this->propertyName = $value;
	}
		
	function printBody() {
		$this->printMultiPropTitleRow();

		$divId = $this->getTableDivId();
		$cssClass = $this->getTableDivCssClass();
		print "\n<div id=\"$divId\" class=\"$cssClass\">";
		$this->printItemTablePart();
		print "\n</div>";
	}
	/** @return string alpanumeric id of the item table div tag */
	function getTableDivId() {
		return 'pntMptb'. $this->getPropertyName();
	}
	/** @return string html the value of the class attribute of the item table div tag */
	function getTableDivCssClass() {
		return 'pntMultiPropTableDiv';
	}
	/** @return string (untrusted) id of the table tag */
	function getItemTableId() {
		return $this->itemTableId;
	}
	/** @return string alphanumeric */
	function getType($partName=null) {
		if ($partName == 'TablePart') 
			return $this->getPropertyType();
		
		return parent::getType();
	}	
	
	function getItems() {
		if (isSet($this->items)) return $this->items;
		
		$obj = $this->getRequestedObject();
		$prop = $this->getPropertyDescriptor();
		if (!$prop) return trigger_error('PropertyDescriptor missing', E_USER_WARNING);

		return $this->items = $obj ? $this->getPropertyValueFor($obj) : array();
	}
	
	function setItems($items) {
		$this->items = $items;
	}
	
	function isLayoutReport() {
		return $this->whole->isLayoutReport();
	}
	
	function printMultiPropTitleRow() {
		$propName = $this->getPropertyName(); //alphanumeric
		print "\n<Div id=\"pntMptr$propName\" class=\"pntMultiPropTitleRow\">";
		if ($this->printLabel)
			$this->printMultiPropLabel();
		if ($this->hasMultiPropIcons())
			$this->printMultiPropIcons();
		print "\n</div>";
	}
	
	function hasMultiPropIcons() {
		return $this->printIcons && !$this->isLayoutReport() && $this->getReqParam('id');
	}
	
	/** Print the label above the table with the items from a multi value property
	* @param $prop PntMultiValuePropertyDescriptor (not null)
	*/
	function printMultiPropLabel() {
		$prop = $this->getPropertyDescriptor();
		if (!$prop) trigger_error('Property missing', E_USER_ERROR);
		print "<div class=\"$this->propLabelCssClass\">";
		$this->htOut(ucFirst($prop->getLabel()));
		print '</div>';
	}
	
	function printMultiPropIcons() {
		//ignore
	}
	
	function printItemTablePart()  {
		if (!$this->getRequestedObject()) return;

		$table = $this->getItemTable();
		$table->printBody();
	}

	function getItemTable() {
		if (!isSet($this->itemTable))
			$this->itemTable = $this->getInitItemTable();
		return $this->itemTable;
	}
		
	function getInitItemTable() { 
		$propType = $this->getPropertyType();
		$this->useClass($propType, $this->getPropertyClassDir());
		$columnPaths = $this->getItemTableColumnPaths();
		
		$partName = 'TableProperty'.$this->getPropertyName().'Part';
		$part = $this->getPart(array($partName, $propType, $columnPaths));
		if (!$part) {
			$partName = 'TablePart';
			$part = $this->getPart(array($partName,$propType, $columnPaths));
		}
		
		$this->itemTableId = $part->getTableId();
		$part->setItems($this->getItems());
		return $part;
	}

	function getPropertyDescriptor() {
		$obj = $this->getRequestedObject();
		if (!$obj) return null;
		return $obj->getPropertyDescriptor($this->getPropertyName());
	}

	/** @throws PntError */
	function getPropertyValueFor($obj) {
		$prop = $this->getPropertyDescriptor();
		if (!$prop) throw new PntError("No propertydescriptor '". $this->getPropertyName(). "'");
		
		$sm = $this->controller->getSecurityManager();
		$values = $prop->getValueFor($obj);
		$result = array();
		forEach($values as $key => $value)
			if (!$sm->checkViewObject($value, null))
				$result[$key] = $value; 
		return $result;
	}
	
	/** @return string alphanumeric type of the property */
	function getPropertyType() {
		$prop = $this->getPropertyDescriptor();
		if (!$prop)
			return null;
		return $prop->getType();
	}

	function getPropertyClassDir() {
		$prop = $this->getPropertyDescriptor();
		if (!$prop) return null;
		return $prop->getClassDir();	
	}

	/** @return string label of the type of the property */
	function getItemTypeLabel() {
		$prop = $this->getPropertyDescriptor();
		$clsDes = PntClassDescriptor::getInstance($prop->getType() );
		return $clsDes->getLabel();
	}
	
	function getSubsaveActions() {
		return array();
	}
	
	function getNewButtonUrl($type=null) {
		$prop = $this->getPropertyDescriptor();
		if (!$prop || $prop->getReadonly()) return false;
		
		$idProp = $prop->getIdPropertyDescriptor();
		if (!$idProp) return false;

		if (!$type)	
			$type = $this->getPropertyType();
		$params = array('pntHandler' => 'EditDetailsPage'
			, 'pntRef' => $this->getFootprintId()
			, 'pntType' => $type
			, $idProp->getName() => $this->getReqParam('id')
			);
		$appName = $this->controller->getAppName($this->getPropertyClassDir(), $type, $params['pntHandler']);
		return $this->controller->buildUrl($params, $appName);
	}
	
	/** @return string (untrusted) referring to the context page. */
	function getThisPntContext() {
		$type = $this->getType();
		$id = $this->getReqParam('id');
		$propName = $this->getPropertyName();
		return "$type*$id*$propName";
	}

	function getSpecificPartPrefix($partName=null) {
		if (Gen::subStr($partName, 0, 5) == 'Table')
			return $this->getPropertyType();
			
		return parent::getSpecificPartPrefix($partName);
	}

	/** Returns the paths for the columns to show in the itemtable 
	* $return Array, whose String keys are used as labels, for numeric keys the paths will be used
	*/
	function getItemTableColumnPaths() {
		$itemClsDes = PntClassDescriptor::getInstance($this->getPropertyType());
		if ($this->isLayoutReport()) 
			$columnPaths = pntCallStaticMethod($this->getPropertyType(), 'getReportColumnPaths');
		if (!isSet($columnPaths))
			$columnPaths = $itemClsDes->getUiColumnPaths();
		if (!is_array($columnPaths))
			$columnPaths = explode(' ', $columnPaths);

		$twinName = $this->getPropertyDescriptor()->getTwinName();
		if ($twinName) {
			//remove column of twin from columnPaths
			$twinColumnKey = array_search($twinName, $columnPaths);
			if ($twinColumnKey!==false)
				unset($columnPaths[$twinColumnKey]);
		}
		
		return $columnPaths;
	}
		
}
?>