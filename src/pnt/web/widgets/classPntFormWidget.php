<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPagePart', 'pnt/web/parts');

/** Abstract FormWidget superclass that generates html 
* specifying an input type=Text.
*
* There is no concrete subclass for this class, 
* if you need one, please add it to the widgets classFolder.
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/widgets
*/
class PntFormWidget extends PntPagePart {

	public $value;
	public $formKey;
	public $cssClass = 'pntFormWidget'; //to be overridden by subclass
	public $error;
	
	function __construct($whole, $requestData, $formText=null) {
		parent::__construct($whole, $requestData);
		$this->initialize($formText);
	}

	function initialize($formText) {
		if ($formText) {
			$this->setFormKey($formText->getFormKey());
			$reqObj = isSet($formText->item)
				? $formText->item
				: $this->getRequestedObject();
			$nav = $formText->getNavigation();
			if (isSet($formText->contentLabel) || $nav->isSingleValue())
				$this->setValue($formText->getMarkupWith($reqObj));
			$this->setError($formText->getError());
		}
	}

	//getName

//setters that form the public interface
	/** @param string $formKey must be alphanumeric according to checkAlphaNumeric (caller must check that if not sure) */
	function setFormKey($value) {
		$this->formKey = $value;
	}

	/** @param HTML $value the value of the value attribute of the tag */
	function setValue($value) {
		$this->value = $value;
	}
	
	/** @param $errorMessage string HTML used as the value of the title attribute of the tag.
	 * 	If set, the widget is highlighted as holding an errorneous value  */
	function setError($errorMessage) {
		$this->error = $errorMessage;
	}

	//just demonstrating...
	function printBody() {
?>
		<input type='hidden' name='<?php $this->printFormKey() ?>' id='<?php $this->printFormKey() ?>' value='<?php $this->printValue() ?>'>
<?php
	}

	//print- and getter methods used by printBody

	/** print string alphaNumeric according to checkAlphaNumeric */ 
	function printFormKey() {
		$this->htOut($this->formKey);
	}

	/** print string HTML the value of the value attribute of the tag */
	function printValue() {
		print $this->value;
	}

	function initDialogUrlNoId($nav) {
		//ignore
	}
	
	/** @return string HTML the value of the class attribute of the tag */
	function getCssClass() {
		return $this->cssClass. $this->getCssClassAdder();
	}
	
	/** @return string HTML added by getCssClass to highlight an error */
	function getCssClassAdder() {
		if ($this->error) return ' error';
	}
	
	/** print string HTML the value of the title attribute of the tag */
	function printTitle() {
		$this->htOut($this->error);
	}
	
}