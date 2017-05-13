<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPropertyDescriptor', 'pnt/meta');

/** An object of this class describes a field property of a peanut 
* and supplies default property behavior.
* @see http://www.phppeanuts.org/site/index_php/Pagina/98
* @package pnt/meta
*/
class PntFieldPropertyDescriptor extends PntPropertyDescriptor 	{

	public $persistent = PNT_READ_WRITE;
	public $fieldProperties;
	public $tableName;

	function __construct($name, $type, $readOnly, $minValue, $maxValue, $minLength, $maxLength, $classDir, $persistent=true) 
	{
		parent::__construct($name, $type, $readOnly, $minValue, $maxValue, $minLength, $maxLength, $classDir);
		$this->setPersistent($persistent);
	}

	/** Defines the persistency of the property. 
	* @return mixed PNT_NOT (=false), PNT_READ_WRITE (=true), PNT_READ_ONLY
	*/
	function getPersistent() {
		return $this->persistent;
	}
	
	/** Defines the persistency of the property. 
	* @param mixed $value PNT_NOT (=false), PNT_READ_WRITE (=true), PNT_READ_ONLY
	*/
	function setPersistent($value) {
		$this->persistent = $value;
	}

	/** Get the paths that can be used to make the database sort by this property 
	* @return Array of string Navigational paths, not null
	*/
	function getDbSortPaths() {
		if (isSet($this->dbSortPaths)) return $this->dbSortPaths;
		
		if ($this->getPersistent()) return array($this->getName());
		else return array();
	}

	function isFieldProperty() {
		return(true);
	}

	/** Return the name of the databaseColumn mapped to this property
	* Default is to return the property name.
	* @see PntDbClassDescriptor::getFieldMap()
	* @return String columnName 
	*/
	function getColumnName() {
		if (isSet($this->columnName)) return $this->columnName;
		
		return $this->getName();
	}
	
	/** Set the name of the databaseColumn mapped to this property
	* @see PntDbClassDescriptor::getFieldMap()
	* @return String $value the columnName 
	*/
	function setColumnName($value) {
		$this->columnName = $value;
	}

	/** Return the name of the database table holding the column mapped to this property
	* Is set at propertydescripter adding to the tableName of the overridden propertyDescriptor.
   * if none, is set to the tableName from the classDescriptor.
	* @see PntDbClassDescriptor::addPropertyDescriptor()
	* @return String tableName
	*/
	function getTableName() {
		return $this->tableName;
	}

	function setTableName($aString)
	{
		$this->tableName = $aString;
	}

	/** Return the property value for the object
	* Called if no getter method exists.
	* Returns the field value
	@param PntObject $obj  The object whose property value to answer
	*/
	function deriveValueFor($obj) {
		$name = $this->getName();
		if (isSet($obj->$name))
			return $obj->$name;

		return null; 
	}

	/** Set the property value for the object
	* Called if no setter method exists and the property is not readOnly.
	* Sets the field value
	@param mixed $value The value to set
	@param mixed $obj The object whose property value to set
	*/
	function propagateValue_for($value, $obj) {
		$name = $this->getName();
		return $obj->$name = $value; //set the field
	}

	function getFieldProperties()	{
		// depricated support

		if (empty($this->fieldProperties)) {
			$this->fieldProperties["type"]=$this->getType();
			$this->fieldProperties["readOnly"]=$this->getReadOnly();			
			
			$temp = $this->getMinValue();
			$this->fieldProperties["minValue"]=($temp===null?'':$temp);

			$temp = $this->getMaxValue();
			$this->fieldProperties["maxValue"]=($temp===null?'':$temp);

			$temp = $this->getMinLength();
			$this->fieldProperties["minLength"]=($temp===null?'':$temp);

			$temp = $this->getMaxLength();
			$this->fieldProperties["maxLength"]=($temp===null?'':$temp);
		}

		return $this->fieldProperties;
	}
	
	function isIdProperty()
	{
		$name = $this->getName();
		return strToLower(substr($name, -2)) == 'id';
	}
}
?>