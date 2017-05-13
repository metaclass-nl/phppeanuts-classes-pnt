<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObjectDetailsPage', 'pnt/web/pages');

/** Kind of DetailsPage showing property labels and values of a single object,
* but also a TablePart with values for each multi value property.
* Navigation leads to other ReportPages. 
* What details are shown can be overridden by overriding getFormTextPaths method.
* What multi value properties are shown can be overriden by overriding
* the getMultiPropNames method. 
* Columns shown in each TablePart can be overridden by creating a 
* getReportColumnPaths method on the type of objects shown in the table.
* Layout can be overridden, see http://www.phppeanuts.org/site/index_php/Pagina/65
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectReportPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/pages
*/
class PntObjectReportPage extends PntObjectDetailsPage {

	public $object;
	public $formTexts;
	public $showButtonsPanel = true;

	/** if inPopup is true, no menu, info and buttons.
	* when set to false you should adapt printBodyTagIeExtraPiece and skinReportPart.php */
	public $inPopup = true;

	function getName() {
		return 'Report';
	}

	function printBody() { 
		if ($this->inPopup)
			$this->printPart('ReportPart');
		else 
			parent::printBody();
	}
	
	function isLayoutReport() {
		return $this->inPopup;
	}

	function getBackToOrigin() {
		trigger_error('should not implement', E_USER_ERROR);
	}
	
	//only used if !$this->inPopup
	function printMainPart() { 
		$this->printPart('ReportPart');
	}

	function printBodyTagIeExtraPiece() {
		//none
	}


	function insertCheckboxInItemTable($table) {
		// no checkboxes
	}
	
	function printButtonsPanel() {
		if (!$this->showButtonsPanel) return;
		$part = $this->getPart(array('ButtonsPanel'));
		$part->printBody(array());
	}

	function getButtonsList() {
		$list = $this->inPopup 
			? array(array())
			: parent::getButtonsList();
			
		$list[0][]= $this->getButton("Print", "window.print();");

		return $list;
	}
}
?>