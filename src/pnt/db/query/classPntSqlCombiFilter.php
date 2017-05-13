<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntSqlFilter', 'pnt/db/query');

/** Specifies the combination of mutliple PntSqlFilters by AND or OR. 
* Used by FilterFormPart in the simple search.
* part for navigational query specification, part of a PntSqlSpec
* @see http://www.phppeanuts.org/site/index_php/Pagina/170
*
* PntSqlFilters produce what comes after the WHERE clause to retrieve
* some objects as well as a JOIN clause to access related tables.
* Objects of this class combine the JOIN clauses from multiple PntSqlFilters
* from $this->parts and combine their WHERE expressions using their combinator field
* (by defauilt 'AND').
* @see http://www.phppeanuts.org/site/index_php/Pagina/170
*
* Current version is MySQL specific. In future, all SQL generating methods should 
* delegate to PntQueryHandler to support other databases
* @package pnt/db/query
*/
class PntSqlCombiFilter extends PntSqlFilter {

	public $parts;
	public $combinator = 'AND';

	function __construct()
	{
		parent::__construct();
		$this->parts = array();
	}

	/** 
	* @return String the name of the database table the instances are stored in
	* @abstract - override for each subclass
	*/
	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();

		$this->addFieldProp('combinator', 'string');
		$this->addMultiValueProp('parts', 'PntSqlFilter', false);
		
		//addFieldProp($name, $type, $readOnly=false, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null, $persistent=true) 
		//addDerivedProp/addMultiValueProp($name, $type, $readOnly=true, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null) 
	}
	
	function addPart($value) {
		if (!$value) trigger_error('Geen part', E_USER_ERROR);
		$this->parts[] = $value;
	}

	/* recursively clones the parts */
	function __clone()
	{
		$parts = array();
		reset($this->parts);
		while(list($id) = each($this->parts))
			$parts[$id] = clone $this->parts[$id];
		$this->parts = $parts;
	}

	/** @depricated.
	* Returns a recursive copy of $this 
	*/
	function copy() {
		return clone $this; //already clones the parts
	}
	
	/** If $this->itemType not set, look for itemType on parts */
	function getItemType() {
		if ($this->itemType) return $this->itemType;
		$parts = $this->getParts();
		if ($parts === null) return null;
		while (list($key) = each($parts)) {
			$this->itemType = $parts[$key]->getItemType();
			if ($this->itemType) return $this->itemType;
		}
		return null;
		
	}
	
	function getParts() {
		return $this->parts;
	}

	/** All parts must apply 
	 * @result boolean wheather the filter applies to the itemType 
	 * @param string $type type name
	 * @param boolean wheather using ::getSql */
	function appliesTo($type, $persistent=false) {
		reset($this->parts);
		forEach($this->parts as $part)
			if (!$part->appliesTo($type, $persistent)) return false;
		return true;
	}
	
	function setItemType($value) {
		reset($this->parts);
		forEach($this->parts as $part)
			$part->set('itemType', $value);
	}
	
	function setTableAlias($value) {
		reset($this->parts);
		forEach($this->parts as $part)
			$part->set('tableAlias', $value);
	}
	
	function setComparatorId($value) {
		$this->comparatorId = $value;
		reset($this->parts);
		while (list($key) = each($this->parts))
			$this->parts[$key]->setComparatorId($value);
	}

	function setValue1($value) {
		$this->value1 = $value;
		reset($this->parts);
		while (list($key) = each($this->parts))
			$this->parts[$key]->setValue1($value);
	}

	function setValue2($value) {
		$this->value2 = $value;
		reset($this->parts);
		while (list($key) = each($this->parts))
			$this->parts[$key]->setValue2($value);		
	}

	/** (Navigational query DSL)
	 * @return PntSqlCombiFilter for AND this with the specified 
	 * @param string $path the path to filter by
	 * @param string $comparatorId one id from PntComparator::getInstances
	 * @param mixed $value1 dynamically typed by >>valueType
	 * @param mixed $value2 dynamically typed by >>valueType
	 * @throws PntReflectionError 
	 */
	function andWhere($path, $comparatorId=null, $value1=null, $value2=null) {
		return $this->combinator == 'AND'
			? $this->combi($path, $comparatorId, $value1, $value2)
			: parent::andWhere($path, $comparatorId, $value1, $value2);
	}

	/** (Navigational query DSL)
	 * @return PntSqlCombiFilter for OR this with the specified 
	 * @param string $path the path to filter by
	 * @param string $comparatorId one id from PntComparator::getInstances
	 * @param mixed $value1 dynamically typed by >>valueType
	 * @param mixed $value2 dynamically typed by >>valueType
	 * @throws PntReflectionError 
	 */
	function orWhere($path, $comparatorId=null, $value1=null, $value2=null) {
		return $this->combinator == 'OR'
			? $this->combi($path, $comparatorId, $value1, $value2)
			: parent::orWhere($path, $comparatorId, $value1, $value2);
	}

	/** (Navigational query DSL)
	 * @return PntSqlCombiFilter for extending this with the specified 
	 * @param string $path the path to filter by
	 * @param string $comparatorId one id from PntComparator::getInstances
	 * @param mixed $value1 dynamically typed by >>valueType
	 * @param mixed $value2 dynamically typed by >>valueType
	 * @throws PntReflectionError 
	 */
	function combi($path, $comparatorId=null, $value1=null, $value2=null) {
		if (Gen::is_a($path, 'PntSqlFilter')) {
			$otherFilter = $path;
		} else {
			$otherFilter = PntSqlFilter::getInstance($this->getItemType(), $path);
			if ($comparatorId)
				$otherFilter->by($comparatorId, $value1, $value2);
		}
		$this->addPart($otherFilter);
		return $this;
	}

	/* @return string with SQL what comes after the WHERE keyword using nested queries
	*/
	function getSql() {
		$result = '(';
		$combi = '';
		reset($this->parts);
		forEach($this->parts as $part) {
			$result .= $combi;
			$result .= $part->getSql();
			$combi = " $this->combinator ";
		}
		$result .= ')';
		return $result;
	}
	
	/** Add the parameter values from this to $qh
	 * @param PntDao $qh  */
	function addParamsTo($qh) {
		reset($this->parts);
		forEach($this->parts as $part) 
			$part->addParamsTo($qh);
	}
	
	/* NOT USED
	 * @return string with SQL what comes after the WHERE keyword using nested queries
	 * @param &$aliasCount int for counting the number of aliases used
	 * @param &$paramCount int (not used) for counting the number of parameters used
	 * @param $backTrack array with filters from which this has been called recursively
	*/
	function generateSql(&$aliasCount, &$paramCount, $backTrack=array()) {
		$backTrack[] = $this;
		$result = '(';
		$combi = '';
		reset($this->parts);
		forEach($this->parts as $part) {
			$result .= $combi;
			$result .= $part->generateSql($aliasCount, $paramCount, $backTrack);
			$combi = " $this->combinator ";
		}
		$result .= ')';
		return $result;
	}

	function generateSqlForJoins(&$aliasCount, &$paramCount, $backTrack=array()) {
		$result = '';
		reset($this->parts);
		forEach($this->parts as $part) 
			$result .= $part->generateSqlForJoins($aliasCount, $paramCount, $backTrack);
		return $result;
	}
	
	function getDescription($conv) {
		if (isSet($this->description)) return $this->description;
		
		$result = '';
		reset($this->parts);
		$combi = '';
		forEach($this->parts as $part) {
			$result .= $combi;
			$pd = $part->getDescription($conv);
			$result .= Gen::is_a($part, 'PntSqlCombiFilter') ? "($pd)" : $pd;
			$combi = " $this->combinator ";
		}
		return $result;
	}

	function canBeSortSpec() {
		return false;
	}

	/* Return the result of evaluating the supplied object against this. 
   */
	function evaluate($item) {
		$nParts = count($this->parts); 
		if ($nParts == 0)
			return null;
		$keys = array_keys($this->parts);
		$result = $this->parts[$keys[0] ]->evaluate($item);
		for ($i=1; $i<$nParts; $i++) 
			$result = $this->combine( $result, $this->parts[$keys[$i] ]->evaluate($item) );

		return $result;
	}
	
	function combine($bool1, $bool2) {
		if ($this->combinator == 'AND')
			return $bool1 && $bool2;
		else
			return $bool1 || $bool2;
	}
}
?>