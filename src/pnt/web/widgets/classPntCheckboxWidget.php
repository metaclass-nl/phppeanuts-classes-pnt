<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntFormWidget', 'pnt/web/widgets');

/** FormWidget that generates html specifying a checkbox. 
*
* This abstract superclass provides behavior for the concrete
* subclass CheckboxWidget in the widgets classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/widgets
*/
class PntCheckboxWidget extends PntFormWidget {

	/** @var string $checkedValue HTML */
	public $checkedValue;

	function getName() {
		return 'CheckboxWidget';
	}

//setters that extend the public interface
	/** @param string $checkedValue HTML */
	function setCheckedValue($checkedValue) {
		$this->checkedValue = $checkedValue;
	}

	function printBody() {
?> 
		<input type="CHECKBOX" name="<?php $this->printFormKey() ?>" id="<?php $this->printFormKey() ?>" value="<?php print $this->getCheckedValue() ?>" <?php $this->printChecked() ?> >
<?php
	}

	//print- and getter methods used by printBody
	function printChecked() {
		if ($this->value == $this->getCheckedValue())
			print 'checked';
	}
	
	function getCheckedValue() {
		if ($this->checkedValue)
			return $this->checkedValue;
			
		$cnv = $this->getConverter();
		$true = true;
		return $cnv->toHtml($cnv->toLabel($true, 'boolean'));
	}
	
	
}