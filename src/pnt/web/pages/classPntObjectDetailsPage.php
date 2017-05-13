<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPage', 'pnt/web/pages');

/** Page showing property labels and editing property values of a single object
* By default shows properties specified by  getUiFieldPaths method 
* on the class of the shown object. Layout can be specialized, 
* @see http://www.phppeanuts.org/site/index_php/Pagina/150
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectDetailsPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/pages
*/
class PntObjectDetailsPage extends PntPage {

	public $object;
	public $formTexts;

	function getName() {
		return 'Details';
	}

	/** Polymorpism support: forward or redirect to proper page if requestedObject is of 
	* type different of pntType
	*/
	function handleRequest() {
		$this->useClass($this->getType(), $this->getDomainDir());
		$obj = $this->getRequestedObject();
		if (!$obj || $this->getType() == $obj->getClass()) 
			return parent::handleRequest(); // normal handling by this object
	
		$requestData = $this->requestData;
		$requestData['pntType'] = $obj->getClass();
		$handler = $this->getRequestHandler($requestData);

		$dir = $this->controller->getAppName($obj->getClassDir(), $requestData['pntType'], $this->getReqParam('pntHandler') ). '/';
		if ($dir != $this->getDir()) 
			$this->redirectRequest($requestData, $this->information, $dir);
				
		//forward to another page
		$handler->setRequestedObject($obj); //so that the page does not have to retrieve it again
		//the following may have been set by another handler
		$handler->setInformation($this->information); 
		$handler->setFormTexts($this->formTexts);
	 	$handler->setInfoStyle($this->infoStyle);

	 	$handler->handleRequest();
	}

	function initForHandleRequest() {
		// initializations
		parent::initForHandleRequest();
		$this->getRequestedObject();
		$this->getFormTexts();

	}

	/** Overridden to add subparts of DetailsPart 
	 * 2DO: add subparts of MultiPropsParts */
	function ajaxPrintUpdates($preFix='') {
		parent::ajaxPrintUpdates($preFix='');
		
		$part = $this->getPart(array('DetailsPart'));
		$part->ajaxUpdatePartIds = $this->getAjaxUpdateSubPartIds('DetailsPart.');
		$part->ajaxPrintUpdates('DetailsPart', $preFix.'DetailsPart');
	}
	
	/** Check access to a $this with the SecrurityManager. 
	* Forward to Access Denied errorPage and die if check returns an error message.
	*/
	function checkAccess() {
		$err = $this->controller->checkAccessHandler($this, 'ViewObject');
		if ($err) $this->controller->accessDenied($this, $err); //dies
	}

	function getButtonsList() {
		$buts = array();
		$this->addContextButtonTo($buts);
		if ($this->object) {
			$this->addDetailsButton($buts); 
			$this->addReportButtons($buts);
		}
		return array($buts);
	}
	
	function addReportButtons(&$buts) {
		$params = array('pntType' => $this->getType()  
			, 'id' => $this->getReqParam('id')
			, 'pntRef' => $this->getFootprintId()
			);
		$params['pntHandler'] = 'ReportPage';
		$hrefLit = $this->getConverter()->toJsLiteral($this->controller->buildUrl($params), "'");
		$buts[]=$this->getButton('Report', "popUpWindowAutoSizePos($hrefLit);");
	}

	function addDetailsButton(&$buts) {
		$params = array('pntType' => $this->getType()  
			, 'id' => $this->getReqParam('id')
			, 'pntRef' => $this->getFootprintId()
			);
		$hrefLit = $this->getConverter()->toJsLiteral($this->controller->buildUrl($params), "'");
		$buts[]=$this->getButton('Details', "document.location.href=$hrefLit;");
	}
	
	/** for ReportPage the default is to print the MultiPropsPart */
	function printEventualMultiPropsPart() {
		$this->printPart('MultiPropsPart');
	}

}
?>