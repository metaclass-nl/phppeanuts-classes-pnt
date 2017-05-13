<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntFormWidget', 'pnt/web/widgets');

/** FormWidget that generates html specifying an input type=Text.
*
* This abstract superclass provides behavior for the concrete
* subclass TextWidget in the widgets classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/widgets
*/
class PntTextWidget extends PntFormWidget {

	public $size = 40;
	public $cssClass = 'pntTextWidget';

	function getName() {
		return 'TextWidget';
	}

	//setters that extend the public interface

	/** @param HTML $value the value of the size attribute of the input tag */
	function setSize($value) {
		$this->size = (int) $value;
	}

	/** @param HTML $value the value of the type attribute of the input tag */
	function setType($value) {
		$this->type = $value;
	}

	/** @param HTML $value the value of the maxlength attribute of the input tag */
	function setMaxLength($value) {
		$this->maxLength = (int) $value;
	}

	function printBody() {
		?><input type="<?php print $this->getType() ?>" name="<?php $this->printFormKey() ?>" id="<?php $this->printFormKey() ?>" size="<?php $this->printSize() ?>" maxlength="<?php $this->printMaxLength(); ?>" value="<?php $this->printValue() ?>" class="<?php print $this->getCssClass() ?>" title="<?php $this->printTitle() ?>"><?php
	}

	/** print HTML the value of the size attribute of the input tag */
	function printSize() {
		print $this->size;
	}

	/** @return string HTML the value of the type attribute of the input tag */
	function getType() {
		if (isSet($this->type)) return $this->type;
		return 'TEXT';
	}

	/** print HTML the value of the maxlength attribute of the input tag */
	function printMaxLength() {
		if (isSet($this->maxLength)) print $this->maxLength;
	}
	
}