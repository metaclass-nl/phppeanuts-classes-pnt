<?php 
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

/** For indexing multiple values under a single key
* @package pnt
*/

class PntIndex {
	public $contentArrays;
	
	function __construct() {
		$this->contentArrays = array();
	}
	
	//removed: at_put
	
	function at_push($key, $value) {
		$this->makeKeyExist($key);
		$this->contentArrays[$key][] = $value;
	}
	
	function at_unhift($key, $value) {
		$this->makeKeyExist($key);
		array_unshift($this->contentArrays[$key], $value);
	}
	
	function includesKey($key) {
		return isSet($this->contentArrays[$key]) && (count($this->contentArrays[$key]) > 0);
	}
	
	function at($key) {
		if (!$this->includesKey($key)) return [];
		return $this->contentArrays[$key];
	}
	
	function firstAt($key) {
		if (!$this->includesKey($key)) return null;
		return $this->contentArrays[$key][0];
	}
	
	function lastAt($key) {
		if (!$this->includesKey($key)) return null;
		return $this->contentArrays[$key][count($this->contentArrays[$key])-1];
	}
	
	function shiftAt($key) {
		if (!$this->includesKey($key)) return null;
		return array_shift($this->contentArrays[$key]);
	}
	
	function popAt($key) {
		if (!$this->includesKey($key)) return null;
		return array_pop($this->contentArrays[$key]);
	}

	function includes($value) {
		forEach(array_keys($this->contentArrays) as $key) {
			if ($this->at_includes($key, $value)) return true;
		}
		return false;
	}
	
	function at_includes($key, $value) {
		$arr =& $this->contentArrays[$key];
		forEach(array_keys($arr) as $arrKey) {
			if ($value == $arr[$arrKey]) return true;
		}
		return false;
	}
	
	function asArray() {
		$result = array();
		forEach(array_keys($this->contentArrays) as $key) {
			$arr =& $this->contentArrays[$key];
			forEach(array_keys($arr) as $arrKey)
				$result[] = $arr[$arrKey];
		}
		return $result;
	}
	
	function keys() {
		return array_keys($this->contentArrays);
	}
	
	function ksort() {
		ksort($this->contentArrays);
	}
	
	function krSort() {
		krSort($this->contentArrays);
	}
	
	function count() {
		$resuls = 0;
		forEach($this->keys() as $key)
			$result += count($this->at($key));
		return $result;
	}
	
	function pack() {
		forEach (array_keys($this->contentArrays) as $key)
			if (empty($this->contentArrays[$key])) 
				unSet($this->contentArrays[$key]);
	}
	
	/** @private */
	function makeKeyExist($key) {
		if (!isSet($this->contentArrays[$key]))
			$this->contentArrays[$key] = array();
	}
	
}
?>
