<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('ObjectVerifyDeletePage');
Gen::includeClass('PntPoint', 'pnt/graphics');

/** Dialog for verifying a recursive delete op one or more objects.
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectDialog in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/dialogs
*/
class PntObjectVerifyDeleteDialog extends ObjectVerifyDeletePage {

	public $printValidFrom = false;
	
	/** @static 
	 * @return the piece of the javascript that will be called by the dialog
	 * @param string $formKey (ignoored)
	 */
	static function getReplyScriptPiece($formKey='') {
		return "function verifyDeleteCallback(firstOid) {
		";
			
	}

	static function getMinWindowSize() {
			return new PntPoint(800,450);
	}
	
	function printReturnFuncCall() {
		$firstOid = '';
		if (isSet($this->markedObjects[0])) 
			$firstOid =  $this->markedObjects[0]->getOid();
		$firstOid = $this->getConverter()->toJsLiteral($firstOid, "'");
		print "verifyDeleteCallback($firstOid)";
	}

	//copied from PntDialog
	function printBody() {
		$this->includeSkin('DialogBody');
	}
	
	function ajaxPrintUpdates($preFix='') {
		$this->ajaxPrintPartUpdateHeader($this->getName(), $this->getReqParam('pntAjaxUpd'));
		$this->printBody();
		$this->ajaxPrintPartUpdateFooter();
	}
	
	//returns Array of objects
	function getRequestedObject() {
		if (isSet($this->object)) return $this->object;
		
		$this->getDeleteErrorMessages();

		//if errors, stille needed to allow the SecurityManager to check viewing (errors about) the objects
		reset($this->markedObjects); 
		while (list($key) = each($this->markedObjects)) {
			$this->object[] = $this->markedObjects[$key];
			$this->markedObjects[$key]->addVerifyOnDeleteValues($this->object);
		}

		return $this->object;
	}
	
	function initMarkedObjects() {
		if (!isSet($this->markedObjects)) {
			$collector = $this->getMarkedItemsCollector();
			$this->markedObjects = $collector->getMarkedObjects($this->requestData);
		}
	}

	//we only need to override the filterPart printing, so we\
	//do not need a skin of our own. This gets the skin of IndexPage
	function printMainPart() {
		$this->printPart('IndexPart');
	}

	function hasFilterForm() {
		return false;
	}

	function printItemTablePart() {
		$this->printVerifyDeletePart();
	}

	function printDetailsFieldsHidden() {
		//ignore
	}
	
	/** 
	 * @return array of string errormessages from marked objects 
	 */
	function getDeleteErrorMessages() {
		if (isSet($this->errorMessages)) return $this->errorMessages;
		
		$this->errorMessages = array();
		$this->initMarkedObjects();
		reset($this->markedObjects);
		while (list($key) = each($this->markedObjects)) {
			$this->errorMessages = array_merge($this->errorMessages, $this->markedObjects[$key]->getDeleteErrorMessages());
		}
		return $this->errorMessages;
	}

	function printSelectScriptPart() {
		?>
			<script>
				function okButtonPressed() {
					if (pntOpenerLocation == window.opener.document.location) {
						window.opener.<?php $this->printReturnFuncCall() ?>;
					} else {
						alert(<?php print $this->getConverter()->toJsLiteral( $this->getOpenerLocationChangedMessage(), "'") ?>);
					}
					window.close();
				}
				function tdl(obj, itemId) {
					popUpWindowAutoSizePos(tdlGetHref(obj, itemId)+'&pntHandler=ReportPage');
				}
				function tdlGetHref(obj, itemId) {
					arr = itemId.split('*');
					link = '../'+arr[0]+'index.php?pntType='+encodeURIComponent(arr[1])+'&id='+encodeURIComponent(arr[2]);
					if (pntFootprintId != null)
						link = link + '&pntRef=' + pntFootprintId; //important because IE does not pass HTTP_REFERER from javascript
					return link;
				}				
				//store opener location at page load time
				var pntOpenerLocation = window.opener.document.location;
			</script>
		<?php
	}
	
	function getOpenerLocationChangedMessage() {
		return 'Can not delete because: the page from wich this dialog was openend has been left or closed';
	}
	
	function getOkButton() {
		if (count($this->getDeleteErrorMessages()) == 0)
			return $this->getButton("OK", "okButtonPressed();");
	}

	function getCancelButton() {
		return $this->getButton("Cancel", "window.close();");
	}
	

}	
?>