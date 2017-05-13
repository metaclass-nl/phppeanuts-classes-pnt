<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntFormWidget', 'pnt/web/widgets');

/** FormWidget that generates html specifying textfield
* and a button. Both will react to a click by open a Dialog.
* When the dialog is closed it calls a funcion specified by
* this Widget to set the new value and label in this Widget. 
*
* This abstract superclass provides behavior for the concrete
* subclass DialogWidget in the widgets classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/widgets
*/
class PntDialogWidget extends PntFormWidget {

	public $labelValue;
	public $showClearButton;
	public $dialogType = 'Dialog';
	public $propName;
	public $cssClass = 'pntDialogWidget';
	
	/* @throws PntError */
	function initialize($text) {
		parent::initialize($text);
		$cnv = $this->getConverter();
		if ($text) {
			$reqObj = isSet($text->item)
				?  $text->item
				: $this->getRequestedObject();
			$nav =  $text->getNavigation();
			$attType = $nav->getResultType();
			//set the label key to the formKey without 'Id'
			$this->setLabelKey(subStr($this->formKey, -1) == ']'
				? subStr($this->formKey, 0, strLen($this->formKey)-3). ']'
				: subStr($this->formKey, 0, strLen($this->formKey)-2) );
			if ($text->contentLabel !== null) {
				// contentLabel is set by requestHandler, but not converted
				$text->setItem($reqObj);
				$text->setConvertMarkup($text->contentLabel);
				$defaultId = $text->getContentWith($reqObj);
				$attTypeDesc = PntClassDescriptor::getInstance($attType);
				$defaultObject = $attTypeDesc->getPeanutWithId($defaultId);
				if ($defaultObject)
					$this->setLabelValue($cnv->toHtml($defaultObject->getLabel()));
			} elseif ($nav->isSingleValue()) {
					$this->setValue( $text->getMarkupWith($reqObj) );
					$defaultObject = $nav->evaluate($reqObj);
					if ($defaultObject)
						$this->setLabelValue($cnv->toHtml($defaultObject->getLabel()));
			}
			$this->propName = $text->prop->getName();
			$this->setShowClearButton(!$nav->isSettedCompulsory());
	
			$this->initDialogUrlNoId($nav);
			$targetApp = $this->getTargetAppName($nav, $this->dialogType);
			
			$dialogClass = $attType.$this->dialogType;
			if ( !$this->tryUseClass($dialogClass, $targetApp) ) {
				$dialogClass = "Object$this->dialogType";
				$this->useClass($dialogClass, $targetApp);
			}
			$this->setDialogClass($dialogClass);
	//print "dialogClass $dialogClass";
			$this->setDialogSize( pntCallStaticMethod($dialogClass, 'getMinWindowSize') );

			$textSize = 32;
			if (!$this->showClearButton)
				$textSize += 4;
			$this->setTextSize($textSize);
		}
			
	}
	
	function initDialogUrlNoId($nav) {
		$targetApp = $this->getTargetAppName($nav, $this->dialogType);
		$params = array('pntHandler' => $this->dialogType
			, 'pntType' => $nav->getResultType()
			, 'pntProperty' => $this->propName
			, 'pntFormKey' => $this->formKey
			, 'pntRef' => $this->getFootprintId()
			, 'id' => '' //must be last
			);
		$this->setDialogUrlNoId($this->controller->buildUrl($params, $targetApp));
	}

	function getName() {
		return 'DialogWidget';
	}

//setters that form the public interface
	function setLabelKey($value) {
		$this->labelKey = $value;
	}
	
	function setLabelValue($value) {
		$this->labelValue = $value;
	}
	
	function setTextSize($value) {
		$this->textSize = (int) $value;
	}
	
	function setShowClearButton($value) {
		$this->showClearButton = $value;
	}

	function setDialogUrlNoId($value) {
		$this->dialogUrlNoId = $value;
	}
	
	function setDialogClass($value) {
		$this->dialogClass = $value;
	}

	function setDialogSize($value) {
		$this->dialogSize = $value;
	}

	function printBody() {
	?>
		<div class="pntDialogWidgetDiv"><input type='TEXT' name='<?php $this->printLabelKey() ?>' id='<?php $this->printLabelKey() ?>' value='<?php $this->printLabelValue() ?>' readonly='true' size='<?php $this->printTextSize() ?>' style='cursor:hand; cursor:pointer;' onclick="<?php $this->printOnClickHandler() ?>" class="<?php print $this->getCssClass() ?>" title="<?php $this->printTitle() ?>"><input type='HIDDEN' name='<?php $this->printFormKey() ?>' id='<?php $this->printFormKey() ?>' value='<?php $this->printValue() ?>'><a class="pntDialogButtonLink" href="javascript: <?php $this->printOnClick() ?>" title='Item selection dialog'><img class=pntButtonPopup border='0' src='<?php print $this->getImagesDir() ?>buttonpopup.gif'></a><?php $this->printClearButton() ?></div>
<?php 
	}
	
	function printOnClickHandler() {
		print "pntDialogWidgetClicked(this ";
		$this->printOnClickExtraParams();
		print ");";
	}
	
	function printOnClick() {
		$formKeyLit = $this->getConverter()->toJsLiteral($this->formKey, "'");
		print "pntOpenDialogFor($formKeyLit ";
		$this->printOnClickExtraParams();
		print ");";
	}
	
	function printOnClickExtraParams() {
		print ','; 
		print $this->getConverter()->toJsLiteral($this->getDialogUrlNoId(), "'");
		print ',';
		$this->printDialogSize();
	}
	
	//print- and getter methods used by printBody
	function printClearButton() {
		if ($this->showClearButton) {
			$formKeyLit = $this->getConverter()->toJsLiteral($this->formKey, "'");
			$labelKeyLit = $this->getConverter()->toJsLiteral($this->labelKey, "'");
			$imagesDir = $this->getImagesDir();
			print "<a href=\"javascript:clrDialogWidget($formKeyLit, $labelKeyLit);\" style=\"cursor:hourglass;\" TITLE=\"Clear\"><img class=\"pntClearWidgetButton\" src=\"$imagesDir"."delete.gif\" border=\"0\" height=\"19\"></a>";
		}
	}	

	function printReplyScriptPiece() {
		print pntCallStaticMethod($this->dialogClass, 'getReplyScriptPiece', $this->formKey);
	}
	
	function printDialogSize() {
		$dialogSize = $this->dialogSize;
		$sizeX = (int) $dialogSize->x;
		$sizeY = (int) $dialogSize->y;
		print "$sizeX,$sizeY";
	}

	function printLabelKey() {
		$this->htOut($this->labelKey);
	}
	
	function printLabelValue() {
		print $this->labelValue;
	}
	
	function printTextSize() {
		print $this->textSize;
	}

	/** @return string url ending with id= */
	function getDialogUrlNoId() {
		return $this->dialogUrlNoId;
	}
	
	/** @depricated */
	function printScriptPiece() {
?>
			 function openPopUpFor<?php $this->printFormKey() ?>() {
			 	objectId = document.detailsForm.<?php $this->printFormKey() ?>.value;
			 	str = '<?php $this->printDialogUrlNoId() ?>'+encodeURIComponent(objectId);
			 	popUp(str, <?php $this->printDialogSize() ?>,100,75);
			}
			<?php $this->printReplyScriptPiece() ?>
			document.detailsForm.<?php $this->printFormKey() ?>.value = pId;
			if (pLabel=='')
				document.detailsForm.<?php $this->printLabelKey() ?>.value = pId;
			else
				document.detailsForm.<?php $this->printLabelKey() ?>.value = pLabel;
			}
<?php
	}
	
	/** @depricated */
	function printDialogUrlNoId() {
		$this->getConverter()->toJsLiteral($this->getDialogUrlNoId(), '');
	}
}