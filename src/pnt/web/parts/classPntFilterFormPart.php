<?php
/* Copyright (c) MetaClass, 2003-2013

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPagePart', 'pnt/web/parts');
Gen::includeClass('PntNavigation', 'pnt/meta');

/** Part used by SearchPage to output html describing search forms.
* The search options are modeled by pnt.db.query.PntSqlSpec objects.
* @see http://www.phppeanuts.org/site/index_php/Pagina/41
*
* This abstract superclass provides behavior for the concrete
* subclass FilterFormPart in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/parts
*/
class PntFilterFormPart extends PntPagePart {

	public $implicitCombiFilter;
	public $allItemsSize;
	public $filters;
	public $widgetDir = 'widgets';
	public $nAdvancedFilters = 5; //override on subclass to get different number of filters
	public $minSorts = 3; //override on subclass to get different number of sorts
	public $extraSortParams = array(); //override to pass extra parameters to the SortDialog, 
	public $conversionErrors = array();
	
	function getName() {
		return $this->whole->getFilterFormPartName();
	}

	function getNadvancedFilters() {
		return (int) $this->nAdvancedFilters;
	}
	
	function getNSorts() {
		$clsDes = $this->getTypeClassDescriptor();
		$labelSort = $clsDes->getLabelSort();
		return max(count($labelSort->getSortSpecFilters()), $this->minSorts);
	}

	function getFilterId($num=1) {
		return $this->getReqParam('pntF'.$num);
	}

	/** Returns filter comparator (unsanitized) */
	function getFilterCmp($num=1) {
		if (!isSet($this->requestData['pntF'.$num.'cmp']) ) return 'LIKE';
		//getReqParam sanitizes < away
		return $this->requestData['pntF'.$num.'cmp'];
	}

	function getFilterCombinators() {
		if (isSet($this->filterCombinators)) return $this->filterCombinators;
		
		$this->filterCombinators = array();
		for ($i=1; $i<=$this->nAdvancedFilters; $i++) {
			$value = $this->getFilterCombinator($i);
			if (!$value) return $this->filterCombinators;
			if ($value != 'AND' && $value != 'OR')
				throw new PntValidationException('Illegal filter combinator');
			$this->filterCombinators[$i] = $value;
		}
		return $this->filterCombinators;
	}

	function getFilterCombinator($num) {
		return $this->getReqParam("pntFC$num");
	}

	function getFilterValue1($num=1) {
		return $this->getReqParam('pntF'.$num.'v1');
	}

	function getFilterValue2($num=1) {
		return $this->getReqParam('pntF'.$num.'v2');
	}

	function getFilter1Id() {
		if (isSet($this->filter1Id) ) return $this->filter1Id;
			
		return isSet($this->requestData['pntF1']) ? $this->requestData['pntF1'] : null;
	}

	function getFilter1Cmp() {
		return $this->getFilterCmp();
	}

	function getFilter1Value1() {
		return $this->getFilterValue1();
	}
	
	function getFilter1Value2() {
		return $this->getFilterValue2();
	}

	function getAllItemsSize() {
		if ($this->allItemsSize !== null) return $this->allItemsSize;
			
		return $this->getReqParam('allItemsSize');
	}
			
	function getPageItemOffset() {
		return $this->getReqParam('pageItemOffset');
	}

	/** Wheather the sort is specific. If False the default sort is to be used
	* When first sortId is set empty by the SortDialog, return false
	* @return boolean
	*/
	function getSortIsSpecific() {
		return $this->getSortId() && $this->getReqParam('pntSiS');
	}

	function getSortId($num=1) {
		return $this->getReqParam('pntS'.$num);
	}
	
	function getSortDirection($num=1) {
		$param = $this->getReqParam('pntS'.$num.'d');
		return $param ? $param : 'ASC'; //default is ascending
	}

	function printSearchButtonLabel() {
		$this->htOut($this->whole->getSearchButtonLabel());
	}

	/** @return array with HTML strings */
	function getCombinatorOptions() {
		return array(
			'' => ''
			, 'AND' => 'AND'
			, 'OR' => 'OR'
			);
	}


	function printCancelButtonLabel() {
		print "Cancel"; //if variable use $this->htOut
	}

	function printDivDisplayStyle($divId) {
		$show = isSet($this->requestData['advanced']); //empty if no button at all -> advanced is default hidden
		if ($divId == 'simpleFilterDiv')
			$show = !$show;
		if ($show)
			print 'block';
		else
			print 'none';
	}

	function printExtraFormParameters() {
		forEach($this->getExtraFormParameterKeys() as $key) {
			print "<input type=hidden name='$key' value='";
			print $this->getReqParam($key, true);
			print "'>\n";
		}
	}
	
	function getExtraFormParameterKeys() {
		return array('pntProperty', 'pntLayout', 'id', 'pntContext', 'pntFormKey');
	}
	
	function printFilterSelectWidget($num=1) {
		Gen::includeClass('SelectWidget', $this->widgetDir);

		$filters = $this->getFilters();
		reset($filters);

		$selectedId = $this->getFilterId($num);
		$widget = new SelectWidget($this, $this->requestData);
		$widget->setFormKey('pntF'.$num);
		if (substr($selectedId, 0, 4) != 'All ')
			$widget->setSelectedId($selectedId);
		$widget->setAutoSelectFirst(true);
		$widget->setOptionsFromObjects($filters, 'PntSqlFilter');
		$widget->printBody();
	}
	
	function printComparatorSelectWidget($num=1) {
		Gen::includeClass('Comparator', '');
		Gen::includeClass('SelectWidget', $this->widgetDir);
		
		$selectedId = $this->getFilterCmp($num);

		$widget = new SelectWidget($this, $this->requestData);
		$widget->setFormKey('pntF'.$num.'cmp');
		$widget->setSelectedId($selectedId);
		$widget->setOnChangeScript("eventComparatorChanged(this.options[this.selectedIndex].value, 'comparatorDiv".$num."');", $num);
		$widget->setAutoSelectFirst(true);
		$widget->setOptionsFromObjects($this->getComparators(), 'PntComparator');
//		$widget->setWidthFromOptionsMaxLength(8);
		$widget->printBody();
	}

	function getComparators() {
		if (!isSet($this->comparators)) 
			$this->comparators = Comparator::getInstances();
		return $this->comparators;
	}

	function printCombinatorSelectWidget($num) {
		Gen::includeClass('SelectWidget', $this->widgetDir);
		$widget = new SelectWidget($this, $this->requestData);
		$widget->setFormKey('pntFC'.($num));
		$widget->setSelectedId($this->getFilterCombinator($num));
		$widget->setAutoSelectFirst(true);
		$widget->setOptions($this->getCombinatorOptions());
		$widget->setOnChangeScript('showHideFilterDivs();');
		$widget->setWidth('48px');
		$widget->display = $this->getFilterId($num) ? 'block' : 'none';
		$widget->printBody();
	}

	function getRequestedObject() {
		return $this->getFilter();
	}
	
	function printAdvancedFilterDescriptions() {
		$filter = $this->getFilter();
		if (!$filter || substr($filter->get('id'), 0, 4) == 'All ') return;

		$filters = $this->getInitializedAdvancedFilters();
		forEach(array_keys($filters) as $key) 
			$this->printAdvancedFilterDescription($filters[$key]);
	}
	
	function printAdvancedFilterDescription($filter) {
		$this->htOut($filter->getDescription($this->getConverter()));
		$combiOptions = $this->getCombinatorOptions();
		$option = $combiOptions[$this->getFilterCombinator($filter->get('id'))];
		print " <B>$option</B> ";
	}

	function printAdvancedFilterDivs() {
		for ($i=1; $i<=$this->nAdvancedFilters; $i++)
			$this->printAdvancedFilterDiv($i);
	}

	function printAdvancedFilterDiv($num) {
		$this->includeSkin('AdvFilterDiv', $num);
	}
	
	function showAdvancedFilterDiv($num) {
		return $num <= count($this->getFilterCombinators()) + 1;
	}
	
	function printSortParams() {
		$sortIsSpecific = $this->getSortIsSpecific(); //returns boolean
		print "<INPUT TYPE='hidden' name='pntSiS' value='$sortIsSpecific'>
		";
		$sort = $this->getSort($this->getFilter());
		$filters = $sort->getSortSpecFilters();
		$filterKeys = array_keys($filters);
		$cnv = $this->getConverter();
		for ($i=1; $i <= $this->getNSorts(); $i++) {
			if (isSet($filterKeys[$i-1])) {
				$filter = $filters[$filterKeys[$i-1]];
				$sortId = $cnv->toHtml($filter->getPath()); //comes from meta data
				$direction = $sort->getSortDirection($filter); //hardcoded as ASC or DESC
			} else {
				$sortId = '';
				$direction = 'ASC';
			}
			print "<INPUT TYPE='hidden' name='pntS$i' value='$sortId'>
		<INPUT TYPE='hidden' name='pntS$i"."d' value='$direction'>
		";
		}
	}

	function getFormName() {
		if (!$this->getFilter1Id() || $this->getFilter1Id() == 'All stringfields')
			return 'simpleFilterForm';
		else
			return 'advancedFilterForm';
	}

	function getFilter() {
		if (isSet($this->filter)) return $this->filter;

		$filterId = $this->getFilterId(1);
		if ($filterId == 'All stringfields') {
			$this->filter = $this->getAllStringfieldsFilter();
			return $this->filter;
		}

		if (!$filterId || !$this->getFilter1Cmp()) 
			return null;
		
		$this->filter = $this->getCombinedAdvancedFilters();
		return $this->filter;
	}

	/** @return PntSqlFilter */
	function getCombinedAdvancedFilters() {
		$filters = $this->getInitializedAdvancedFilters();
		$combinators = $this->getFilterCombinators();
		//PRECONDIION: count($filters) == count($combinators) + 1
		$result = $filters[1];
		reset($combinators);
		forEach($combinators as  $combinatorNum => $combinator ) {
			$nextFilter = $filters[$combinatorNum + 1];
			if ($combinator == 'AND') {
				$result = $this->advancedFilterCombine($result, $nextFilter, 'AND', $combinatorNum);
			} else {
				$result = $this->advancedFilterOr($result, $nextFilter, $combinatorNum);
			}
		}
//Gen::show($result);
		return $result;
	}

	function advancedFilterCombine($currentFilter, $nextFilter, $combinator, $id) {
		$result = $this->getNewCombiFilter($id);
		$result->set('combinator', $combinator);
		$result->addPart($currentFilter);
		$result->addPart($nextFilter);
		return $result;
	}

	function advancedFilterOr($currentFilter, $nextFilter, $id) {
		if (!Gen::is_a($currentFilter, 'PntSqlCombiFilter') || $currentFilter->get('combinator') != 'AND')
			return $this->advancedFilterCombine($currentFilter, $nextFilter, 'OR', $id);

		//replace currentFilter->parts[*last*] by new OR($lastPart, $nextFilter)
		$parts = $currentFilter->getParts();
 		$partKeys = array_keys($parts);
 		$lastPartKey = $partKeys[count($partKeys)-1] ;
 		$lastPart = $parts[$lastPartKey];
 		unSet($parts[$lastPartKey]);
 		$currentFilter->addPart($this->advancedFilterCombine($lastPart, $nextFilter, 'OR', $id) );
		return $currentFilter;
	}

	/** @return array of PntSqlFilter 
	 * @throws PntValidationException if filter not found
	 * Enter description here ...
	 */
	function getInitializedAdvancedFilters() {
		$filters = $this->getFilters();
		$returnFilters=array();
		$num = count($this->getFilterCombinators()) + 1;
		for ($i=1; $i<=$num; $i++) {
			$id = $this->getFilterId($i);
			if (!isSet($filters[$id])) 
				throw new PntValidationException("Filter not found for ". $this->getFilterId($i));
			$filter = $filters[$id]; 
			$filter = clone $filter;
			$filter->id = $i;
			$this->initFilter($filter, true, $i);
			$returnFilters[$i] = $filter; 
		}
//printDebug($returnFilters);
		return $returnFilters;
	}

	function getFilters() {
		if (!$this->filters) {
			$clsDes = $this->whole->getTypeClassDescriptor();
			$this->filters = $clsDes->getFilters(2); //does includeClass
		}
		return $this->filters;
	}

	function getNewCombiFilter($id) {
		Gen::includeClass('PntSqlCombiFilter', 'pnt/db/query');
		$comfilter = new PntSqlCombiFilter();
		$comfilter->set('id', $id);
		return $comfilter;
	}

	//now gets all fields filter
	function getAllStringfieldsFilter() {
		$clsDes = $this->whole->getTypeClassDescriptor();
		$filters = $this->getFilters(); 
		$result = $clsDes->getAllFieldsFilter($filters, null);
		$result = clone $result; //recursively copies the filter
		//$this->initFilter($result, true);
		$parts = $result->get('parts');
		forEach (array_keys($parts) as $key) {
			$filter = $parts[$key];
			$this->initFilter($filter, true);
			if (isSet($this->conversionErrors[$filter->getPath()]))
				unSet($result->parts[$key]);
//			print "<br>\n". $filter->getPath();
//			print " ". $filter->get('value1');
		}
		$this->conversionErrors = array(); 
		return $result;
	}

	/** @return HTML info about the sringconversion and validation errors */
	function getErrorInfo() {
		$result = '';
		$filter = $this->getFilter();
		forEach($this->conversionErrors as $path => $error) {
			if ($result) $result .= '; ';
			$errFilter = $this->findFilter($this->getFilter(), $path);
			$result .= $errFilter ? $errFilter->getLabel() : $path;
			$result .= ": $error";
		}
		return $this->getConverter()->toHtml($result, true);
	}
	
	/**
	 * Recursive search through CombiFilter tree
	 * @param PntSqlFilter $filter The filter to search through
	 * @param string $path the path of the filter searched for
	 * @return PntSqlFilter the filter, or null if not found
	 */
	function findFilter($filter, $path) {
		if (Gen::is_a($filter, 'PntSqlCombiFilter')) {
			forEach($filter->getParts() as $part) {
				$found = $this->findFilter($part, $path);
				if ($found) return $found;
			}
		} else { 
			if ($filter->getPath() == $path) return $filter;
		}
	}
		
	function initFilter($filter, $addWildcards=false, $num=1) {
		if (!$filter) return null;

		$conv = $this->getConverter();
		$filter->initConverter($conv);

		$valueType = $filter->getValueType();
		$cmp = $this->getFilterCmp($num);
		if ($cmp) {
			if ($cmp=='LIKE' && $valueType!="string") //illegal
				$cmp='=';
		} else {
			$cmp = $valueType=="string" ? 'LIKE' : '=';
		}
		if (!Comparator::getInstance($cmp, $valueType))
			throw new PntValidationException("'$cmp' is not a valid comparator for valuetype '$valueType'");
			
		$filter->set('comparatorId', $cmp);

		$fv1 = $this->getFilterValue1ForInit($filter, $num);
		if ($addWildcards && in_array($filter->get('comparatorId'), array('LIKE', 'NOT LIKE'))
				&& $filter->get('valueType') == 'string')
			$fv1 = $this->addWildcards(trim("$fv1"));
		
		//may have to include valueType class
		$lastFilter = $filter->getLast();
		$clsDes = PntClassDescriptor::getInstance($lastFilter->getItemType());
		$prop = $clsDes->getPropertyDescriptor($lastFilter->get('key'));
		if($prop && !$prop->isTypePrimitive)
			Gen::includeClass($prop->getType(), $prop->getClassDir());
		
		$filter->set('value2', $this->convertFilterValue($conv, $filter, $this->getFilterValue2($num)) );
		$filter->set('value1', $this->convertFilterValue($conv, $filter, $fv1 ) );
	}

	//for override
	//by default '' is converted to NULL. This is the same as in SaveAction,
	//so normally search should work on Pnt edited data
	function convertFilterValue($conv, $filter, $value) {
		$result = $conv->fromLabel($value);
		if ($conv->error)
			$this->conversionErrors[$filter->getPath()] = $conv->error;
		return $result;
	}
	
	// for override 
	function getFilterValue1ForInit($filter, $num=1) {
		return $this->getFilterValue1($num);
	}

	function addWildcards($filterValue)
	{
		if (strpos($filterValue, '*') !== 0)
			$filterValue = '*'.$filterValue;
		if (strrpos($filterValue, '*') !== strLen($filterValue) - 1)
			$filterValue .= '*';
		return $filterValue;
	}
	
	function getSort($filter) {
		if ($this->getSortIsSpecific())
			$sort = $this->getSortSpecified();
		else
			$sort = $this->getSortDefault($filter);
		$sort->setFilter($filter);
		return $sort;
	}
	
	function getSortSpecified() {
		Gen::includeClass('PntSqlSort', 'pnt/db/query');
		$sort = new PntSqlSort('filterForm', $this->whole->getType() );
		for ($i=1; $i<=$this->getNSorts(); $i++) {
			$path = $this->getSortId($i);
			if (!$path) break;
			$sort->addSortSpec($path, $this->getSortDirection($i));
		}
		return $sort;
	}

	/** Default is to sort ascentding by the filter path,
	* undersorted by the type labelSort. If the filter can not be
	* used as a sortSpec, The labelSort is used 
	*/
	function getSortDefault($filter) {
		$clsDes = $this->whole->getTypeClassDescriptor();
		$sort = $clsDes->getLabelSort();

		if ($filter !== null && $filter->canBeSortSpec()) {
			//sort by the filter criterium first if there will be different values for it
			$filtersUniqueValue = array('=' => true, 'IS NULL' => true);
			if (!isSet($filtersUniqueValue[$filter->get('comparatorId')]) ) {
				//for some unknown reason assigning by value does not copy the filter, 
				//so we must create a new one to get rid of the comparatorId without modifying the filter itself
				$nav = $filter->getNavigation();
				$resultType = $nav->getResultType();
				if (is_subclassOr($resultType, 'PntDbObject')) {
					//add labelSort specs
					$clsDes = PntClassDescriptor::getInstance($resultType);
					$resultTypeSort = $clsDes->getLabelSort();
					forEach(array_reverse($resultTypeSort->getSortNavs()) as $resultSortNav) {
						$extendedNav = clone $nav;
						$extendedNav->setNext($resultSortNav);
						$sortSpec = PntSqlFilter::getInstanceForNav($extendedNav);
						$sort->addSortSpecFilterFirstOrMoveExistingFirst($sortSpec);
					}
				} else { //should check for other objects ...
					$sortSpec = PntSqlFilter::getInstance($filter->get('itemType'), $filter->getPath() );
					$sort->addSortSpecFilterFirstOrMoveExistingFirst($sortSpec);
				}
			}
		}
		return $sort;
	}
	
	/** Returns the filter from getRequestedObject combined with the 
   * implicitCombiFilter. Assumed to be called only once!
	*/
	function getCombinedFilter() {
		$combiFilter = $this->getImplicitCombiFilter();
		if (!$combiFilter)
			return $this->getRequestedObject();
		
		$obj = $this->getRequestedObject();
		if ($obj) $combiFilter->addPart($obj);
		return $combiFilter; 
	}
	
	//PRECONDITION: getCombinedFilter() does not return null
	/** @throws PntError */
	function getFilterResult($rowCount=20) {
		if (isSet($this->result)) return $this->result;
		
		$size = $this->getAllItemsSize();
		if ($size && !(is_numeric($size) && $size == (int) $size) ) 
			throw new PntValidationException('All items size no integer: '. $size);
		$offset = $this->getPageItemOffset();
		if ($offset && !(is_numeric($offset) && $offset == (int) $offset) ) 
			throw new PntValidationException('Page item offset no integer: '. $offset);
		
		$filter = $this->getCombinedFilter();
		$sort = $this->getSort($filter);

		$clsDes = $this->whole->getTypeClassDescriptor();
		$qh = $clsDes->getSimpleQueryHandler();
		
		if (!($rowCount == 'All' || $size || $qh->supportsSelectRowCount())) {
			$tableName = $clsDes->getTableName();
			$qh->query = "SELECT count(DISTINCT($tableName.id)) FROM $tableName";
			if ($clsDes->polymorphismPropName) 
				$qh->joinAllById($clsDes->getTableMap(), $tableName);
			
			$qh->addSqlFromSpec($filter, false);
			$this->allItemsSize = $qh->getSingleValue(null, 'Error counting search result');
		}
		
		$qh = $clsDes->getSelectQueryHandler(); //zonder SELECT .. getSimpleQueryHandler
		$qh->addSqlFromSpec($sort);
//print $qh->query;
		if ($rowCount == 'All') {
			$result = $clsDes->getPeanutsRunQueryHandler($qh);
			$this->allItemsSize = count($result);
		} elseif (is_numeric($this->getAllItemsSize())) {
			$qh->limit($rowCount, $offset);
			$result = $clsDes->getPeanutsRunQueryHandler($qh);
		} else {
			$result = $this->runQhStoreAllItemsSize_getItemsLimitedTo($qh, $rowCount);
		}

		$this->result = $result;
		return $result;
	}
	
	/** @throws PntError */
	function runQhStoreAllItemsSize_getItemsLimitedTo($qh, $rowCount) {
		$clsDes = $this->whole->getTypeClassDescriptor();
		$qh->runQuery();
		$this->allItemsSize = $qh->getRowCount();
		$result = array();
		$offset = $this->getPageItemOffset();
		if ($this->allItemsSize > $offset) {
			$i = 0;
			$qh->dataSeek($offset);
			while ( $i < $rowCount && ($row=$qh->getAssocRow()) ) {
				$instance = $clsDes->getDescribedClassInstanceForData($row, null);
				$result[] = $instance;
				$i++;
			}
		}
		$qh->release();	
		return $result;
	}
	
	function getImplicitCombiFilter() {
		if (!isSet($this->implicitCombiFilter)) return $this->getImplicitCombiFilterDefault();
		return $this->implicitCombiFilter;
	}

	function getImplicitCombiFilterDefault() {
		return $this->whole->getGlobalCombiFilter();
	}
	
	function setImplicitCombiFilter($combiFilter) {
		$this->implicitCombiFilter = $combiFilter;
	}
	
	function printSortDialogScripts() {
		$className = $this->whole->getType().'SortDialog';
		$result = $this->tryUseClass($className, $this->getDir());
		if (!$result) {
			$className = 'ObjectSortDialog';
			$this->useClass($className, $this->getDir());
		}
		$size = pntCallStaticMethod($className, 'getMinWindowSize');
		$x = (int) $size->x;
		$y = (int) $size->y;
		$replyFunctionHeader = pntCallStaticMethod($className, 'getReplyScriptPiece');
		$params = $this->extraSortParams;
		$params['pntHandler'] = 'SortDialog';
		$params['pntType'] = $this->getType();
		$urlLit = $this->getConverter()->toJsLiteral($this->controller->buildUrl($params), "'");
		print "
		func"."tion openSortDialog() {
			form = pntGetFilterFormVisible();
			var url = $urlLit;
			var i = 0;
			while (form.elements['pntS' + (i+1) + 'd']) {
				var param = 'pntS' + (i+1);
				url = url + '&' + encodeURIComponent(param) + '=' + encodeURIComponent(form.elements[param].value);
				url = url + '&' + encodeURIComponent(param) + 'd=' + encodeURIComponent(form.elements[param+'d'].value);
				i = i + 1;
			}
			popUpWindow(url,$x,$y,100,10);
		}
		$replyFunctionHeader
			form = pntGetFilterFormVisible();
			for (i=0; i<directions.length; i++) {
				param = 'pntS' + (i+1);
				form.elements[param].value = paths[i];
				form.elements[param+'d'].value = directions[i];
			}
			form.pntSiS.value = '1';
			form.submit();
		}";
	}

	/** @depricated **/
	function getOwnFormParameterKeys() {
		$result = array('pntType', 'pntHandler', 'pntScd', 'pntRef'
			, 'simple', 'advanced', 'allItemsSize', 'pageItemOffset', 'pntSiS');

		for ($i=1; $i <= $this->nAdvancedFilters; $i++) {
			$result[] = 'pntF'. $i;
			$result[] = 'pntF'. $i. 'cmp';
			$result[] = 'pntF'. $i. 'v1';
			$result[] = 'pntF'. $i. 'v2';
			$result[] = 'pntFC'. $i;
		}
		for ($i=1; $i <= $this->getNSorts(); $i++) {
			$result[] = 'pntS'. $i;
			$result[] = 'pntS'. $i. 'd';
		}
		return $result;
	}

	/** @depricated */
	function _runQhStoreAllItemsSize_getItemsLimitedTo($qh, $rowCount) {
		try {
			return $this->runQhStoreAllItemsSize_getItemsLimitedTo($qh, $rowCount);
		} catch (PntError $err) {
			return $err;
		}
	}
}
?>