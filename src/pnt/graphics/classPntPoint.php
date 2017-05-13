<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


/** This class allows to be extended to support simple graphics calculations
* like vector addition, multiplication
* A Point is a value object, i.e. it should not be modified. 
* Instead, a new point will be created by calculating functions.
* @package pnt/graphics
*/
class PntPoint {
	
	public $x;
	public $y;
	
	function __construct($x, $y) {
		$this->x = $x;
		$this->y = $y;
	}
	
	static function fromLabel($value) {
		$pieces = explode(', ', $value);
		if (count($pieces) == 2) {//like 22.3, 33.8 or 22,3, 33,8
			$pieces[0] = str_replace(',', '.', $pieces[0]);
			$pieces[1] = str_replace(',', '.', $pieces[1]);
		} else { //like 22.3,33.8
			$pieces = explode(',', $value);
		}
		if (count($pieces) == 4) //like 22,7,44,3
			$pieces = array("$pieces[0].$pieces[1]", "$pieces[1].$pieces[2]");
			
		return new PntPoint($pieces[0], $pieces[1]);
	}
	
	function __toString() {
		$label = $this->getLabel();
		return "Point($label)";
	}
	
	function getLabel() {
		return "$this->x, $this->y";
	}
	
	
}