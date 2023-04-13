<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntIdentifiedOption', 'pnt');

/** Objects of this class describe a comparision.
* Used by FilterFormPart in the advanced search.
* part for navigational query specification, part of PntSqlFilter
* @see http://www.phppeanuts.org/site/index_php/Pagina/170
*
* Current version is MySQL specific. In future, all SQL generating methods should 
* delegate to PntQueryHandler to support other databases
*
* This abstract superclass provides behavior for the concrete
* subclass Comparator in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @package pnt/db/query
*/
class PntComparator extends PntIdentifiedOption {

	/** SECURITY WARNING: do not pass dynamic values to $id as it will be used in eval. 
	* IOW, if $id gets passed php-code, it will be executed possably leading to breach of security
	*/
	function __construct($id=null, $label=null, $sqlOperator=null, $addition=null, $preceder=null) {
		parent::__construct($id, $label);
		$this->sqlOperator = $sqlOperator;
		$this->addition = $addition;
		$this->preceder = $preceder;
	}

	/** 
	* @return String the name of the database table the instances are stored in
	* @abstract - override for each subclass
	*/
	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();

		$this->addFieldProp('id', 'string');
		$this->addFieldProp('sqlOperator', 'string');
		$this->addFieldProp('addition', 'string');
		$this->addFieldProp('preceder', 'string');
	}

	/** Returns the instances 
	* @static
	* @abstract
	* @return Array of instances
	*/
	static function getInstances($type=null, $compulsory=false) {
		$cache = Comparator::getCache();
		$voluntary = $compulsory ? array() : $cache[3] ;
		if (!$type || Comparator::isMagnitudeType($type)) {
			return array_merge($cache[0], $cache[1], $cache[2], $voluntary);
		} else {
			return array_merge($cache[0], $voluntary);
		}
	}
	static function getCache() {
		static $instCache;
		if (!$instCache) {
			//general instances at index 0
			$instCache[0]['='] = new PntComparator('=');
			$instCache[0]['!='] = new PntComparator('!=');
			//string instances at index 1
			$instCache[1]['LIKE'] = new PntComparator('LIKE', '~', 'LIKE');
			$instCache[1]['NOT LIKE'] = new PntComparator('NOT LIKE', '!~', 'LIKE', null, 'NOT');
			//magnitude instances at index 2
			$instCache[2]['>'] = new PntComparator('>');
			$instCache[2]['>='] = new PntComparator('>=');
			$instCache[2]['<'] = new PntComparator('<');
			$instCache[2]['<='] = new PntComparator('<=');
			$instCache[2]['BETWEEN AND'] = new PntComparator('BETWEEN AND', '<= <=', 'BETWEEN', 'AND');
			//voluntary at index 3
			$instCache[3]['IS NULL'] = new PntComparator('IS NULL', null, 'IS');
			$instCache[3]['NOT NULL'] = new PntComparator('NOT NULL', null, 'IS NOT');
		}
		return $instCache;
	}
	
	static function getInstance($comparatorId, $type=null, $compulsory=false) {
		$cache = Comparator::getCache();
		if (isSet($cache[0][$comparatorId])) return $cache[0][$comparatorId];

		if (!$type || Comparator::isMagnitudeType($type)) { 
			if (isSet($cache[1][$comparatorId])) 
				return $cache[1][$comparatorId];
			if (isSet($cache[2][$comparatorId])) 
				return $cache[2][$comparatorId];
		}
		if (!$compulsory && isSet($cache[3][$comparatorId]))
			return $cache[3][$comparatorId];
		return null;
	}
	
	/** Needs to be overridden if new maginitude types are added 
	* that are not in PntPropertyDescriptor::primitiveTypes
	* PRECONDITION: PntPropertyDescriptor has been included
	* @static
	* @return boolean wheather magnitude operators can be applied to the type 
	*/
	static function isMagnitudeType($type) {
		return $type != 'boolean'
			&& in_array($type, PntPropertyDescriptor::primitiveTypes());
	}

	// If there where subclasses for different comparators, this would be a polymorphism,
    // but  that would result in many extra subclasses to be parsed 
    // if this method is not heavily used, this case switch is probably more efficient  
	function evaluateValue_against($value, $value1, $value2) {
		if ($this->id == '=')
			return $value == $value1;
		if ($this->id == 'BETWEEN AND') 
			return $value >= $value1 && $value <= $value2;
		if ($this->id == 'IS NULL')
			return $value === null;  //SQL compatible null handling
		if ($this->id == 'NOT NULL')
			return $value !== null;  //SQL compatible null handling
		if ($this->id == 'LIKE')
			return trigger_error('PntComparator::evaluateValue_against Not yet implemented for LIKE', E_USER_ERROR);
			
		//security assessment: $value and $value2 are varables in the evald code, 
		//if they contain code, it does not get evaluated.
		//$this->id is set when from literal strings creating static instances.
		return eval("return \$value $this->id \$value1;"); 	//Warning about id added to contructor
	}

	function getSqlOperator() {
		if ($this->sqlOperator)
			return $this->sqlOperator;
			
		return $this->get('id');
	}
	
	function sqlFromValue($value) {
        if ($value===null)
            return null;
		if ($this->id == 'LIKE' || $this->id == 'NOT LIKE')
			return str_replace('*', '%', $value);
			
		if ($this->comparesToNull())
			return null;
			
		return $value;
	}
	
	function comparesToNull() {
		return $this->id == 'IS NULL' || $this->id == 'NOT NULL';
	}

}
?>