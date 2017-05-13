<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

	
Gen::includeClass('PntDescriptor', 'pnt/meta');
Gen::includeClass('PntReflectionError', 'pnt/meta');

/** Class of  objects describing a class of peanuts. 
* closest thing to a metaclass.
* @see http://www.phppeanuts.org/site/index_php/Pagina/96
* @package pnt/meta
*/
class PntClassDescriptor extends PntDescriptor {

	public $propertyDescriptors; 
	public $name;

	/** @param String $name Should not be null!
	*/
	function __construct($name=null) {
		$this->propertyDescriptors = array();
		$this->setName($name);
	}

	/** Must return a reference to the array so that ohter method can add instances */
	static function &getInstances() {
		static $instances;
		return($instances);
	}

	/** Returns the PntClassDescriptor for the spefied class.
	* ClassDescriptors are cached in a static variable
	* PREREQUISITE: the class must be loaded and support the PntObject metaobjects protocol
	* Class of returned instance depends on what className the static method
	* getClassDescriptorClass on the specified class returns 
	* @static
	* @param name name of the class to describe
	* @return PntClassDescriptor the instance describing the specified class
	*/
	static function getInstance($name) {
		$key = strToLower($name);
		$arr =& PntClassDescriptor::getInstances();
		
		if (!isSet($arr[$key])) {
			if (!$name || !class_exists($name)) {
				trigger_error("class does not exist: $name", E_USER_WARNING);
				return null; //causes trouble in the sender, which is usually better debuggable
			}
			$descriptorClass = pntCallStaticMethod($name, 'getClassDescriptorClass');
			
			$clsDesc = new $descriptorClass($name);
			$arr[$key] = $clsDesc;
		} else {
			$clsDesc = $arr[$key];			
		}
			
		return $clsDesc ;
	}

	function isPropertyDescriptorSet($propertyName) {
		//answer wheater the propertyDescriptor with the given name has been added already
		$prop = $this->propertyDescriptors[$propertyName];
		//if ($prop) print '<BR> set: '.$prop;
		return( isSet($prop) );
	}

	function getPropertyDescriptors() {
		//return copy with refs to the original propertyDescriptors
		return $this->refPropertyDescriptors();
	}
	
	/* Must return a reference to the array so that other methods can add instances */
	function &refPropertyDescriptors() {
		if (empty($this->propertyDescriptors)) {
			$this->propertyDescriptors = array();
			$className = $this->getName();
			if (!class_exists($className))
				throw new PntReflectionError("Class $className does not exist");
			// print "<BR>initializing propertyDescriptors of $className";
			$anInstance = new $className(); //initializes propertyDescriptors
		}
		return $this->propertyDescriptors;
	}			

	function getMultiValuePropertyDescriptors() {
		$result = array(); // anwering reference to unset var may crash php
		$props =& $this->refPropertyDescriptors();
		reset($props);
		while (list($name) = each($props)) {
			$prop = $props[$name];
			if ($prop->isMultiValue())
				$result[$name] = $prop;
		}
		return( $result );
		
		//forEach no good with objects: it allways copies them
		//so does list($name, $prop)
	}

	function getSingleValuePropertyDescriptors() {
		$result = array(); // anwering reference to unset var may crash php
		$props =& $this->refPropertyDescriptors();
		reset($props);
		while (list($name) = each($props)) {
			$prop = $props[$name];
			if (!$prop->isMultiValue())
				$result[$name] = $prop;
		}
		return( $result );
	}

	function getPropertyDescriptor($name) 
	{
		$props =& $this->refPropertyDescriptors();
		if (array_key_exists($name, $props))
			return $props[$name]; // gives trouble with references
		else {
//debug:	trigger_error($this->getName().' unknown property: '.$name, E_USER_WARNING);
			return null;
		}
	}

	/** Get the twin of a property whose name and type are...
	* If a property implements a role in a relationship, 
	* the property that implements the role on the other side is its twin.
	* @param String $propName The name of the property whose twin is requested
	* @param String $type The type of the property whose twin is requested
	* @return PntPropertyDescriptor the twin or null if not found. Currently only multi value properties will be returned.
	* PRECONDITION: both the properties type and the twins type must be loaded
	*/ 
	function getTwinOf_type($propName, $type)
	{
		$props = $this->getMultiValuePropertyDescriptors();
		while (list($key) = each($props)) {
			if ( $props[$key]->getTwinName() == $propName
					&&  is_subclassOr($type, $props[$key]->getType()) )
				return $props[$key];
		}
	}

	function addPropertyDescriptor($anPntPropertyDescriptor) 
	{
			
		$anPntPropertyDescriptor->setOwner($this);
		// print '<BR>'.$anPntPropertyDescriptor;

		//do not use getPropertyDescriptors() here, that would cause infinite recursion
		$this->propertyDescriptors[$anPntPropertyDescriptor->getName()] = $anPntPropertyDescriptor;
	}

	function hasPropertyDescriptor($name) 
	{
		$props = $this->refPropertyDescriptors();
		return( isSet($props[$name]) );
	}

	/** Returns the directory of the class file
	* @return String 
	*/
	function getClassDir() {
		return pntCallStaticMethod($this->getName(), 'getClassDir');
	}

	function getLabel() {
		if (isSet($this->label)) return $this->label;
			
		$clsName = $this->getName();

		$label = class_exists($clsName)
			? pntCallStaticMethod($clsName, 'getClassLabel')
			: null;
		if ($label) return $label;
		
		return $clsName;
	}

	/** Returns the default user interface table column paths 
	* If the static method PntObject::getUiColumnPaths() has been overridden,
	* its result will be returned. Otherwise the names of
	* the result of getUiPropertyDescriptors will be returned
	* @return Array of String
	*/
	function getUiColumnPaths() {
		$clsName = $this->getName();
		$paths = pntCallStaticMethod($clsName, 'getUiColumnPaths', $this);
		if ($paths!==null) 
			return $paths;
			
		$paths = array_keys($this->getUiPropertyDescriptors());
		
		if (empty($paths))
			return array('label');
		else
			return $paths;
	}
	
	/** Returns the default user interface field paths 
	* If the static method PntObject::getUiFieldPaths() has been overridden,
	* its result will be returned. Otherwise the names of
	* getUiPropertyDescriptors will be returned
	* @return Array of String
	*/
	function getUiFieldPaths() {
		$clsName = $this->getName();

		$paths = pntCallStaticMethod($clsName, 'getUiFieldPaths', $this);
		if ($paths!==null) {
			if (!is_array($paths))
				return explode(' ', $paths);
			else
				return $paths;
		}
		return array_keys($this->getUiPropertyDescriptors());
	}

	/** Returns the default user interface single value property descriptors.
	* These are the names of all 
	* sinlge value propertyDesctiptors except label, id or *Id
	* but if that would be empty, label
	* @return Array of PntPropertyDescriptor
	*/
	function getUiPropertyDescriptors() 
	{
		$result = array();
		$props = $this->getSingleValuePropertyDescriptors();
		while (list($name) = each($props)) {
			if ($props[$name]->getVisible())
				$result[$name] = $props[$name];
		}
		return $result;
	}

	function getParentclassDescriptor() 
	{
		$name = $this->getName();
		$parentName = get_parent_class($name);
		return PntClassDescriptor::getInstance($parentName);
	}

	/** Return a reference to the default property to derive the label from 
	* of peanuts described by this. May return null. Caches result
	* @param Array of PntPropertyDescriptor $candidates to get the property from
	* @patam Array of String $reject names of propertyDescriptors not to return
	* @return PntPropertyDescriptor the first visible fieldproperty that is 
	*   not in $reject and not ending on 'Id'.
	*/
	function getDefaultLabelProp($candidates, $reject) {
		if (isSet($this->defaultLabelProp)) return $this->defaultLabelProp;
		reset($candidates);
		while (list($name) = each($candidates)) {
			if (array_search($name, $reject) || substr($name, -2) == 'Id') continue;
			$prop = $candidates[$name];
			if ($prop->isFieldProperty() && $prop->getVisible())
				return $this->defaultLabelProp = $prop;
		}
		return null;
	}

	function getVerifyOnDelete() {
		return false; //delete not supported
	}
	
//---------- reflective behavior --------------------------------------

	/** Returns the instance of the described class with 
	* the id to be equal to the specfied value, or null if none
	* @param mixed id
	* @return PntObject or null 
	* @throws PntEror
	*/
	function getPeanutWithId($id) {
		// id like null, 0 or '' mean 'no value'
		if (!$id)
			return null;

		if (Gen::class_hasMethod($this->getName(), 'getInstance') ) {
			$result = pntCallStaticMethod($this->getName(), 'getInstance', $id);
			return $result;
		}

		$arr = $this->getPeanutsWith('id', $id);
		
		if (count($arr)) {
			reset($arr);
			
			return $arr[key($arr)]; //current would have copied the object
		} else 
			return null;
	}

	/** Returns the instances of the described class
	* @throws PntReflectionEror
	* @return Array of instances of the described class
	*/
	function getPeanuts() {
		$clsName = $this->getName();
		if (Gen::class_hasMethod($clsName, 'getInstances'))
			return pntCallStaticMethod($clsName, 'getInstances');
		
		throw new PntReflectionError("$clsName::getInstances is undefined");
	}

	/** Returns the instances of the described class with 
	* the specfied property value to be equal to the specfied value
	* @param String propertyName 
	* @param mixed value
	* @return Array of instances of the discribed class
	* @throws PntError
	*/
	function getPeanutsWith($propertyName, $value) {
		$instances = $this->getPeanuts();

		$prop = $this->getPropertyDescriptor($propertyName);
		if (!$prop) 
			throw new PntReflectionError("property not found: ". $this->getName(). ">>$propertyName");
			
		$result = array();
		forEach(array_keys($instances) as $key) {
			$propVal = $prop->getValueFor($instances[$key]);
			if ($propVal == $value) $result[] = $instances[$key];
		}
		return $result;
	}
	
	/** Returns the instances of the described class according to the supplied specification
	* @param PntSqlSpec 
	* @return array of PntObject
	* @throws PntEror */
	function getPeanutsAccordingTo($spec) {
		$found = $this->getPeanuts();
		if (Gen::is_a($spec, 'PntSqlSort')) {
			$filter = $spec->getFilter();
			$found = $filter->selectFrom($found);
			$spec->asort($found);
		} else {
			return $spec->selectFrom($found);
		}
	}

	/** @depricated */
	function _getPeanuts() {
		try {
			return $this->getPeanuts();
		} catch (PntError $err) {
			return $err;
		}
	}
	
	/** @depricated */
	function _getPeanutsWith($propertyName, $value) {
		try {
			return $this->getPeanutsWith($propertyName, $value);
		} catch (PntError $err) {
			return $err;
		}
	}
	
	/** @depricated */
	function _getPeanutWithId($id) {
		try {
			return $this->getPeanutWithId($id);
		} catch (PntError $err) {
			return $err;
		}
	}
	
}
?>