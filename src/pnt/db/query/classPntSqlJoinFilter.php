<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntSqlFilter', 'pnt/db/query');

/** Specifies a navigation over a relationship, generates SQL.
* Used by FilterFormPart in the advanced search.
* part for navigational query specification, part of a PntSqlSpec
* @see http://www.phppeanuts.org/site/index_php/Pagina/170
*  
* PntSqlFilters produce what comes after the WHERE clause to retrieve
* some objects as well as a JOIN clause to access related tables.
* Objects of this class produce JOIN clauses.
* $this->next must be set to a PntSqlFilter that will provide
* the WHERE expression for searching the related table, 
* or to another PntSqlJoinFilter for another join.
*
* Current version is MySQL specific. In future, all SQL generating methods should 
* delegate to PntQueryHandler to support other databases
* Current implementation will AND the own where expression only if a sqlTemplate has been set.
* @package pnt/db/query
*/
class PntSqlJoinFilter extends PntSqlFilter {

	public $next = null;

	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();

		$this->addFieldProp('key', 'string', false, null, null, 0, null);
		
		//addFieldProp($name, $type, $readOnly=false, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null, $persistent=true) 
		//addDerivedProp/addMultiValueProp($name, $type, $readOnly=true, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null) 
	}

	/** clones the next filter */
	function __clone() {
		parent::__clone();
		if (isSet($this->next))
			$this->next = clone $this->next;
	}

	/* Return the result of evaluating the supplied object against this.
	* for multivalue property returns true if the property contains an item that matches $this->next 
   */
	function evaluate($item) {
		if (!$this->next) {
		 	$idProp = $this->getIdPropertyDescriptor();
		 	$value = $idProp ? $idProp->getValueFor($item) : $item;
			return $this->evaluateValue($value);
		}
		// this way of getting the value is inefficient and assumes pntObject 
		//shoud have a PntNavigation in a field
		$nextItem = $item->get($this->key);
		if ($this->valueType != 'Array' && is_array($nextItem)) 
			//to be replaced by more efficient detect method
			return count($this->next->selectFrom($nextItem)) != 0;
		else 
			return $this->next->evaluate($nextItem);
	}

	function getPath() {
		$result = $this->key;
		if ($this->next)
			$result .= '.'.$this->next->getId();
		return $result;
	}

	function getLabel() {
		if ($this->label)
			return $this->label;

		$result = $this->get('propLabel'); 
		
		if ($this->next)
			$result .= '.'.$this->next->getLabel();
		return $result;
	}
	
	/** true if column 'id' is on the aliased table  (relation is n to 1)
	* false if column 'id' is on the own table (relation is 1 to n)
	* The value of this property is calculated by getIdPropertyDescriptor 
	*/
	function getIdColumnIsOnAlias() {
		return $this->foreignKeyIsOnItemTable;
	}
	
	/** if columnName has been explicitly set, this property must be set too
    * (default is true).
    */
	function setIdColumnIsOnAlias($aBoolean) {
		$this->foreignKeyIsOnItemTable = $aBoolean;
	}
	
	//field must be set if navigation is not a valid PntObjectNavigation
	function getValueType() {
		if (isSet($this->next)) 
			return $this->next->getValueType();

		if (isSet($this->valueType)) return $this->valueType;
		
		$prop = $this->getValueProp();
		return $prop->getType();
	}
	
	function getValueProp() {
		if (isSet($this->next)) 
			return $this->next->getValueProp();

		$idProp = $this->getIdPropertyDescriptor();
		if ($idProp) return $idProp;
		
		return parent::getValueProp();
	}

	function getNext() {
		return $this->next;
	}
	
	/** Both this and next must apply 
	 * @result boolean wheather the filter applies to the itemType 
	 * @param string $type type name
	 * @param boolean wheather using ::getSql */
	function appliesTo($type, $persistent=false) {
		$clsDes = PntClassDescriptor::getInstance($this->itemType);
		$prop = $clsDes->getPropertyDescriptor($this->key);
		if (!$prop) return false;

		$applies = parent::appliesTo($type, $persistent);
		if (!$applies) {
			$idProp = $prop->getIdPropertyDescriptor();
			 $applies = $idProp && (!$persistent || $idProp->getPersistent());
		}
		if (!isSet($this->next)) return $applies;
		return $applies && $this->next->appliesTo($prop->getType(), $persistent);
	}
	
	function setNext($value) {
		$this->next = $value;
		if (isSet($this->navigation)) 
			$this->navigation->setNext($value ? $value->getNavigation() : null);
	}

	function setComparatorId($value) {
		$this->comparatorId = $value;
		if (isSet($this->next))
			$this->next->setComparatorId($value);
	}

	function setValue1($value) {
		$this->value1 = $value;
		if (isSet($this->next))
			$this->next->setValue1($value);
	}

	function setValue2($value) {
		$this->value2 = $value;
		if (isSet($this->next))
			$this->next->setValue2($value);
	}
	
	/** Normal filters do not apply global filters, but ValidVersionFilters do,
	 * unless this method is called with true as the argument
	 */ 
	function ignoreGlobalFilter($wheater) {
		if (isSet($this->next))
			$this->next->ignoreGlobalFilter($wheater);
	}

	function getColumnName() {
		if ($this->columnName) 
			return $this->columnName;
		
		$this->getIdPropertyDescriptor();
		$map = $this->getFieldMapPrefixed();
		
		//foreignKeyIsOnItemTable initialized by getIdPropertyDescriptor()
		return $map[$this->foreignKeyIsOnItemTable ? $this->idProp->getName() : 'id'];
	}

	function getIdPropertyDescriptor() {
		if (isSet($this->idProp)) return $this->idProp;
		
		$clsDes = PntClassDescriptor::getInstance($this->itemType);
		$prop = $clsDes->getPropertyDescriptor($this->key);
		
		$this->idProp = $prop->getIdPropertyDescriptor();
		if (!$this->idProp) throw new PntRefelctionError("no id property found for ". $prop->getLabel());
		
		$this->foreignKeyIsOnItemTable = !$prop->isMultiValue(); 
		return $this->idProp;
	}

	/** The name of the table, used for creating join condition by previous JoinFilter.
  	*/
	function getItemTableName() {
		if ($this->itemTableName) return $this->itemTableName;

		$clsDes = PntClassDescriptor::getInstance($this->itemType);
		$idProp = $this->getIdPropertyDescriptor(); //initializes foreignKeyIsOnItemTable
		if ($this->foreignKeyIsOnItemTable) {//singe value prop
			//previous join must join the table that holds $idProp
			return $this->itemTableName = $idProp->getTableName();
		} else {//multi value prop
			//all tables contain the id, return the default one 
			return $this->itemTableName = $clsDes->getTableName();
		}
	}

	/* Returns what comes after the WHERE keyword to retrieve the objects' data
	* the columnName is used for the join, so we assume that the own WHERE expression
	* is only to be used if a sqlTemplate has been set.
	* PRECONDDITION: initJoinData has been called
	* (getSqlForJoin calls generateSqlForJoins calls initJoinData)
	*/
	function getSql() {
		$idProp = $this->getIdPropertyDescriptor();
		$result =  $this->getExtraConditionSql();

		if (isSet($this->next)) {
				if ($result)
					$result .= ' AND ';
				$result .= $this->next->getSql();
		}

		if ($result) return $result;
		
		return parent::getSql();
	}

	function getExtraConditionSql() {
		return (isSet($this->sqlTemplate))
			? parent::getSql()
			: '';
	}
	
	/** Add the parameter values from this to $qh
	 * @param PntDao $qh  */
	function addParamsTo($qh) {
		$this->addExtraConditionParamsTo($qh);
		if (isSet($this->next)) 
			$this->next->addParamsTo($qh);
		else 
			parent::addParamsTo($qh);
	}
	
	/** Add the extra condition parameter values from this to $qh
	 * @param PntDao $qh  */
	function addExtraConditionParamsTo($qh) {
		if (isSet($this->sqlTemplate))
			parent::addParamsTo($qh);
		//else
		//default is to ignore
	}
	
	function generateSqlForJoins(&$aliasCount, &$paramCount, $backTrack=array()) {
		$this->initJoinData($aliasCount, $paramCount, $backTrack);
		
		$result = '';
		forEach ($this->joinData as $tableName => $joinData) {
			$result .= $this->getJoin($joinData);	
		}
		if (isSet($this->next)) 
			$result .= $this->next->generateSqlForJoins($aliasCount, $paramCount, $backTrack);
		return $result;
	} 
	
	function initJoinData(&$aliasCount, &$paramCount, &$backTrack) {
		$backTrack[] = $this; //may have to be done somewhere else
		$this->joinData = array();
		$idProp = $this->getIdPropertyDescriptor();

		if (!isSet($this->next))
			if ($this->foreignKeyIsOnItemTable) return;
			else $this->initDefaultNext();

		//$this->next now exists
		$this->nextClsDes = PntClassDescriptor::getInstance($this->next->getItemType());
		$prop = $this->nextClsDes->getPropertyDescriptor('id');
		$nextIdColumnName = $prop->getColumnName();

		$currentIdColumn = $this->getColumnName();
		if (!$this->foreignKeyIsOnItemTable) { 
			if ($idProp->getTableName() == $this->next->getItemTableName()) {
				$nextIdColumnName = $idProp->getColumnName();
			} else {
				$joinData = $this->initTableJoinData($aliasCount, $currentIdColumn, $idProp->getTableName(), $idProp->getColumnName(), $backTrack);
				$currentIdColumn = $joinData['nextTableAlias'].'.'.$nextIdColumnName; 
			}
		} 
		$this->initMoreJoinData($aliasCount, $currentIdColumn, $nextIdColumnName, $backTrack);
	}
	
	function initMoreJoinData(&$aliasCount, $currentIdColumn, $nextIdColumnName, $backTrack) {
		$joinData = $this->initTableJoinData($aliasCount, $currentIdColumn, $this->next->getItemTableName(), $nextIdColumnName, $backTrack);
		$this->next->set('tableAlias', $joinData['nextTableAlias']);
		return $joinData;
	}
	
	function &initTableJoinData(&$aliasCount, $currentIdColumn, $nextTableName, $nextIdColumnName, $backTrack) {
		$aliasCount++;
		$idProp = $this->getIdPropertyDescriptor();
		$names['currentIdColumn'] = $currentIdColumn;
		$names['nextTableName'] = $nextTableName;
		$names['nextTableAlias'] = 'AL_'.$aliasCount;
		$names['nextIdColumn'] = $names['nextTableAlias']. '.'. $nextIdColumnName;
		
		$this->joinData[$nextTableName] =& $names;
		return $names;
	}
	
	function getJoin($names, $left=true) {
		$left = $left ? ' LEFT' : '    ';
		return "\n$left JOIN $names[nextTableName] AS $names[nextTableAlias] ON $names[currentIdColumn] = $names[nextIdColumn]";
	}
	
	function getSortSelectExpr($sortByColumnName) {
		$first = current($this->joinData);
		$joins = '';
		$extraWhere = '';
		$filter = $this;
		while ($filter &&  $filter->isJoinFilter()) {
			forEach($filter->joinData as $names)
				if ($names != $first) 
					$joins .= $filter->getJoin($names, false);
			if ($filter->isPreferredFilter())
				$preferredFilter = $filter;
			if ($extraCond = $filter->getExtraConditionSql()) 
				$extraWhere .= "
					AND ($extraCond)";
			$filter = $filter->getNext();
		}
		if (!isSet($preferredFilter)) $preferredFilter = $this;
		return $preferredFilter->getSortSubSelect($first,  $this->getColumnName(), $sortByColumnName, $joins, $extraWhere);
	}
	
	/** Add the parameter values for the SortSelectExpression to $qh
	 * @param PntDao $qh  */
	function addSortSelectParamsTo($qh) {
		$filter = $this;
		while ($filter &&  $filter->isJoinFilter()) {
			$filter->addExtraConditionParamsTo($qh);
			$filter = $filter->getNext();
		}
	}
	
	function isPreferredFilter() {
		return false;
	}
		
	function getSortSubSelect($names, $nextSql, $sortByColumnName, $joins, $extraWhere) {
		return "(SELECT $sortByColumnName FROM $names[nextTableName] $names[nextTableAlias] $joins
	WHERE $names[nextIdColumn] = $nextSql $extraWhere LIMIT 1)";
	}
	
	function initDefaultNext() {
		//initialize default next to 'id'
		$nav = $this->getNavigation();
		$this->next = PntSqlFilter::getInstance($nav->getResultType(), 'id');
		$this->next->setComparatorId($this->comparatorId);
		$this->next->setValue1($this->value1);
		$this->next->setValue2($this->value2);
	}
	
	function getLast() {
		$next = $this->getNext();
		
		return $next ? $next->getLast() : $this;
	}
	
	function isJoinFilter() {
		return true;
	}
	
	//had problems with serialize, so let's only serialize the data 
	function getPersistArray() {
		$result = parent::getPersistArray();
		$result['next'] = $this->next->getPersistArray();
		return $result;
	}

	function initFromPersistArray($array) {
		parent::initFromPersistArray($array);
		$this->next = PntSqlFilter::instanceFromPersistArray($array['next']); 
		$this->navigation->setNext($this->next->navigation);
	}
	
	function getDescription($conv) {
		if (isSet($this->description)) return $this->description;
		
		$comp = $this->get('comparator');
		$last=$this->getLast();
		if (!$comp || !Gen::is_a($last, 'PntSqlJoinFilter') || !$this->get('value1')) 
			return parent::getDescription($conv);
		
		$peanut = $last->getValue1Peanut();
		return $this->getLabel()
			.' '. $comp->getLabel()
			.' '. ($peanut ? $peanut->getLabel() : '');
	}
	
	/** @precondition $this is last */
	function getValue1Peanut() {
		$nav = $this->getNavigation();
		$idProp = $this->getIdPropertyDescriptor();
		$clsDes = PntClassDescriptor::getInstance($nav->getResultType());
		return $clsDes->getPeanutWithId($this->get('value1'));
	}

	/* NOT USED
	 * @return string with SQL what comes after the WHERE keyword USING Sub Queries
	 * @param &$aliasCount int for counting the number of aliases used
	 * @param &$paramCount int (not used) for counting the number of parameters used
	 * @param $backTrack array with filters from which this has been called recursively
	*/
	function generateSql(&$aliasCount, &$paramCount, $backTrack=array()) {
		$this->getIdPropertyDescriptor();
		if (!isSet($this->next) && $this->foreignKeyIsOnItemTable) 
			return parent::generateSql($aliasCount, $paramCount, $backTrack);//" >>columnName = >>value1";

		$this->initJoinData($aliasCount, $paramCount, $backTrack);
		
		$result = $this->next->generateSql($aliasCount, $paramCount, $backTrack);
		
		$keys = array_keys($this->joinData);
		for ($i=count($keys)-1; $i>=0; $i--) {
			$tableName = $keys[$i];
			$result = $this->mergeTemplate($this->getSubQueryTemplate(), $this->joinData[$tableName],  $result);	
		}

		return $result;
	}
	/* NOT USED */
	function getSubQueryTemplate() {
		if (!isSet($this->next)) return parent::getSqlTemplate();
		
		return '$currentIdColumn IN 
		(SELECT $nextIdColumn FROM $nextTableName $nextTableAlias WHERE $nextSql)';
	}
	/* NOT USED */
	function mergeTemplate($sql, $names, $nextSql) {
		$sql = str_replace('$currentIdColumn', $names['currentIdColumn'], $sql);
		$sql = str_replace('$nextIdColumn', $names['nextIdColumn'], $sql);
		$sql = str_replace('$nextTableName', $names['nextTableName'], $sql);
		$sql = str_replace('$nextTableAlias', $names['nextTableAlias'], $sql);
		$sql = str_replace('$nextColumn', $names['nextColumn'], $sql);
		$sql = str_replace('$nextSql', $nextSql, $sql);
		return $sql;
	}
}
?>