<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


/** Helper class to collect marked items that where marked by the user
* in the ItemsTableForm of pages like ObjectIndexPage, ObjectPropertyPage and ObjectSearchpage
* @package pnt/web/helpers
*/
class PntMarkedItemsCollector {
	
	/** The pattern used to recognize marked item parameter keys */
	public $pattern = '*!@';
	
	/** Constructor. 
	* @param PntRequestHandler requestHandler
	*/
	function __construct($requestHandler) {
		$this->requestHandler = $requestHandler;
	}

	/** Gets the objects corresponding to the items 
	* Calls useClass method on $requestHandler, and getDomainDir
	* if $this->domainDir is not initialized
	* @param Array $requestData with data like $_REQUEST
	* @return Array of PntObject
	* @throws PntError
	*/
	function getMarkedObjects($requestData) {
		$markedObjects = array(); 
		$markedOids = $this->getMarkedOids($requestData);
		$cnv = $this->requestHandler->getConverter();
		
		while (list($key, $oid) = each($markedOids) ) {
			$pos = strPos($oid, '*');
			$clsName = subStr($oid, 0, $pos);
			$id = $cnv->fromRequestData( subStr($oid, $pos+1) );
			$this->requestHandler->useClass($clsName, $this->getDomainDir($clsName));
			$clsDes = PntClassDescriptor::getInstance($clsName);
			$cnv->initFromProp($clsDes->getPropertyDescriptor('id'));
			$obj = $clsDes->getPeanutWithId($cnv->fromLabel($id));
			if ($cnv->error) 
				throw new PntError("$oid conversion: ". $cnv->error);
			if ($obj)
				$markedObjects[] = $obj;
		}
		return $markedObjects;
	}
	
	function getDomainDir($type) {
		if (isSet($this->domainDir)) return $this->domainDir;
		
		//this does not work with polymorphism, 
		//in that case override PntSite::getDomainDir
		$clsDes = PntClassDescriptor::getInstance($this->requestHandler->getType());
		$props = $clsDes->getMultiValuePropertyDescriptors();
		forEach ($props as $prop)
			if ($prop->getType() == $type) return $prop->getClassDir();
			
		return $this->requestHandler->getDomainDir($type);
	}

	/** Gets the oids of the objects corresponding to the items that where marked by the user
	* in the ItemsTableForm of pages like ObjectIndexPage, ObjectPropertyPage and ObjectSearchpage
	* @param Array $requestData with data like $_REQUEST
	* @return Array of String the oids
	*/
	function getMarkedOids($requestData) {
		$result = array(); // php may crash if reference to unitialized var is returned
		$oidParams = $this->getMarkedItemParams($requestData);
		$patternLength = strLen($this->pattern);
		while (list($key) = each($oidParams))
			$result[] = substr($key, $patternLength, strLen($key) - 3);
		return $result;
	}

	/** Gets the request parameters of the items that where marked by the user
	* in the ItemsTableForm of pages like ObjectIndexPage, ObjectPropertyPage and ObjectSearchpage
	* @param Array $requestData with data like $_REQUEST
	* @return Array of String with data like $_REQUEST
	*/
	function getMarkedItemParams($requestData) {
		$result = array(); // php may crash if reference to unitialized var is returned
		reset($requestData);
		while (list($key, $value) = each($requestData))
			if (strPos($key, $this->pattern) === 0)
				$result[$key] = $value;
		return $result;
	}

}
?>