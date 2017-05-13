<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


Gen::includeClass('PntPage', 'pnt/web/pages');
Gen::includeClass('PntPoint', 'pnt/graphics');

/** Abstract Dialog superclass. 
* @see http://www.phppeanuts.org/site/index_php/Menu/244
* @package pnt/web/dialogs
*/
class PntDialog extends PntPage {

	/** @static 
	 * @return the piece of the javascript that will be called by the dialog
	 * the piece must deliver the id in variable pId and the label in pLabel
	 * @param string $formKey must already be checkedAlpaNumeric!
	 */
	static function getReplyScriptPiece($formKey='') {
		return "func"."tion pntSetDialogResult(pId, pFormKey, pLabel) {
		";
			
	}
	
	// Return a PntPont with minimum width and height
	static function getMinWindowSize() {
		$result = new PntPoint(600,450);
		return $result;
	}

	function printBody() {
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
		$okBut = $this->getOkButton();
		if ($okBut) $actButs[] = $okBut;
		$actButs[] = $this->getButton("Cancel", "window.close();");
		return array($actButs);
	}

	function getOkButton() {
		return $this->getButton("OK", "dialogForm.submit();");
	}

	function printReturnFuncName() {
		$propName = $this->getReqParam('pntProperty');
		$this->checkAlphaNumeric($propName);
		print 'set'.$propName;
	}

}	
?>