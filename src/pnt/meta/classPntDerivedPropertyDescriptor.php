<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPropertyDescriptor', 'pnt/meta');

/** An object of this class describes a derived property of a peanut 
* and supplies default property behavior.
* @see http://www.phppeanuts.org/site/index_php/Pagina/99
* @package pnt/meta
*/
class PntDerivedPropertyDescriptor extends PntPropertyDescriptor {

	public $shortlistPath;
	
	function isDerived() {
		return true;
	}

	
	/** If a property implements a role in a relationship, 
	* the property that implements the role on the other side is its twin.
	* @return PntPropertyDescriptor The twin property in the relationship
	*/ 
	function getTwin() {
		$className = $this->getType();
		if (!class_exists($className))
			Gen::tryIncludeClass($className, $this->getClassDir()); //parameters from own properties

		if ( !class_exists($className) ) return null;
		$typeClsDesc = PntClassDescriptor::getInstance($className);
		if (!$typeClsDesc) return null;
		$typeMultiProps = $typeClsDesc->getMultiValuePropertyDescriptors();
		forEach($typeMultiProps as $prop) {
			if ($prop->getTwinName() == $this->getName()
				&& is_subclassOr($this->ownerName, $prop->getType())
			) return $prop;
		}
		return null; 
	}
	
	
	/** Defines the persistency of the property. 
	* For derived properties it answers wheather the receiver's values are persistent.
	* for derived properties that are persistent the value(s)
	* will automatically be retrieved from persistent storage
	* otherwise the value may be derived from the propertyOptions
	* @return mixed PNT_NOT (=false), PNT_READ_WRITE (=true)
	*/
	function getPersistent() {

		$type = $this->getType();
		if (!class_exists($type))
			Gen::tryIncludeClass($type, $this->getClassDir()); //parameters from own properties
		if (!class_exists($type))
			return false;
			
		$clsDesc = $this->getOwner();
		return $this->getIdPropertyDescriptor()
				&& pntCallStaticMethod($type, 'isPersistent');
	}

	/** Get the paths that can be used to make the database sort by this property 
	* @return Array of string Navigational paths, not null
	*/
	function getDbSortPaths() {
		if (isSet($this->dbSortPaths)) return $this->dbSortPaths;
		if (!$this->getPersistent()) {
			if ($this->getName() == 'label') {
				$sort = pntCallStaticMethod($this->ownerName, 'getLabelSort', $this->ownerName);
				return $sort->getSortPaths();
			} else {
				return array();
			}
		}
		$type = $this->getType();
		$sort = pntCallStaticMethod($type, 'getLabelSort', $type);
		$filters = $sort->getSortSpecFilters();
		$result = array();
		forEach(array_keys($filters) as $key)
			$result[] = $this->getName().'.'.$filters[$key]->getPath();
		return $result;
	}

	/** Returns the propertyDescriptor of the corresponding id-Property
	* this is the property named as this, extended with 'Id'
	* @return PntPropertyDescriptor
	*/
	function getIdPropertyDescriptor()
	{
		$clsDesc = $this->getOwner();
		return $clsDesc->getPropertyDescriptor($this->getName().'Id');
	}
	
	function isValueInOptionsOf($obj, $filter=false) {
		$idProp = $this->getIdPropertyDescriptor();

		if ($this->hasOptionsGetter($obj) || !$idProp) 
			return parent::isValueInOptionsOf($obj, $filter);
			
		//for peanuts derived by id we do not want to retrieve all options
		//because those may be all objects in the database 
		$id = $idProp->getValueFor($obj);
		if (!$id) return true; //nothing selected, is taken care of by PntObject::validateGetErrorString
		
		Gen::includeClass($this->getType(), $this->getClassDir());
		$clsDesc = PntClassDescriptor::getInstance($this->getType());
		try {
			$result = $clsDesc->getPeanutWithId($id);
		} catch (PntError $e) {
			 return parent::isValueInOptionsOf($obj, $filter);
		}
		if ($result === null) return false; //not in the database is not an option
		
		$optionsFilter = $this->getOptionsFilter($obj, $filter);
		if (!$optionsFilter) return true; //all values in the database are options

		return $optionsFilter->evaluate($result); //option if according to filter
	}

	/** Return the property value for the object
	* Called if no getter method exists.
	* Delegate to the types classDescriptor
	* if unsucessfull, retrieve option with key = idProperty.value
	* @throws PntReflectionError for primitive types, 
	* if no idProperty, if no options. Return null if option not found
	* 
	* @param @obj PntObject The object whose property value to answer
	* @param PntSqlFilter $filter ignoored
	*/
	function deriveValueFor($obj, $filter=true) {
		$className = $this->getType();
		Gen::tryIncludeClass($className, $this->getClassDir()); //parameters from own properties
		
		if (!class_exists($className)) {
			throw new PntReflectionError("$this unable to derive value: no getter and type is not a class");
		}
			
		$idProp = $this->getIdPropertyDescriptor();
		if (!$idProp) 
			throw new PntReflectionError("$this Unable to derive value: no getter and no id-property");

		try {
			$id = $idProp->getValueFor($obj);
		} catch (PntError $err) {
			throw new PntReflectionError(
				$this.' Unable to derive value of id-property'
				, 0	, $err
			);
		}
			
		$clsDesc = PntClassDescriptor::getInstance($className);
		try {
			return $clsDesc->getPeanutWithId($id);
		} catch (PntReflectionError $withIdErr) {
			try {
				$options = $this->getOptionsFor($obj, false);
			} catch (PntError $getOptionsErr) {
				throw new PntReflectionError(
					$this. ' Unable to derive value: no getter or '
					,0 , $getOptionsErr
				);
			}
			if (empty($options)) 
				throw new PntReflectionError("$this Unable to derive value: options empty");
					
			if (isSet($options[$id]))
				return $options[$id];
			
			throw new PntReflectionError("$this Unable to derive value: no option with id: $id");
		}
	}
	
	/** Set the property value for the object
	* Called if no setter method exists and the property is not readOnly.
	* Delegate to the types classDescriptor
	* @throws PntReflectionError for primitive types or if no id property
	* @param @value varianr The value to set
	* @param @obj PntObject The object whose property value to set
	*/
	function propagateValue_for($value, $obj) {
		$className = $this->getType();
		if (!class_exists($className)) 
			throw new PntReflectionError($this. ' unable to propagate value: no setter and type is not a class');
		
		$idProp = $this->getIdPropertyDescriptor();
		if (!$idProp) 
			throw new PntReflectionError("$this Unable to propagate value: no setter and no id-property");
		
		if ($value === null) {
			$id = null;
		} else {
			$valueClsDes = $value->getClassDescriptor();
			$valueProp = $valueClsDes->getPropertyDescriptor('id');
			if (!$valueProp) 
				throw new PntReflectionError("$this Unable to propagate value: no setter and value has no id-property");
			try {
				$id = $valueProp->getValueFor($value);
			} catch (PntError $err) {
				throw new PntReflectionError(
					"$this Unable to propagate value: no setter or"
					,0 , $err
				);
			}
		}			
		$idProp->setValue_for($id, $obj);
		return true;
	}

	/** @return boolean Wheather null values, empty Strings, values <= 0 or objects with empty labels are forbidden
	*/
	function getCompulsory() {
		$idProp = $this->getIdPropertyDescriptor();
		return parent::getCompulsory() || 
			($idProp && $idProp->getCompulsory());
	}
	
	/** @return string The path to the multi value property holding the shortList a value for this property 
	* may be selected from. 
	* @precondition: ShortlistDialogWidget must be installed
	* @precondition: no options getter method or Dialog must be adapted to only 
	* 	select values from options
	* */
	function getShortlistPath() {
		return $this->shortlistPath;
	}
	
	/** @param string $value The path to the multi value property holding 
	 * the shortList a value for this property may be selected from */
	function setShortlistPath($value) {
		$this->shortlistPath = $value;
	}
	

}
?>