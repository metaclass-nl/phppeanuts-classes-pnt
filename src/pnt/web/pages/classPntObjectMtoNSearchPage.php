<?php 
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObjectSearchPage', 'pnt/web/pages');

/** Specialization of SearchPage that is used by PntMtoNPropertyPart
* for searching and selection of values to add relations to.
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectMtoNSearchPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/pages
*/
class PntObjectMtoNSearchPage extends PntObjectSearchPage {

	public $tablePartClass = 'TablePart';

	/** This page runs in an i-frame so it needs to override the default X-Frame-Options header */
	function printHeaderXframeOptions() {
		header('X-Frame-Options: SAMEORIGIN');		
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
		$this->includeSkin('DialogBody');
	}

	//override PntObjectSearchPage method to get specific skin
	function printMainPart() {
		$this->printPart('MtoNSearchPart');
	}

	function getFilterFormPartName() {
		return 'MtoNFilterFormPart';
	}

	function printSelectScriptPart() {
		$addItemFunction = $this->getPropertyName(). 'AddItem';
		$propName = $this->getPropertyName();
		print "
			<script>
				function tdl(id, label) {
					parent.$addItemFunction(id, label);
				}
			</script> ";
	}

	function printHeader() {
			//output starts here
			$this->includeSkin('HeaderClean');
	}
	
	function printFooter() {
			$this->includeSkin('FooterClean');
	}

	function getButtonsList() {
		$navButs=array();
		$builder = $this->getPagerButtonsListBuilder();
		$builder->setPageButtonSize(22);
		$builder->addPageButtonsTo($navButs);
		
		return array(array(), $navButs);
	}

	function getPageItemCount() {
		return 10;
	}

	function printItemTablePart() {
		if (!$this->getRequestedObject()) return;

		$table = $this->getInitItemTable();
		$table->printBody();
	}

	function getInitItemTable() {

		$part = $this->getPart(array($this->getTablePartClass(), $this->getType()
			, $this->getItemTableColumnPaths())
		);
		$this->setTableHeaderSortParams($part);

		$part->setItemSelectWidgets(false);
		$part->setHandler_printTableHeaders($this);
		$part->setHandler_printItemSelectCell($this);
		$part->setHandler_getCellOnClickParam($this);
		return $part;
		
	}
	
	function getTablePartClass() {
		return $this->tablePartClass;
	}

	function setTablePartClass($value) {
		$this->tablePartClass = $value;
	}

	/** Returns the paths for the columns to show in the itemtable 
	* $return Array, whose String keys are used as labels, for numeric keys the paths will be used
	*/
	function getItemTableColumnPaths() {
		$paths = array($this->getTypeLabel() => 'label');
		return $paths;
	}
	
	function getCellOnClickParam($table, $item) {
		$cnv = $this->controller->converter;
		$itemLabelLit = $cnv->toJsLiteral($item->get('label'), "'");
		$itemIdLit = $cnv->toJsLiteral($item->get('id'), "'");
		return "onClick=\"tdl($itemIdLit, $itemLabelLit);\"";;
	}
	
	//PntObjectMtoNSearchPage uses propertyname for different purpose
	function getPropertyFilter() {
		return null;
	}

	function printTableHeaders($table) {
		print "<TD class=pntIth>
				&nbsp;
			</TD>";
		$table->printTableHeaders($table);
	}

	function printItemSelectCell($item, $index) {
		$cnv = $this->controller->converter;
		$itemLabelLit = $cnv->toJsLiteral($item->get('label'), "'");
		$itemIdLit = $cnv->toJsLiteral($item->get('id'), "'");
?> 
			<TD>
				<IMG SRC='<?php print $this->getImagesDir() ?>pijllinks.gif' ALT='Toevoegen' BORDER='0' style='cursor:arrow;' onClick="tdl(<?php print "$itemIdLit , $itemLabelLit" ?>);">
			</TD>
<?php
	}
	
}
?>