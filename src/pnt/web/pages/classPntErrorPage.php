<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPage', 'pnt/web/pages');

/** page used by ErrorHandler to show error message to end user.
* @see http://www.phppeanuts.org/site/index_php/Pagina/32
*
* This abstract superclass provides behavior for the concrete
* subclass ErrorPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
 * @package pnt/web/pages
*/
class PntErrorPage extends PntPage {

	function getLabel() {
		return ($this->getReqParam('status') == 401)
			? $this->getAccessDenied() : $this->getName();
	}

	function getName() {
		return 'Error';
	}
	
	function getAccessDenied() {
		return 'Access Denied';
	}

	function initForHandleRequest() {
		// do not try to useClass
		
		//session is needed for filters which may be used by the requestedObject
		$this->startSession();
		$this->doScouting(); // must be done after starting the session!!
	}

	/** Allow all to see this page 
	*/
	function checkAccess() {
		//ignore 
	}

	function ajaxShouldUpdate($partId, $partName=null, $extraParam=null) {
		return $partName == 'MainPart' || $partName == 'InformationPart';
	}

	function printMainPart() {
		print $this->getBody();
	}

	function printMenuPart() {
		print "<A href='javascript:history.back();' >back</A>";
	}

	function printInformationPart() {
		$this->htOut($this->getLabel());
	}

	function getBody() {
		if (isSet($this->whole->errorMessage))
			return $this->whole->errorMessage;
			
		$errorMessage = $this->getReqParam('errorMessage');
		if ($errorMessage) {
			$cnv = $this->getConverter();
			$errorMessage = $cnv->toHtml($errorMessage, true, true);
		} else {
			$errorMessage = $this->getDefaultErrorMessage();
		}
		return "<H2>$errorMessage<H2>";
	}
	
	function getDefaultErrorMessage() {
		return 'An error occurred';
	}
	
	/** Tell the scout how to interpret the requests
	* We do not want the ErrorPage to be included in the footprint trail
	* PRECONDITION: Session started
	*/
	function doScouting() {
		$scout = $this->getScout();
		//no call to $scout->moved
		//fool next page to think referrer was the previous page
		$this->footprintId = $scout->getReferrerId($this->requestData);
	}
}
?>