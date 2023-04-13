<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntFormNavValue', 'pnt/web/dom');

/** 
 * PntFormMtoNRelValue is used to process a form value into a property value
 * for a role in an MtoNRelationship. The form will hold the ids of the 
 * related peanuts. 
 * If no form value the navigation is used to retrieve the 
 * property value from the supplied item. The representation in 
 * HTML is the id(s) of these peanuts. 
 *
 * FormNavValue does not merge its content with its markUp.
 * If you need to merge, use a NavValue or NavText.
 *
 * Currently it is not clear how to handle paths longer then 1.
 * So for now, it works only with single step paths.
 * As a consequence some funcion will later be delegated to the Navigation
 * and the interface may change
 * @package pnt/web/dom
 */
class PntFormMtoNRelValue extends PntFormNavValue {

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
	* HACK: PntFormMtoNRelValue returns the ids separated by 
	*  semicolons for multiple objects
	*/
	function getContentLabelWith($item) {
		if ($this->contentLabel !== null) 
			return $this->contentLabel;
		
		$content = $this->getContentWith($item); //initializes the converter
		if (!$content) return null;
		
		$labels = array();
		forEach(array_keys($content) as $key) 
			$labels[] = $this->converter->toLabel($content[$key], $this->getContentType());

		return implode(';', $labels);				
	}

	/* If contentLabel is not set, the content is the attribute value obtained from the item.
	* If contentLabel is set, content is (to be) converted from contentLabel so it can
	* be set to the attribute of the item. 
	* NB, because PntRequestHandler sets contentLabel
	* without setting content, the caller must explicitly initialize content 
	* by calling setConvertMarkup
	* @throws PntError
	*/
	function getContentWith($item)
	{
		if ($this->contentLabel !== null) 
			return $this->content;
			
		$this->initProp(get_class($item));
		$this->converter = clone $this->getConverter();  
		$this->converter->initFromProp($this->prop);

		$values = $this->getValue($item);
		$result = array();
		forEach(array_keys($values) as $key) {
			$result[] = $this->prop->getValueFor($values[$key]);
		}		
		
		return $result;
	}
	

	/** sets the value from the form, converts it, 
	* but does not set the value on the item.
	*/
	function setConvertMarkup($value=false) {
		if ($value !== false)
			$this->contentLabel = $value;

		if ($this->contentLabel=='###@@@***') { //saveMtoNPropTableState was not called or has failed, see PntObjectMtoNPropertyPage::printSaveScript
			$this->error = 'invalid form value';
			return false;
		}		

		$this->converter = clone $this->getConverter(); 
		$this->converter->initFromProp($this->prop);

		$labels = $this->contentLabel===null
            ? []
            : explode(';', $this->contentLabel);

		$this->content = array();
		$this->error = null;
		forEach($labels as $label) {
			if ($label !== '') {
				$this->content[] = $this->converter->fromLabel($label);
				if ($this->converter->error)
					return false;
			}
		}
		
		forEach($this->content as $value) {
			$this->error = $this->item->validateGetErrorString($this->prop, $value, false); //do not validate readOnly
			if ($this->error) return false;
		}

		return $this->error === null;
	}
	
	/** sets the already converted value on the item
	* PntFromMtoNRelValue needs to mutate the property to hold 
	* objects with ids as in $this->content
	* @return wheather the object has been modified
	* @throws PntError
	*/
	function commit() {
		$nav = $this->getNavigation();
		$values = $nav->evaluate($this->item);
		$idsToAdd = $this->content; //makes a copy
		$idsToRemove = array();
		forEach(array_keys($values) as $key) {
			$id = $values[$key]->get('id');
			$i = array_search($id, $idsToAdd);
			if ($i===false)
				$idsToRemove[] = $id;
			else
				unSet($idsToAdd[$i]);
		}
		if (count($idsToAdd) == 0 && count($idsToRemove) == 0) return false; //not modified
		
		$mthName = $this->getNavKey(). 'MtoNmodIds';
//$mods = array('add' => $idsToAdd, 'remove' => $idsToRemove);
//printDebug($mods);
		if (method_exists($this->item, $mthName))
			$this->item->$mthName($idsToAdd, $idsToRemove);
		else {
			$prop = $nav->getSettedProp();
			$prop->mutateRelationFor_ids($this->item, $idsToAdd, $idsToRemove);
		}
		return true; //modified
	}

	function getError() {
		if (isSet($this->error))
			return $this->error;
		else
			return $this->converter->error;
	}
	
	function setItem($item) {
		$this->item = $item;
		$this->initProp(get_class($item));
	}
	
	/** This class initializes the prop to the property by which
	* the ids can be derived from the Peanuts from getContent(). 
	* Either $this->navigation must be initialized to two steps for 
	* deriving the n to m related Penauts or it must be initialized to
	* a single step path and the propertyDescriptor of the first step 
	* of the path must have its derivationPath/nav set.
	*/
	function initProp($itemType) {
		if ($this->prop) return;
		
		$nav = $this->getNavigation();
		$prop = $nav->getSettedProp();

		if (!$nav->getNext()) {
			$clsDes = PntClassDescriptor::getInstance($prop->getType());
			$idProp = $clsDes->getPropertyDescriptor('id');
		} else {
			trigger_error('PntFormMtoNRelValue multiple steps not yet supported', E_USER_ERROR);
			$idProp = $prop->getIdPropertyDescriptor();
		}
		if ($idProp)
			$this->prop = $idProp;
		else
			$this->prop = $prop;

	}

	/** @return string must be according to checkAlphaNumeric 
	 * 		Key that can be used as value for the name attribute of an input tag in a form
	 * 		For this class it is the navKey  
	 */ 
	function getFormKey() {
		return $this->getNavKey();
	}
	
	function getNavKey() {
		$nav = $this->getNavigation();
		return $nav->getKey();
	}
	
	function usesIdProperty() {
		return true;
	}

	/** Returns the property value
	* @param PntObject $item whose property value to retrieve
	* @return Array of PntObject the related objects 
	* @throws PntError
	*/
	function getValue($item) {
		$nav = $this->getNavigation();
		if (isSet($this->contentLabel)) {
			if ($this->contentLabel) {
				$cls = $nav->getResultType();
				$clsDes = PntClassDescriptor::getInstance($cls);
				$idProp = $clsDes->getPropertyDescriptor('id');
				$qh = $clsDes->getSelectQueryHandler();
				$qh->query .= " WHERE ";
				$qh->in($idProp->getColumnName(), $this->getContentWith($item));
				return $clsDes->getPeanutsRunQueryHandler($qh);
			} else { //empty contentLabel, return empty array
				return array();
			}
		} else { //contentLabel is null or not set, derive property value
			return $nav->evaluate($item);
		}
	}
}
?>