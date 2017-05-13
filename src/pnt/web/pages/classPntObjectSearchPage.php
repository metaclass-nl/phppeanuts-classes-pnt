<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObjectIndexPage', 'pnt/web/pages');

/** Kind of IndexPage with FilterFormPart for searching for objects. 
* Results are shown in a TablePart. Paging buttons are created by 
* a PntPagerButtonsListBuilder, whose classfolder is pnt/web/helpers.
* Columns of the TablePart can be specified in metadata on the class
* specified by pntType request parameter, 
* @see http://www.phppeanuts.org/site/index_php/Pagina/61
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectSearchPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/pages
*/
class PntObjectSearchPage extends PntObjectIndexPage {

	public $advancedFilterOverlayLeft = 120;
	
	function getName() {
		return $this->getSearchButtonLabel();
	}
	
	function getSearchButtonLabel() {
		return 'Search';
	}

	//we only need to override the filterPart printing, so we\
	//do not need a skin of our own. This gets the skin of the superclass
	function printMainPart() {
		$this->printPart('IndexPart');
	}

	function printFilterPart() {
		$this->printPart($this->getFilterFormPartName());
		$this->includeSkin('FilterPart');
	}
	
	function getFilterFormPartName() {
		return 'FilterFormPart';
	}

	function hasFilterForm() {
		return true;
	}

	/* Get the filterFormPart
	*	return $part;
	*/
	function getFilterFormPart() {
		$part = $this->getPart(array($this->getFilterFormPartName()));
		Gen::includeClass('PntSqlCombiFilter', 'pnt/db/query');
		$propFilter = $this->getPropertyFilter();
		if ($propFilter) {
			$combi = new PntSqlCombiFilter();
			$combi->addPart($propFilter);
			$part->setImplicitCombiFilter($combi);
		} 
		return $part;
	}
	
	function getPropertyFilter() {
		$propName = $this->checkAlphaNumeric($this->getReqParam('pntProperty'));
		if (!$propName) {
			$filter = null;
			return $filter;
		}
		
		$filter = PntSqlFilter::getInstance($this->getType(), $propName);
		$filter->setComparatorId('=');
		$filter->setValue1($this->getReqParam('id'));
		return $filter;	
	}

	//returns Array of objects
	function getRequestedObject() {
		if (isSet($this->object))
			return $this->object;

		$filterFormPart = $this->getFilterFormPart();
		if ($this->useDefault($filterFormPart)) 
			return $this->getRequestedObjectDefault();

		
			
		$this->object = $filterFormPart->getFilterResult(
			$this->isLayoutReport() ? 'All' : $this->getPageItemCount()
		);
		return $this->object;
	}

	function useDefault($filterFormPart) {
		return !$filterFormPart->getRequestedObject();
		
		//if you want to page through the default search results, try the following
		//(will give the default unless the user really enters some search value)
		//$filterId = $filterFormPart->getFilterId(1);
		//$param = $filterFormPart->getFilterValue1(1);
		//return !$filterFormPart->getRequestedObject()
		//	|| $filterId == 'All stringfields' && !$param && $param !== '0';
	}

	function getRequestedObjectDefault() {
		$this->object = array();
		return $this->object;
	}
	
	/** @return string HTML describing the active filter. */
		function getFilterPartString() {
		$filter = $this->getPropertyFilter();
		if (!$filter) return parent::getFilterPartString();
		
		$this->filterPartString = $this->getConverter()->toHtml( 
			$this->getFilterPartDescription($filter) );
		return $this->filterPartString;
	}
	
	function getFilterPartDescription($filter) {
		$propName = $filter->getPath();
		$description = $filter->getDescription($this->getConverter());
		if (subStr($propName, -2, 2) == 'Id') {
			$clsDes = $this->getTypeClassDescriptor();
			$prop = $clsDes->getPropertyDescriptor(subStr($propName, 0, strLen($propName) - 2));
			if ($prop) {
				Gen::includeClass($prop->getType(), $prop->getClassDir()); //from typeClassDescriptor
				$typeClsDes = PntClassDescriptor::getInstance($prop->getType());
				$obj = $typeClsDes->getPeanutWithId($filter->get('value1'));
				if ($obj)
					$description = $prop->getLabel(). " = '". $obj->getLabel(). "'";
			}
		} 
		return $description;
	}
	
	function getPageButtonScript($pageItemOffset) {
		$allItemsSize = Gen::asInt($this->getAllItemsSize());
		$pageItemOffset = Gen::asInt($pageItemOffset);
		$formName = $this->getFormName();
		$script = "document.$formName.allItemsSize.value='$allItemsSize';";
		$script .= " document.$formName.pageItemOffset.value='$pageItemOffset';";
		$script .= " document.$formName.submit();";
		return $script;
	}
	
	function getFormName() {
		$filterFormPart = $this->getFilterFormPart();
		return $filterFormPart->getFormName();
	}
	
	function getAllItemsSize() {
		$filterFormPart = $this->getFilterFormPart();
		return $filterFormPart->getAllItemsSize();
	}

	/** @return HTML info about the number of items and paging, here overridden to show errors if they exist */
	function getItemsInfo() {
		$filterFormPart = $this->getFilterFormPart();
		if ($filterFormPart->conversionErrors)
			return $filterFormPart->getErrorInfo();
		
		return parent::getItemsInfo();
	}
	
	function printItemTablePart() {
		$part = $this->getPart(array('TablePart'));
		$this->setTableHeaderSortParams($part);
		$part->printBody();
	}

	function getInitItemTable() {
		$part = $this->getPart(array('TablePart'));
		$this->setTableHeaderSortParams($part);
		return $part;
	}
	
	function setTableHeaderSortParams($table) {
		reset($table->headers);
		while (list($key, $label) = each($table->headers)) {
			$nav = $table->cells[$key]->getNavigation();
			$paths = $nav->getDbSortPaths(); //obtain paths from meta data
			$table->addHeaderSortParams($key, $this->getHeaderSortParams($paths));
		}
	}

	/** @return string HTML the onClick sort parameter piece of the table header tag
	* @param Array $paths with strings. Warning, do not obtain the paths
		from insecure sources or sanitize them first
	*/ 
	function getHeaderSortParams($paths) {
		$result = "";
		if (count($paths) == 0) return $result;
		
		$filterFormPart = $this->getFilterFormPart();
		$aantal = $filterFormPart->getNSorts();
		$result .= " onClick=\"pntFfpColSort( Array(";
		for ($i=0; $i<$aantal; $i++) {
			if ($i > 0) $result .= ',';
			$path = isSet($paths[$i]) ? $paths[$i]: '';
			$result .= $this->controller->converter->toJsLiteral($path, "'");
		}
		$result .= ") );\" style='cursor: s-resize;'";
		return $result;
	}	

	function ajaxPrintUpdates($preFix='') {
		parent::ajaxPrintUpdates($preFix);
		$this->ajaxPrintPartUpdate('FilterFormPart', $preFix.'FilterFormPart');
	}
}
?>