<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


/** Meant to repesent links in intermediate hypercode and pnt site page tekst
* [~refType:refKey|content] .
* @package pnt/web/helpers 
*/
class PntShortLink {

	/** Text repesentation of the shortlink */
	public $shortCut;
	public $refType;
	public $refKey;
	/** Start position of the shortlink in the string it was found in */
	public $position;
	public $content;
	/** reference to the referred object, may not be initialized */
	public $referred;
	/** Used to pass extra parameters, , may not be initialized */
	public $extraUrlParams;
	
	/* Searches for shortlink 
	* @param String $value to be searched
	* @param integer $startPos position in $value to start searching
	* @return instance representing first shortlink in $value 
	* at or after $startPos 
	*/
	static function searchIn_startingAt($value, $startPos) {
		$instance = new PntShortLink();
		$result = $instance->searchInit($value, $startPos);
		if ($result===true) return $instance;
		else return $result;
	}
	
	function searchInit($value, $startPos) {
        if ($value===null) return null;
		$this->position = strPos($value, '[~', $startPos);
		if ($this->position === false) return null;
		
		$shortCut = Gen::getSubstr($value, '[~', ']', $this->position);
		if ($shortCut === false) return "shortcut without end marker at ch: $this->position";
		
		$pieces = explode('|', $shortCut);
		if (!$pieces[0]) return "shortcut without reference at ch: $this->position";
		
		$refSepPos = strPos($pieces[0], ':');
		$this->refType = subStr($pieces[0], 0, $refSepPos);
		if (!$this->refType) return "reference without a type: $pieces[0] at ch: $this->position";
		$this->refKey = subStr($pieces[0], $refSepPos + 1, strLen($pieces[0]) - $refSepPos);
		if (!$this->refKey) return "reference without a key: $pieces[0] at ch: $this->position";

		if (isSet($pieces[1])) $this->content = $pieces[1];
		$this->shortCut = "[~$shortCut]";

		return true;
	}

	function getShortCut() {
		return $this->shortCut;
	}
	
	function getRefType() {
		return $this->refType;
	}

	function getRefKey() {
		return $this->refKey;
	}


	function getPosition() {
		return $this->position;
	}

	function getContent() {
		if (isSet($this->content))
			return $this->content;
			
		return $this->getRefKey();
	}

	function getReferred() {
		return $this->referred;
	}

	function setReferred($value) {
		$this->referred = $value;
	}
	
	function getExtraUrlParams() {
		return $this->extraUrlParams;
	}

	function setExtraUrlParams($value) {
		$this->extraUrlParams = $value;
	}
}
?>