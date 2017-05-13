<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntDbObject', 'pnt/db');

/** (correct this)
* @package pnt/db 
*/
class PntDbObjectTemplate extends PntDbObject {

	function __construct($id=null) {
		parent::__construct($id);
	}

	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();
		
		//the following adds a fieldProperty for each column in the database
		$this->addDbFieldProps(); 	//remove this line if you want to define properties manually
		
		//$this->addFieldProp($name, $type, $readOnly=false, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null, $persistent=true) 
		//$this->addDerivedProp/addMultiValueProp($name, $type, $readOnly=true, $minValue=null, $maxValue=null, $minLength=0, $maxLength=null, $classDir=null) 
	}

	/** @static 
	* @return String the name of the database table the instances are stored in
	* @abstract - override for each subclass
	*/
	static function getTableName() {
		return 'testdbobjects';
	}
	
	/** Returns the classFolder
	* @static
	* @return String 
	*/
	static function getClassDir() {
		return 'pnt/db';
	}
	
//remove the following if you do not want to override them
	
	/** String representation for representation in UI 
	* @return String 
	*/
	function getLabel() {
		return parent::getLabel();
	}

	/** Default implementation - to be overridden by subclasses that override getLabel()
	* @static 
	* @param string $itemType itemType for the sort (may be the sort will be for a subclass)
	* @return PntSqlSort that specifies the sql for sorting the instance records by label
	*/
	static function getLabelSort($subclass) {
		Gen::includeClass('PntSqlSort', 'pnt/db/query');
		$sort = new PntSqlSort('label', $subclass);
		$sort->addSortSpec('id');
		return $sort;
	}
	
}
?>