<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntEvaluation', 'pnt/meta');		
Gen::includeClass('PntObject', 'pnt');	
Gen::includeClass('PntNavigation', 'pnt/meta');

/** An object of this class represents a navigational step 
* starting from a logical instance of PntObject.  
* PntNavigations can be nested to create a navigational path.
* In many places in the user interface nopt only properties can be 
* specified, but also paths. This makes the user interface more flexible. 
* PntNavigations can execute the navigation, answering the value of the last property of the path.
* PntObjectNavigation also supports reasoning about navigations
* on a meta level, like getting the type of the results of navigating the entire path 
* @package pnt/meta
*/
class PntObjectNavigation extends PntNavigation {

	public $stepResultType;

	/** @throws PntReflectionError */
	static function getPropIncludeType($key, $itemType) {
		$clsDes = PntClassDescriptor::getInstance($itemType);
		$prop = $clsDes->getPropertyDescriptor($key);
		if (!$prop )
			throw new PntReflectionError("$itemType Property does not exist: $key");	
		$type = $prop->getType();
		Gen::tryIncludeClass($type, $prop->getClassDir()); //parameters from propertyDescriptor
		return $prop;
	}
	
	/** @throws PntReflectionError */
	function setNextPath($path) {
		$this->setNext(PntNavigation::getInstance($path, $this->getStepResultType()));
		return $this;
	}
	
	function newJoinFilter() {
		return new PntSqlJoinFilter();
	}
	
	/** Single value navigation from the argument using the key.
	* if the argument is null, return null
	* else, use propertyDescriptor to get next value.
	* @argument PntObject item from which to navigate
	* @return mixed result of the navigation step
	* @throws PntReflectionError
	*/
	function step($item) {
		if ($item === null)
			return null;
			
		if (!Gen::is_ofType($item, 'PntObject'))
			throw new PntReflectionError(
				$this->getLabel(). " can not navigate from item of unsupported type: ". Gen::toString($item)
			);
		
		$clsDesc = $item->getClassDescriptor();
		$prop = $item->getPropertyDescriptor($this->getKey());
		if (!$prop)
			throw new PntReflectionError(
				$this->getLabel(). " can not navigate because no properyDescriptor '". $this->getKey(). "' for: $item"
			);

		return $prop->getValueFor($item);	
	}
	
	/** @throws PntError */
	function getOptions($item) {
		if (!$item) { //may happen when AdvancedFilterFormPart gets options
			$class = $this->getItemType();
			$item = new $class();
		} elseIf (is_array($item))
			//? collect options of each of the items 
			throw new PntError('Not Yet Implemented');
		
		$next = $this->getNext();
		if (!$next) 
			return $this->getOptionsStep($item);
		
		$nextItem = $this->step($item);
		return $next->getOptions($nextItem);
	}

	/**
	 * Gets the options for the setted property
	 *
	 * @param PntObject $item
	 * @return PntObjectNavigation
	 * @throws PntError
	 */
	function getOptionsStep($item) {
		if (!Gen::is_a($item, 'PntObject'))
			throw new PntReflectionError(
				$this->getLabel(). " can not get options from item of unsupported type: ". Gen::toString($item)
			);
			
		$clsDesc = $item->getClassDescriptor();
		$prop = $item->getPropertyDescriptor($this->getKey());
		if (!$prop)
			throw new PntReflectionError(
				$this->getLabel(). " can not get options because no properyDescriptor for: $item"
			);

		return $prop->getOptionsFor($item);
	}

	/** Return the PropertyDescriptor of the setted property
	* This is the last property.
	* Remark: if the setted properties type is not a primitive type,
	* The user interface will actually set the idProperty of the setted property
	*/
	function getSettedProp() {
		$next = $this->getNext();
		if (!$next) return $this->getFirstProp();
		
		return $next->getSettedProp();		
	}

	/**
	 * @return PntNavigation to the value whose property will be set.
	 * this is the one but last property, or null if only one step.
	 */
	function getToSettedOn() {
		$prop = $this->getFirstProp();
		$next = $this->getNext();
		if (!$next || !$prop && $prop->isMultiValue()) return null;
		
		$result = clone($this);
		$result->popAfterSettedOn();
		return $result;
	}
	
	function popAfterSettedOn() {
		$prop = $this->getFirstProp();
		$next = $this->getNext();
		if (!$next || !$prop && $prop->isMultiValue()) return null; //nothing to pop off
		
		$popped = $next->popAfterSettedOn();
		if ($popped) return $popped;
		
		//this is the one but last, or multi value, pop off next and return it
		$this->next = null;
		return $next;
	}
	
	function setValue($item, $value) {
		$next = $this->getNext();
		return $next
			? $next->setValue($this->step($item), $value) 
			: $this->getFirstProp()->setValue_for($value, $item);
	}
	
	/** @return the one but last single value step result */
	function getItemToSetOn($item) {
		$prop = $this->getFirstProp();
		$next = $this->getNext();
		return $next && $prop && !$prop->isMultiValue()
			? $next->getItemToSetOn($this->step($item))
			: $item;
	}
	
	/* Return the type of the navigation result according to the metadata
	* If no metadata, return null
	* @result String @see PntPropertyDescriptor::getType
	*/
	function getResultType() {
					
		$next = $this->getNext();
		if ($next)
			return $next->getResultType();
		else
			return $this->getStepResultType();
	}

	function getFirstProp() {
		$clsDes = PntClassDescriptor::getInstance($this->getItemType());
		$prop = $clsDes->getPropertyDescriptor($this->getKey());
		return $prop;
	}

	/** @return wheather the entire path is single value */
	function isSingleValue() {
		$prop = $this->getFirstProp();
		if (!$prop ) return null;
		
		$next = $this->getNext();
		if (!$next) return !$prop->isMultiValue();
		
		return !$prop->isMultiValue() && $next->isSingleValue();
	}
	
	function getFirstPropertyLabel() {
		$prop = $this->getFirstProp();
		if (!$prop )
			return $this->getKey();
		
		return $prop->getLabel();
	}

	
	function getPathLabel() {
		$next = $this->getNext();
		if ($next)
			return $this->getFirstPropertyLabel().'.'.$next->getPathLabel();
		else
			return $this->getFirstPropertyLabel();
	}


	/** Returns the path to the id property
	* @return String
	*/
	function getIdPath() {
		$prop = $this->getSettedProp();
		$idProp = $prop->getIdPropertyDescriptor();
		if (!$idProp) return null;
		
		$toSettedOn = $this->getToSettedOn();
		$path = $toSettedOn ? $toSettedOn->getPath().'.' : '';		
		return $path.$idProp->getName();
	}
	
	function getResultClassDir() {  
		$prop = $this->getLastProp();
		return $prop->getClassDir();
	}
	
	function isSettedReadOnly() {
		$prop = $this->getSettedProp();
		return $prop->getReadOnly();
	}		
	
	function isSettedCompulsory() {
		$prop = $this->getSettedProp();
		return $prop->getCompulsory();
	}

	function getLastProp() {
		$next = $this->getNext();
		if ($next)
			return $next->getLastProp();
		
		$clsDes = PntClassDescriptor::getInstance($this->getItemType());
		return $clsDes->getPropertyDescriptor($this->getKey());
	}

	/** The paths that can be used to make the database sort by this
	* If not all but the last property is persistent, an empty array is returned.
	* Otherwise this' path except for the last step is concatenated with
	* the last properties' dbSortPaths.
	* @return Array of string Navigational paths
	*/
	function getDbSortPaths() {
		$prop = $this->getFirstProp();
		$next = $this->getNext();
		if (!$next) return $prop->getDbSortPaths();

		$paths = $next->getDbSortPaths();
		$result = array();
		if (!$prop->getPersistent()) return $result;
		
		forEach(array_keys($paths) as $key)
			$result[] = $this->getKey(). '.'. $paths[$key];
		return $result;
	}
	
	/** @return PntNavigation for the way back 
	 * Requires the properties of all steps to have twins
	 * Does not include filters that may be necessary because of polymorphisms */
	function getWayBack($nextBack=null) {
		$prop = $this->getFirstProp();
		$twin = $prop->getTwin();
//print "prop1: $prop twin: $twin<br>\n";
		
		if (!$twin) throw new PntReflectionError($prop->getLabel(). ' has no twin');
		$stepBack = PntNavigation::getInstance($twin->getName(), $prop->getType());
		if ($nextBack) $stepBack->setNext($nextBack);
		if (!isSet($this->next)) return $stepBack;
		
		return $this->next->getWayBack($stepBack);
	}
	
	/** Multi value navigation from the argument over a mixed path.
	* @argument array $argument holding instances of $this->itemType
	* @return array With the results of navigating
	* 	from each element collecting all the results in a single array
	* @throws PntError if an error occurs
	*/
	function collectAll($argument, $extra1=null, $extra2=null, $extra3=null) {
		$result = array();
		forEach(array_keys($argument) as $key) {
			$stepResult = $this->step($argument[$key]);
			if ($stepResult === null) continue; 
			if (is_array($stepResult))
				$result = array_merge($result, $stepResult);
			else 
				$result[] = $stepResult;
		}
		$next = $this->getNext();
		return $next 
			? $next->collectAll($result, $extra1, $extra2, $extra3)
			: $result;
	}

	function collectAllDistinct($argument, $extra1=null, $extra2=null, $extra3=null) {
		$found = $this->collectAll($argument, $extra1, $extra2, $extra3);
		return $this->distinct($found);
	}

	/** @depricated */
	function _getOptions($item) {
		try {
			return $this->getOptions($item);
		} catch (PntError $err) {
			return $err;
		}
	}

	/** @depricated */
	function _getOptionsStep($item) {
		try {
			return $this->getOptionsStep($item);
		} catch (PntError $err) {
			return $err;
		}
	}
	
}
?>
