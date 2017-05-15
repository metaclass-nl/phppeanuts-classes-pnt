<?php 
/* Copyright (c) MetaClass, 2003-2013

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPropertyPart', 'pnt/web/parts');

/** Part used by ObjectMtoNPropertyPage.
* Contains a table from which the user can remove items by clicking their remove icons.
* and a SearchFrame with an MtoNSearchPage from which the user can search and add items
* by clicking in them. The table is adapted client-side to show the resulting related
* items. When the save button is pressed the id's of the added and removed items
* are sent to the server and processed to add and remove the relationship objects 
* according to the selections of the user.
* This part can also be used inside a TabsPart inside an EditDetalsPage to create 
* a single large form that holds both the detailsPart as the MtoNSearchPart(s). 
* The user can then edit each tab and after he is finished send the entire form
* to the server at once so that all details and n to m relationships are processed at once.
*
* This abstract superclass provides behavior for the concrete
* subclass MtoNPropertyPart in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/parts
*/
class PntMtoNPropertyPart extends PntPropertyPart {

	
	function printBody() {
		$this->includeSkin($this->getName());
	}
		
	function getInitItemTable() {
		$part = parent::getInitItemTable();

		$part->setItemSelectWidgets(false);
		$part->setNoTableIfNoItems(false);
		$part->setHandler_printTableHeaders($this);
		$part->setHandler_printRows($this);
		
		return $part;
	}

	/** Returns the paths for the columns to show in the itemtable 
	* $return Array, whose String keys are used as labels, for numeric keys the paths will be used
	*/
	function getItemTableColumnPaths() {
		$paths = array($this->getItemTypeLabel() => 'label');
		return $paths;
	}
	
	/** @return string alphaNumeric */
	function getSearchFrameName() {
		return $this->getPropertyName().'SearchFrame';
	}
	
	/** For the use in PntObjectMtoNDialog the objects whose ids are in 
	* <propertyName> request parameter precede over the property value. 
	* If <propertyName> request parameter is empty, an empty array is returned, 
	* if it is null the property value is returned
	*/
	function getPropertyValueFor($obj) {
		Gen::includeClass('PntFormMtoNRelValue', 'pnt/web/dom');
		$ids = $this->getReqParam($this->getPropertyName());
		$formRelValue = new PntFormMtoNRelValue(null, $this->getType(), $this->getPropertyName());
		$formRelValue->setConverter($this->getConverter());
		$formRelValue->setitem($obj);
		$formRelValue->setConvertMarkup($ids);
		return $formRelValue->getValue($obj);
	}

	function printTableHeaders($table) {
		print "<TD class=pntIth ". ">&nbsp;</TD>";
		$table->printTableHeaders($table);
	}
	
	/** @return string HTML */
	function getOnClickRemoveRow() {
		return "onClick='". $this->getPropertyName(). "RemoveRowOf(this.parentNode);' ";
	}
	
	function printRows($table) {
		$items = $table->getItems();
		$cnv = $this->getConverter();
		forEach(array_keys($items) as $key) {
			$item = $items[$key];
			$idParam = $table->peanutItems ? "id='".$cnv->toHtml($item->get('id')). "'" : '';
			print "
		<TR $idParam "; 
		?> bgcolor="<?php $table->handler_printItemBgColor->printItemBgColor($table, $item, $key) ?>" onMouseOver="this.style.background='<?php print $table->itemHlColor ?>';" onMouseOut="this.style.background='<?php $table->handler_printItemBgColor->printItemBgColor($table, $item) ?>';" style="cursor:hand; cursor:pointer;">
			<?php $this->printItemActionCell($item, $key) ?>
			<?php $table->printItemCells($table, $item, $key) ?> 
		</TR>
<?php
		}
	}
	
	/** Return a single line template with html of the row cells */
	function getItemActionCellContent() {
		$removeRow = $this->getOnClickRemoveRow();
		$imagesDir = $this->getImagesDir();
		return "<IMG SRC=\"$imagesDir"."delete.gif\" ALT=\"Remove\" BORDER=\"0\" style=\"cursor:arrow;\" $removeRow>";
	}

	function printItemActionCell($item, $index) {
?> 
			<TD>
				<?php print $this->getItemActionCellContent() ?>
			</TD>
<?php
	}
	
	function printSearchPageUrl() {
		$prop = $this->getPropertyDescriptor();
		$params = array('pntHandler' => 'MtoNSearchPage'
			, 'pntType' => $prop->getType()
			, 'pntProperty' => $prop->getName()
			);
		$appName = $this->controller->getAppName($prop->getClassDir(), $prop->getType(), $params['pntType']);
		print $this->controller->buildUrl($params, $appName);
	}

	function printAddRemoveScriptPart() {
		$cnv = $this->getConverter();
		$propName = $this->getPropertyName();
		$bgCol = $this->itemTable->itemBgColor;
		$tableIdLit = $cnv->toJsLiteral($this->getItemTableId(), "'");
		$actionCellContentLit = $cnv->toJsLiteral($this->getItemActionCellContent(), "'");
		$replyScriptPiece = $this->getEditDetailsDialogReplyScriptPiece();
		print "
<script>
	pntMtoNPropsEdited[pntMtoNPropsEdited.length] = new Array('$propName', '". $this->getItemTableId(). "');
	func"."tion tdl(tableCell, id) {
		//id may be wrong becuase we copy rows, use id from row
		row = tableCell.parentNode;
		document.location.href = tdlGetHref(tableCell, row.id);
	}
	func"."tion $propName"."RemoveRowOf(tableCell) {
		row = tableCell.parentNode;
		itemTable = getElement($tableIdLit);
		table_deleteRow(itemTable, tableCell.parentNode);
	}
	func"."tion table_deleteRow(itemTable, row) {
		for (i=0; i<itemTable.rows.length; i++) 
			if (itemTable.rows[i] == row) 
				return itemTable.deleteRow(i);
	}
	func"."tion $propName"."AddItem(id, label) {
		itemTable = getElement($tableIdLit);
		tbody = getNodeByTagName(itemTable.childNodes, 'TBODY', 0);
		headerRow = itemTable.rows[0];
		row = document.createElement('TR');
		row.id = id;
		row.bgColor='$bgCol';
		cell = document.createElement('TD');
		cell.innerHTML=$actionCellContentLit;
		row.appendChild(cell);
		cell = document.createElement('TD');
		row.appendChild(cell);
		txt = document.createTextNode(label);
		cell.appendChild(txt);
		//For now we do not set eventhandlers for onClick on cells and onMouseOver/Out on row
		//row.innerHtml does ont work on IE to specify the cells
		tbody.appendChild(row);
	}
	$replyScriptPiece
	 	$propName"."AddItem(pId, pLabel);
	}
</script> ";

//      this javascript code was reusing the row from the MtoNPropertyPage but IE did not like that
//		the code that is now used has the disadvantage of having a lot of the layout of the part
//		in quite complicated javascript. So in future we may switch to a debugged version of the code below
//		row = tableCell.parentNode.cloneNode(true);
//		table_deleteRow(tableCell.parentNode.parentNode.parentNode, tableCell.parentNode);
//		// replace image 
//		firstCell = getFirstNodeByTagName(row.childNodes, 'TD', 0);
//		getFirstNodeByTagName(firstCell.childNodes, 'IMG', 0).src='../images/delete.gif';
//		// add the row to the table
//		itemTable = getElement('". $this->getItemTableId(). "');
//		addCsQs_ToValueOf(id, document.detailsForm.pntMtoN$propName"."AddedIds);
//		tbody = getFirstNodeByTagName(itemTable.childNodes, 'TBODY', 0);
//		alert(row.tagName);
//		tbody.appendChild(row);
	}
	
	function getEditDetailsDialogReplyScriptPiece() {
		$prop = $this->getPropertyDescriptor();
		$propName = $prop->getName();
		$className = $prop->getType(). 'EditDetailsDialog';
		if (!$this->tryUseClass($className, $prop->getClassDir())) {
			$className = 'ObjectEditDetailsDialog';
			$this->useClass($className, $prop->getClassDir());
		}
		return pntCallStaticMethod($className, 'getReplyScriptPiece', $propName);
	}
}
?>