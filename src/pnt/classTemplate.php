<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

	
Gen::includeClass('PntObject', 'pnt');

/** This file serves as a template for creating new subclasses
* of PntObject. 
* @package pnt
*/
class PntObjectTemplate extends PntObject {

	/** Returns the classFolder
	* @static
	* @return String 
	*/
	static function getClassDir() {
		return 'pnt';
	}

	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();

		//$this->addFieldProp($name, $type, $readOnly=false, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null, $persistent=true) 
		//$this->addDerivedProp/addMultiValueProp($name, $type, $readOnly=true, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null) 

	}

	
}
?>