<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObjectMtoNPropertyPage', 'pnt/web/pages');
Gen::includeClass('PntPoint', 'pnt/graphics');

/** Dialog for editing the value of an MtoNDialogWidget
* 
* This abstract superclass provides behavior for the concrete
* subclass ObjectMtoNDialog in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/dialogs
*/
class PntObjectMtoNDialog extends PntObjectMtoNPropertyPage {

	public $itdHeightSubst=108;//ItemTableDiv Height Substractor
	
// *****************  methods from PntDialog (may be adapted) *******************

	/** @static 
	 * @return the piece of the javascript that will be called by the dialog
	 * the piece must deliver the id in variable pId and the label in pLabel
	 * @param string $formKey must already be checkedAlpaNumeric!
	 */
	static function getReplyScriptPiece($formKey='') {
		return "func"."tion set$formKey(pId, pIgnoored, pLabel) {
		";
			
	}
	
	// Return a PntPont with minimum width and height
	static function getMinWindowSize() {
			$result = new PntPoint(800,450);
			return $result;
	}

	function getName() {
		return 'MtoNDialog';
	}

	/** Check access to a $this with the SecrurityManager. 
	* Forward to Access Denied errorPage and die if check returns an error message.
	*/
	function checkAccess() {
		$err = $this->controller->checkAccessHandler($this);
		if (!$err) {
			$sm = $this->controller->getSecurityManager();
			$err = $sm->checkSelectProperty(
				$this->getRequestedObject()
				, $this->getTypeClassDescriptor()
				, $this->getPropertyName() );
		}
		if ($err) $this->controller->accessDenied($this, $err); //dies
	}

	// use skinBody without menu
	function printBody() {
		print "<!-- ". $this->getInformation(). "-->";
		$this->includeSkin('DialogBody');
	}

	/** This method should take care of update of an existing 
	* dialog. The default is to update the enire dialog with
	* all subParts in a single update element. In practice this
	* is used to load the contents of an overlay.
	* No context scouting is done on AJAX requests.
	*/
	function ajaxPrintUpdates($preFix='') {
		$this->ajaxPrintPartUpdateHeader($this->getName(), $this->getReqParam('pntAjaxUpd'));
		$this->printBody();
		$this->ajaxPrintPartUpdateFooter();
	}

	function getButtonsList() {

		$actButs[] = $this->getOkButton();
		$actButs[] = $this->getNewItemButton();
		$actButs[] = $this->getButton("Cancel", "window.close();");
		$navButs=array();
		
		return array($actButs, $navButs);
	}

	function getOkButton() {
		return $this->getButton("OK", "saveAndClose()");
	}

	function printReturnFuncName() {
		print 'set'.$this->getPropertyName();
	}

//  *********** specific methods ************************

	/** @return string the label from which this can be recognized by the user */
	function getLabel() {
		$obj = $this->getRequestedObject();
		return ($obj ? $obj->getLabel(): $this->getTypeLabel())
			." - "
			.$this->getItemsInfo();
	}

	function getItemsInfo()	{
		$clsDes = $this->getTypeClassDescriptor();
		$prop = $clsDes->getPropertyDescriptor($this->getPropertyName());
		if (!$prop) trigger_error("Property missing: ". $this->getPropertyName(), E_USER_ERROR);
			
		return $prop->getLabel();
	}

	/* Print script for saving the EditDetailsForm. 
	* creates array pntMtoNPropsEdited in which MtoNPropertyParts should register
	* their propertynames and ItemTables' ids.
	* This script must be executed by the browser before scripts of 
	* MtoNPropertyParts are executed */
	function printSaveScript() {
		$propName = $this->getPropertyName();  //checkedAlphaNumeric
		$cnv = $this->getConverter();
		$labelItemSepLit = $cnv->toJsLiteral( $cnv->labelItemSeparator, "'");
		print "<script>
			var pntMtoNPropsEdited = new Array();
			func"."tion saveAndClose() {
				var i;
				for (i in pntMtoNPropsEdited) {
					if (pntMtoNPropsEdited[i][0] == '$propName')
						saveMtoNPropTableState(pntMtoNPropsEdited[i][1]);
				}
				window.close();
			}
			func"."tion saveMtoNPropTableState(tableId) {
				itemTable = getElement(tableId);
				tbody = getNodeByTagName(itemTable.childNodes, 'TBODY', 0);
				var ids = '';
				var labels = '';
				for (i=0; i<tbody.rows.length; i++) {
					if (i > 0) {
						ids = ids + ';';
						labels = labels + $labelItemSepLit;
					}
					ids = ids + tbody.rows[i].id;
					labels = labels + tbody.rows[i].cells[1].innerHTML;
				}
				window.opener.pntSetMtoNDialogResult(ids, '$propName', labels);
			} 
			</script>";
	}

}	
?>