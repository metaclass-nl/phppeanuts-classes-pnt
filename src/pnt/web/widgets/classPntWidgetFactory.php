<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


Gen::includeClass('PntFormNavValue', 'pnt/web/dom');

/** 
* This abstract superclass provides behavior for the concrete
* subclass MenuPart in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/widgets
*/
class PntWidgetFactory {

	public $textAreaTreshold = 120;
	public $dialogTreshold = 30;
	public $widgetDir = 'widgets';
	
	function __construct($page) {
		$this->page = $page;
	}

	/** 
	 * @param string $itemType name of the (super) class of the item, must already be checkedAlphaNumeric
	 * @param string $path UNTRUSTED to the property edited by the widget
	 * @param string $contentLabel UNTRUSTED human readable representation of the value in the request
	 * @param string $formKey name of the widget in the form
	 * @trhows PntReflectionError if text holds invalid $path for $itemType 
	 */
	function getFormWidget($itemType, $path, $contentLabel, $formKey=null) {
		$text = new PntFormNavValue(null, $itemType, $path);
		$text->setConverter($this->page->getConverter());
		$text->setContentLabel($contentLabel); //should not be called if no form value - value will then be derived
		if (isSet($this->object)) $text->setItem($this->object);
		
		$widget = $this->getDetailsFormWidget($text);
		if ($formKey) {
			$widget->setFormKey($formKey);
			if (method_exists($widget, 'setLabelKey'))
				$widget->setLabelKey($formKey.'Label');
			$widget->initDialogUrlNoId($text->getNavigation(), 'Dialog');
		}
		if (isSet($this->object)) $widget->object = $this->object;
		return $widget;
	}

	/** @trhows PntReflectionError if text holds invalid path for the itemType */
	function getDetailsFormWidget($text) {
		$obj = $this->getRequestedObject();

		$customWidget = $this->getCustomWidget($text);
		if ($customWidget) return $customWidget;
		
		$nav =  $text->getNavigation();
		if ($nav->getResultType() == 'boolean') {
			if ($nav->isSettedCompulsory())
				return $this->getCheckboxWidget($text);
			else 
				return $this->getSelectWidget($text);
		}
		if ($this->isPasswordWidget($text)) {
			return $this->getPasswordWidget($text);
		}
		if ($nav->getResultType() == 'date') {
			return $this->getDateWidget($text);
		}
		if ($nav->getResultType() == 'html') {
			return $this->getHtmlWidget($text);
		}
		$prop = $nav->getSettedProp();
		if ($text->usesIdProperty() && $prop->getShortlistPath()) {
			//!! do not set the ShortlistPath if you do not have the ShortlistDialogWidget installed!
			return $this->getShortlistDialogWidget($text);
		}
		
		$dialogClass = $nav->getResultType().'Dialog';
		if ($this->page->tryUseClass($dialogClass, $this->page->getDir()))
			return $this->getDialogWidget($text);

		if (Gen::is_a($text, 'PntFormMtoNRelValue'))
			 return $this->getMtoNDialogWidget($text);

		if ($text->usesIdProperty()) {
			$clsDes = PntClassDescriptor::getInstance($nav->getResultType());
			$optionsFilter = $this->page->controller->getGlobalOptionsFilter($prop->getType(), $prop->getName());
			if ($optionsFilter && !$optionsFilter->appliesTo($clsDes->getName(), true))
				$optionsFilter = null; //non-persistent options filter may be too slow, use unfiltered count
			if (is_subclassOr($nav->getResultType(), 'PntDbObject')
					&& !$prop->hasOptionsGetter($obj)
					&& $clsDes->getPeanutsCount($optionsFilter) >= $this->getDialogTreshold($text)
				) {
				return $this->getDialogWidget($text);
			} else {
				return $this->getSelectWidget($text);
			}
		} else {
			if ($prop->hasOptionsGetter($obj))
				return $this->getSelectWidget($text);
		}
		if ($nav->getResultType() == 'email')
			return $this->getTextWidget($text);
			
		$maxLength = $text->prop->getMaxLength();
		if ($maxLength > $this->getTextAreaTreshold())
			return $this->getTextAreaWidget($text);
		//else
		return $this->getTextWidget($text);
	}

	function getCustomWidget($text) {
		if (!$text) return null;
		$widgetKey = str_replace('.', '_', $text->getPath());
		$this->page->checkAlphaNumeric($widgetKey);
		$methodName = 'getCustom' . ucfirst($widgetKey) . 'Widget';
		if (method_exists($this->page, $methodName)) 
			return $this->page->$methodName($text);
		if (method_exists($this, $methodName)) 
			return $this->$methodName($text);
		
		return null;
	}

	function getCustomWebsiteWidget($text) {
		return $this->getTextWidget($text);
	}
	
	function getCustomUrlWidget($text) {
		return $this->getTextWidget($text);
	}
	
	function isPasswordWidget($text) {
		return subStr($text->getFormKey(), 0, 8) == 'password';
	}

	/** WARNING: Do not call this method with class name from unrusted data */
	function getAndIncludeWidget($text, $class) {
		Gen::includeClass($class, $this->widgetDir); //warning in method comment, this class only uses literal strings
		$result = new $class($this->page, $this->page->requestData, $text);
		return $result;
	}

	function getCheckboxWidget($text) {
		return $this->getAndIncludeWidget($text, 'CheckboxWidget');
	}

	function getDateWidget($text) {
		//Not yet implemented class
		//return $this->getAndIncludeWidget($text, 'DateWidget');
		return $this->getTextWidget($text);
	}
		
	function getPasswordWidget($text) {
		$result = $this->getAndIncludeWidget($text, 'TextWidget');
		$result->setType('PASSWORD');
		return $result;
	}

	function getTextWidget($text) {
		return $this->getAndIncludeWidget($text, 'TextWidget');
	}
	
	function getHtmlWidget($text) {
		return class_exists('HtmlWidget')
			? $this->getAndIncludeWidget($text, 'HtmlWidget')
			: $this->getTextAreaWidget($text);
			
	}
		
	function getAdvancedHtmlWidget($text) {
		return $this->getAndIncludeWidget($text, 'AdvancedHtmlWidget');		//not supported
	}		

	function getTextAreaWidget($text) {
		return $this->getAndIncludeWidget($text, 'TextAreaWidget');		
	}

	function getSelectWidget($text) {
		return $this->getAndIncludeWidget($text, 'SelectWidget');		
	}
	
	function getRadioWidget($text) {
		return $this->getAndIncludeWidget($text, 'RadioSelectWidget');		//not supported
	}

	//precondition: resultType has already been included (happens in getDetailsLinkFromNavText)
	function getDialogWidget($text) {	
		return $this->getAndIncludeWidget($text, 'DialogWidget');
	}
	
	//precondition: resultType has already been included (happens in getDetailsLinkFromNavText)
	function getMtoNDialogWidget($text) {
		return $this->getAndIncludeWidget($text, 'MtoNDialogWidget');		
	}	

	function getShortlistDialogWidget($text) {
		$result = $this->getAndIncludeWidget($text, 'ShortlistDialogWidget');
		$result->setWidgetDir($this->widgetDir);
		return $result;
	}
	
	function getRequestedObject() {
		if (isSet($this->object)) return $this->object;
		
		$obj = $this->page->getRequestedObject();
		return $obj;
	}

	function getTextAreaTreshold() {
		return $this->textAreaTreshold;
	}
	function setTextAreaTreshold($value) {
		$this->textAreaTreshold = $value;
	}

	function getDialogTreshold() {
		return $this->dialogTreshold;
	}
	function setDialogTreshold($value) {
		$this->dialogTreshold = $value;
	}
	
	function __toString() {
		if (isSet($this->page))
			return get_class($this). " of $this->page";
		return 'a '. get_class($this);
	}
}
?>