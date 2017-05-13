<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


Gen::includeClass('PntObject', 'pnt');
Gen::includeClass('PntDbClassDescriptor', 'pnt/db');

/** Abstract superclass of persistent peanuts. 
* @see  http://www.phppeanuts.org/site/index_php/Menu/206
* @package pnt/db
*/
class PntDbObject extends PntObject {
	 	 
	public $id = 0;

	/** Constructor
	* Constuctors can not decide what class to instatiate.
	* If subclass has to instanciated depending on the data loaded,
	* implement it in a subclass of PntDbClassDescriptor 
	* Therefore this constructor should not be called from framework
	*/
	function __construct($id=0) {
		parent::__construct();
		$this->id = 0;
		if ($id>0) {
			$this->loadData($id);	
		}
	}
	
	/** Answer wheather the instances can be loaded from and
	* stored in a database.
	* @static
	* @return boolean;
	*/
	static function isPersistent() {
		return true;
	}

	//static - override if different kind of classDescriptor required
	static function getClassDescriptorClass() {
		return 'PntDbClassDescriptor';
	}

	/** @static 
	* @return String the name of the database table the instances are stored in
	* @abstract - override for each subclass
	*/
	static function getTableName() {
		return null;
	}

	/** @static - override for special filters
	* @return Array of PntSqlFilter the filters by which instances can be searched for
	* @param $className name of the subclass - static will be inherited and will not know the name of the class it is called on :-(
	*/
	static function getFilters($className, $depth) {
		// default implementation is to get the defaults from the class descriptor
		$clsDes = PntClassDescriptor::getInstance($className);
		$defaults = $clsDes->getDefaultFilters($depth);
		
		return $defaults;
	}

	/** Default implementation - returns sort by defaultLabelProp taken from persistent props
	* @static 
	* @param string $itemType itemType for the sort (may be the sort will be for a subclass)
	* @return PntSqlSort that specifies the sql for sorting the instance records by label
	*/
	static function getLabelSort($subclass) {
		$clsDes = PntClassDescriptor::getInstance($subclass);
		$prop = $clsDes->getDefaultLabelProp($clsDes->getPersistentFieldPropertyDescriptors(), array('id'));

		Gen::includeClass('PntSqlSort', 'pnt/db/query');
		$sort = new PntSqlSort('label', $subclass);
		$sort->addSortSpec($prop ? $prop->getName() : 'id');
		return $sort;
	}

	static function newQueryHandler() {
		//call whenever a queryhandler is needed to store/retrieve/delete objects of this class
		//override if specific queryHandler is required
		$qh = new QueryHandler();
		return $qh;
	}

	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();
		//name, type ,[readOnly,min,max,minSize,maxSize,persistent]
		$this->addFieldProp('id', 'number', false, 0, null, 1, '7,0');
		$this->addDerivedProp('oid', 'string');
	}
	
	/** Adds fieldProperties for the columns from the database.
	* This method assumes column names to be equal to the names 
	* of their corresponding field properties. 
	* @param array $includeList names of properties to include
	*	If omitted, all columns will be used in the order they appear in the table, 
	* 	but if a fieldProperty is already defined it is left untouched.
	* @param String $tableName the name of the table whose columns to use.
	*      If omitted, the result of $this->getClassDescriptor()->getTableName() is used
	* @return array propertyDescriptors that where added, by property name
	*/
	function addDbFieldProps($includeList=null, $tableName=null) {
		if (!$tableName) {
			$clsDes = $this->getClassDescriptor();
			$tableName = $clsDes->getTableName();
		}
		$qh = $this->newQueryHandler();
		return $qh->addFieldPropsTo_table($this, $tableName, $includeList);
	}

	/** String representation for representation in UI 
	* @return String Default is to return the values of the paths in the labelSort 
	*  separated by spaces
	*/
	function getLabel() {
		$clsDes = $this->getClassDescriptor(); //caches labelSort
		$sort = $clsDes->getLabelSort(); 
		$result = '';
		if ($sort) {
			$navs = $sort->getSortNavs();
			forEach(array_keys($navs) as $key) {
				try {
					$value = $navs[$key]->evaluate($this);
				} catch (PntError $err) {
					//this method is used by __toString which is used by the error handling.
					//therefore we can only trigger a notification
					trigger_error($err->getLabel(), E_USER_NOTICE);
					 continue;
				}
				if (!empty($result)) $result .= $this->getLabelSeparator($key, $navs);
				$result .= Gen::labelFrom($value);
			}
		}
		$result = trim($result);
		if (empty($result))
			$result = (string) $this->get('id');
		return $result;
	}
	
	function getLabelSeparator() {
		return ' ';
	}

	/** Initialize an existing object from an associative array retrieved from the datbase.
	*
	* @param $assocArray Associative Array with the columnNames as keys and the values as values
	* @param $fieldMap Associative Array with the fieldNames as keys and the corresponding columnNames as values
	* @returns Associative Array mapping the fields that where not in $assocArray
	*/
	function &initFromData($assocArray, &$fieldMap) {
		$missingFieldsMap = array();
		reset($fieldMap);
		if (get_magic_quotes_runtime()) {
			while (list($field) = each($fieldMap))
				if ( isSet( $assocArray[$fieldMap[$field]] ) ) {
					if (is_string($assocArray[$fieldMap[$field]]))
						$this->$field = stripSlashes($assocArray[$fieldMap[$field]]);
					else
						$this->$field = $assocArray[$fieldMap[$field]]; 
				} else {
					$missingFieldsMap[$field] = $fieldMap[$field];
				}
		} else {
			while (list($field) = each($fieldMap)) 
				if ( isSet( $assocArray[$fieldMap[$field]] ) ) {
					$this->$field = $assocArray[$fieldMap[$field]];
				} else {
					$missingFieldsMap[$field] = $fieldMap[$field];
				}
		}
		return $missingFieldsMap;
	}

	function initMissingFields(&$missingFieldsMap) {
		forEach($missingFieldsMap as $fieldName => $columnName) 
			$this->$fieldName = null;
	}
	
	/** @throws PntError */
	function save() {
		
		$qh= $this->newQueryHandler();
		$clsDesc = $this->getClassDescriptor();
		$tableMap = $clsDesc->getTableMap();
		$insert = $this->isNew();

		reset($tableMap);
		while (list($tableName) = each($tableMap)) {
			$fieldMap = $clsDesc->getFieldMapForTable($tableName);
			if ($insert || count($fieldMap) > 1)
				$qh->setQueryToSaveObject_table_fieldMap($this , $tableName, $fieldMap, $insert);
			$qh->runQuery(null, $this->getClass()." saving has failed");
	
			if ($this->isNew()) { 
				$this->id = $qh->getInsertId();
				$clsDesc->peanutInserted($this);  //adds this to the cache
			}
		}
	}
	
	function isNew()
	{
		return !$this->id ;
	}
	
	/** This method is called by ObjectSaveAction after the properties have been 
	* set  and before save() is called. If the result of this method is not empty,
    * save is not called and the error messages are set to the page forwarded to.
	* @return arrray of String with error messages, or empty array if none
	*/
	function getSaveErrorMessages() {
		$result = array();
		$props = $this->getPropsForCheckOptions();
		forEach(array_keys($props) as $key) 
			$this->checkValueInOptions($props[$key], $result);
		return $result;
	}
	
	/**
	 * Check a property value to be in the options of the property.
	 * @param PntPropertyDescriptor $prop 
	 * @param &array $errs add eventual error message strings to this array
	 * @throws PntError
	 */
	function checkValueInOptions($prop, &$errs) {
		if ($prop->isValueInOptionsOf($this)) return;
			
		$value = $prop->getValueFor($this);
			
		if ($value !== null) 
			return $errs[] = $this->getValueNoOptionErrorMessage($prop, $value);

		//there must be an id property and value must be derived from _getPeanutWithId
		//otherwise null would have resulted in $prop->isValueInOptionsOf($this) to be true
		$idProp = $prop->getIdPropertyDescriptor();
		$id = $idProp->getValueFor($this);
		$errs[] = $this->getValueWithIdNoOptionErrorMessage($prop, $id);
	}

	function getValueNoOptionErrorMessage($prop, $value) {
		return "'". Gen::labelFrom($value). "' is no option for ". $prop->getName();
	}
	
	function getValueWithIdNoOptionErrorMessage($prop, $id) {
		return "value with id ". Gen::toString($id). " is no option for ". $prop->getName();
	}
	
	/**
	 * Default is to check derived single value properties that are not readOnly
	 * @return PntPropertyDescriptor The propertyDescriptor whose values 
	 * 	must be in the options */
	function getPropsForCheckOptions() {
		$result = array();
		$clsDes = $this->getClassDescriptor();
		$props = $clsDes->refPropertyDescriptors();
		reset($props);
		while (list($name) = each($props)) {
			$prop = $props[$name];
			if (!$prop->isMultiValue() && !$prop->getReadOnly())
				$result[$prop->getName()] = $prop;
		}
		return $result;
	}
	
	/** Delete the records of this object from the database. 
	* As of version 2.0.alpha the delete recurses to the values of 
	* multi value properties whose onDelete is 'd' or 'v'
	*/
	function delete() {
		$this->recurseDelete();
		$this->pntDelete();
	}
	
	/* @throws PntError */
	function recurseDelete() {
		$clsDes = $this->getClassDescriptor();
		$props = $clsDes->getMultiValuePropertyDescriptors();
		forEach (array_keys($props) as $propName) {
			$prop = $props[$propName];
			if ($prop->getRecurseDelete()) { 
				$values = $prop->getValueFor($this, false);
				forEach(array_keys($values) as $key)
					$values[$key]->delete();
			}
		}
	}
	
	/**
	 * Copy values of multi value properties that are inCopy recursively. 
	 * Do not copy values of other properties.
	 * @param PntDbObject $original whose multi value properties values may be copied
	 * @param PntPropertyDescriptor $fromProp the property recursing from, or null if recursion starts here
	 */
	function recurseCopyFrom($original, $fromProp=null) {
		$origClsDes = $original->getClassDescriptor();
		$props = $this->getPropsForRecurseCopy($fromProp);
		forEach (array_keys($props) as $propName) {
			$prop = $props[$propName];
			$origProp = $origClsDes->getPropertyDescriptor($propName);
			if ($origProp)
				$this->copyValuesOf($prop, $origProp->getValueFor($original, false));
		}
	}
	
	/** @return array of PntMultiValuePropertyDescriptor whose values are to be copied recursively
	 * @param PntPropertyDescriptor $fromProp the property recursing from
	 */
	function getPropsForRecurseCopy($fromProp=null) {
		$result = array();
		$clsDes = $this->getClassDescriptor();
		$props = $clsDes->getMultiValuePropertyDescriptors();
		forEach (array_keys($props) as $propName) {
			if ($props[$propName]->getInCopy())
				$result[$propName] = $props[$propName];
		}
		return $result;
	}
	
	/** Copy the values for the property 
	 * @param PntMultiValuePropertyDescriptor $prop that will hold the values
	 * @param array of PntObejct $values to be copied
	 */ 
	function copyValuesOf($prop, $values) {
		$twinProp = $prop->getTwin();
		forEach($values as $value) {
			$valueCopy = clone $value;
			$twinProp->setValue_for($this, $valueCopy);
			$valueCopy->copyFrom($value, $prop);
		}
		$prop->releaseCacheOn($this);
	}
	
	/**
	 * Save this as  a new peanut. Assumes this is already cloned and modified.
	 * Copy values of multi value properties that are inCopy recursively. 
	 * Do not copy values of other properties.
	 * Saving is done using the basic function pntSacve. If you need to
	 * do aditional work that ::save normally does, you need to override this method.
	 * @param PntDbObject $original whose multi value properties values may be copied
	 * @param PntPropertyDescriptor $fromProp the property recursing from
	 */
	function copyFrom($original, $fromProp=null) {
		$this->registerCopyFrom($original, $fromProp);
		$this->recurseCopyFrom($original, $fromProp);
	}
	
	function registerCopyFrom($original) {
		$this->pntOriginal = $original;
		$this->id = 0;
		$this->save();
		$original->pntCopyId = $this->get('id');
	}
	
	/** Add the objects the delete will cascade to
	 * @param array &$result to add the objects to
	 * @precondition $this->getDeleteErrorMessages must have been called.
	 * @throws PntError */
	function addVerifyOnDeleteValues(&$result, $exclude=array()) {
		$clsDes = $this->getClassDescriptor();
		$props = $clsDes->getMultiValuePropertyDescriptors();
		forEach (array_keys($props) as $propName) {
			if (in_array($propName, $exclude)) continue;
			$prop = $props[$propName];
			if ($prop->getRecurseDelete()) { 
				$verify = $prop->getOnDelete() == 'v';
				$values = $prop->getValueFor($this, false);
				while (list($key) = each($values)) {
					if ($verify && !isSet($values[$key]->addingVerifyOnDelete)) {
						$result[] = $values[$key];
					}
					$values[$key]->addVerifyOnDeleteValues($result, $verify);
				}
			}
		}
	}
	
	//to be overridden if compound key
	/** @trows PntError */
	function pntDelete() {
		$qh = $this->newQueryHandler();
		$clsDesc = $this->getClassDescriptor();
		$idProp = $clsDesc->getPropertyDescriptor('id');
		$tableMap = $clsDesc->getTableMap();

		reset($tableMap);
		while (list($tableName) = each($tableMap)) {
			$qh->setQueryToDeleteFrom_where_equals(
				$tableName
				, $idProp->getColumnName()
				, $this->id
			);
			$qh->runQuery(null, $this->getClass()." deleting has failed");
		}		
		
		$clsDesc->peanutDeleted($this->id);
	}

	/** This method is called by ObjectDeleteAction before delete() is called. 
	* If the result of this method is not empty,
    * delete() is not called and the error messages are set to the page forwarded to.
    * As of version 2.0.alpha this method adds error messages for 
    * multi value properties onDelete = 'c' and recurses for multi value properties
    * onDelete is set
	* @return arrray of String with error messages, or empty array if none
	* @param array of PntObjects $cascading from which the caller is cascading by the paths from each  
	* @throws PntErorr 
	*/
	function getDeleteErrorMessages($cascading=array()) {
		$result = array();
		forEach ($this->getOnDeleteProps($cascading) as $propName => $prop) {
			$values = $prop->getValueFor($this, false); //do not use global filter
			if ($prop->getOnDelete() == 'c') {
				if (count($values) > 0)
					$result[] = $this->getOnDeleteErrorMessage($prop, $values);
			} else {
				if (empty($cascading)) {
					$nextPath = $propName;
				} else {
					end($cascading);
					$nextPath = key($cascading).'.'.$propName;
				}
				$nextCascading = $cascading; //copy
				$nextCascading[$nextPath]=$this;
				forEach(array_keys($values) as $key)
					$result = array_merge($result, $values[$key]->getDeleteErrorMessages($nextCascading));
			}
		}
		return $result;
	}
	
	function getOnDeleteProps($cascading) {
		$result = array();
		$clsDes = $this->getClassDescriptor();
		$props = $clsDes->getMultiValuePropertyDescriptors();
		forEach ($props as $propName => $prop) 
			if ($prop->getOnDelete()) 
				$result[$propName] = $props[$propName];
		return $result;
	}
	
	function getOnDeleteErrorMessage($prop, $values) {
		$count = count($values);
		return "$this still has $count ". $prop->getLabel();
	}

//------------- Legacay support ---------------------------

	function loadData($id) {
		//print "<BR>loadData($id)";
		$clsDesc = $this->getClassDescriptor();
		$qh = $clsDesc->getSelectQueryHandler();
		$qh->where_equals('id', $id);
		$qh->runQuery(); //takes care of error handling
		$row = $qh->getAssocRow();
		$map = $clsDesc->getFieldMap();
		if ($row)
			$this->initFromData($row, $map);
		else 
			trigger_error(
				'er is geen '.$this->getClass()." met id=$id"
				, E_USER_WARNING
			);
		$qh->release();
	}


}


?>