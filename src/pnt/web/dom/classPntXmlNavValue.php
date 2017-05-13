<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntXmlNavText', 'pnt/web/dom');

/** @package pnt/web/dom
 * @depricated intermediate class
*/
class PntXmlNavValue extends PntXmlNavText {

	public $navigation;
	public $isAlwaysVisible = true;

	function __construct($whole, $itemType='Array', $path=null) {
		// do not add as part of the whole
		parent::__construct(null, $itemType, $path);
		// compensate for not being initialized from the whole
		$this->initFrom($whole);
	}

	function isAlwaysVisible() {
		return $this->isAlwaysVisible;	
	}
	
	function setAlwaysVisible($value) {
		$this->isAlwaysVisible=$value;
	}

	function getError() {
		return null;
	}

	function isReadOnly() {
		if (isSet($this->readOnly)) return $this->readOnly;
		
		$nav = $this->getNavigation();
		return $nav->isSettedReadOnly();
	}
	
	function setReadOnly($value) {
		$this->readOnly = $value;
	}

}
?>