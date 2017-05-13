<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPage', 'pnt/web/pages');

/** Abstract superclass of PageParts.
* Generates html for a part of a page.
* @see http://www.phppeanuts.org/site/index_php/Menu/242
* @package pnt/web/parts
*/
class PntPagePart extends PntPage {

	function printBody() {
		$this->includeSkin($this->getName());
	}
	
	/** This method should print the update for an existing 
	* page given the new requestData. 
	* The default is to update the enire part with
	* all subParts in a single update element.
	* This implementation only works when a direct AJAX request is made to the part.
	* This requires the part to work independently from a page. Currently most parts do not.
	* No context scouting is done on AJAX requests.
	*/
	function ajaxPrintUpdates($preFix='') {
		$this->ajaxPrintPartUpdate('Body', $preFix.'Body');
	}

	// returns an appropiate form value for pntHandler
	function getThisPntHandlerName() {
		return $this->whole->getThisPntHandlerName();
	}
	
	function getType() {
		return $this->whole->getType($this->getName() );
	}
	
	function getRequestedObject() {
		if (isSet($this->object)) return $this->object;
		return $this->whole->getRequestedObject($this);
	}
	
	function getFormTexts() {
		return  $this->whole->getFormTexts();
	}
	
	function getOwnFormTexts() {
		return parent::getFormTexts();
	}
	
	function getFootprintId() {
		return isSet($this->footprintId) 
			? $this->footprintId
			: $this->whole->getFootprintId();
	}
	
	function getExcludedMultiValuePropButtonKeys() {
		if (isSet($this->excludedMultiValuePropButtonKeys)) return $this->excludedMultiValuePropButtonKeys;
		return $this->whole->getExcludedMultiValuePropButtonKeys();
	}
}
?>