<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObject', 'pnt');

/** Abstract superclass for identiefied options.  
* @see http://www.phppeanuts.org/site/index_php/Menu/218
* @abstract
* @package pnt
*/
class PntIdentifiedOption extends PntObject {

	public $id, $label;
	
	function __construct($id=null, $label=null) {
		parent::__construct();
		$this->id = $id;
		$this->label = $label;
	}

	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();

		$this->addFieldProp('id', 'number', false,null,null,0,'6,0');
		$this->addFieldProp('label', 'string');
	}

	function getLabel()
	{
		if ($this->label)
			return $this->label;

		return $this->get('id');
	}	
	
	/** Returns the instances by Id
	* @static
	* @abstract
	* @return Array of instances
	static function getInstances() {
		static $instances;
		if (!$instances) {
			//initialize instances here
		} else
			reset($instances);
			
		return $instances;
	}
	*/

}
?>