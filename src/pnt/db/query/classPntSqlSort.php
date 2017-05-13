<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntSqlSpec', 'pnt/db/query');
Gen::includeClass('PntSqlJoinFilter', 'pnt/db/query');

/** PntSqlSort specifies (and produces) what comes after the ORDER BY keywords
* Used by FilterFormPart and PntDbClassDescriptor.
* part for navigational query specification, part of a PntSqlSpec
* @see http://www.phppeanuts.org/site/index_php/Pagina/170
* @see http://www.phppeanuts.org/site/index_php/Pagina/70
* @package pnt/db/query
*/
class PntSqlSort extends PntSqlSpec {

	public $sortUsingJoins = true; //though depricated. call getExtraSelectExpressions to avoid JOINs
	
	function __construct($id=null, $itemType=null) {
		parent::__construct($id);
		$this->itemType = $itemType;
		$this->sortSpecFilters = array();
	}

	function initPropertyDescriptors() {
		// only to be called once

		parent::initPropertyDescriptors();

		$this->addFieldProp('filter', 'PntSqlFilter', false, null, null, 0, null);
		$this->addMultiValueProp('sortSpecFilters', 'PntSqlFilter', false, null, null, 0, null);

		//$this->addFieldProp($name, $type, $readOnly=false, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null, $persistent=true) 
		//$this->addDerivedProp/addMultiValueProp($name, $type, $readOnly=true, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null) 
	}

	static function over($className, $propName, $sort=null) {
		$clsDes = PntClassDescriptor::getInstance($className);
		$prop = $clsDes->getPropertyDescriptor($propName);
		if (!$prop) throw new PntReflectionError("Property '$className>>$propName' not found");
		return PntSqlSort::overProp($prop, $sort);
	}
	
	static function overProp($prop, $sort=null) {
		if (!$sort) {
			Gen::includeClass($prop->getType(), $prop->getClassDir());
			$typeClsDes = PntClassDescriptor::getInstance($prop->getType());
			$sort = $typeClsDes->getLabelSort();
		}
		$result = new PntSqlSort($sort->id, $prop->ownerName);
		forEach($sort->getSortNavs() as $nav) {
			 $propStep = PntNavigation::getInstance($prop->getName(), $prop->ownerName);
			 $propStep->setNext($nav);
			 $result->addSortSpecFilter(PntSqlFilter::getInstanceForNav($propStep));
		}
		return $result;
	}
	
	function getFilter() { 
		return $this->filter;
	}
	
	function setFilter($filter) {
		return $this->filter = $filter;
	}
	
	/** @see getSortDirection()
	*/
	function addSortSpecFilter($aPntSqlFilter) {
		$this->sortSpecFilters[$aPntSqlFilter->getId()] = $aPntSqlFilter;
	}
	
	//the array_merge will, instead of putting the new value in front,
	// move the old value there. 
	function addSortSpecFilterFirstOrMoveExistingFirst($aPntSqlFilter) {
		$elementArr[$aPntSqlFilter->getId()] = $aPntSqlFilter;
		$this->sortSpecFilters= array_merge($elementArr,$this->sortSpecFilters);
	}

	/** Adds a sort specification
	 * @param string $path the path to sort by
	 * @param string $direction 'ASC' or 'DESC'
	 * @throws PntReflectionError if the path does not exist from >>itemType
	 */
	function addSortSpec($path, $direction='ASC') {
		$filter = PntSqlFilter::getInstance($this->itemType, $path);
		$filter->comparatorId = ($direction == 'DESC' ? '>' : '<');
		$this->addSortSpecFilter($filter);
	}

	/** (Navigational query DSL)
	 * Adds a sort specification
	 * @param string $path the path to sort by
	 * @param string $direction 'ASC' or 'DESC'
	 * @return PntSqlSort this  
	 * @throws PntReflectionError if the path does not exist from >>itemType
	 */
	function sortBy($path, $direction='ASC') {
		$this->addSortSpec($path, $direction);
		return $this;
	}
	
	
	function appliesTo($type, $persistent=false) {
		if ($persistent && !is_subclassOr($type, 'PntDbObject'))
			return false;
		return is_subclassOr($type, $this->get('itemType'));
	}
	
	/** Get the name of the property that can be given a suggested value
	* when creating a new item. The suggested value may be the last search value
	*/
	function getNewItemPropName() {
		forEach(array_keys($this->sortSpecFilters) as $key) 
			if (!$this->sortSpecFilters[$key]->isJoinFilter())
				return $this->sortSpecFilters[$key]->getPath();
		return null;
	}

	function setTableAlias($alias) {
		if (isSet($this->filter))
			$this->filter->set('tableAlias', $alias);
		
		reset($this->sortSpecFilters);
		while (list($key) = each($this->sortSpecFilters)) 
			$this->sortSpecFilters[$key]->set('tableAlias', $alias);
	}
	
	/** For compatibilty with older versions
	* @return String getSql_WhereToLimit
	*/
	function getSql() {
		return $this->getSql_WhereToLimit(false);
	}
	
	/* Returns what comes after the WHERE clause up to but not including the LIMIT clause.
	* This includes eventual ORDER BY, GROUP BY and HAVING clauses
	*/
	function getSql_WhereToLimit($groupBy=true) {
		$result = '';
		$paramCount = 0;
		if (isSet($this->filter)) {
			$result = $this->filter->getSql();
			if ($groupBy)
				$result .= $this->filter->getSqlForGroupBy();
		} else {
			$result .= 'true';
		}
		$result .= "\n ORDER BY ";
		$result .= $this->getOrderBySql();
		return $result;
	}

	/** Add the parameter values from this to $qh
	 * @param PntDao $qh  */
	function addParamsTo($qh) {
		$this->addExtraSelectParamsTo($qh);
		if (isSet($this->filter)) 
			$this->filter->addParamsTo($qh);
	}
	
	/** Add the parameter values for the ExtraSelectExpressions to $qh
	 * @param PntDao $qh  */
	function addExtraSelectParamsTo($qh) {
		forEach($this->sortSpecFilters as $spec) 
			if ($spec->isJoinFilter()) 
				$spec->addSortSelectParamsTo($qh);
	}
	
	/** @return string SQL select expressions for sorting by
	 * this is to be called instead of the depricated ::getSqlForJoin
	 * and appended to the SELECT clause 
	 */
	function getExtraSelectExpressions() {
		$this->sortUsingJoins = false;
		$this->aliasCount = 0;
		$result = '';
		reset($this->sortSpecFilters);
		$count = 1;
		forEach($this->sortSpecFilters as $spec) 
			if ($spec->isJoinFilter()) {
				$result .= '
	, '. $this->getSelectExpression($spec, $count);
				$count ++;
			}
		return $result;
	}

	function getSelectExpression($filter, $count) {
		$paramCount = 0; //not used
		$lastFilter = $filter->getLast();
		$lastFilter->set('comparatorId', 'IS NOT NULL');
		$firstFilter = $filter;
		$backTrack=array();
		while ($filter && $filter->isJoinFilter()) {
			$filter->initJoinData($this->aliasCount, $paramCount, $backTrack);
			$filter = $filter->getNext();
		}
//Gen::show($firstFilter);
		return $firstFilter->getSortSelectExpr($lastFilter->getColumnName())
			. ' AS pntSort'.$count;

	}
	
	/** Return a piece of SQL for extending the FROM clause with the tables to be joined
	*/
	function getSqlForJoin() {
		$paramCount = 0;
		if ($this->sortUsingJoins) $this->aliasCount = 0;
		return $this->generateSqlForJoins($this->aliasCount, $paramCount);
	}
	
	function generateSqlForJoins(&$aliasCount, &$paramCount, $backTrack=array()) {
		$result = '';
		if ($this->sortUsingJoins)
			forEach($this->sortSpecFilters as $spec) 
				$result .= $spec->generateSqlForJoins($aliasCount, $paramCount, $backTrack);
		if (isSet($this->filter))
			$result .= $this->filter->generateSqlForJoins($aliasCount, $paramCount, $backTrack);
		return $result;		
	}
	
	/* Returns what comes after the ORDER BY keyword 
	 * if ($this->sortUsingJoins) the columnNames from the last specFilters are used,
	 * otherwise pntSort<count>
	*/
	function getOrderBySql() {
		$result = '';
		reset($this->sortSpecFilters);
		$comma = '';
		$count = 1;
		forEach($this->sortSpecFilters as $spec) {
			$result .= $comma;
			if ($this->sortUsingJoins || !$spec->isJoinFilter()) {
				$specLastFilter = $spec->getLast();
				$result .= $specLastFilter->getColumnName();
			} else {
				$result .= 'pntSort'.$count;
				$count++;
			}
			$result .= ' ';
			$result .= $this->getSortDirection($spec);
			$comma = ', ';
		}
		return $result;
	}
	
	/** Actually we abuse PntSqlFilters as sort specifiers. 
	* For the sort direction we use the comparatorId > as DESC, otherwise ASC
	*/
	function getSortDirection($sortSpecFilter) {
		$comp = $sortSpecFilter->get('comparatorId');
		if ($comp === '>')
			return 'DESC';
		else 
			return 'ASC';		
	}
	
	function getSortSpecFilters() {
		return $this->sortSpecFilters;
	}
	
	function getSortPaths() {
		$filters = $this->getSortSpecFilters();
		$result = array();
		forEach(array_keys($filters) as $key) 
			$result[] = $filters[$key]->getPath();
		return $result;
	}
	
	function getSortNavs() {
		$filters = $this->getSortSpecFilters();
		$result = array();
		forEach(array_keys($filters) as $key) 
			$result[] = $filters[$key]->navigation;
		return $result;
	}
	
	//NOT TESTED!!! DO NOT USE THIS
	function sorted_insert(&$someSortedObjects, $toInsert) {
		//initialize sortSpecFilters values
		forEach(array_keys($this->sortSpecFilters) as $filterKey) {
			$filter = $this->sortSpecFilters[$filterKey];
			$filter->setValue1( $toInsert->get($filter->key) );
		}
				
		$i = 0;
		$keys = array_keys($someSortedObjects);
		$met = false;
		while (!$met && $i < count($keys)) {
			forEach(array_keys($this->sortSpecFilters) as $filterKey) {
				$filter = $this->sortSpecFilters[$filterKey];
				$value = $someSortedObjects[ $keys[$i] ]->get($filter->key);
				if ($value != $filter->value1) {
					$met = $met || $this->sortSpecFilters[$filterKey]->evaluateValue($value);
					break;
				}
			}
			$i++;
		}
		 array_splice($someSortedObjects, $i, $i, $toInsert);
	}
	
	function sort($items) {
		reset($this->sortSpecFilters);
		forEach($this->sortSpecFilters as $filter) 
			$filter->getNavigation();
		return usort($items, array($this, 'compare'));
	}
	
	function asort($items) {
		reset($this->sortSpecFilters);
		forEach($this->sortSpecFilters as $filter) 
			$filter->getNavigation();
		return uasort($items, array($this, 'compare'));
	}
	
	function compare($a, $b) {
		reset($this->sortSpecFilters);
		forEach($this->sortSpecFilters as $filter) {
			$x = $filter->compare($a, $b);
			if ($x != 0) return $x; 
		}
		return 0;
	}
}
?>