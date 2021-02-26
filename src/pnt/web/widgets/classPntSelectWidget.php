<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntFormWidget', 'pnt/web/widgets');

/** FormWidget that generates html specifying 
* a SELECT tag, by default as a dropdown list
* with values that are options for the property.
* @see http://www.phppeanuts.org/site/index_php/Pagina/128
*
* Limitation: PntSelectWidget assumes the id in the form, the idProperty
* and the id of the selected object in the property options to be equal.
* If the derived property's getter and setter behave differently,
* The SelectWidget may not be able to find the selected object in the list.
*
* This abstract superclass provides behavior for the concrete
* subclass SelectWidget in the widgets classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/widgets
*/
class PntSelectWidget extends PntFormWidget {
	
	public $settedCompulsory = true;
	public $autoSelectFirst = false;
	public $width;
	/** string label of the option by which to select 'no value' */
	public $unselectLabel = '';
	public $cssClass = 'pntSelectWidget';
	public $selectedId;

	function __construct($whole, $requestData, $formText=null, $optionItems=null) {
		parent::__construct($whole, $requestData);
		$this->initialize($formText, $optionItems);
	}

	function getName() {
		return 'SelectWidget';
	}

	/** @throws PntError */
	function initialize($text, $options=null) {
		parent::initialize($text);
		if ($text) {
			$reqObj = isSet($text->item)
				?  $text->item
				: $this->getRequestedObject();

			$nav = $text->getNavigation();
			$this->setSettedCompulsory($nav->isSettedCompulsory());
			if ($text->contentLabel !== null) {
				$text->setItem($reqObj);
				$text->setConvertMarkup($text->contentLabel); //id's from the request are converted
			}
			$settedProp = $nav->getSettedProp(); //not the idProp
			if (isSet($text->contentLabel) || $nav->isSingleValue())
				$this->setSelectedId($text->getContentLabelWith($reqObj));
			if ($options===null)
				$options = $nav->getOptions($reqObj);
			if ($settedProp->isTypePrimitive()) {
				$this->setOptionsFromValues($options, $nav->getResultType(), $text->getConverter());
			} else {
				$this->setOptionsFromObjects($options, $nav->getResultType());
			}
		}
	}

	//setters that extend the public interface
	/** @value string the selected id or value, not HTML */
	function setSelectedId($value) {
		$this->selectedId = $value;
	}
	
	/** @param boolean $value wheather the user must select a value. 
	 * 		If not, an option is included in the list for unselecting unless autoselectFirst */
	function setSettedCompulsory($value) {
		$this->settedCompulsory = $value;
	}

	function setWidth($value) {
		$this->width = (int) $value;
	}

	/** @param boolean $value wheather the first option in the list should be automatically selected when selecteId is empty, 0 or null */
	function setAutoSelectFirst($value) {
		$this->autoSelectFirst = $value;
	}

	/** @param array associative of strings with strings (not HTML) as the keys and option content (HTML) as the values */ 
	function setOptions($value) {
		$this->options = $value;
	}

	/** @param javacript value of the onChanged attribute of the select tag */
	function setOnChangeScript($value) {
		$this->onChangeScript = $value;
	}

	/** @param array $objects of $type to derive the options from 
	 * @param string $type of the objects, must have a ClassDescriptor that supports ::getPeanutWithId
	 */
	function setOptionsFromObjects($objects, $type) {
		$options = array();
		$cnv = $this->getConverter();
		$this->optionsMaxLength = 0;
		$selectedIdInOptions = false;
		$clsDes = PntClassDescriptor::getInstance($type);
		$idProp = $clsDes->getPropertyDescriptor('id');
		$cnv->initFromProp($idProp);
		forEach($objects as $obj) 
			if ($this->addOptionFromObject($options, $obj, $type, $cnv, $idProp)) 
				$selectedIdInOptions = true;
				
		if ($this->getSelectedId() && !$selectedIdInOptions) {
			$selectedItem = $clsDes->getPeanutWithId($this->getSelectedId());
			if ($selectedItem) 
				$this->addOptionFromObject($options, $selectedItem, $type, $cnv, $idProp);
		}
		$this->setOptions($options);
	}
	
	/** Adds an option
	 * @param array &$options where the option is to be added to
	 * @param Object $obj of $type 
	 * @param string $type of $object 
	 * @param PntStringConverter $cnv initialized to convert id and $obj toLabel and toHtml
	 * @param PntPropertyDescriptor $idProp to derive the id from the object
	 */
	function addOptionFromObject(&$options, $obj, $type, $cnv, $idProp) {
		$id = $cnv->toLabel( $idProp->getValueFor($obj), $idProp->getType() );
		
		$label = $cnv->toLabel($obj, $type);
		$options[$id] = $cnv->toHtml($label, false, 2);
		$this->optionsMaxLength = max($this->optionsMaxLength, strLen($label));
		return $id == $this->getSelectedId();
	}
	
	/** set options using the labels of the values as keys and as HTML as values
	 * @param array $values of mixed 
	 * @param string $type of value
	 * @param PntStringConverter $cnv initialized to convert the $values toLabel and toHtml
	 */
	function setOptionsFromValues($values, $type, $cnv) {
		$options = array();
		$this->optionsMaxLength = 0;
		$selectedIdInOptions = false;
		forEach($values as $value) {
			$label = $cnv->toLabel($value, $type);
			$html = $cnv->toHtml($label);
			$options[$label] = $html;
			$this->optionsMaxLength = max($this->optionsMaxLength, strLen($label));
			if ($label == $this->getSelectedId()) $selectedIdInOptions = true;
		}
		if (strLen($this->getSelectedId()) && !$selectedIdInOptions) {
			$options[$this->getSelectedId()] = $cnv->toHtml($this->getSelectedId());
		}
		$this->setOptions($options);
	}
	
	/** Calculate the width from $this->optionsMaxLength and set it to the calculated value
	* Only has effect if $this->optionsMaxLength is set and > 0
	* @param int $multiplier the number of pixels per character
	* @param int $min the minimum width. If null no minimum is applied
	* @param int $max the maximum width. If null no maximum is applied
	* @return int the calculated value or null if nothing was calculated
	*/
	function setWidthFromOptionsMaxLength($multiplier, $min=null, $max=null) {
		 if (!isSet($this->optionsMaxLength) || $this->optionsMaxLength == 0) return null;
		
		$width = $this->optionsMaxLength * $multiplier;
		
		if ($min !== null)
			$width = max($width, $min);
		if ($max !== null)
			$width = min($width, $max);
		$this->setWidth($width);
		return $width;
	}

	function printBody() {
?><select name="<?php $this->printFormKey() ?>" id="<?php $this->printFormKey() ?>"<?php 
				$this->printStyle();
				$this->printOnChangeScript(); ?> class="<?php print $this->getCssClass() ?>" title="<?php $this->printTitle() ?>"><?php 
				$this->printUnselectOption();
			$this->printSelectOptions() ?> 
		</select><?php
	}

	//print- and getter methods used by printBody
	
	function printOnChangeScript() {
		if (isSet($this->onChangeScript)) {
			print 'onchange="'.$this->onChangeScript.'"';
		}
	}

	function printStyle() {
		if (isSet($this->width)) 
			print "style='width:$this->width' ";	
	}

	function printUnselectOption() {
		if ($this->getSettedCompulsory() &&
				($this->getSelectedId() || $this->getAutoSelectFirst()) )
			return;
		
		$cnv = $this->getConverter();
		$selected = $this->getOptionSelected(null);
		$label = $this->unselectLabel ? $cnv->toHtml($this->unselectLabel) : '&nbsp;';
		print "
			<option value=\"\" $selected>$label</option>";
	}
	
	function printSelectOptions() {
		$cnv = $this->getConverter();
		$options = $this->getOptions();
		reset($options);
		foreach ($options as $key => $content) {
			$selected = $this->getOptionSelected($key);
			$valueHtml = $cnv->toHtml($key);
			print "
				<option $selected value=\"$valueHtml\">$content</option>";
		}
	}
	
	function getOptionSelected($id) {
//		print "id: $id selectedId: $this->selectedId";
		if ($id == $this->getSelectedId())
			return 'SELECTED';
		else
			return '';
	}

	/** @return array associative with strings (not HTML) as its keys and option content (HTML) as its values */ 
	function getOptions() {
		return $this->options;
	}

	/** @return string the selected id or value, not HTML */
	function getSelectedId() {
		return $this->selectedId;
	}
	
	/** @return boolean $value wheather the user must select a value. 
	 * 		If not, an option is included in the list for unselecting unless autoselectFirst */
	function getSettedCompulsory() {
		return $this->settedCompulsory;
	}
	
	/** @return boolean $value wheather the first option in the list should be automatically selected when selecteId is empty, 0 or null */
	function getAutoSelectFirst() {
		return $this->autoSelectFirst;
	}

}