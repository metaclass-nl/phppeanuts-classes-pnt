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
class PntMtoNDialogWidget extends PntFormWidget {

	public $labelValue;
	public $showClearButton;
	public $cssClass = 'pntMtoNDialogWidget';
	//	public $textSize = null; 

	function initialize($text) {
		parent::initialize($text);
		$cnv = $this->getConverter();
		if ($text) {
			$reqObj = $this->getRequestedObject();
			$nav =  $text->getNavigation();
			if ($text->contentLabel === null) {
				$this->setValue($text->getMarkupWith($reqObj));
			} else {
				// contentLabel is set by requestHandler, but not converted
				$text->setItem($reqObj);
				$text->setConvertMarkup($text->contentLabel);
			}
			$objects = $text->getValue($reqObj);
			$label = $cnv->arrayToLabel($objects, $nav->getResultType());
			$this->setLabelValue($cnv->toHtml($label, true));
	
			$dialogType = 'MtoNDialog';
			$params = array('pntHandler' => $dialogType
				, 'pntType' => $this->getType()
				, 'pntProperty' => $this->formKey
				, 'pntFormKey' => $this->formKey
				, 'pntRef' => $this->getFootprintId()
				, 'id' => $this->getReqParam('id')
				, $this->formKey => '' //must be last
				);
			$this->setDialogUrlNoId($this->controller->buildUrl($params));
			
			$dialogClass = $params['pntType'].$dialogType;
			if ( !$this->tryUseClass($dialogClass, $this->getDir()) ) {
				$dialogClass = "Object$dialogType";
				$this->useClass($dialogClass, $this->getDir());
			}
			$this->setDialogClass($dialogClass);
	//print "dialogClass $dialogClass";
			$this->setDialogSize( pntCallStaticMethod($dialogClass, 'getMinWindowSize') );
		}
			
	}

	function getName() {
		return 'MtoNDialogWidget';
	}

//setters that form the public interface
	
	/** @param HTML $value the content shown by the label div */
	function setLabelValue($value) {
		$this->labelValue = $value;
	}
	
	function setTextSize($value) {
		$this->textSize = (int) $value;
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
		$formKeyLit = $this->getConverter()->toJsLiteral($this->formKey, "'");
?> 
		<div class="pntDialogWidgetDiv"><div id="pntWidgetTxt_<?php $this->printFormKey() ?>" class="<?php $this->htOut($this->getCssClass()) ?>" title="<?php $this->printTitle() ?>" style="cursor:hand; cursor:pointer;<?php $this->printWidthStyle() ?>" onClick="pntOpenDialogFor(<?php print $formKeyLit ?>, '<?php $this->printDialogUrlNoId() ?>', <?php $this->printDialogSize() ?>);"><?php $this->printLabelValue() ?></div><INPUT TYPE='HIDDEN' NAME='<?php $this->printFormKey() ?>' id='<?php $this->printFormKey() ?>' VALUE='<?php $this->printValue() ?>'><A HREF="javascript:pntOpenDialogFor(<?php print $formKeyLit ?>, '<?php $this->printDialogUrlNoId() ?>', <?php $this->printDialogSize() ?>);" TITLE='Item selection dialog'><IMG class=pntMtoNButtonPopup BORDER='0' SRC='<?php print $this->getImagesDir() ?>buttonpopup.gif'></A></div>
<?php 
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

	function printLabelValue() {
		if (strlen($this->labelValue) == 0)
			print "&nbsp;";
		else
			print $this->labelValue;
	}
	
	function printTextSize() {
		print $this->textSize;
	}
	
	function printDialogUrlNoId() {
		print $this->dialogUrlNoId;
	}
	
	function printWidthStyle() {
		if (!isSet($this->textSize)) return;
		
		print " width: ";
		$this->printTextSize();
		print ";";
	}
}
?>