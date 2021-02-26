<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntEvaluation', 'pnt/meta');
Gen::includeClass('PntReflectionError', 'pnt/meta');

/** An object of this class represents a navigational step 
* starting from an object or an associative array.  
* PntNavigations can be nested to create a navigational path.
* In many places in the user interface nopt only properties can be 
* specified, but also paths. This makes the user interface more flexible. 
* PntNavigations can execute the navigation, answering the value of the 
* last property or associative key of the path.
* @package pnt/meta
*/
class PntNavigation extends PntEvaluation {

	public $itemType;
	public $key;
	public $next;
	public $getterName; //just a private cache

	/** get an instance of the proper subclass for 
	* navigating from the specified itemType over
	* the specified path
	*
	* @static
	* @param path String the navigation path with 
	*      property names or keys separated by dots
	* @param itemType String 'Array', 'List' or className
	* @return PntNavigation
	* @throws PntReflectionError
	*/   
	static function getInstance($path, $itemType=null) {
		$i = strpos($path, '.');
		if ($i===false) {
			$key = $path;
			$nextPath = null;
		} else {
			$key = substr($path,0,$i);
			$nextPath = substr($path,$i+1);
		}
		if ($itemType && is_subclassOr($itemType, 'PntObject')) {
			$result = pntCallStaticMethod($itemType, 'newNavigation', $key, $itemType);
		} elseif ($itemType == 'Array' || $itemType == null || class_exists($itemType)) {
			$result = new PntNavigation();
			$result->setItemType($itemType);
			$result->setKey($key);
		} else {
			throw new PntReflectionError(
				"$itemType>>$path unknown itemType" 
			);
		}
		if ($nextPath) $result->setNextPath($nextPath);
		return $result;
	}
	
	function getItemType() {
		return $this->itemType;
	}
	
	function setItemType($value) {
		$this->itemType = $value;
	}

	function getKey() {
		return $this->key;
	}
	
	function setKey($value) {
		$this->key = $value;
		$this->getterName = 'get'.$value;
	}

	function getNext() {
		return $this->next;
	}
	
	function setNext($value) {
		$this->next = $value;
	}
	
	/* Return the type of the result of navigating only this step.
	*    For PntObjectNavigation this is $this->getFirstProp()->getType()
	*    If no metadata, return null
	* @result String @see PntPropertyDescriptor::getType
	*/
	function getStepResultType() {
		return $this->stepResultType;
	}
	
	function setStepResultType($value) {
		$this->stepResultType = $value;
	}

	/** @trhows PntReflectionError 
	 * PRECONDITION: this is an instance of the right class */
	function setPath($path) {

		$i = strpos($path, '.');
		if ($i===false) {
			$this->setKey($path);
			return $this;
		}
		
		$this->setKey(substr($path,0,$i));
		return $this->setNextPath(substr($path,$i+1));
	}
	
	/** @trhows PntReflectionError */
	function setNextPath($path) {
		$next = PntNavigation::getInstance($path);

		$this->setNext($next);
		return $this;
	}
		
	/**
	 * @param int $length if positive, include the specified numer of steps,
	 * 			or all if the path is shorter
	 * 		if negative, exclude specified number of steps at the end,
	 * 			return null if this leaves no steps
	 * 		if not specified return the entire path
	 * @return string keys of the steps concatenated with '.' inbetween
	 */
	function getPath($length=null) {
		if ($length===null)
			$nextLength = null;
		else 
			$nextLength = $length > 0 ? $length-1 : $length+1;
		$next = $this->getNext();
		if ($length < 0) {
			if (!$next) return null; //no steps left
			if ($nextLength < 0) {
				$nextPath = $next->getPath($nextLength);
				if ($nextPath === null) return null; //no steps left on next

				return $this->getKey().'.'.$nextPath;
			}
		} else { //$length may be unspecified
			if ($next && $nextLength !== 0) 
				return $this->getKey().'.'.$next->getPath($nextLength);
		}
		return $this->getKey();
	}
	
	function getFirstPropertyLabel() 
	{
		// no metadata
		return $this->getKey();
	}
	
	function getPathLabel()
	{
		// no metadata
		return $this->getPath();
	}
	
	function getLabel() {
		return $this->getItemType().'>>'.$this->getPath();
	}

	function __clone() {
		if ($this->next)
			$this->next = clone($this->next);
	}
	
	function pop() {
		$next = $this->getNext();
		if (!$next) return null; //nothing to pop off
		
		$popped = $next->pop();
		if ($popped) return $popped;
		
		//this is the one but last, pop off next and return it
		$this->next = null;
		return $next;
	}
	
	/** Single value navigation from the argument using the key.
	* if the argument is null, return null
	* if the argument is an array, get the next value using the key,
	* else, if the argument is not an object, return an NntReflectionError
	* if the argument has a getter method for the key, get the next value 
	* using the getter, else get the field named like the key.
	* if next, return the result of _evaluat next with the next value.
	* @argument mixed $item Array or Object
	* @return mixed result of the navigation
	* @throws PntError
	*/
	function evaluate($item, $extra1=null, $extra2=null, $extra3=null) {
		if ($item === null) return null;
		
		$nextItem = $this->step($item);
		
		$next = $this->getNext();
		if ($next) 
			return $next->evaluate($nextItem, $extra1, $extra2, $extra3);
		else
			return $nextItem;
	}

	/** Multi value navigation from the argument over a single value path.
	* if the argument is null, return null
	* otherwise return an array with the results of navigating
	* from each element under the same keys as those of the elements in the argument. 
	* If an error occurs, exit returning the error 
	* @argument array $argument of mixed Array or Object
	* @return mixed result of the navigation
	* @throws PntError
	*/
	function collect($argument) {
		if ($argument === null) return null;
		$result = array();
		forEach(array_keys($argument) as $key) {
			$result[$key] = $this->evaluate($argument[$key]);
		}
		return $result;
	}
	
	/** Single value navigation step from the argument using the key.
	* if the argument is null, return null
	* if the argument is an array, get the next value using the key,
	* else, if the argument is not an object, return an NntReflectionError
	* if the argument has a getter method for the key, get the next value 
	* using the getter, else get the field named like the key.
	* @argument mixed $item Array or Object
	* @return mixed result of the navigation 
	* @throws PntError
	*/
	function step($item) {
		if ($item === null)
			return null;

		if (is_array($item))
			$nextItem = isSet($item[$this->getKey()]) ? $item[$this->getKey()] : null;
		elseif (is_object($item))
			if (method_exists($item, $this->getterName)) {
				$getter = $this->getterName;
				$nextItem = $item->$getter();
			} else {
				$field = $this->getKey();
				$nextItem = $item->$field;
			}
		else
			throw new PntReflectionError(
				$this->getLabel(). " can not navigate from item of unsupported type: $item"
			);
		return $nextItem;
	}
	
	/** Return an array with the elements from the supplied one 
	* sorted by the results of the supplied navigation.
	* and under-sorted by the keys from the supplied array ( this allows sub-sorting
	*  by first applying the second criterium and then again calling this method with the first)
	* Keys are not retained in the result array
	* Current implementation is case sentitive, this may change in future
	* @static
	* @param array array the array holding the elements to be sorted
	* @param $nav PntNavigation the navigation to sort by. ResultType must be primitive datatype
	* @param $ascending boolean wheather the sort order is ascending
	* @result Associative Array with keys for sorting and values from array param
	* @throws PntError
	*/
	static function nav1Sort(&$array, $nav, $ascending=true) {
		$result = PntNavigation::byNav1SortKey($array, $nav);
		
		//sort the result array by key
		if ($ascending)
			kSort($result);
		else
			krSort($result);
			
		return $result;
	}
	
	/** Return an array with the elements from the supplied one 
	* indexed by keys for sorting, i.e. from concatenating the results of the supplied navigation
	* with the original keys, both padded to the length of the longest. 
	* @static
	* @param array $array the array holding the elements to be sorted
	* @param $nav PntNavigation the navigation to use. ResultType must be primitive datatype
	* @result Associative Array with keys for sorting and values from array param
	* @throws PntError
	*/
	static function byNav1SortKey(&$array, $nav) {
		reset($array);
		//Calculate the maximal lenght of keys and navigation results.
		//Cache the navigation results (retrieving them can take considerable resources)
		$maxLength = 0;
		$maxKeyLength = 0;
		$navResults = array();
		foreach ($array as $key => $value) {
			try {
				$navResult = $nav->evaluate($value);
			} catch (PntError $err) {
				throw new PntReflectionError('nav1Sort can not retrieve sortKey', 0, $err);
			}
			if (is_object($navResult))
					throw new PntReflectionError('nav1Sort navigation result not a primitive datatype: '. Gen::toString($navResult));
			
			$maxLength = max($maxLength, strLen($navResult));
			$maxKeyLength = max($maxKeyLength, strLen($key));
			$navResults[$key] = $navResult;
		}
		// build array with keys by concatenating navigation results and original keys,
		// both paddes up to the length of the longest with spaces. Padd strings right and numbers left.
		$result = array();
		foreach ($navResults as $key => $navResult) {
			if (is_string($navResult))
				$just = STR_PAD_RIGHT;
			else
				$just = STR_PAD_LEFT;
			$sortKey = str_pad($navResult, $maxLength, ' ', $just);	
			
			if (is_string($key))
				$keyJust = STR_PAD_RIGHT; 
			else
				$keyJust = STR_PAD_LEFT;
			$sortKey .= str_pad($key, $maxKeyLength, '!', $keyJust);
			
			$result[$sortKey] =& $array[$key];
		}
		return $result;	
	}

	/** Multi value navigation from the argument using a path string
	* if the argument is null, return null
	* otherwise return an array with the results of navigating
	* from each element under the same keys as those of the elements in the argument. 
	* If an error occurs, trigger E_USER_WARNING and return false.
	* @static
	* @argument Array $array  
	* @path String $path the path to navigate 
	* @return variant result of the navigation 
	* @throws PntError
	*/
	static function collect_path($array, $path, $itemType=null) {
		$nav = PntNavigation::getInstance($path, $itemType);
		return $nav->collect($array);
	}

	/** @return array of PntObject, each only once, in last occurrence order
	 * @param array of PntObject $items
	 */
	static function distinct($items) {
		$result = array();
		$current = end($items);
		while ($current) {
			$key = is_object($current) 
				? method_exists($current, 'getOid')
					? $current->getOid()
					: Gen::toString($current)
				: $current;
			$result[$key] = $current;
			$current = prev($items);
		}
		return array_reverse($result);
	}
	
	/** @depricated */
	static function _getInstance($path, $itemType=null) {
		try {
			return PntNavigation::getInstance($path, $itemType);
		} catch (PntError $err) {
			return $err;
		}
	}
	
	/** @depricated */
	function _setPath($path) {
		try {
			return $this->setPath($path);
		} catch (PntError $err) {
			return $err;
		}
	}

	/** @depricated */
	function _step($item) {
		try {
			return $this->step($item);
		} catch (PntError $err) {
			return $err;
		}
	}

	/** @depricated */
	function _collect(&$argument) {
		try {
			return $this->collect($argument);
		} catch (PntError $err) {
			return $err;
		}
	}

	/** @depricated */
	static function _byNav1SortKey(&$array, $nav) {
		try {
			return PntNavigation::byNav1SortKey($array, $nav);
		} catch (PntError $err) {
			return $err;
		}
	}

	/** @depricated */
	static function _nav1Sort(&$array, $nav, $ascending=true) {
		try {
			return PntNavigation::nav1Sort($array, $nav, $ascending);
		} catch (PntError $err) {
			return $err;
		}
	}
	}

?>