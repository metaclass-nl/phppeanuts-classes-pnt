<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntXmlNavValue', 'pnt/web/dom');

/** 
 * FormNavValue is used to store a form value in the dom. 
 * The form value is stored as label text.
 * If no form value the navigation is used to retrieve content
 * from the supplied item. 
 *
 * FormNavValue does not merge its content with its markUp.
 * If you need to merge, use a NavValue or NavText.
 *
 * @package pnt/web/dom
 */
class PntFormNavValue extends PntXmlNavValue {

	public $item;
	public $prop; // PntPropertyDescriptor
	public $error;
	// $html is used for storing form values, no merge.

	function initPropertyDescriptors() {
		parent::initPropertyDescriptors();
	}

	function getMarkupWith($item) {
		if ($this->markup !== null) 
			return $this->markup;
			
		$contentLabel = $this->getContentLabelWith($item);
		$conv = $this->getConverter();
		return $conv->toHtml($contentLabel);
	}

	/** The label of the content. contentLabel is either set by 
	* PntRequestHandler or obtained by converting content 
	*/
	function getContentLabelWith($item) {
		if ($this->contentLabel !== null) 
			return $this->contentLabel;
		
		$content = $this->getContentWith($item); //initializes the converter
		$conv = $this->getConverter();
		return $conv->toLabel($content, $this->getContentType());
		//do not set contentLabel here, it is only to be set by PntRequestHandler and setConvertMarkup
	}
	
	/* If contentLabel is not set, the content is the attribute value obtained from the item.
	* If contentLabel is set, content is (to be) converted from contentLabel so it can
	* be set to the attribute of the item. 
	* NB, because PntRequestHandler sets contentLabel
	* without setting content, the caller must explicitly initialize content 
	* by calling setConvertMarkup
	* @throws PntError
	*/
	function getContentWith($item) {
		if ($this->contentLabel !== null) 
			return $this->content;
			
		return $this->getContentFrom($item);
	}
	
	function getContentFrom($item) {
		$this->initProp(get_class($item));
		$this->converter = clone $this->getConverter(); 
		$this->converter->initFromProp($this->prop);
		
		if (!$this->usesIdProperty())
			return $this->getNavigation()->evaluate($item);

		$toSetOn = $this->getItemToSetOn($item);
		if (!$toSetOn) return null; 
		return $this->prop->getValueFor($toSetOn);
	}

	function initConverter($conv) {
		$nav = $this->getNavigation();
		$this->initProp($nav->getItemType());
        $conv->initFromProp($this->prop);
		return $conv;
	}

	/** sets the value from the form, converts it, 
	* but does not set the value on the item.
	*/
	function setConvertMarkup($value=false) {
		if ($value !== false) $this->contentLabel = $value;

		$hadOldContent=pntMember_exists($this, 'oldContent');
		if (!$hadOldContent) 
			$this->oldContent = $this->getContentFrom($this->item); //initializes converter
		
		$this->error = null;
		$this->content = $this->converter->fromLabel($this->contentLabel);
// print "<BR>!".$this->getPath()."($this->oldContent) $this->content from: $this->contentLabel";
		if ($this->converter->error) return false;

/* Het is beter om de wijzigingen wel te verwerken en de verloren gegane input achteraf te signaleren
   Dit niet weghalen want mogelijk later voor die signalering te gebruiken 
  		$itemContent = $this->getContentFrom($this->item);
		if ($hadOldContent && !ValueValidator::equal($this->content, $this->oldContent, $this->prop->getType())
			&& !ValueValidator::equal($this->oldContent, $itemContent, $this->prop->getType())
			&& !ValueValidator::equal($this->content, $itemContent, $this->prop->getType())
			) {
				$this->error = ValueValidator::getDerivedChangeConflictMessage($this->contentLabel);
				return false;
		}
*/		
		$toSetOn = $this->getItemToSetOn($this->item);
		if (!$toSetOn) {
			//assume this is only used for a search widget - use a new object for the validation
			$toSetOnNav = $this->getNavigation()->getToSettedOn();
			$toSetOnType = $toSetOnNav->getResultType();
			$toSetOn = new $toSetOnType();
		}
		$this->error = $toSetOn->validateGetErrorString($this->prop, $this->content);
		return $this->error === null;
	}
	
	/** sets the already converted value on the item
	* @throws PntError
	*/
	function commit() {
		if (ValueValidator::equal($this->content, $this->oldContent, $this->prop->getType()))	
			return true;
//print $this->getFormKey(). " comitting $this->content, old: $this->oldContent <br>\n";
			
		if ($this->usesIdProperty()) {
			$toSetOn = $this->getItemToSetOn($this->item);
			if (!$toSetOn) throw new PntError("$this no item to set on");
			$this->prop->setValue_for($this->content, $toSetOn);
		} else {
			$nav = $this->getNavigation();
			$nav->setValue($this->item, $this->content);
		}
		return true;
	}

	function getItemToSetOn($item) {
		$nav = $this->getNavigation();
		return $nav->getItemToSetOn($item);
	}
	
	function getError() {
		if (isSet($this->converter->error))
			return $this->converter->error;
		else
			return $this->error;
	}
	
	function setItem($item) {
		$this->item = $item;
		$this->initProp(get_class($item));
	}
	
	function initProp($itemType) {
		$nav = $this->getNavigation();
		$clsDes = PntClassDescriptor::getInstance($itemType);

		$prop = $nav->getSettedProp();
		if (!$prop) trigger_error('Setted property '. $nav->getPath().' missing on '. $itemType, E_USER_ERROR);
		$idProp = $prop->getIdPropertyDescriptor();
		if ($idProp)
			$this->prop = $idProp;
		else
			$this->prop = $prop;
	}

	/** @return string must be according to checkAlphaNumeric 
	 * 		Key that can be used as value for the name attribute of an input tag in a form 
	 */ 
	function getFormKey() {
		if (isSet($this->formKey)) return $this->formKey;
		
		$nav = $this->getNavigation();
		if ($this->prop === null) 
			$this->initProp($nav->getItemType());
		if (!$this->usesIdProperty()) return parent::getFormKey(); 
			
		$path = $nav->getIdPath();
		return $this->formKeyFrom($path);
	}
	
	function getNavKey() {
		$nav = $this->getNavigation();
		return $nav->getKey();
	}
	
	function usesIdProperty() {
		$nav = $this->getNavigation();
		return $nav->getSettedProp() != $this->prop;
	}
	
	function isReadOnly() {
		return false;
	}
}
?>