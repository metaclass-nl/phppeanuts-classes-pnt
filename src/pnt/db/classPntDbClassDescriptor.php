<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


Gen::includeClass('PntClassDescriptor', 'pnt/meta');
Gen::includeClass('QueryHandler');
Gen::includeClass('PntNavigation', 'pnt/meta');

/** ClassDescriptor for persistent peanuts.
* Retrieves peanuts from the database.  Generates PntSqlFilters for searching.
* ClassDescriptor: @see http://www.phppeanuts.org/site/index_php/Pagina/96 
* Persistency: @see  http://www.phppeanuts.org/site/index_php/Menu/206
* Override or reimplement this class to adapt persistency or create your own,
* @see http://www.phppeanuts.org/site/index_php/Pagina/52
* @package pnt/db
*/
class PntDbClassDescriptor extends PntClassDescriptor {

	/** Name of property that holds class name for polymorhism support */
	public $polymorphismPropName = null;

	// just for caching
	public $tableName;
	public $fieldMap;
	public $peanutsById;
	public $polyClassesAllowed;
	public $labelSort;
	public $tableMap;
	public $fieldMapPrefixed;

	function __construct($name) {
		parent::__construct($name);
		$this->peanutsById = array();
	}

	function addPropertyDescriptor($aPntPropertyDescriptor) 
	{
		if ($aPntPropertyDescriptor->isFieldProperty() ) {
			if ($this->polymorphismPropName) {
				$name = $aPntPropertyDescriptor->getName();
				$parentDesc = $this->getParentclassDescriptor();
				$inherited = $parentDesc->getPropertyDescriptor($name);
			
				if ($inherited && $inherited->isFieldProperty() && $inherited->getPersistent() && $name != 'id' && $inherited->getTableName() ) {
					$aPntPropertyDescriptor->setTableName($inherited->getTableName() );
				} else {//no polymorphism for this property
					$aPntPropertyDescriptor->setTableName($this->getTableName() );
				}
			} else { //no polymorphism for this classdescriptor
				$aPntPropertyDescriptor->setTableName($this->getTableName() );
			}
		} 
		parent::addPropertyDescriptor($aPntPropertyDescriptor);
	} 
			 
	/** Set which property used for polymorphic retrieval
    * If no value, retrieval is monomorphic (default)
	* @param String the name of the property
	*/
	function setPolymorphismPropName($value)
	{
		$this->polymorphismPropName = $value;
	}

	//used to save persistent object to disk
	function getPersistentFieldPropertyDescriptors() {
		$result = array(); // anwsering reference to unset var may crash php
		$props =& $this->refPropertyDescriptors();
		if (empty($props))
			return $result;
		reset($props);
		foreach ($props as $name => $prop) {
			if ($prop->isFieldProperty() && $prop->getPersistent())
				$result[$name] = $prop;
		}

		return( $result );
	}

	// get the getPersistentFieldPropertyDescriptors except the idProperties
	function getPersistentValuePropertyDescriptors() {
		$result = array(); // anwsering reference to unset var may crash php
		$props = $this->getPersistentFieldPropertyDescriptors();
		foreach ($props as $name => $prop) {
			if (!$prop->isIdProperty())
				$result[$name] = $prop;
		}
		return $result;
	}


	function getPersistentRelationPropertyDescriptors($type='PntDbObject') {
		$result = array(); // anwsering reference to unset var may crash php
		$props =& $this->refPropertyDescriptors();
		if (empty($props))
			return $result;
		reset($props);
		foreach ($props as $name => $prop) {
			if ($prop->isDerived()
					&& $prop->getPersistent() 
					&& ($idProp = $prop->getIdPropertyDescriptor())
					&& $idProp->getPersistent() ) {
				$relatedType = $prop->getType();
				if (!class_exists($relatedType)) {
					$classDir = $prop->getClassDir();
					$used = Gen::tryIncludeClass($relatedType, $classDir); //parameters retrieved from propertyDescriptor
					if (!$used)
						trigger_error($this->getName().'>>'. $prop->getName(). " class file not found: $classDir/class$relatedType.php",E_USER_WARNING);
				}
				if (is_subclassOr($prop->getType(), $type))
					$result[$name] = $prop;
			}
		}
		return( $result );
	}

	/* Returns the name of the tabel properties specific for this class are mapped to
	*/
	function getTableName() {
		if (!isSet($this->tableName)) {
			$this->tableName = pntCallStaticMethod($this->getName(), 'getTableName');
		}
		return $this->tableName;
	}

	/** Return an array with table names as keys and class names as values.
	* the first table will be used to obtain the id of new objects, with polymorphic 
	* retrieval this table should be the topmost persistent superclass, so that it
	* contains a record for each logical instance in the polymorphism, making the ids
	* unique for all objects within a polymorphism
	* @return Array the map
	*/
	function initTableMap(&$anArray)
	{
		if ($this->getTableName() === null) return;

		$this->refPropertyDescriptors(); //initializes the polymorphismPropName too 
		if ($this->polymorphismPropName) { //polymorphism, include parent tablenames first
			$parentDesc = $this->getParentclassDescriptor();
			$parentDesc->initTableMap($anArray);
		}
		if ( !isSet($anArray[$this->tableName]) )
			$anArray[$this->tableName] = $this->name; //add own tablename only if it was not already added by parent
	}
	
	/** Return an array with the tablenames as keys, in parent first order
	*/
	function getTableMap()
	{
		if (!isSet($this->tableMap)) {
			$this->tableMap = array();
			$this->initTableMap($this->tableMap);
		}
		return $this->tableMap;
	}

	/** Returns the field to column mapping for the described class.
	* For polymorphic persistency a single peanut can be mapped to several tables.
	* This funtion returns a single fieldmap for all tables.
	* When loading several tables may be JOINed by the database. Eventually
	* missing data is loaded using an extra query. @see _getDescribedClassInstanceForData
	* 
	* Because a fields persistence can be PNT_READ_ONLY, the map for
	* loading the object is not necessarily the same as the map for saving.
	* This method returns the fieldmap for loading, this includes fields whose 
	* propertyDescriptors' persistent === PNT_READ_ONLY
	*
	* ! There may still be methods that are not supporting fieldmapping
	* ! Returns reference to cached Array, allways reset before using forEach or while each
	* @return columnNameMapping Associative Array with field names as the keys and (unprefixed) column names as the values
	*/
	function getFieldMap() {
		if ($this->fieldMap !== null)
			return $this->fieldMap;

		$this->fieldMap = array();
		$props = $this->getPersistentFieldPropertyDescriptors();
		if (empty($props))
			return $this->fieldMap;

		reset($props);
		foreach ($props as $name => $prop) {
			$this->fieldMap[$prop->getName()] = $prop->getColumnName();
		}
		return $this->fieldMap;
	}

	/** Returns the field to column mapping for the described class.
	*
	* ! There may still be methods that do not yet support column mapping
	* ! Returns reference to cached Array, allways reset before using forEach or while each
	* @return columnNameMapping Associative Array with field names as the keys and (prefixed) column names as the values
	*/
	function getFieldMapPrefixed() {
		if ( isSet($this->fieldMapPrefixed) )
			return $this->fieldMapPrefixed;

		$this->fieldMapPrefixed = array();
		$props = $this->getPersistentFieldPropertyDescriptors();
		reset($props);
		foreach ($props as $name => $prop) {
			$this->fieldMapPrefixed[$prop->getName()] = $prop->getTableName(). '.'. $prop->getColumnName();
		}
		return $this->fieldMapPrefixed;
	}

	/** For polymorphic persistency a single peanut can be mapped to several tables.
	* This funtion returns a specific fiedMap for each table
	* Because a fields persistence can be PNT_READ_ONLY, the map for
	* loading the object is not necessarily the same as the map for saving.
	* This method returns the fieldmap for saving, this excludes fields whose 
	* propertyDescriptors' persistent === PNT_READ_ONLY
	* @param String $tableName the name of the table to get the map for
	* @return array fieldmap for saving, with fieldNames as keys and columnNames as values
	*/
	function getFieldMapForTable($tableName) {
		$fieldMap = array();
		$props = $this->getPersistentFieldPropertyDescriptors();
		if (empty($props))
			return $fieldMap;

		reset($props);
		foreach ($props as $name => $prop) {
			if (($prop->getTableName() == $tableName || $prop->getName() == 'id')
					&& ($prop->getPersistent() !== PNT_READ_ONLY))
				$fieldMap[$prop->getName()] = $prop->getColumnName();
		}
		return $fieldMap;
	}

	function getSelectQueryHandler($distinct=true) {
		return $this->getSelectQueryHandlerFor(
			$this->getTableName()
			, $this->getTableMap()
			, $this->getFieldMapPrefixed()
			, $distinct
			);
	}

	function getSelectQueryHandlerFor($tableName, $tableMap, $fieldMapPrefixed, $distinct=true) {
		$qh = $this->getSimpleQueryHandler();
		$qh->select_from($fieldMapPrefixed, $tableName, $distinct); // NB, distinct gives trouble with sort by columnt not in the SELECT
		if ($this->polymorphismPropName) 
			$qh->joinAllById($tableMap, $tableName);

		return $qh;
	}
	
	function getSimpleQueryHandler() {	
		return pntCallStaticMethod($this->getName(), 'newQueryHandler');
	}


	/** The filters are be produced by static getFilter() on the class.
	* The default for that static is to call back getDefaultFilters().
	* it should add a filter for the label if required.
	* Override the static to get different filters.
	* @return Array of PntSqlFilter the filters by which instances can be searched for
	*/
	function getFilters($depth) {
		$clsName = $this->getName();
		return pntCallStaticMethod($clsName, 'getFilters', $clsName, $depth); //className argument necessary for callback
	}

	function getAllFieldsFilter(&$filters, $type)
	{
		Gen::includeClass('PntSqlCombiFilter', 'pnt/db/query');
		$filter = new PntSqlCombiFilter();
		$filter->set('combinator', 'OR');
		$filter->set('key', "All $type".'fields');
		$filter->set('itemType', $this->getName());
		$filter->set('valueType', $type);
		foreach ($filters as $key => $part)
			if (!$type || $part->getValueType() == $type)
				$filter->addPart($part);

		return $filter;
	}

	/**
	* @return Array of PntSqlFilter filters derived from metadata
	*/
	function getFieldFilters() {
		$result = array();
		Gen::includeClass('PntSqlFilter', 'pnt/db/query');

		$props = $this->getPersistentFieldPropertyDescriptors();
		foreach ($props as $name => $prop) {
			if ($prop->getVisible()) {
				$filter = new PntSqlFilter();
				$filter->set('key', $name);
				$filter->set('itemType', $this->getName());
				$filter->set('propLabel', $prop->getLabel());
				$filter->set('valueType', $prop->getType());
				$result[$name] = $filter;
			}
		}
		return $result;
	}

	/**
	* @return Array of PntSqlFilter filters derived from metadata
	*/
	function getDefaultFilters($depth) {
		$result = $this->getFieldFilters();

		if ($depth < 2) return $result;

		$props = $this->getPersistentRelationPropertyDescriptors();
		foreach ($props as $name => $prop) {
			if (!$prop->isMultiValue())
				$this->addReferenceFilters($result, $prop, $depth);
		}
		
		$sort = $this->getLabelSort();
		$filters = $sort->getSortSpecFilters();
		forEach($filters as $filter) {
			$key = $filter->getId();
			if (!isSet($result[$key]))
				$result[$key] = $filter;
		}
		return $result;
	}

	function addReferenceFilters(&$result, $prop, $depth) {
		Gen::includeClass('PntSqlJoinFilter', 'pnt/db/query');
		$filter = PntSqlFilter::getInstance($this->getName(), $prop->getName());
		
		$relatedClsDesc = PntClassDescriptor::getInstance($prop->getType());
		$relatedFilters = $relatedClsDesc->getDefaultFilters($depth - 1);
		foreach ($relatedFilters as $key => $relatedFilter) {
			$copy = clone $filter;
			$copy->setNext($relatedFilter);
			$result[$copy->getId()] = $copy;
		}
	}

	function getLabelSort($filter=null) {
		if (!isSet($this->labelSort)) {
			$clsName = $this->getName();
			$this->labelSort = pntCallStaticMethod($clsName, 'getLabelSort', $clsName);
		}
		$result = clone $this->labelSort;
		$result->setFilter($filter);
		return $result;
	}
	
	/** @return boolean Wheather some of the multi value properties
	* onDelete = 'v' values recursively if onDelete = 'd'.
	*/
	function getVerifyOnDelete() {
		$props =& $this->refPropertyDescriptors();
		reset($props);
		foreach ($props as $name => $prop) {
			if ($prop->isMultiValue() && $prop->getVerifyOnDelete()) 
				return true;
		}
		return false;
	}

//---------- meta behavior --------------------------------------

	/** Returns an instance of the described class
    * or if polymorphismPropName is set, from class according to data
	* initialized from the data in the supplied associative array.
	* When loading several tables may have been JOINed by the database. Eventually
	* missing data is loaded using an extra query. In case of polymorphic retrieval 
	* that starts with a supertype, the mechanism is not efficient for the habit of 
	* MySQL not to include keys for null values: it causes en extra query to load 
	* 'missing' fields that are already queried for. It would be better to fetch the
	* data as an indexed array, than map the indexes directly to fields...
	* @return PntDbObject (new or from chache)
	* @throws PntError
	*/
	function getDescribedClassInstanceForData($assocArray, $delegator) {
		//polymorhism support: delegate to appropriate classDescriptor
		if (!$delegator && $this->polymorphismPropName && $assocArray[$this->polymorphismPropName] 
				&& $assocArray[$this->polymorphismPropName] != $this->getName() ) {
			if (!class_exists($assocArray[$this->polymorphismPropName])) {
				$this->checkPolyClassName($assocArray[$this->polymorphismPropName]); //throws PntError
				Gen::includeClass($assocArray[$this->polymorphismPropName], $this->getClassDir() ); //checked by checkPolyClassName
			}
			$clsDes = $this->getInstance($assocArray[$this->polymorphismPropName]);
			return $clsDes->getDescribedClassInstanceForData($assocArray, $this);
		}

		//create instance of described class and initialize it
		$map = $this->getFieldMap();
		if (isSet($map['id']) && isSet($this->peanutsById[$assocArray[$map['id']]])) {
			return $this->peanutsById[$assocArray[$map['id']]];
		} else {
			$toInstantiate = $this->getName();
			$peanut = new $toInstantiate();
			$missingFieldsMap =& $peanut->initFromData($assocArray, $map);
			if (count($missingFieldsMap) != 0)
				if ($delegator) {
					$result = $this->loadMissingFields($peanut, $assocArray['id'], $missingFieldsMap, $delegator);
					if ($result) return $result; //query error
				} else {
					$peanut->initMissingFields($missingFieldsMap);
				}
			$id = isSet($map['id']) ? $assocArray[$map['id']] : $peanut->getId();
			$this->peanutsById[$id] = $peanut;
			return $this->peanutsById[$id];
		}
	}
	
	/** Check wheather the class name is allowed for polymorphic persistency 
	 * @param string $className the class name
	 */
	function checkPolyClassName($className) {
		if (!isSet($this->polyClassesAllowed[$className])) 
			throw new PntReflectionError("Polymorphism class name not allowed: '$className'");
	}
	
	/** set the classes that are allowed for polymorphic persistency 
	 * @param array $map with the names of the allowed classes as keys and not-null values
	 * 		WARNING: keys with values like false are allowed too! 
	 */
	function setPolyClassesAllowed($map) {
		$this->polyClassesAllowed = $map;
	}
	
	/** Polymorphism support: $peanut has been initialized from data, but
	* some field values where not in the data. Probably the query the data 
	* was retrieved with, was created by a superclass' classDescriptor.
	* run another query to retrieve the missing data and initialize 
	* the peanut from it.
	* PRECONDITION: $delegator not null
	* @param PntDbObject The peanut that is being retrieved
	* @param integer $id The id of the object
	* @param Array $missingFieldsMap With names of missing fields as keys and columnNames as values
	* @param PntClassDescriptor $delegator The classDescriptor that issued the original query that resulted in delegation and missing fields
	* @throws PntError
	*/
	function loadMissingFields($peanut, $id, &$missingFieldsMap, $delegator) {
		//collect maps for missing tables and fields 
		$delegatorTableMap = $delegator->getTableMap();
		$ownTableMap = $this->getTableMap();
		$missingTableMap = array();
		$fieldMapPrefixed = array();
		forEach($missingFieldsMap as $field => $columnName) {
			$prop = $this->getPropertyDescriptor($field);
			$tableName = $prop->getTableName();
			if (!isSet($delegatorTableMap[$tableName]) ) {
				$missingTableMap[$tableName] = $ownTableMap[$tableName];
				$fieldMapPrefixed[$field] = $tableName. ".". $columnName;
			}
		}
		if (count($fieldMapPrefixed) == 0) return; //assume some of the fields that are normally retrieved by the delegator where left out deliberately

		//there still are fields to retrieve, build query
		$qh = $this->getSelectQueryHandlerFor($this->getTableName(), $missingTableMap, $fieldMapPrefixed);
		$qh->where_equals(key($missingTableMap).'.id', $id);

		$qh->runQuery();
		if ($qh->getError())
			throw new PntReflectionError($qh->getError(), $qh->getErrNo());
		$row = $qh->getAssocRow(); 
		$qh->release();
		if (!$row) 
			throw new PntReflectionError($this. "no rows when loading missing fields of: ". $peanut->getLabel());
		$peanut->initMissingFields($peanut->initFromData($row, $missingFieldsMap));
	}
	
	/** Register that a peanut has been deleted.
	* The peanut must be removed from the cache.
	* @param integer $id the id of the deleted peanut
	*/
	function peanutDeleted($id) {
		unSet($this->peanutsById[$id]);
	}
	
	/** Register that a peanut has been insetred.
	* The peanut must be addes to the cache.
	* @param PntDbObject $peanut The peanut that has been inserted
	*/
	function peanutInserted($peanut) {
		$this->peanutsById[$peanut->get('id')] = $peanut;
	}

	function peanutCached($id) {
		return isSet($this->peanutsById[$id]) ? $this->peanutsById[$id] : null;
	}
	
	/** Returns instances of the described class
	* initialized from the supplied PntQueryHandler
	* @return Array
	* @throws PntError
	*/
	function getPeanutsRunQueryHandler($qh, $sortPath=null) {
		$qh->runQuery();
		$result = array();
		while ($row=$qh->getAssocRow()) {
			$instance = $this->getDescribedClassInstanceForData($row, null);
			if (Gen::is_a($instance, 'PntError')) {
				$qh->release();
				throw $instance;
			}
			$result[] = $instance;
		}
		$qh->release();
		if ($sortPath === null)
			return $result;

		$nav = PntNavigation::getInstance($sortPath, $this->getName());
		return PntNavigation::nav1Sort($result, $nav);
	}

	/** Returns all the instances of the described class
	* @return Array
	* @throws PntReflectionError
	*/
	function getPeanuts() {
		return $this->getPeanutsAccordingTo($this->getLabelSort());
	}

	/** Returns the instances of the described class with
	* the specfied property value to be equal to the specfied value
	* @param String propertyName
	* @param mixed value
	* @return Array
	* $throws PntError
	*/
	function getPeanutsWith($propertyName, $value) {
		$qh = $this->getSelectQueryHandler();

		$map = $this->getFieldMapPrefixed();
		if (!isSet($map[$propertyName]) )
			throw new PntReflectionError("$this Property not in fieldMap: $propertyName");  
	
		if ($propertyName == 'id') {
			$qh->where_equals($map[$propertyName], $value);
		} else {
			$sort = $this->getLabelSort();
			$extra = $sort->getExtraSelectExpressions();
			$qh->query = str_replace(' FROM ', $extra. ' FROM ', $qh->query);
			$qh->query .= $sort->getSqlForJoin();

			$qh->where_equals($map[$propertyName], $value);

			$qh->query .= ' ORDER BY '. $sort->getOrderBySql();
		}
		return $this->getPeanutsRunQueryHandler($qh);
	}

	/** Returns the instance of the described class with
	* the id to be equal to the specfied value, or null if none
	* @param integer id
	* @return PntObject
	* @throws PntEror
	*/
	function getPeanutWithId($id) {
		if (isSet($this->peanutsById[$id]))
			return $this->peanutsById[$id];

		return parent::getPeanutWithId($id);
	}

	/** Returns the instances of the described class according to the supplied specification
	* @param PntSqlSpec 
	* @return PntObject
	* @throws PntEror */
	function getPeanutsAccordingTo($spec) {
		$sort = Gen::is_a($spec, 'PntSqlSort')
			? $spec
			: $this->getLabelSort($spec);
		
		if ($spec->appliesTo($this->getName(), true)) {
			$qh = $this->getSelectQueryHandler();
			$qh->addSqlFromSpec($sort);
//print "\n<br>". $qh->query;
			return $this->getPeanutsRunQueryHandler($qh);
		}
		
		//NYI extract the persistent part of the filter, get peanuts according to that, possibly sorting
		//then filter the results.
		//if the database dit not sort, finally apply the sort
		return parent::getPeanutsAccordingTo($spec);
	}
	
	/** @return int the number of peanuts 
	 * @param PntSqlFilter $filter the filter to apply, or null if none
	 * 
	 */
	function getPeanutsCount($filter) {
		if ($filter && !$filter->appliesTo($this->getName(), true)) 
			//does not apply to persistency
			return count($this->getPeanutsAccordingTo($filter));

		$clsName = $this->getName();
		$qh = pntCallStaticMethod($clsName, 'newQueryHandler');
		$qh->select_from(
			array('count(*)')
			, $this->getTableName());
		if ($filter) 
			$qh->addSqlFromSpec($filter);
		return $qh->getSingleValue('', $error="Error retrieving number of rows");
	}
	
	/** @depricated */
	function _loadMissingFields($peanut, $id, &$missingFieldsMap, $delegator) {
		try {
			return $this->loadMissingFields($peanut, $id, $missingFieldsMap, $delegator);
		} catch (PntError $err) {
			return $err;
		}
	}

	/** @depricated */
	function _getPeanutsRunQueryHandler($qh, $sortPath=null) {
		try {
			return $this->getPeanutsRunQueryHandler($qh, $sortPath);
		} catch (PntError $err) {
			return $err;
		}
	}
	
	/** @depricated */
	function _getDescribedClassInstanceForData($assocArray, $delegator) {
		try {
			return $this->getDescribedClassInstanceForData($assocArray, $delegator);
		} catch (PntError $err) {
			return $err;
		}
	}
}
?>
