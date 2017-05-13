<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPage', 'pnt/web/pages');

/** Page that serves as the main page of an application.
* By default includes skinIndexPage.php fromt the application folder. 
* Supports linking to search- and indexpages for domain classes
*
* This abstract superclass provides behavior for the concrete
* subclass IndexPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/pages
*/
class PntIndexPage extends PntPage {

	function initForHandleRequest() {
		// no PntType, 

		$this->startSession();
		$this->doScouting(); // must be done after starting the session!!
	}

	function getName() {
		return 'Index';
	}

	function getTypeLabel() {
		$dir = $this->getDir();
		return subStr($dir, 0, strLen($dir)-1);
	}
	
	/** Check access to a $this with the SecrurityManager. 
	* Forward to Access Denied errorPage and die if check returns an error message.
	*/
	function checkAccess() {
		$err = $this->controller->checkAccessHandler($this);
		if ($err) $this->controller->accessDenied($this, $err); //dies
	}

	function printMainPart() {
		$this->includeSkin($this->getName());
	}
}
?>