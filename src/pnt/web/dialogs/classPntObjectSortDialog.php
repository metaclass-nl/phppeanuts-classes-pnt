<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntDialog', 'pnt/web/dialogs');
Gen::includeClass('PntPoint', 'pnt/graphics');

/** @package pnt/web/dialogs
*/
class PntObjectSortDialog extends PntDialog {

	function getName() {
		return 'SortDialog';
	}

	static function getMinWindowSize() {
		$result = new PntPoint(400,270);
		return $result;
	}

	/** @static 
	 * @return the piece of the javascript that will be called by the dialog
	 * @param string $formKey ignoored
	 */
	static function getReplyScriptPiece($formKey='') {
		return "func"."tion reSortBy(paths, directions) {
		";
	}
	
	/** Check access to a $this with the SecrurityManager. 
	* Forward to Access Denied errorPage and die if check returns an error message.
	*/
	function checkAccess() {
		$err = $this->controller->checkAccessHandler($this);
		if ($err) $this->controller->accessDenied($this, $err); //dies
	}


	function printMainPart() {
		parent::printMainPart();
		$this->printOKScript();
	}

	function getOkButton() {
		return $this->getButton("OK", "eventOK()");
	}

	function printOKScript() {
		$pathAfterEmptyLit = $this->getConverter()->toJsLiteral($this->getPathAfterEmptyMessage(), '"');
		print "
	<script>
		func"."tion eventOK() {
			paths = new Array();
			directions = new Array();
			//not yet implemented: collect paths and directions
			var i = 0;
			var lege = false;
			while (document.pntSortForm.elements['pntS' + (i+1) + 'd']) {
				var param = 'pntS' + (i+1);
				paths[i] = document.pntSortForm.elements[param].value;
				if (document.pntSortForm.elements[param+'d'][1].checked) 
					directions[i] = 'DESC';
				else
					directions[i] = 'ASC';
				if (paths[i] == '') {
					lege = true;
				} else {
					if (lege) {
						alert($pathAfterEmptyLit);
						return;
					}
				}
				i = i + 1;
			}				
			window.opener.reSortBy(paths, directions);
			window.close();
		}
	</script>";
	}

	/** @return string message shown if criteria are selected after an empty criterium */
	function getPathAfterEmptyMessage() {
		$defaultLblAdd = $this->getReqParam('dsOptLbl');
		if ($defaultLblAdd) {
			$defaultLblAdd = " or '$defaultLblAdd'";
		}
		return "After empty selection$defaultLblAdd remaining selections must be empty";
	}

	function getRequestedObject() {
		return null;
	}

}	
?>