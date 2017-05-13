<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

	
Gen::includeClass('ObjectSelectionReportPage');

/** Selection ReportPage with HorizontalTablePart.
* mouseover highlighting has to be improved
* @package pnt/web/pages
*/
class PntHorSelReportPage extends ObjectSelectionReportPage {
	
	public $totals = true;
	
	function getInitItemTable() {
		$table = $this->getPart(array(
			'HorizontalTablePart'
			, $this->getType()
			, $this->getTableColumnPaths($this->getType())
		));
		if ($this->totals) {
			$table->extraCells = $this->getTotalCells($table);
			$table->setHandler_printItemCells($this); // to calculate the totals
			$table->setHandler_printPropFinish($this); // to print the totals row
			$table->setHandler_printHeaderPropFinish($this);
		}
		return $table;
	}
 
	//nothing different from the original handler, except that we calculate the totals
	/** Prints TD's for the supplied item, after an eventual ItemSelectCell has been printed
	* Eventhandler 
	* @argument PntObject $item the item this row displays
	* @argument PntTablePart $table $this, made explicit for copy&paste as event handler
	*/
	function printItemCells($table, $cell, $rowKey=null) {
		$items = $table->getItems();
		reset($items);
		while (list($key) = each($items)) {
			$item = $items[$key];

			$onClick = $table->getCellOnClickParam($table, $item);
			print "
			<TD $onClick "; 
?>			bgcolor="<?php $table->handler_printItemBgColor->printItemBgColor($table, $item) 
?>" onMouseOver="this.style.background='<?php print $table->itemHlColor 
?>';" onMouseOut="this.style.background='<?php $table->handler_printItemBgColor->printItemBgColor($table, $item) 
?>';" style="cursor:hand; cursor:pointer;"> <?php
			print $cell->getMarkupWith($item);
			print "</TD>";
			if (isSet($table->extraCells[$cell->pntTableIndex]) )
				$table->extraCells[$cell->pntTableIndex]->totalize($cell->content);
		}
	}
	
	function printPropFinish($table, $cell) {
		$zero = 0;
		print "
		<TD>";
		if (isSet($table->extraCells[$cell->pntTableIndex]) ) {
			$cell = $table->extraCells[$cell->pntTableIndex];
			print $cell->getMarkupWith($zero); //for some unknown reason 0 is replaced by the argument value
		} else {
			print  '&nbsp;' ;
		}
		print "</TD>";
	} 

	function printHeaderPropFinish($table) {
		print "<TD>";
		print $this->getTotalsRowLabel();
		print "</TD>";
	}

}
?>