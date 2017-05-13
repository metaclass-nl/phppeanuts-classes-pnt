<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPagePart', 'pnt/web/parts');

/** Part that outputs html descirbing two rows of buttons.
*
* This abstract superclass provides behavior for the concrete
* subclass ButtonsPanel in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/parts
*/
class PntButtonsPanel extends PntPagePart {

	/** int $buttonType key to array in buttonsList to be printed or null if all should be printed */ 
	public $buttonType;
	public $buttonsList;
	
	public $typeSeparator = "</TD></TR><TR id='Buttons1'><TD>";
	public $buttonSeparator = "&nbsp;";

	function setButtonType($value) {
		$this->buttonType = $value;
	}

	function getName() {
		return 'ButtonsPanel';
	}

	function printBody($args=null) {
		if (isSet($args[1]))
			$this->setButtonType($args[1]);
		parent::printBody();
	}

	function ajaxPrintUpdates($preFix='') {
		$buttonsList = $this->getButtonsList();
		while (list($key) = each($buttonsList)) {
			$this->buttonType = $key;
			$this->ajaxPrintPartUpdate('ButtonsListPart', $preFix."Buttons$key");
		}
	}

	//called by parent::printBody
	function printButtonsListPart() {	
		$buttonsList = $this->getButtonsList();

		if ($this->buttonType !== null) { //only print the ones from type
			if (isSet($buttonsList[$this->buttonType]))
				$this->printButtons($buttonsList[$this->buttonType], $this->buttonType);
		} else {
			//if no buttontype is set, print all
			while (list($key) = each($buttonsList)) {
				$this->printTypeSeparator($key);
				$this->printButtons($buttonsList[$key], $key);
			}
		}
	}
	
	/** @param array $parts of PntButtonPart 
	 * @param int $type may be used by the button to decide its cssClass
	 */
	function printButtons($parts, $type) {
		$notFirst = false;
		forEach (array_keys($parts) as $i) {
			if ($notFirst)
				$this->printButtonSeparator($i, $type);
			$this->printButton($parts[$i], $type);
			$notFirst = true;
		}
	}
	
	function printTypeSeparator($type) {
		if ($type)
			print $this->typeSeparator;
	}
	
	function printButton($button, $type) {
		print $button->printBody(array(), $type);
	}
	
	function printButtonSeparator($key, $type) {
		if ($key)
			print $this->buttonSeparator;
	}

	/** @return array of arrays of PntButtonPart. The keys of the outer array correspond to the buttontype. s*/
	function getButtonsList() {
		if (!isSet($this->buttonsList))
			$this->buttonsList = $this->whole->getButtonsList();

		return $this->buttonsList;
	}

}
?>