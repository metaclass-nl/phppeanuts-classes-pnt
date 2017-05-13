<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPagePart', 'pnt/web/parts');

/** Part that outputs the MainPart of PntObjectSortDialog
*
* This abstract superclass provides behavior for the concrete
* subclass SortDialogPart in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/parts
*/
class PntSortDialogPart extends PntPagePart {

	public $widgetDir = 'widgets';
	/** int number of sort criteria */
	public $nSorts = 3; //default, set in ::initNsorts from counting pntS.. parameters in any
	public $filters;
	
	/** HTML label of sort direction radio button */
	public $labelAscending = 'Ascending';
	public $labelDescending = 'Descending';

	function printBody() {
		$this->initNsorts();
		parent::printBody();
	}
	
	/** Find out how many sortdirection parameters there are. 
	* If any, initialize $this->nSorts accordingly
	*/
	function initNsorts() {
		$num = 1;
		while ($this->getReqParam("pntS$num"."d")) {
			$this->nSorts = $num;
			$num++;
		}
	}

	function printSortWidgets() {
		for ($i=1; $i<=$this->getNSorts(); $i++) {
			$this->printFilterSelectWidget($i);
			$this->printSortDirectionWidget($i);
			print "<BR>";
		}
	}	

	
	/** @return HTML String information for the end user */
	function getInformation() {
		$info = parent::getInformation();
		if (!$info)
			$info = $this->getInformationDefault();
		return $info;
	}
	
	function getInformationDefault() {
		return "Select one or more properties to sort by, and for each property the direction";
	}
	
	/** @return int number of sort criteria */
	function getNSorts() {
		return $this->nSorts;
	}
	
	function printSortDirectionWidget($num=1) {
		$checkedParams = $this->getSortDirection($num) != 'DESC'
			? array('CHECKED', '') 
			: array('', 'CHECKED');
		
		print "<INPUT TYPE='RADIO' NAME='pntS$num"."d' $checkedParams[0]>$this->labelAscending
		<INPUT TYPE='RADIO' NAME='pntS$num"."d' $checkedParams[1]>$this->labelDescending";
	}

	function printFilterSelectWidget($num=1) {
		Gen::includeClass('SelectWidget', $this->widgetDir);

		$filters = $this->getFilters();
		reset($filters);

		$selectedId = $this->getSortId($num);
		$widget = new SelectWidget($this, $this->requestData);
		$widget->setFormKey('pntS'.$num);
		$widget->setSelectedId($selectedId);
		$widget->setSettedCompulsory(false);
		$widget->setOptionsFromObjects($filters, 'PntSqlFilter');
		if ($num==1) 
			$widget->unselectLabel = $this->getReqParam('dsOptLbl');
		$widget->printBody();
	}
	
	function getSortId($num=1) {
		return $this->getReqParam('pntS'.$num);
	}
	
	function getSortDirection($num=1) {
		$param = $this->getReqParam('pntS'.$num.'d');
		return $param ? $param : 'ASC'; //default is ascending
	}

	function getFilters() {
		if ($this->filters) return $this->filters;

		$clsDes = $this->whole->getTypeClassDescriptor();
		$this->filters = $clsDes->getFilters(2); //does includeClass
		
		//make sure all paths are in selection lists
		for ($i=1; $i<=$this->getNSorts(); $i++) {
			 $path = $this->getSortId($i);
			 if ($path && !isSet($this->filters[$path]))
			 	$this->addFilter($path);
		}
		return $this->filters;
	}
	
	function addFilter($path) {
		$this->checkAlphaNumeric(str_replace('.', '_', $path));
		$this->filters[$path] = PntSqlFilter::getInstance($this->getType(), $path);
	}
}
?>