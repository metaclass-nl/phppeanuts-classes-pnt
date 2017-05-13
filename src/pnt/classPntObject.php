<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


Gen::includeClass('PntDerivedPropertyDescriptor', 'pnt/meta');
Gen::includeClass('PntFieldPropertyDescriptor', 'pnt/meta');
Gen::includeClass('PntMultiValuePropertyDescriptor', 'pnt/meta');
// ValueValidator included by PntSite

/** General Peanut superclass. 
* @see http://www.phppeanuts.org/site/index_php/Pagina/90
* @abstract
* @package pnt
*/
class PntObject {

	/**
	* Constuctors can not decide what class to instatiate.
	* If subclass has to instanciated depending on a parameter,
	* implement it in a subclass of PntClassDescriptor
	* Therefore this constructor should not be called from framework
	*/
	function __construct() {
		// call this constructor from subclasses,
		// except from those using depricated support only

		$clsDes = $this->getClassDescriptor();
		if (empty($clsDes->propertyDescriptors) )
			$this->initPropertyDescriptors();
	}

	static function isPersistent() {
		return false;
	}

	//override if different kind of classDescriptor required
	static function getClassDescriptorClass() {
		return 'PntClassDescriptor';
	}

	/** Returns the directory of the class file
	* @static
	* @return String
	*/
	static function getClassDir() {
		return null;
	}

	/** Returns the label of the class, or null if none.
	* To get a defaulted label use the PntClassDescriptor method
	* @static
	* @return String
	*/
	static function getClassLabel() {
		return null;
	}

	//static - override if required
	// if not overridden and not specified on the classDescriptor,
	// the classDescriptor will make something up, see PntClassDescriptor
	static function getUiColumnPaths() {
		return null;
	}

	/** Returns the paths for columns to be used in reports
	* Default is null, the reporpage will use getUiColumnPaths
	* @static override if required.
	* @return Array of String or String
	*   For keys that are Strings, the keys will be used as column label
	*/
	static function getReportColumnPaths() {
		return null;
	}

	//static - override if required
	// if not overridden and not specified on the classDescriptor,
	// the classDescriptor will make something up, see PntClassDescriptor
	static function getUiFieldPaths($clsDes) {
		return null;
	}

	static function newNavigation($key, $itemType) {
		Gen::includeClass('PntObjectNavigation', 'pnt/meta');
		$prop = PntObjectNavigation::getPropIncludeType($key, $itemType);
		
		$result = class_exists($prop->getType()) && Gen::class_hasMethod($prop->getType(), 'newNavigationOver') 
			? pntCallStaticMethod($prop->getType(), 'newNavigationOver', $prop)
			: new PntObjectNavigation();
		$result->setItemType($itemType);
		$result->setKey($key);
		$result->setStepResultType($prop->getType());
		return $result;
	}
	
	/** @depricated, use clone $this, implement __clone() */
	function copy() {
		return clone $this;
	}

	function getClass() {
		return get_class($this);
	}

	function getClassDescriptor() {
		return PntClassDescriptor::getInstance($this->getClass()) ;
	}

	function initPropertyDescriptors() {
		$this->addDerivedProp('label', 'string');
		$prop = $this->addDerivedProp('classDescriptor', 'PntClassDescriptor', true);
		$prop->setVisible(false);

		//addFieldProp($name, $type, $readOnly=false, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null, $persistent=true)
		//addDerivedProp/addMultiValueProp($name, $type, $readOnly=true, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null)
	}

	/** @returns a string that identifies the object (case insensitive)
	* the class name is in original case so it should be possible to include the class file 
	* using the class name from the oid. */
	function getOid() {
		return $this->getClass().'*'.$this->get('id');
	}

	/** @param string $aString must be alpanumeric */
	function addFieldProp($name, $type, $readOnly=false, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null, $persistent=true)
	{
		if (strlen($name)==0) {
			trigger_error("addFieldProp without a name", E_USER_WARNING);
			return null;
		}

		$clsDes = $this->getClassDescriptor();
		$prop = new PntFieldPropertyDescriptor($name, $type, $readOnly, $minValue, $maxValue, $minLength, $maxLength, $classDir, $persistent);
		$clsDes->addPropertyDescriptor($prop);
		return $prop;

	}

	/** @param string $aString must be alpanumeric */
	function addDerivedProp($name, $type, $readOnly=true, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null)
	{
		if (strlen($name)==0) {
			trigger_error("addDerivedProp without a name", E_USER_WARNING);
			return null;
		}

		$clsDes = $this->getClassDescriptor();
		$prop = new PntDerivedPropertyDescriptor($name, $type, $readOnly, $minValue, $maxValue, $minLength, $maxLength, $classDir);
		$clsDes->addPropertyDescriptor($prop);
		return $prop;
	}

	/** @param string $aString must be alpanumeric */
	function addMultiValueProp($name, $type, $readOnly=true, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null)
	{
		if (strlen($name)==0) {
			trigger_error("addMultiValueProp without a name", E_USER_WARNING);
			return null;
		}

		$clsDes = $this->getClassDescriptor();
		$prop = new PntMultiValuePropertyDescriptor($name, $type, $readOnly, $minValue, $maxValue, $minLength, $maxLength, $classDir);
		$clsDes->addPropertyDescriptor($prop);
		return $prop;
	}

	function getPropertyDescriptor($propertyName) {
		$clsDes = $this->getClassDescriptor();
		if (!$clsDes) 
			trigger_error($this. ' no classdescriptor while getting propertydescriptor: '.$propertyName, E_USER_ERRRO);
		return $clsDes->getPropertyDescriptor($propertyName);
/*		// code like in classdescriptor here to get better error message
		$props = $clsDes->refPropertyDescriptors();
		if (array_key_exists($propertyName, $props))
			return $props[$propertyName]; // gives trouble with references
		else {
			trigger_error($this.' unknown property: '.$propertyName, E_USER_WARNING);
			return null;
		}
*/	}

	/** get the value of the property with the specified name
	 * @param string $propertyName the name by which a PntPropertyDescriptor can be obtained from ::getPropertyDescriptor
	 * @param PntSqlFilter $filter to apply, or null if unfiltered (currently only used by multi value properties)
	 * @return mixed dynmically typed by the PropertyDescriptor>>type
	 * @throws PntError if property missing, derivation fails, or anything an evetial getter method throws 
	 */
	function get($propertyName, $filter=true) {
		$prop = $this->getPropertyDescriptor($propertyName);
		if (!$prop) 
			throw new PntReflectionError($this->getClass()." property not found: '$propertyName'");
		
		return $prop->getValueFor($this, $filter);
	}

	//set the value of the property with the specified name
	// The value is pased by value, thus copied.
	// If you need to pass the value by reference, use
	// PntPropertyDescriptor::setValue_for directly
	function set($propertyName, $value) {
		$prop = $this->getPropertyDescriptor($propertyName);
		if (!$prop) {
			trigger_error(
				$this->getClass()." property not found: $propertyName"
				, E_USER_ERROR);
			return null;
		}
		$prop->setValue_for($value, $this);
		return true;
	}

	/** Return the property options for this
	* If an options method exists, answer the method result.
	* otherwise delegate to the types ClassDescriptor
	* @param string $name property name
	* @param PntSqlFilter $filter or boolean wheather to use the global filter
	* @return array with elements dynamically typed by the PropertyDescriptor
	* @throws PntReflectionError if ClassDescriptor returns null, or type is not a class
	*/
	function getOptions($name, $filter=true) {
		$clsDesc = $this->getClassDescriptor();
		$prop = $clsDesc->getPropertyDescriptor($name);
		return $prop->getOptionsFor($obj, $filter);
	}

	/** Returns the ValueValidator used for validating property values
	* !Do not call this method directly, use validateGetErrorString($propertyDescriptor, $value)
	* @param prop PntPropertyDescriptor
	* @return ValueValidator
	*/
	function getValueValidator($prop) {
		return $prop->getValueValidator();
	}

	/** Validate the value for the property. Answer null if valid.
	* Answer the error String if invalid.
	* Override this method to modify the validation behavior.
	* Default is just to use the validator from _getValueValidator.
	* You may change ValueValidator to override the error messages
	* or the generic behavior inherited from PntValueValidator
	* @param prop PntPropertyDescriptor
	* @param value mixed value to be validated.
	* @return String the error string or null if the value is valid
	*/
	function validateGetErrorString($prop, $value, $validateReadOnly=true) {
		$validator = $this->getValueValidator($prop);
		return $validator->validate($value, $validateReadOnly);
	}

	function basicGetLabel() {
		// warning: overriding may change __toString() behavior in an unexpected way
		return ('a '.$this->getClass() );
	}

	/** String representation for representation in UI 
	* Default implementation: value of defaultLabelProp, if none basicGetLabel
	*/
	function getLabel() {
		$clsDes = $this->getClassDescriptor();
		$prop = $clsDes->getDefaultLabelProp($clsDes->refPropertyDescriptors(), array());
		if (!$prop) return $this->basicGetLabel();
		try {
			$result = $prop->getValueFor($this);
		} catch (PntError $err) {
			trigger_error($err->getLabel(), E_USER_NOTICE);
			return $this->basicGetLabel();
		}
		return Gen::labelFrom($result); //make sure it is a string. (This may not be the correct stringconversion!)
	}

	/** String representation for debugging purposes */
	function __toString() {
		$basicLabel = $this->basicGetLabel();
		$label = $this->getLabel();
		if ($label == $basicLabel)
			return $basicLabel; //the label is already showing the class name

		//combine class name and label
		return $this->getClass()."($label)";
	}

	/** Release values that are cached on $this. 
	 * Default is to release cached values of all multi value properties.
	 * This method should be overridden if the there are other values cached on $this.
	 * (Derived properties default behavior is not to cache their values on the peanuts,
	 * their values are already cached on the ClassDescriptor, 
	 * @see PntDbClassDescriptor::getDescribedClassInstanceForData
	 */
	function release() {
		$clsDes = $this->getClassDescriptor();
		forEach ($clsDes->getMultiValuePropertyDescriptors() as $prop) {
			$prop->releaseCacheOn($this);
		}
	}
	
	/** Information for the user that is editing the object
	* Should be overridden
	* @return String Html
	*/
	function getEditInfo() {
		// default is no information
		return null;
	}
	
	/** @depricated */
	function toString() {
		return $this->__toString();
	}

	/** @depricated */
	function _getValueValidator($prop) {
		return $this->getValueValidator($prop);
	}
}


?>