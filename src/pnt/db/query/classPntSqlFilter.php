<?php
/* Copyright (c) MetaClass, 2003-2017

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntDbObject', 'pnt/db');
Gen::includeClass('PntSqlSpec', 'pnt/db/query');
Gen::includeClass('Comparator');
 
/** * PntSqlFilters specify (and produce) what comes after
* the WHERE clause to retrieve some objects
*
* Used by FilterFormPart in the advanced search.
* part for navigational query specification, part of PntSqlFilter
* @see http://www.phppeanuts.org/site/index_php/Pagina/170
* for navigation instances of a subclass also produce JOIN clauses to access related tables.
* Objects of this class produce an empty JOIN clause.
* Also used by other types of SqlFilter to produce
* more complicated WHERE expressions, JOIN and ORDER BY clauses
*
* Current version is MySQL specific. In future, all SQL generating methods should
* delegate to PntQueryHandler to support other databases
* @package pnt/db/query
*/
class PntSqlFilter extends PntSqlSpec {

	public $comparatorId = 0;
	public $valueType;
	public $tableAlias;
	public $itemTableName;
	public $key;
	public $value1; 
	public $value2;
	public $columnName;
	public $sqlTemplate;
	public $navigation;
	public $visible = true;
	
	/** @static
	 * @param string $itemType type of items to filter from/retrieve
	 * @param string $path the path to filter by
	 * @param string $comparatorId one id from PntComparator::getInstances
	 * @param mixed $value1 dynamically typed by >>valueType
	 * @param mixed $value2 dynamically typed by >>valueType
	 * @return PntSqlFilter as specified
	 * @throws PntReflectionError 
	*/
	static function getInstance($itemType, $path, $comparatorId=null, $value1=null, $value2=null) {
		$nav = PntNavigation::getInstance($path, $itemType);
		if (get_class($nav) == 'PntNavigation')
			throw new PntReflectionError('PntObjectNavigation required for SqlFilter on '. $path. ' from '. $itemType); 
		$result = PntSqlFilter::getInstanceForNav($nav);
		if ($comparatorId)
			$result->by($comparatorId, $value1, $value2);
		return $result;
	}

	/** @static
	* @param PntObjectNavigation $nav
	 * @return PntSqlFilter as specified
	 * @throws PntReflectionError 
	*/
	static function getInstanceForNav($nav) {
		$prevFilter = null;
		while ($nav) {
			$firstProp = $nav->getFirstProp();
			if ($firstProp->getIdPropertyDescriptor()) {
				$newFilter = $nav->newJoinFilter();
			} else {
				$newFilter = new PntSqlFilter();
				$newFilter->set('valueType', $nav->getResultType());
			}
			$newFilter->set('key', $nav->getKey());
			$newFilter->set('itemType', $nav->getItemType());
			$newFilter->set('propLabel', $nav->getFirstPropertyLabel());
			$newFilter->set('navigation', $nav);

			if (!isSet($result))
				$result = $newFilter;
			if ($prevFilter)
				$prevFilter->setNext($newFilter);
			$prevFilter = $newFilter;
			$nav = $nav->getNext();
		}
		return $result;
	}

	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();

		$this->addFieldProp('tableAlias', 'string', false, null, null, 0, null);
		$this->addFieldProp('itemTableName', 'string', false, null, null, 0, null);

		$this->addFieldProp('key', 'string', false, null, null, 0, null);
		$prop = $this->addFieldProp('navigation', 'PntNavigation', false);
		$prop->setPersistent(false);

		$this->addFieldProp('comparatorId', 'string', false, null, null, 0, null);
		$this->addDerivedProp('comparator', 'Comparator', false, null, null, 0, null);

		$this->addFieldProp('value1', 'string', false, null, null, 0, null);
		$this->addFieldProp('value2', 'string', false, null, null, 0, null);
		$this->addFieldProp('valueType', 'string', false, null, null, 0, null);

		$this->addFieldProp('columnName', 'string', false, null, null, 0, null);
		$this->addFieldProp('sqlTemplate', 'string', false, null, null, 0, null);
		
		//addFieldProp($name, $type, $readOnly=false, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null, $persistent=true)
		//addDerivedProp/addMultiValueProp($name, $type, $readOnly=true, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null)
	}

	function getId() {
		if (isSet($this->id)) return $this->id;

		return $this->getPath();
	}

	function getPath() {
		return $this->key;
	}

	function getItemType() {
		return $this->itemType;
	}

	function getPropLabel() {
		if (!$this->itemType) return $this->key;
		$clsDesc = PntClassDescriptor::getInstance($this->itemType);
		if (!$clsDesc) return $this->key;
		$prop = $clsDesc->getPropertyDescriptor($this->key);
		if (!$prop) return $this->key;
		return $prop->getLabel();
	}
	
	/** @result boolean wheather the filter applies to the itemType 
	 * @param string $type type name
	 * @param boolean wheather using ::getSql */
	function appliesTo($type, $persistent=false) {
		$clsDesc = PntClassDescriptor::getInstance($type);
		if (!$clsDesc) throw new PntReflectionError("No ClassDescriptor for '$type'");
		
		$prop = $clsDesc->getPropertyDescriptor($this->key);
		return $prop && (!$persistent || $prop->getPersistent());
	}
	
	/** the alias or tablename to be used as prefix with the columnName.
	* Set by previous JoinFilter */
	function getTableAlias() {
		//if no alias, use tableName
		//important: do not cache the tableAlias, otherwise getFieldMapPrefixed will no longer support polymorphism
		if ($this->tableAlias)
			return $this->tableAlias;
		else
			return $this->getItemTableName();
	}

	/** The name of the table the column is stored in.  Used for creating join condition.
	*	if the key and itemtype do not identify a persistent fieldProperty, this must be set explicitly
  	*/
	function getItemTableName() {
		if ($this->itemTableName)
			return $this->itemTableName;
	
		$clsDesc = PntClassDescriptor::getInstance($this->getItemType());
		$prop = $clsDesc->getPropertyDescriptor($this->key);
		//if (!$prop) throw new PntError("property '$this->key' missing on ".$this->getItemType());
		//if (!method_exists($prop, 'getTableName')) throw new PntError(Gen::toString($prop). ' has no method ::getTableName');
		return $prop->getTableName();
	}
	
	function getValueProp() {
		$nav = $this->getNavigation();
		if ($nav && method_exists($nav, 'getLastProp'))
			return $nav->getLastProp();
		return null;
	}

	function getValueType() {
		return $this->valueType;
	}
	
	function setComparatorId($value) {
		$this->comparatorId = $value;
	}

	function setValue1($value) {
		$this->value1 = $value;
	}

	function setValue2($value) {
		$this->value2 = $value;
	}
	
	/** (Navigational query DSL)
	 * Sets comparator and values to filter by
	 * @param string $comparatorId one id from PntComparator::getInstances
	 * @param mixed $value1 dynamically typed by >>valueType
	 * @param mixed $value2 dynamically typed by >>valueType
	 * @return PntSqlFilter $this
	 * @throws PntReflectionError 
	 */
	function by($comparatorId, $value1=null, $value2=null) {
		$valueType = $this->getValueType();
		if (!Comparator::getInstance($comparatorId, $valueType))
			throw new PntReflectionError("'$comparatorId' is not a valid comparator for valuetype '$valueType'");
		$this->setComparatorId($comparatorId);
		$this->setValue1($value1);
		$this->setValue2($value2);
		return $this;
	}

	/** (Navigational query DSL)
	 * @return PntSqlCombiFilter for AND this with the specified 
	 * @param string $path the path to AND filter by, or PntSqlFilter to AND filter by
	 * @param string $comparatorId one id from PntComparator::getInstances
	 * @param mixed $value1 dynamically typed by >>valueType
	 * @param mixed $value2 dynamically typed by >>valueType
	 * @throws PntReflectionError 
	 */
	function andWhere($path, $comparatorId=null, $value1=null, $value2=null) {
		Gen::includeClass('PntSqlCombiFilter', 'pnt/db/query');
		if (Gen::is_a($path, 'PntSqlFilter')) {
			$otherFilter = $path;
		} else {
			$otherFilter = PntSqlFilter::getInstance($this->getItemType(), $path);
			if ($comparatorId)
				$otherFilter->by($comparatorId, $value1, $value2);
		}
		$result = new PntSqlCombiFilter();
		$result->addPart($this);
		$result->addPart($otherFilter);
		return $result;
	}

	/** (Navigational query DSL)
	 * @return PntSqlCombiFilter for OR this with the specified 
	 * @param string $path the path to OR filter by, or PntSqlFilter to OR filter by
	 * @param string $comparatorId one id from PntComparator::getInstances
	 * @param mixed $value1 dynamically typed by >>valueType
	 * @param mixed $value2 dynamically typed by >>valueType
	 * @throws PntReflectionError 
	 */
	function orWhere($path, $comparatorId=null, $value1=null, $value2=null) {
		$result = $this->andWhere($path, $comparatorId, $value1, $value2);
		$result->set('combinator', 'OR');
		return $result;
	}
	
	/** (Navigational query DSL)
	 * @param string $path the path to sort by
	 * @param string $direction 'ASC' or 'DESC'
	 * @return PntSqlSort for sorting the results of this 
	 * @throws PntReflectionError if the path does not exist from >>itemType
	 */
	function sortBy($path, $direction='ASC') {
		Gen::includeClass('PntSqlSort', 'pnt/db/query');
		$result = new PntSqlSort($this->get('id'), $this->getItemType()); 
		$result->setFilter($this);
		$result->addSortSpec($path, $direction);
		return $result;
	}
	
	function getFieldMapPrefixed() {
		if (!$this->getItemType())
			trigger_error($this. ' No itemtype', E_USER_ERROR);	
		$clsDesc = PntClassDescriptor::getInstance(
			$this->getItemType()
		);
		if ($this->tableAlias) {
			$qh = $this->getQueryHandler();
			return $qh->prefixColumnNames(
				$clsDesc->getFieldMap(),
				$this->getTableAlias()
			);
		} else 
			//becuase of polymorhic retrieval the classdescriptor must provide the prefixes  
			return $clsDesc->getFieldMapPrefixed();
	}

	// if field not set, builds template from sqlForPath and comparator(Id)
	function getSqlTemplate() {
		if ($this->sqlTemplate) return $this->sqlTemplate;

		$template = '';
		$comp = $this->get('comparator');
		if (!$comp) throw new PntError('No comparator '. Gen::toString($this));
		$sqlOperator = $comp->getSqlOperator();
		if ($sqlOperator == '=' && $this->value1 === null)
			$sqlOperator = 'IS';
		if ($sqlOperator == '!=' && $this->value1 === null)
			$sqlOperator = 'IS NOT';
		if ($comp && ($comparatorPreceder = $comp->get('preceder')) )
			$template .= "$comparatorPreceder ";
		$template .= "(\$columnName $sqlOperator ?";
		if ($comp && ($comparatorAddition = $comp->get('addition')) )
			$template .= " $comparatorAddition ?";
		$template .= ")";

		return $template;
	}

	/** Add the parameter values from this to $qh
	 * @param PntDao $qh  */
	function addParamsTo($qh) {
		$comparator = $this->get('comparator');
        if ($comparator)
            $qh->param($comparator->sqlFromValue($this->value1));
		if ($comparator && $comparator->get('addition') )
			$qh->param($comparator
				? $comparator->sqlFromValue($this->value2)
				: $this->value2);
	}
	
	/* The prefixed column name */
	function getColumnName() {
		if ($this->columnName || ! $this->key)
			return $this->columnName;

		$map = $this->getFieldMapPrefixed();

		return $map[$this->key];
	}

	/* @return string with SQL what comes after the WHERE keyword
	 * Implementation is to return the sqlTempate merged with value1 and value2 converted to SQL
	 * 
	 * @param &$aliasCount int for counting the number of aliases used
	 * @param &$paramCount int (not used) for counting the number of parameters used
	 * @param $backTrack array with filters from which this has been called recursively
	*/
	function generateSql(&$aliasCount, &$paramCount, $backTrack=array()) {
		if (isSet($this->next))
			return $this->next->generateSql($aliasCount, $paramCount, $backTrack);
			
		return $this->getSql();
	}
	
	/* @return string with SQL what comes after the WHERE keyword
	 * Implementation is to return the sqlTempate merged with value1 and value2 converted to SQL
	 */
	function getSql() {
		$sql = $this->getSqlTemplate();
		$qh = $this->getQueryHandler();
		$comparator = $this->get('comparator');
		$columnName = $this->getColumnName();
		$sql = str_replace('$columnName', $columnName, $sql);
		$sql = str_replace(
			'$value1',
			$qh->convertConditionArgumentToSql($comparator
				? $comparator->sqlFromValue($this->value1)
				: $this->value1),
			$sql);
		$sql = str_replace(
			'$value2',
			$qh->convertConditionArgumentToSql($comparator
				? $comparator->sqlFromValue($this->value2)
				: $this->value2),
			$sql);
//print $sql;
		return $sql;
	}

	
	/** When querying for peanuts, JOINS may be made to search by colums related
	* by a 1 to m relationship. Without GROUP BY this will lead to a row for each
	* value found in each related column. In practice one wants each peanut only once.
	* By grouping by id only one row is returned for each column.
	* @return String complete GROUP BY clause
	* PRECONDITION: all columns in the FROM clause must contain the same value for each row
	* this condition will be met when only single value column path are used,
	* which is the case with the WHERE clause of PntDbClassDescriptor::getSelectQueryHandler
	*/
	function getSqlForGroupBy() {
		$groupBySql = $this->getGroupBySql();
		if (strLen($groupBySql) > 0)
			return " GROUP BY $groupBySql";
		else 
			return "";
	}
	
	function getGroupBySql() {
		if (isSet($this->groupBySql)) return $this->groupBySql;

		$clsDesc = PntClassDescriptor::getInstance($this->getItemType());
		$groupByField = $this->getGroupByField();
		$fieldMap = $clsDesc->getFieldMapPrefixed();
		return isSet($fieldMap[$groupByField]) ? $fieldMap[$groupByField] : "";
	}
	
	/** Returns the name of the field to use for GROUP BY, @see getSqlForGroupBy 
	*/
	function getGroupByField() {
		if (isSet($this->groupByField)) return $this->groupByField;
		return 'id';
	}

	/** Returns what comes after the WHERE clause up to but not including the LIMIT clause.
	* This includes eventual ORDER BY, GROUP BY and HAVING clauses
	*/
	function getSql_WhereToLimit($groupBy=true) {
		$result = $this->getSql();
		if ($groupBy)
			$result .= $this->getSqlForGroupBy();
		return $result;
	}

	function getExtraSelectExpressions() {
		return '';
	}
	
	function getDescription($conv) {
		if (isSet($this->description)) return $this->description;
		
		$this->initConverter($conv);
		$value1String = $conv->toLabel($this->get('value1'), $conv->type);
		$value2String = $conv->toLabel($this->get('value2'), $conv->type);
		$comp = $this->get('comparator');

		if (!$comp) return $this->getLabel();

		if ($this->get('comparatorId') == 'BETWEEN AND')
			return $value1String.' <= '
				. $this->getLabel().' <= '
				. $value2String;

		$result = $this->getLabel() .' '. $comp->getLabel();
		if (!$comp->comparesToNull())
			$result .= ' '. $value1String;
		return $result;
	}

	/** Initialize the converter
	* @param $conv PntStringConverter
	*/
	function initConverter($conv) {
		$prop = $this->getValueProp();
		if ($prop)
			$conv->initFromProp($prop);
		else
			$conv->type = $this->getValueType(); 
	}

	//had problems with serialize, so let's only serialize the data 
	function getPersistArray() {
		$result = array();
		$clsDes = $this->getClassDescriptor();
		$props = $clsDes->getSingleValuePropertyDescriptors();
		while (list($propName) = each($props))
			if ($props[$propName]->isFieldProperty() && $props[$propName]->getPersistent() 
					&& isSet($this->$propName))
				$result[$propName] = $this->$propName;
		$result['clsId'] = $clsDes->getName();
		return $result;
	}
	
	/** Create a new instance according to the supplied array
	 * @static
	 * PRECONDITION: the class of the filter has to be included
	 * @param array $array created by ::getPersistArray
	 * @return PntSqlFilter
	 * @throws PntReflectionError
	*/
	static function instanceFromPersistArray($array) {
		if (!is_subclassOr($array['clsId'], 'PntSqlFilter'))
			throw new PntReflectionError('not a subclass of PntSqlFilter: '. $array['clsId']);
		$result = new $array['clsId']();
		$result->initFromPersistArray($array);
		return $result;
	}

	/** @trhows PntReflectionError */
	function initFromPersistArray($array) {
		$clsDes = $this->getClassDescriptor();
		$props = $clsDes->getSingleValuePropertyDescriptors();
		while (list($propName) = each($props))
			if (isSet($array[$propName]) )
				$this->$propName = $array[$propName];

		if (!isSet($this->key)) return;
		
		$this->navigation = PntNavigation::getInstance($this->key, $this->itemType);
	}

	/** @throws PntError */
	function getNavigation() {
		if (!isSet($this->navigation) && isSet($this->key)) {
			$this->navigation = PntNavigation::getInstance($this->getPath(), $this->itemType);
		}
		return $this->navigation;
	}

	function getLast() {
		return $this;
	}

	function canBeSortSpec() {
		return true;
	}

	/** Normal filters do not apply global filters, but ValidVersionFilters do,
	 * unless this method is called with true as the argument
	 */ 
	function ignoreGlobalFilter($wheater) {
		//ignore
	}
	
	/* Return the result of evaluating the supplied object against this. 
   */
	function evaluate($item) {
		// this way of getting the value is inefficient and assumes pntObject 
		//shoud have a PntNavigation in a field 
		return $this->evaluateValue($item->get($this->key));
	}

	/** Return the result of comparing the supplied value to the vaules of this, using the comparator
	*/ 
	function evaluateValue($value) {
		if (!isSet($this->comparator) )
			$this->comparator = $this->get('comparator');
		return $this->comparator->evaluateValue_against($value, $this->value1, $this->value2);
	}
	
	/** Select objects from $array that match $this, leaving keys intact 
	*/
	function assocSelectFrom(&$array) {
			$result = array();
			forEach(array_keys($array) as $eachKey) {
				if ($this->evaluate($array[$eachKey]))
					$result[$eachKey] = $array[$eachKey];
			}
			return $result;
	}		

	/** Select objects from $array that match $this, renumbering the keys 
	*/
	function selectFrom(&$array) {
			$result = array();
			forEach(array_keys($array) as $eachKey) {
				if ($this->evaluate($array[$eachKey]))
					$result[] = $array[$eachKey];
			}
			return $result;
	}
		
	function compare($a, $b) {
		if (!$this->navigation->isSingleValue()) 
			throw new PntError('Can only compare over single value paths');
		$valueA = $this->navigation->evaluate($a);
		$valueB = $this->navigation->evaluate($b);
		if  ($valueA == $valueB) return 0;
		return $this->comparatorId === '>'
			? ($valueA > $valueB ? 1 : -1)
			: ($valueA > $valueB ? -1 : 1);
	}
	
	function __clone() {
		if (isSet($this->navigation)) 
			$this->navigation = clone $this->navigation;
	}
}
?>