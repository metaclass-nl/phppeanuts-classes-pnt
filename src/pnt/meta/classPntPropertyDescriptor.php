<?php
/* Copyright (c) MetaClass, 2003-2017

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntClassDescriptor', 'pnt/meta');

define('PNT_NOT', false);
define('PNT_READ_WRITE', true);
define('PNT_READ_ONLY', 2);

/** An object of this class describes a property of a peanut and supplies default property behavior.
* @see http://www.phppeanuts.org/site/index_php/Menu/212
* @package pnt/meta
*/
class PntPropertyDescriptor extends PntDescriptor
{
	public $name;
	public $type;
	public $minValue;
	public $maxValue;
	public $minLength = 0;
	public $maxLength;
	public $readOnly;
	public $ownerName;
	public $classDir;
	public $isTypePrimitive;
	public $visible; 
//	public $dbSortPaths;

	/** @param string $aString must be alpanumeric */
	function __construct($name, $type, $readOnly, $minValue, $maxValue, $minLength, $maxLength, $classDir) {
		$this->setName($name);
		$this->setType($type);
		$this->isTypePrimitive = in_array($type, $this->primitiveTypes());
		$this->setMinValue($minValue);
		$this->setMaxValue($maxValue);
		$this->setMinLength($minLength);
		$this->setMaxLength($maxLength);
		$this->setReadOnly($readOnly);
		$this->setClassDir($classDir);
	}

	static function primitiveTypes() {
		return array('number', 'string', 'date', 'timestamp', 'time', 'boolean', 'currency', 'email', 'html');
	}

	function getName()
	{

		return( $this->name );
	}

	/** @param string $aString must be alpanumeric */
	function setName($aString) {
		$this->name = $aString;
	}

	function isLegalType($aString) {
		return (array_search($aString, $this->primitiveTypes()) !== false)
			|| class_exists($aString);
	}

	// answer 'number', 'date', 'string', 'timestamp', 'email' or the name of the class
	// that defines the interface of the objects held by the property.
	function getType()
	{
		return $this->type ;
	}

	function setType($aString)
	{
		$this->type = $aString;
	}

	/** answer wheather the property value may be modified explicitely
	* if false, calling a setter or changing a field value or other
	* explicit property change action will be undefined
	*/
	function getReadOnly()
	{
		return( $this->readOnly );
	}

	function setReadOnly($aValue) {
		// see getter
		$this->readOnly = $aValue;
	}

	function getMinValue()
	{
		// answer tho lowest value that is allowed for this property
		// if used with objects the objects must implement the comparable interface

		return( $this->minValue );
	}

	function setMinValue($aValue)
	{
		// see getter
		$this->minValue = $aValue;
	}

	function getMaxValue()
	{
		// answer tho highest value that is allowed for this property
		// if used with objects the objects must implement the Comparable interface

		return( $this->maxValue );
	}

	function setMaxValue($aValue)
	{
		// see getter
		$this->maxValue = $aValue;
	}

	function getMinLength()
	{
		// answer minimal length that is allowed for this property
		// if used with non-strings, the value will be converted to a string
		// if used with objects the objects must have the label propery

		return( $this->minLength );
	}

	function setMinLength($aValue)
	{
		// see getter
		$this->minLength = $aValue;
	}

	function getMaxLength()
	{
		// answer maximal length that is allowed for this property
		// if used with non-strings, the length will be used
		//   of the value converted to a String
		// if used with objects the lenght of the value of the
		//   label property will be used

		return( $this->maxLength );
	}

	function setMaxLength($aValue)
	{
		// see getter
		$this->maxLength = $aValue;
	}

	/** Defines the persistency of the property. 
	* @return mixed PNT_NOT (=false), PNT_READ_WRITE (=true), PNT_READ_ONLY
	*/
	function getPersistent()
	{
		return PNT_NOT;
	}

	/** Get the paths that can be used to make the database sort by this property 
	* @return Array of string Navigational paths, not null
	*/
	function getDbSortPaths() {
		if (!isSet($this->dbSortPaths)) return array();
		return $this->dbSortPaths;
	}
	
	/** Set the paths that can be used to make the database sort by this property 
	* @param Array of string $paths Navigational paths, not null
	*/
	function setDbSortPaths($paths) {
		$this->dbSortPaths = $paths;
	}

	/** Returns the directory of the class file of the type of the property
	* As the type class can not be loaded without knowing its classDir,
	* the classDir must be specified ont the property if it is not default.
	* Default is the classDir of the owner.
	* @return String
	*/
	function getClassDir() {
		if ($this->classDir !== null)
			return $this->classDir;

		$owner = $this->getOwner();
		return $owner->getClassDir();
	}

	/** See Getter */
	function setClassDir($value)
	{
		$this->classDir = $value;
	}

	function getOwner()
	{
		return PntClassDescriptor::getInstance($this->ownerName);
	}

	function setOwner($anPntClassDescriptor)
	{
		$this->ownerName = $anPntClassDescriptor->getName();
	}

	/** Returns the ValueValidator used for validating property values
	* !Do not call this method directly, use getValueValidator($propertyName)
	* on the object whose property values have to be validated, or better,
	* let the object do the validation using validateGetErrorString($propertyName, $value)
	* @return ValueValidator
	*/
	function getValueValidator() {
		$validator = new ValueValidator();
		$validator->initFromProp($this);
		return $validator;
	}

	/* @return boolean true if the property should be shown in the user interface 
	*/
	function getVisible() {
		if ($this->visible !== null) return $this->visible;
		
		// nto set, use default
		$name = $this->getName();
		return $this->getName() != 'label' && !$this->getHoldsId();
	}
	
	/* Overrides default wheather the property should be shown in the user interface 
	@param boolean $value if null defautl value will be used
	*/
	function setVisible($value) {
		$this->visible = $value;
	}
	
	/** @return boolean wheather this holds an id. Id's are generally not visible and
	 * numerical ids do not have thousends separators 
	 */
	function getHoldsId() {
		$name = $this->getName();
		return $name == 'id' || $name == 'oid' || substr($name, -2) == 'Id';
	}
	
	/** @return boolean Wheather null values, empty Strings or objects with empty labels are forbidden
	*/
	function getCompulsory() {
		return $this->getMinLength() > 0;
	}		

	function isDerived()
	{
		return(false);
	}

	function isFieldProperty()
	{
		return(false);
	}

	function isTypePrimitive() {
		return $this->isTypePrimitive;
	}

	function isMultiValue()
	{
		return false;
	}

	function getIdPropertyDescriptor() {
		return null;
	}

	function __toString() {
		if (!$this->ownerName)
			return parent::__toString();

		return $this->ownerName.'>>'.$this->getName();
	}

	//------------------ Meta Behavior -------------------------

	/** Answer the property value for the object
	* If a getter method exists, call it (passing the filter) and return the method result.
	* else derive value through default metabehavior
	* @param PntSqlFilter $filter to apply, or null if unfiltered (currently only used by multi value properties)
	* @return mixed dynmically typed by the PropertyDescriptor>>type
	* @throws PntError if property missing, derivation fails, or anything an evetial getter method throws 
	*/
	function getValueFor($obj, $filter=true) {
		//use getter method if there
		$name = $this->getName();
		$mth = "get$name";
		if (method_exists($obj, $mth)) 
			return $filter === true 
				? $obj->$mth()
				: $obj->$mth($filter);  

		return $this->deriveValueFor($obj, $filter); 
	}


	/** Set the property value for the object
	* If a setter method exists, use the method and answer result.
	* else set field value
	*/
	function setValue_for($value, $obj) {
		$name = $this->getName();
		$mth = "set$name";
		if (method_exists($obj, $mth))
			return $obj->$mth($value); //use setter method if there
		else
			return $this->propagateValue_for($value, $obj);
		}

	/** Return the property options for the object
	* If an options method exists, answer the method result.
	* otherwise delegate to the types ClassDescriptor
	* If the type is a class name and the class has a method 'getInstances'
	* assume it is a static method, call it and return the result.
	* @throws PntReflectionError if ClassDescriptor returns null, or type is not a class
	* @param PntObject the object to get the options for 
	* @param PntSqlFilter $filter ignoored
	* @return Array the options
	*/
	function getOptionsFor($obj, $filter=true) {
		$name = $this->getName();
		$mth = "get$name".'Options';

		if (method_exists($obj, $mth))
			return $obj->$mth($filter); //use getter method if there

		$className = $this->getType();
		if ($className == 'boolean') 
			return array(true, false);
		
		if (!class_exists($className)) 
		 	Gen::tryIncludeClass($className, $this->getClassDir()); //parameters from own properties
		 	
		if (!class_exists($className)) 
			throw new PntReflectionError(
				$this. ' no options getter, and type is not a class'
			);
		
		$clsDesc = PntClassDescriptor::getInstance($className);
		try {
			return $clsDesc->getPeanuts();
		} catch (PntError $err) {
			throw new PntReflectionError(
					"$this can not get options: no getter or"
					, 0, $err);
		}
	}

	function hasOptionsGetter($obj)
	{
		$name = $this->getName();
		$mth = "get$name".'Options';

		return method_exists($obj, $mth);
	}
	
	function getOptionsFilter($obj, $filter) {
		if ($filter!==true) return $filter;
		
		return isSet($GLOBALS['site']) //global filtering requites global site to contain a PntSite
			? $GLOBALS['site']->getGlobalOptionsFilter($this->getType(), $this->getName())
			: null;
	}
	

	/** Return the property value for the object
	* Called if no getter method exists.
	* Should be implemented by subClass.
	* @param $obj PntObject The object whose property value to answer
	* @throws PmtError 
	*/
	function deriveValueFor($obj) {
		throw new PntReflectionError(
			$this. ' Should have been implemented by subclass: deriveValueFor'
		);
	}

	/** Set the property value for the object
	* Called if no setter method exists and the property is not readOnly.
	* Should be implemented by subClass.
	* @param @value varianr The value to set
	* @param @obj PntObject The object whose property value to set
	* @throws PntError
	*/
	function propagateValue_for($value, $obj) {
		throw new PntReflectionError(
			$this. ' Should have been implemented by subclass: propagateValue_for'
		);
	}

	
	/** @throws PntError */
	function isValueInOptionsOf($obj, $filter=false) {
		$value = $this->getValueFor($obj);
		if ($value === null || !$this->hasOptionsGetter($obj)) return true;
		//we could check booleans too, but they should already be checked by value validation
		
		$options = $this->getOptionsFor($obj, $filter); 
		return $this->isValue_in($value, $options);
	}
	
	function isValue_in($value, $someObjects) {
		$compareByIds = Gen::is_a($value, 'PntObject') 
			&& is_subclassOr($this->getType(), 'PntObject');
		if ($compareByIds) {
			$typeClsDes = PntClassDescriptor::getInstance($this->getType());
			$typeIdProp = $typeClsDes->getPropertyDescriptor('id');
			$compareByIds = $compareByIds && $typeIdProp;
		}
		
		forEach(array_keys($someObjects) as $key) {
			if ($compareByIds) {
				//if ($value->equals($someObjects[$key])) return true;
				if ($value->get('id') == $someObjects[$key]->get('id')) return true;
			} else {
				if ($value == $someObjects[$key]) return true;
			}
		}
		return false;
	}
	
	/** @depricated */
	function _getValueFor($obj) {
		try {
			return $this->getValueFor($obj);
		} catch (PntError $err) {
			return $err;
		}
	}
	
	/** @depricated */
	function _setValue_for($value, $obj) {
		try {
			return $this->setValue_for($value, $obj);
		} catch (PntError $err) {
			return $err;
		}
	}

	/** @depricated */
	function _getOptionsFor($obj) {
		try {
			return $this->getOptionsFor($obj);
		} catch (PntError $err) {
			return $err;
		}
	}

	/** @depricated */
	function _deriveValueFor($obj) {
		try {
			return $this->deriveValueFor($obj);
		} catch (PntError $err) {
			return $err;
		}
	}

	/** @depricated */
	function _propagateValue_for($value, $obj) {
		try {
			return $this->propagateValue_for($value, $obj);
		} catch (PntError $err) {
			return $err;
		}
	}
	
	/** @depricated */
	function _getValueValidator() {
		return $this->getValueValidator();
	}
}
?>