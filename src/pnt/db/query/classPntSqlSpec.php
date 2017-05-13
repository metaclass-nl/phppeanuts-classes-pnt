<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObject', 'pnt');
Gen::includeClass('QueryHandler');

/** Abstract superclass for parts for navigational query specification.
* @see http://www.phppeanuts.org/site/index_php/Pagina/170* 
* @package pnt/db/query
*/
class PntSqlSpec extends PntObject {
 
	public $label;
	public $propLabel;
	public $itemType;
	
	function __construct($id=null) {
		parent::__construct();
		$this->id = $id;
	}

	/** @static 
	* @return String the name of the database table the instances are stored in
	* @abstract - override for each subclass
	*/
	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();
		
		$this->addFieldProp('id', 'string', false, null, null, 0, null);
		$this->addFieldProp('propLabel', 'string', false, null, null, 0, null);
		$this->addFieldProp('label', 'string', false, null, null, 0, null);
		$this->addFieldProp('itemType', 'string', false, null, null, 0, null);

		$this->addDerivedProp('sqlForJoin', 'string', true, null, null, 0, null);
		$this->addDerivedProp('sql', 'string', true, null, null, 0, null);

		//$this->addFieldProp($name, $type, $readOnly=false, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null, $persistent=true) 
		//$this->addDerivedProp/addMultiValueProp($name, $type, $readOnly=true, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null) 
	}
	
	function getId() {
		return $this->id;
	}

	function getLabel() {
		if ($this->label)
			return $this->label;
		
		if ($propLabel = $this->get('propLabel')) 
			return $propLabel;
			
		return $this->getId();
	}
	
	function getQueryHandler() {
		if (!isSet($this->queryHandler)) {
			if ($this->getItemType()) {
				$clsDesc = PntClassDescriptor::getInstance(
					$this->getItemType()
				);
				$this->queryHandler = $clsDesc->getSelectQueryHandler();
			} else 
				$this->queryHandler = new QueryHandler();
		}
		return $this->queryHandler;
	}

	function getDescription($conv) {
		if (isSet($this->description)) return $this->description;
		
		return $this->getSql();
	}
	
	function setDescription($value) {
		$this->description = $value;
	}

	/** Return a piece of SQL for extending the FROM clause with the tables to be joined
	*/
	function getSqlForJoin() {
		$aliasCount = 0;
		$paramCount = 0;
		return $this->generateSqlForJoins($aliasCount, $paramCount);
	}
	
	function generateSqlForJoins(&$aliasCount, &$paramCount, $backTrack=array()) {
		return '';
	} 
	
	function isJoinFilter() {
		return false;
	}
	 
	/** (Navigational query DSL)
	 * @return the specified PntSqlFilter for >>itemType
	 * @param string $path the path to filter by
	 * @param string $comparatorId one id from PntComparator::getInstances
	 * @param mixed $value1 dynamically typed by >>valueType
	 * @param mixed $value2 dynamically typed by >>valueType
	 * @return PntSqlFilter $this
	 * @throws PntReflectionError 
	 */
	function where($path, $comparatorId=null, $value1=null, $value2=null) {
		return PntSqlFilter::getInstance($this->get('itemType'), $path, $comparatorId, $value1, $value2);
	}

	/** (Navigational query DSL)
	 * @param string $path the path to sort by
	 * @param string $direction 'ASC' or 'DESC'
	 * @return PntSqlSort for sorting the results of this 
	 * @throws PntReflectionError if the path does not exist from >>itemType
	 */
	function sortBy($path, $direction='ASC') {
		Gen::includeClass('PntSqlSort', 'pnt/db/query');
		$result = new PntSqlSort($this->get('id'), $this->get('itemType')); 
		$result->addSortSpec($path, $direction);
		return $result;
	}
	
	/** retrieves specified peanuts, 
	 * @return array of PntObject of >>itemType 
	* @throws PntEror if a database error occurs
	*/
	function retrieve() {
		$clsDes = PntClassDescriptor::getInstance($this->get('itemType'));
		return $clsDes->getPeanutsAccordingTo($this);
	}
	
	/** retrieves specified peanuts, 
	 * @return PntObject of >>itemType first peanut or null
	 * @throws PntEror if a database error occurs
	 */
	function retrieveFirst() {
		$found = $this->retrieve();
		return isSet($found[0]) ? $found[0] : null;
	}
	
	/** retrieves specified peanuts, 
	 * @return PntObject of >>itemType a single peanut or null
	 * @throws PntError if if more then one peanut was retrieved, or if a database error occurred
	 */
	function retrieveOne() {
		$found = $this->retrieve();
		if (count($found) > 1) throw new PntError('Only one '. $this->get('itemType'). ' expected, found: '. count($found) );
		return isSet($found[0]) ? $found[0] : null;
	}
}
?>