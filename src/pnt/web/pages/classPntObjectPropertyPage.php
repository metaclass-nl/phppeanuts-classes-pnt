<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObjectEditDetailsPage', 'pnt/web/pages');

/** Page showing a TablePart with the values of a multi value property.
* The property is specified by the pntProperty request parameter.
* Columns of the TablePart can be specified in metadata on the class
* specified by pntType request parameter, 
* @see http://www.phppeanuts.org/site/index_php/Pagina/61
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectPropertyPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/pages
*/
class PntObjectPropertyPage extends PntObjectEditDetailsPage {

	function getPropertyPartName() {
		return 'PropertyPart';
	}
	
	function getName() {
		$prop = $this->getPropertyDescriptor();
		return $prop ? ucfirst($prop->getLabel()) : $this->getReqParam('pntProperty');
	}

	/** @return string the label from which this can be recognized by the user */
	function getLabel() {
		return Gen::labelFrom($this->getRequestedObject())
			." - "
			.$this->getName();
	}

	function getPropertyDescriptor() {
		$part = $this->getPropertyPart();
		return $part->getPropertyDescriptor();
	}
	
	/** Check access to a $this with the SecurityManager. 
	* Forward to Access Denied errorPage and die if check returns an error message.
	*/
	function checkAccess() {
		$err = $this->controller->checkAccessHandler($this, 'ViewObject');
		if ($err) $this->controller->accessDenied($this, $err); //dies

		$prop = $this->getPropertyDescriptor();
		if ($prop) {
			Gen::includeClass($prop->getType(), $prop->getClassDir());
			$err = $this->controller->checkAccessHandler($this, 'ViewProperty', $prop);		
		} else {
			$err = 'No Property: '. $this->getReqParam('pntProperty');
		}
		if ($err) $this->controller->accessDenied($this, $err); //dies
	}

	function ajaxPrintUpdates($preFix='') {
		parent::ajaxPrintUpdates();
		$this->ajaxPrintPartUpdate('PropertyPart', $preFix. 'PropertyPart');
	}
		
	function printOnUnload() {
		//ignore
	}
	
	function printMainPart() {
		$this->printPart('PropertyPagePart');
	}

	function printInformationPart() {
		print $this->getInformation();
	}
	/** @return HTML String information for the end user */
	function getInformation() {
		$info = parent::getInformation();
		if ($info) return $info;
	
		if ($this->getRequestedObject()) {
			$part = $this->getPropertyPart();
			return count($part->getItems()). ' Item(s)';
		}

		return $this->getEventualItemNotFoundMessage();
	}
	function getObjectEditInfo() {
		return null;
	}
	
	/** @return string HTML describing the active filter.
	 * default the active filter is the first global filter that applies to the type of the page. */
	function getFilterPartString() {
		if (isSet($this->filterPartString)) return $this->filterPartString;

		Gen::includeClass('PntSqlFilter', 'pnt/db/query');
		$cnv = $this->getConverter();
		$part = $this->getPropertyPart();
		$filter = $this->controller->getGlobalFilterFor($part->getPropertyType(), false);
		$this->filterPartString = $filter
			? $cnv->toHtml($filter->getDescription($cnv)) : '';

		return $this->filterPartString;
	}
	
	
	function getNoItemsMarkedMessage() {
		return 'Please mark some items by clicking in the checkboxes in front of each row';
	}

	function printPropertyPart() {
		$part = $this->getPropertyPart();
		$part->printBody();
	}
	
	function getPropertyPart() {
		$name = $this->getPropertyName();
		$partName = $this->getPropertyPartName();
		$partName = subStr($partName, 0, strLen($partName)-4). ucFirst($name). 'Part';
		$part = $this->getPart(array($partName, $name));
		if (!$part) $part = $this->getPart(array($this->getPropertyPartName(), $name)); 
				
		return $part;
	}
	
	function getSubsaveActions() {
		$part = $this->getPropertyPart();
		return $part->getSubsaveActions();
	}
	
	function getButtonsList() {
		$actButs = array();
		$this->addNewButton($actButs);

		//we only want a delete button if values are dependents. 
		$part = $this->getPropertyPart();
		$prop = $part->getPropertyDescriptor();
		if ($prop->getHoldsDependents()) {
			$actButs[]= $this->getButton("Delete", "pntDeleteMarkedButtonPressed();");
		}
		//Not Yet Implemented: Remove button to remove items from the list without deleting them
		//if no idProperty the remove must be processed by a method analogous to with n to m properties
		//if idProptery can hold null, the remove could set the idPropterties of the selected objects to null and save them.
		//$actButs[]=$this->getButton("Remove","document.itemTableForm.submit();");
		
		$tableIdLit = $this->getConverter()->toJsLiteral($part->getItemTable()->getTableId(), "'");
		$actButs[]=$this->getButton('Report', "pntSelectionReport($tableIdLit);");

		$navButs = array();
		$this->addContextButtonTo($navButs);
		$this->addDetailsButton($navButs); 
		$this->addMultiValuePropertyButtons($navButs);

		return array($actButs, $navButs);
	}
	
	/** Add a 'New' button if appropriate. If there is no idProperty, 
	* new objects can not appear in the list after they have been added, 
	* so a button for adding a new object will only confuse the user (inappropriate)
	* @param array $buttons Reference to the array to add the button to
	* @return boolean Wheather the new button was added
	*/
	function addNewButton(&$buttons) {
		$part = $this->getPropertyPart();
		$url = $part->getNewButtonUrl(); 
		if (!$url) return false;

		$buttons[]= $this->getButton("New",	"document.location.href='$url';");
		return true;
	}

	function printDeleteMarkedCallback($callbackFuncHead) {
		print "
	$callbackFuncHead
			pntSubmitDeleteMarked();
	}";
	}
}
?>