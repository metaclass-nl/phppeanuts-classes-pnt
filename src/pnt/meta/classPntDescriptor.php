<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObject', 'pnt');

/** Abstract superclass of meta objects. 
* @package pnt/meta
*/
class PntDescriptor {

	public $name;
	public $label;

	function getName() {
		return $this->name;
	}
	
	function setName($aString) {
		$this->name = $aString;
	}

	function getLabel() {
		if (!isSet($this->label))
			return $this->getName();
			
		return $this->label;
	}
	
	function setLabel($aString) {
		//the label of a class can be changed, for example when the site uses a differen language
		$this->label = $aString;
	}

	/** @return String representation for debugging purposes */
	function __toString() {
		//combine class name and label
		$label = $this->getLabel();
		return get_class($this)."($label)";
    }

	/** @depricated */
	function toString() {
		return (string) $this;
	}
}
?>