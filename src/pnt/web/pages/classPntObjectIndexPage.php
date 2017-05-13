<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPage', 'pnt/web/pages');

/** Page showing a TablePart with all instances of a class. 
* Paging buttons are created by a PntPagerButtonsListBuilder, 
* whose classfolder is pnt/web/helpers.
* Columns of the TablePart can be specified in metadata on the class
* specified by pntType request parameter, 
* @see http://www.phppeanuts.org/site/index_php/Pagina/61
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectIndexPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/pages
*/
class PntObjectIndexPage extends PntPage {

	public $items;
	public $itemsAnnouncement = 'Item(s)';
	public $allItemsSizeAnnouncement = 'from';

	function getName() {
		return 'Index';
	}

	function initForHandleRequest() 
	{
		parent::initForHandleRequest();
		$this->getRequestedObject();
	}
	
	/** @return HTML */
	function getInformation() {
		$info = parent::getInformation();
		if ($info)
			$info .= '<BR><BR>';
			
		return $info. $this->getItemsInfo();
	}
	
	/** @return HTML info about the number of items and paging */
	function getItemsInfo() {
		$obj = $this->getRequestedObject();
		if (count($obj) == 0)
			return '';
			
		return "$this->itemsAnnouncement ". ($this->getPageItemOffset() + 1)
			. ' - '. ($this->getPageItemOffset() + count($obj))
			. " $this->allItemsSizeAnnouncement ". $this->getAllItemsSize() ;
	}
	

	/** @return Array of objects
	 * @throws PntError
	 */
	function getRequestedObject() {
		if (isSet($this->object))
			return $this->object;
			
		$clsDes = $this->getTypeClassDescriptor();
		$filter = $this->getGlobalFilter();
		
		if (!Gen::is_a($clsDes, 'PntDbClassDescriptor') ||
			$filter && !$filter->appliesTo($this->getType(), true)) {
			$this->object = $filter
				? $clsDes->getPeanutsAccordingTo($filter)
				: $clsDes->getPeanuts();
			$this->allItemsSize = count($this->object);
			return $this->object;
		}
		$qh = $clsDes->getSelectQueryHandler();
		$sort = $clsDes->getLabelSort();
		$sort->setFilter($filter);
		$qh->addSqlFromSpec($sort);
		if (!$this->isLayoutReport()) 
			$qh->limit($this->getPageItemCount(), $this->getPageItemOffset());
		
//print $qh->query;		
		$this->object = $clsDes->getPeanutsRunQueryHandler($qh);
		return $this->object;
	}

	function getButtonsList() {
		$cnv = $this->getConverter();
		$navButs = array();
		$actButs = array();
		
		if (!$this->isLayoutReport()) {
			$builder = $this->getPagerButtonsListBuilder();
			$builder->addPageButtonsTo($navButs);
		}
		if (!$this->isReadonly()) {
			$params = array('pntHandler' => 'EditDetailsPage'
				, 'pntType' => $this->getType() 
				, 'pntRef' => $this->getFootprintId() 
				);
			$urlLit = $cnv->toJsLiteral($this->controller->buildUrl($params), "'");
			$actButs[]= $this->getButton("New", "document.location.href=$urlLit;");
			$actButs[]= $this->getButton("Delete", "pntDeleteButtonPressed(); ");
//			$actButs[] = $this->getButton('Copy', "document.itemTableForm.pntHandler.value='CopyMarkedAction'; document.itemTableForm.submit();");
		} 
		$tableIdLit = $this->isLayoutReport() ? '' : $cnv->toJsLiteral($this->getItemTable()->getTableId(), "'");
		$actButs[]=$this->getButton('Report', "pntSelectionReport($tableIdLit);");
		if ($this->isLayoutReport()) 
			$actButs[]= $this->getButton("Print", "window.print();");

		return array($actButs, $navButs);
	}
	
	function isReadonly() {
		return $this->isLayoutReport();
	}

	function getDeleteConfirmationQuestion() {
		return 'Are you sure you want to delete the marked items from the database?';
	}

	function printIndexPart() {
		if ($this->isLayoutReport()) 
			return $this->includeSkin('IndexReportPart');


		$this->includeSkin('IndexPart');
		$this->printDeleteScript();
	}

	function printDeleteScript() {
		$cnv = $this->getConverter();
		$this->useClass('ObjectVerifyDeleteDialog', $this->getDir());
		$dialogSize = ObjectVerifyDeleteDialog::getMinWindowSize();
		$x = (int) $dialogSize->x;
		$y = (int) $dialogSize->y;
		$callbackFuncHead = ObjectVerifyDeleteDialog::getReplyScriptPiece();
		$noItemsMarkedLiteral = $cnv->toJsLiteral($this->getNoItemsMarkedMessage(), '"'); 
		print "
<script>
	func"."tion pntSubmitDelete() {
		document.itemTableForm.submit();
	}
	func"."tion pntDeleteButtonPressed() {
		pntVerifyAndDeleteMarked(\"itemTableForm\", $noItemsMarkedLiteral, $x, $y);
	}
	$callbackFuncHead
		pntSubmitDelete();
	}
</script>\n";		
	}

	function getNoItemsMarkedMessage() {
		return 'Please mark some items by clicking in the checkboxes in front of each row';
	}

	function printBodyTagIeExtraPiece()	{
		if (!$this->isLayoutReport()) 
			parent::printBodyTagIeExtraPiece(); //switches off scrollbar in IE
		//else do not switch off scrollbar
	}


	function printItemTablePart() {
		$part = $this->getItemTable();
		$part->printBody();
	}

	function getItemTable() {
		if (!isSet($this->itemTable))
			$this->itemTable = $this->getInitItemTable();
		return $this->itemTable;
	}
	
	function getInitItemTable() {
		return $this->getPart(array('TablePart'));
	}
	
	function hasFilterForm() {
		return false;
	}

	/** Callback method for PntPagerButtonsListBuilder */
	function getPageButtonScript($pageItemOffset) {
		$cnv = $this->getConverter();
		$params = array('pntHandler' => 'IndexPage'
			, 'pntType' => $this->getType() //checked lphNumeric
			, 'pageItemOffset' => Gen::asInt($pageItemOffset)
			, 'pntRef' => $this->getFootprintId() //int
			);
		$urlLit = $cnv->toJsLiteral($this->controller->buildUrl($params), "'");
		return "document.location.href=$urlLit;"; 
	}
	
	function getAllItemsSize() {
		if (isSet($this->allItemsSize)) return $this->allItemsSize;
			
		$clsDes = $this->getTypeClassDescriptor();
		$filter = $this->getGlobalFilter();
		
		$this->allItemsSize = $clsDes->getPeanutsCount($filter);
		return $this->allItemsSize;
	}
			
	function getPageItemOffset() {
		return isSet($this->requestData['pageItemOffset'])
			? (integer) $this->requestData['pageItemOffset'] : 0;
	}

	function getPageItemCount() {
		return 20;
	}
	
	function getPagerButtonsListBuilder() {
		Gen::includeClass('PntPagerButtonsListBuilder', 'pnt/web/helpers');
		$builder = new PntPagerButtonsListBuilder($this);
		$this->initPagerButtonsListBuilder($builder);
		return $builder;
	}
	
	function initPagerButtonsListBuilder($builder) {
		$builder->setItemCount($this->getAllItemsSize());
		$builder->setPageItemOffset($this->getPageItemOffset());
		$builder->setPageItemCount($this->getPageItemCount());
	}
	
	/** Return a combiFilter for combing the global filters with 
   * the filter of the searchPart.
	* This method may be overriden for applicable logical combination of filters
	* Default implementation: PntSqlCombiFilter withParts: this getGlobalFilters
	* currently only used by custom subclasses
	* Override getGlobalFIlters on custom subclass to select applicable filters
    * and make sure all applicable filter classes are included.
	*/
	function getGlobalCombiFilter() {
		$filter = $this->getGlobalFilter();
		if (!$filter) return null;
		
		Gen::includeClass('PntSqlCombiFilter', 'pnt/db/query');
		$combi = new PntSqlCombiFilter();
		$combi->addPart($filter);
		return $combi;
	}
	
	function getGlobalFilter() {
		return null;
	}
	
	/** @return string HTML describing the active filter.
	 * default the active filter is the first global filter that applies to the type of the page. 
	 * here overridden to only show filters that apply with persistence */
	function getFilterPartString() {
		if (isSet($this->filterPartString)) return $this->filterPartString;

		Gen::includeClass('PntSqlFilter', 'pnt/db/query');
		$cnv = $this->getConverter();
		$filter = $this->getGlobalFilter();
		$this->filterPartString = $filter
			? $cnv->toHtml($filter->getDescription($cnv)) : '';

		return $this->filterPartString;
	}
	
	function getThisPntContext()
	{
		$type = $this->getType(); //checked alphaNumeric
		return "$type**";
	}
	
	function ajaxPrintUpdates($preFix='') {
		parent::ajaxPrintUpdates($preFix);
		$this->ajaxPrintPartUpdate('FilterPart', $preFix.'FilterPart');
		$this->ajaxPrintPartUpdate('ItemTablePart', $preFix.'ItemTablePart');
	}
}
?>