<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPagePart', 'pnt/web/parts');
Gen::includeClass('PntXmlNavText', 'pnt/web/dom');

/** Part that outputs html descirbing a table with rows for object
* and columns for their properties. 
* As a default columns can be specified in metadata on the class
* specified by pntType request parameter, 
* @see http://www.phppeanuts.org/site/index_php/Pagina/61
*
* This abstract superclass provides behavior for the concrete
* subclass TablePart in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/parts
*/
class PntTablePart extends PntPagePart {

	/** HTML message shown instead of rows if noTableIfNoItems */
	public $noItemsMessage = 'No Items';
	/** If true no table is shown if no items */
	public $noTableIfNoItems = true;
	/** If true checkboxes are printed at the start of each row */
	public $itemSelectWidgets = true;
	/** int or empy string */
	public $tableWidth='';
	
	public $selectedId=-1;
	/** If true a header row with columnlabels is printed */
	public $showPropHeaders = true;
	/** HTML item backgroundcolor */
	public $itemBgColor='white';  
	/** HTML item highlightcolor */
	public $itemHlColor='#ffffa0';
	/** HTML table backgroundcolor */
	public $bgColor;
	/** Extra attributes for table tag */
	public $extraTableAtts = "onkeypress=\"window.location.href='#'+String.fromCharCode(event.keyCode).toLowerCase();\"";

	//legecay support - old PntTable protocol from before the intro of PntHorizontalTablePart
	public $showTableHeaders; 
	public $rowBgColor;  
	public $rowHlColor;
	public $handler_printItemRows;
	public $pntTableEqField; //DO NOT OUTPUT THIS

	/** @param string $itemType must be safe to print without encoding 
	*/
	function __construct($whole, $requestData, $itemType=null, $propPaths=null) {
		parent::__construct($whole, $requestData);
		$this->initialize($itemType, $propPaths);
	}

	function getName() {
		return 'TablePart';
	}

	/** Adds html DOM parameters to be added to the table headers.
	 * @param int $index
	 * @param string $params HTML 
	 */
	function addHeaderSortParams($index, $params) {
		$this->headerSortParams[$index] = $params;
	}

	function setItems($items) {
		$this->items = $items;
	}
	
	function setSelectedId($value) {
		$this->selectedId = $value;
	}
	
	function setItemSelectWidgets($value) {
		$this->itemSelectWidgets = $value;
	}

	function setNoTableIfNoItems($value) {
		$this->noTableIfNoItems = $value;
	}
	
	function setShowPropHeaders($value) {
		$this->showPropHeaders = $value;
		$this->showTableHeaders = $value; //depricated support
	}

	/** @param string $value */
	function setTableWidth($value) {
		$this->tableWidth = $value;
	}

	/** @param HTML $value */
	function setBgColor($value) {
		$this->bgColor = $value;
	}
	
	/** @param HTML $value */
	function setItemBgColor($value) {
		$this->itemBgColor = $value;
		$this->rowBgColor = $value; //depricated support
	}

	/** @param HTML $value */
	function setItemHlColor($value) {
		$this->itemHlColor = $value;
		$this->rowHlColor = $value;
	}

	function setHandler_printTableHeaders($handler) {
		$this->handler_printTableHeaders = $handler;
	}
	
	function setHandler_printRows($handler) {
		$this->handler_printRows = $handler;
	}

	function setHandler_printItemSelectCell($handler) {
		$this->handler_printItemSelectCell = $handler;
	}
	
	function setHandler_printItemCells($handler) {
		$this->handler_printItemCells = $handler;
	}
	
	function setHandler_printItemCellContent($handler) { 
		$this->handler_printItemCellContent = $handler;
	}
	
	function setHandler_getCellOnClickParam($handler) {
		$this->handler_getCellOnClickParam = $handler;
	}

	function setHandler_printTableFooter($handler) {
		$this->handler_printTableFooter = $handler;
	}
	
	function setHandler_printItemBgColor($handler) {
		$this->handler_printItemBgColor = $handler;
	}
	
	function initialize($itemType, $propPaths) {
		$this->pntTableEqField = microtime();
		// depricated support - if values are set to depricated fields, set them in the new corresponding fields
		if (isSet($this->showTableHeaders)) 
			$this->showPropHeaders = $this->showTableHeaders;
		else
			$this->showTableHeaders = $this->showPropHeaders;
		if (isSet($this->rowBgColor)) 
			$this->itemBgColor = $this->rowBgColor;
		else
			$this->rowBgColor = $this->itemBgColor;
		if (isSet($this->rowHlColor)) 
			$this->itemHlColor = $this->rowHlColor;
		else
			$this->rowHlColor = $this->itemHlColor;
			
		$this->headers = array();  // prop headers
		$this->cells = array();
		$this->headerSortParams = array();
		$this->items = null;
		$this->setHandler_printRows($this);
		$this->setHandler_printItemSelectCell($this);
		$this->setHandler_printItemCells($this);
		$this->setHandler_printItemCellContent($this);
		$this->setHandler_getCellOnClickParam($this);
		$this->setHandler_printTableHeaders($this);
		$this->setHandler_printTableFooter($this);
		$this->setHandler_printItemBgColor($this);
		$this->itemType = $itemType;
		$this->peanutItems = is_subclassOr($this->getItemType(), 'PntObject');
		if (!$this->peanutItems)
			$this->itemSelectWidgets = false;
		$this->addPropPaths($propPaths);
	}

	function addPropPaths($arrayOrString) {
		$paths = $arrayOrString;
		if ($paths === null && $this->peanutItems) {
			$clsDes = PntClassDescriptor::getInstance($this->getItemType());
			$paths = $clsDes->getUiColumnPaths();
		} 
		if (!is_array($paths))
			$paths = explode(' ', $paths);

		if (!empty($paths))
		    foreach ($paths as $label => $path)
				$this->addPropPath($path, $label);
	}

	function addPropPath($path, $label) {
		if (empty($path)) return;

		$cell = new PntXmlNavText(null, $this->getItemType(), $path);
		$cnv = $this->getConverter(); //copies the converter
		$cell->setConverter($cnv); 
		$nav = $cell->getNavigation(); 
		$cell->pntTableIndex = count($this->cells);
		$this->cells[] = $cell;

		if (is_int($label))
			$label = $nav->getFirstPropertyLabel();
		$this->headers[] = $cnv->toHtml($label);
	}

	function getItemType() {
		if ($this->itemType)
			return $this->itemType;
		
		return $this->whole->getType($this->getName());
	}

	function getItems() {
		if ($this->items !== null)
			return $this->items;
			
		return $this->whole->getRequestedObject();
	}
	
	function printAnchorFor($item) {
		//allows user to find items in a table by pressing the first letter of the label
		if ($this->peanutItems) {
			print "<a name='";
			$this->htOut( substr($item->getLabel(),0,1) );
			print "'></a>\n";
		}
	}

	function printCheckboxCheckedFor($item) {
		if (isSet($this->requestData['*!@'.$item->getOid()]) )
			print 'CHECKED';
	}

	function printTableId() {
		$this->htOut($this->getTableId());
	}

	/** @return string (untrusted) value of the id parameter of the table tag */
	function getTableId() {
		$type = $this->getItemType(); 
		$dir = $this->getAppName();
		$context = $this->whole->getThisPntContext();

		return "$dir*$type*$context";
	}

	function getAppName() {
		if (!$this->peanutItems) 
			return $this->controller->getAppName();
		
		$clsDes = PntClassDescriptor::getInstance($this->getItemType());
		return $this->controller->getAppName($clsDes->getClassDir(), $this->getItemType(), $this->whole->getDetailsLinkPntHandler());
	}

//the rest of the methods contains table layout
	function printBody() {
		if ($this->getItems() || !$this->noTableIfNoItems) {

?>   
	<TABLE class="pntItemTable" id="<?php $this->printTableId() ?>" width="<?php $this->htOut($this->tableWidth) ?>" bgcolor="<?php print $this->bgColor ?>" <?php $this->printExtraTableAtts()?>>
	<?php $this->printThead() ?>
	<TBODY>
		<?php $this->handler_printRows->printRows($this) ?>
	</TBODY>
		<?php $this->handler_printTableFooter->printTableFooter($this) ?> 
	</TABLE>
<?php	
		}
		if (!$this->getItems())
			return $this->printNoItemMessage();
	}
	
	function printExtraTableAtts() {
		print $this->extraTableAtts;
	}
	
	function printThead() {
		if (!$this->showPropHeaders) return;
?>
	<THEAD>
		<TR class="pntIth">
			<?php $this->printItemSelectHeader() ?>
			<?php $this->handler_printTableHeaders->printTableHeaders($this) ?> 
		</TR>
	</THEAD>
<?php	
	}
	
	function printNoItemMessage() {
		print $this->noItemsMessage;
	}

	function printItemSelectHeader() {
		if (!$this->itemSelectWidgets) return;

		$imagesDir = $this->getImagesDir();
		print "<TD class=pntIth>
				&nbsp;<image src='$imagesDir"."invert.gif' ALT='invert selection' onclick='invertTableCheckboxes(this); return false;'>
			</TD>";
	}
	
	/** Prints TD's for the header row, after an eventual ItemSelectHeader has been printed
	* Eventhandler 
	* @argument PntTablePart $table === $this, made explicit for copy&paste as event handler
	*/
	function printTableHeaders($table) {
		reset($table->headers);
		foreach ($table->headers as $key => $label) {
			$sortParams = isSet($table->headerSortParams[$key]) ? $table->headerSortParams[$key] : '';
			print "
			<TD class=pntIth$sortParams>$label</TD>"; //sortParms and labels are HTML
		}
	}

	function printRows($table) {
		//depricated support - remove after copying this eventhandler
		if (isSet($this->handler_printItemRows) 
				&& isSet($this->handler_printRows->pntTableEqField) 
				&& 	$this->handler_printRows->pntTableEqField == $this->pntTableEqField) 
			return $this->handler_printItemRows->printItemRows($table);
		
		$items = $table->getItems();
		//reference anomaly workaround, maar misschien is dit ook wel efficienter
		forEach(array_keys($items) as $key) {
			$item = $items[$key];
			$table->printRow($table, $item, $key);
		}
	}
	
	function printRow($table, $item, $key) {
?> 
		<TR bgcolor="<?php $table->handler_printItemBgColor->printItemBgColor($table, $item, $key) ?>" onMouseOver="this.style.background='<?php print $table->itemHlColor ?>';" onMouseOut="this.style.background='<?php $table->handler_printItemBgColor->printItemBgColor($table, $item, $key) ?>';" style="cursor:hand; cursor:pointer;">
			<?php $table->handler_printItemSelectCell->printItemSelectCell($item, $key) ?>
			<?php $table->handler_printItemCells->printItemCells($table, $item, $key) ?> 
		</TR>
<?php
	}
	
	function printItemBgColor($table, $item, $key=null) {
		//depricated support - remove after copying this eventhandler
		if (isSet($this->handler_printRowBgColor) ) 
			return $this->handler_printRowBgColor->printRowBgColor($table, $item);
		
		if ($table->peanutItems && $item->get('id') == $table->selectedId)
			print $table->itemHlColor;
		else
			print $table->itemBgColor;
	}

	function printItemSelectCell($item, $key=null) {		
		if (!$this->itemSelectWidgets) return;
?> 
			<TD>
				<?php $this->printAnchorFor($item)?>
				<INPUT TYPE='CHECKBOX' <?php $this->printCheckboxCheckedFor($item) ?> VALUE='true' NAME='*!@<?php $this->htOut($item->getOid()) ?>'></INPUT>
			</TD>
<?php
	}

	/** Prints TD's for the supplied item, after an eventual ItemSelectCell has been printed
	* Eventhandler 
	* @argument PntTablePart $table $this, made explicit for copy&paste as event handler
	* @argument PntObject $item the item this row displays
	*/
	function printItemCells($table, $item, $rowKey=null) {
		$type=$this->getType(); //is checked to be alphaNumeric
		reset($table->cells);
		foreach ($table->cells as $key => $cell) {
			$cell = $table->cells[$key];
			$onClick = $table->handler_getCellOnClickParam->getCellOnClickParam($table, $item, $key);
			print "\n			<td class=\"";
			$this->htOut($type. '_' . $cell->getFormKey());
			print "\" $onClick>";
			$this->handler_printItemCellContent->printItemCellContent($this, $item, $cell);
			print "</TD>";
		}
	}
	
	function printItemCellContent($table, $item, $cell) {
		print $cell->getMarkupWith($item);
	}

	/** Return the onClick parameter for inmclusion in the TD tags by printItemCells
	* Eventhandler 
	* @argument PntTablePart $table $this, made explicit for copy&paste as event handler
	* @argument PntObject $item the item this row displays
	* @argument mixed $cellKey (optional) key of the cell content provider in $table->cells 
	*/
	function getCellOnClickParam($table, $item) {
			if (!$table->peanutItems) return ''; 
			
			$itemKey = $this->controller->converter->toJsLiteral($item->get('id'), "'");
			return "onClick=\"tdl(this,$itemKey);\"";
	}

	/** Prints eventual footer
	* Eventhandler. Default implementation is do nothing
	* @argument PntTablePart $table $this, made explicit for copy&paste as event handler
	*/
	function printTableFooter($table) {
		//there can only be extra rows if an external eventhandler is set		
	}

	// ------------------------------  DEPRICATED SUPPORT ------------------------------------

	/** @depricated */
	function setShowTableHeaders($value) {
		$this->setShowPropHeaders($value);
	}

	/** @depricated */
	function setRowBgColor($value) {
		$this->setItemBgColor($value);
	}
	
	/** @depricated */
	function setRowHlColor($value) {
		$this->setItemHlColor($value);
	}

	/** @depricated */
	function addColumnPaths($arrayOrString) {
		$this->addPropPaths($arrayOrString);
	}
			
	/** @depricated */
	function addColumnPath($path, $label) {
		$this->addPropPath($path, $label);
	}

	/** @depricated */
	function setHandler_printItemRows($handler) {
		$this->handler_printItemRows = $handler;
	}

	/** @depricated */
	function printItemRows($table) {
		$this->printRows($table);
	}
	
	/** @depricated */
	function setHandler_printRowBgColor($handler) {
		$this->handler_printRowBgColor = $handler;
	}
	
}