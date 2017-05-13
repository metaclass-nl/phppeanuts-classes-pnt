<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPagePart', 'pnt/web/parts');

/** Does not print properties for which viewing is not authorized 
* @package pnt/web/parts
*/
class PntMultiPropsPart extends PntPagePart {

	/** @var boolean wheather to print icons for each propertyPart */
	public $printIcons = false;
	
	/** @var string the name of the class of the propertypart without the 'Part' ending, 
	 * 		used if the property name is no key of $specificPropertyPartTypes */ 
	public $defaultPropertyPartType = 'Property';
	
	/** @var array with property names as keys and as values the classes of the propertyparts without the 'Part' endings */
	public $specificPropertyPartTypes = array();
	
	/** @param boolean $value wheather to print icons for each propertyPart */
	function setPrintIcons($value) {
		$this->printIcons = $value;
	}
	
	/** @param string $value the name of the class of the propertypart without the 'Part' ending, 
	 * 		used if the property name is no key of $specificPropertyPartTypes */ 
	function setDefaultPropertyPartType($value) {
		$this->defaultPropertyPartType = $value;
	}
	
	/** Adds to specificPropertyPartTypes
	 * @param string $propName property name, the key under whick to add
	 * @param string $partName the classe of the propertyparts without the 'Part' ending */
	function addSpecificPropertyPartType($propName, $partName) {
		$this->specificPropertyPartTypes[$propName] = $partName;
	}
	
	//copied from PntRequestHandler
	function getType() {
		if (!isSet($this->requestData['pntType'])) return null;
		$type = $this->requestData['pntType'];
		return $this->checkAlphaNumeric($type);
	}
	
	//tdl for reports is printed by ReportPage
	function printBody() {
		$obj = $this->getRequestedObject();
		if ($obj === null) return;
		
		$clsDes = $obj->getClassDescriptor();
		$names = $this->getMultiPropNames();
		reset($names);
		$sm = $this->controller->getSecurityManager();
		while (list($key, $name) = each($names)) {
			$prop = $clsDes->getPropertyDescriptor($name);
			if ($prop === null)
				trigger_error("Property not found: $name", E_USER_ERROR);
			if ($sm->checkViewProperty($obj, $prop)) continue;
			
			$part=$this->getPropertyPart($name);
			$part->printBody();
		}
	}
	
	function getSubsaveActions() {
		$names = $this->getMultiPropNames();
		$result = array();
		while (list($key, $name) = each($names)) {
			$part=$this->getPropertyPart($name);
			$result = array_merge($result, $part->getSubsaveActions());
		}
		return $result;
	}
	
	/** Gets the propertyPart. As the name of the propertyPart the following is tried in this order:
	* - <partType><propertyName>Part
	* - <partType>Part
	* Where the partType may be a specific type name configured through ::addSpecificPropertyPartType
	* or the default partType from $this->defaultPropertyPartType, which by default is 'Property'.
	* This makes the default partNames compatible with the request dispatch for pntHandler=PropertyPage"
	* - Property<propertyName>Part
	* - PropertyPart.
	* Remember that the actual part class inclusion and instatiation by PntPage::getPart is tried like this:
	* - <pntType><partName> from application folder
	* - <pntType><partName> from classes folder
	* - <partName> from application folder
	* - <partName> from classes folder
	* @param String $name the name of the property
	* @return PntPagePart the part, from the cache if available, or newly included and instantiated
	*/
	function getPropertyPart($name) {
		$partType = $this->getPropertyPartType($name);
		
		//must be compatible with PntPropertyPage
		$part = $this->getPart(array($partType. $name. 'Part', $name), $name);
		if (!$part) $part = $this->getPart(array($partType. 'Part', $name), $name); 
		
		if (!$part) trigger_error("no $partType". 'Part', E_USER_ERROR);
		$part->printLabel = true;
		$part->printIcons = $this->printIcons;
		return $part;
	}
	
	/** @return the name of the class of the propertypart without the 'Part' ending
	 * @param string $propName the name of the property whose values will be shown by the propertypart. 
	 */
	function getPropertyPartType($propName) {
		if (isSet($this->specificPropertyPartTypes[$propName])) 
			return $this->specificPropertyPartTypes[$propName];
		
		return $this->defaultPropertyPartType;
	}

	/** Returns the names of the multi value properties to include in the report
	* in the right order. May be overridden by subclasses for specialized reports.
	*/
	function getMultiPropNames() {
		$obj = $this->getRequestedObject();
		$clsDes = $obj->getClassDescriptor();
		$multiProps = $clsDes->getMultiValuePropertyDescriptors();
		$excluded = $this->getExcludedMultiValuePropButtonKeys();
		$names = array();
		forEach(array_keys($multiProps) as $propName)
			if ($multiProps[$propName]->getVisible() && !isSet($excluded[$propName])) 
				$names[] = $propName;
		
		return $names;
	}

	function getButton($caption, $script, $ghost=false, $len=null) {	
		return $this->whole->getButton($caption, $script, $ghost, $len);
	}
	
	function isLayoutReport() {
		return $this->whole->isLayoutReport();
	}
}
?>