<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPage', 'pnt/web/pages');
Gen::includeClass('PntXmlTotalText', 'pnt/web/dom');

/** Page showing a TablePart with manually selected objects. 
* Navigation leads to ReportPages. 
* Columns shown in the TablePart can be overridden by creating a 
* getReportColumnPaths method on the type of objects shown in the table.
* totals are shown of columns with values of properties typed as number. 
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectEditDetailsPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/pages
*/
class PntObjectSelectionReportPage extends PntPage {

	function getName() {
		return 'SelectionReport';
	}

	function initForHandleRequest() {
		// initializations
		parent::initForHandleRequest();
		$this->getRequestedObject();
	}

	//returns Array of objects
	function getRequestedObject() {
		$collector = $this->getMarkedItemsCollector();
		return $collector->getMarkedObjects($this->requestData);
	}

	/** Tell the scout how to interpret the requests
	* PRECONDITION: Session started
	*/
	function doScouting() {
		$scout = $this->getScout();
		$referrerId = $scout->getReferrerId($this->requestData);
		$direction = $this->getReqParam('pntScd'); 
		if ($direction || !$referrerId) 
			return parent::doScouting();
		
		$uris = $scout->getFootprintUris();
		$refUri = $uris[$referrerId];
		
		$refRequest = $this->request->getFunkyRequestData(null, $refUri); 
		$refHandler = isSet($refRequest['pntHandler']) ? $refRequest['pntHandler'] : null;
		$direction = $this->isSameContextHandler($refHandler) ? '': 'down';
		$this->footprintId = $scout->moved($referrerId, $direction, $this->requestData);
	}

	function isSameContextHandler($pntHandler) {
		return $pntHandler == 'SelectionReportPage';
	}

	// no menu, info and buttons, 
	// call this method printMainPart to get menu, info and buttons
	// and adapt printBodyTagIeExtraPiece and skinReportPart.php
	function printMainPart() { 
		$this->printPart('SelectionReportPart');
	}

	function printBodyTagIeExtraPiece() {
		//none
	}


/*	function insertCheckboxInItemTable($table) {
		// no checkboxes
	}
*/
	function getButtonsList() {
		// only used if menu, info and buttons
		$actButs = array();
		$actButs[]=$this->getButton('Report', "document.itemTableForm.submit();");
		$actButs[]= $this->getButton("Print", "window.print();");

		$navButs=array();
		if (!$this->isLayoutReport())
			$this->addContextButtonTo($navButs);

		return array($actButs, $navButs);
	}
	
	function getDetailsLinkPntHandler() {
		return 'ReportPage';
	}
	
	/** Returns the paths for the columns to show in the table for the 
	* specified type
	* default is getReportColumnPaths from the type 
	* If null is returned, the columns will default to the uiColumnPaths
	* @param $propName String The name of type
	* $return String holding paths seperated by space, or Array. 
	*   If Array, String keys are used as labels, for numeric keys the paths will be used
	*/
	function getTableColumnPaths($type) {
		return pntCallStaticMethod($type, 'getReportColumnPaths');
	}
	
	function printItemTablePart() {
		$table = $this->getInitItemTable();
		$table->printBody();
	}
	
	function getInitItemTable() {
		$table = $this->getPart(array(
			'TablePart'
			, $this->getType()
			, $this->getTableColumnPaths($this->getType())
		));
		$table->extraCells = $this->getTotalCells($table);
		$table->setHandler_printItemCells($this); // to calculate the totals
		$table->setHandler_printTableFooter($this); // to print the totals row
		return $table;
	}

	function getTotalCells($table) {
		$totalCells = array();
		reset($table->cells);
		while (list($key) = each($table->cells)) {
			$cellText = $table->cells[$key];
			$nav = $cellText->getNavigation();
			if ($nav && $cellText->getContentType() == 'number') {
				$prop = $nav->getLastProp();
				$decimalPrecision = ValueValidator::getDecimalPrecision($prop->getMaxLength());
				$contentType='number';
			} else {
				$decimalPrecision=0;
				$contentType='string';
			}
			$totalCells[$key] = new PntXmlTotalText(null, null, 0, $contentType, $decimalPrecision);
			$totalCells[$key]->setConverter($this->getConverter());
		}
		return $totalCells;
	}

	//nothing different from the original handler, except that we calculate the totals
	function printItemCells($table, $item, $rowKey=null) {
		reset($table->cells);
		while (list($key) = each($table->cells)) {
			$cell = $table->cells[$key];
			$onClick = $table->handler_getCellOnClickParam->getCellOnClickParam($table, $item, $key);
			print "
			<TD $onClick>";
			print $cell->getMarkupWith($item);
			print "</TD>";
			
			if (isSet($table->extraCells[$key]))
				$table->extraCells[$key]->totalize($cell->content);
		}
	}

	function printTableFooter($table) {
?> 
	<TFOOT>
		<TR class="pntItf">
			<TD>&nbsp;</TD> <!-- for itemSelect column -->
			<?php $this->printTotalCells($table) ?> 
		</TR>
	</TFOOT>
<?php
	}
	
	function printTotalCells($table) {
		$zero = 0;
		$labelSet = false;
		reset($table->cells);
		while (list($key) = each($table->cells)) {
			print "
			<TD>";
			if (isSet($table->extraCells[$key])) {
				$cell = $table->extraCells[$key];
				print $cell->getMarkupWith($zero); //for some unknown reason 0 is replaced by the argument value
			} else {
				print ($labelSet ? '&nbsp;' : $this->getTotalsRowLabel());
				$labelSet = true;
			}
			print "</TD>";
		}
	}
	
	/** @return html string */
	function getTotalsRowLabel() {
		return 'total';
	}

}
?>