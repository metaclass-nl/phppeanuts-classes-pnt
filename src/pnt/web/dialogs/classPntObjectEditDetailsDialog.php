<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObjectEditDetailsPage', 'pnt/web/pages');
Gen::includeClass('PntPoint', 'pnt/graphics');

/** Dialog to edit details of a single peanut.
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
class PntObjectEditDetailsDialog extends PntObjectEditDetailsPage {

// *****************  methods from PntDialog *******************

	/** @static 
	 * @return the piece of the javascript that will be called by the dialog
	 * the piece must deliver the id in variable pId and the label in pLabel
	 * @param string $formKey must alreade be checkedAlpaNumeric!
	 */
	static function getReplyScriptPiece($formKey='') {
		return "func"."tion pntSetDialogResult(pId, pFormKey, pLabel) {
		";
	}
	
	// Return a PntPont with minimum width and height
	static function getMinWindowSize() {
		$result = new PntPoint(640,450);
		return $result;
	}

	/** Must be equal to Dialog */
	function printReturnFuncName() {
		print 'pntSetDialogResult';
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

//  *********** specific methods ************************

	function getBackToOrigin() {
		return true;
	}

	// use skinBody without menu
	function printBody() {
		if (isSet($this->requestData['pntEditFeedback']))
			$this->printReturnAndCloseScript();
		
		$this->includeSkin('DialogBody');
	}

	function getButtonsList() {
		$buts = parent::getButtonsList();
		$actButs =& $buts[0];
		if ($this->getReqParam('pntScd')=='d')
			$this->addContextButtonTo($actButs);
		$actButs[] = $this->getButton("Close", "window.close();");

		$navButs=array();
		
		return array($actButs, $navButs);
	}

	//override PntObjectEditDetailsPage method to get dialog skin
	function printMainPart() {
		$this->printPart('DetailsDialogPart');
	}

	function isMultiSelect() {
		return false; 
		//multiselect not yet supported, plan is to use request parameter tot activate multiselect
		// implement printMultiSelectScript first
	}

	function printReturnAndCloseScript() {
		$cnv = $this->getConverter();
		$obj = $this->getRequestedObject();
		$labelLit = $cnv->toJsLiteral( $cnv->toLabel($obj, $this->getType()), "'");
		$idProp = $obj->getPropertyDescriptor('id');
		$cnv->initFromProp($idProp);
		$idLit = $cnv->toJsLiteral($cnv->toLabel($idProp->getValueFor($obj), $idProp->getType()), "'");
		$formKeyLit = $cnv->toJsLiteral($this->getFormKey(), "'");
		
		print "<script>window.opener.";
		$this->printReturnFuncName();
		print "($idLit, $formKeyLit, $labelLit); \n";
		print "window.close();</script>";
	}

	/** @return string according to ::checkAlphaNumeric */
	function getFormKey() {
		$param = $this->getReqParam('pntFormKey');
		return $param ? $this->checkAlphaNumeric($param) : $this->getPropertyName();
	}


}	
?>