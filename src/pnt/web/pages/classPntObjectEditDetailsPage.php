<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObjectDetailsPage', 'pnt/web/pages');

/** Page showing property labels and editing property values of a single object
* By default shows properties specified by  getUiFieldPaths method 
* on the class of the shown object. Layout can be specialized, 
* @see http://www.phppeanuts.org/site/index_php/Pagina/150
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectEditDetailsPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/pages
*/
class PntObjectEditDetailsPage extends PntObjectDetailsPage {

	public $widgetDir = 'widgets';
	public $detailsPartName = 'EditDetailsPart';

	function printExtraHeaders() {
		parent::printExtraHeaders();
		if (Gen::tryIncludeClass('HtmlWidget', $this->widgetDir))
			HtmlWidget::printPartHeaders();
	}

	function getName() {
		$args = func_get_args();
		$id = isSet($args[0]) ? $args[0] : $this->getReqParam('id');
		return $id ? 'Update' : 'Create';
	}

	/** @return Array of PntNavValue
	*/
	function getFormTexts() {
		if (isSet($this->formTexts)) return $this->formTexts;
	
		parent::getFormTexts();
		$this->extraInitFormTexts($this->formTexts, $this->object, $this->requestData, true); //hides errors
		return $this->formTexts;
	}
	
	static function extraInitFormTexts($formTexts, $item, $requestData, $hideErrors=false) {
		forEach($formTexts as $key => $current) {
			if (!$current->isReadOnly() && isSet($requestData[$key])
					&& !Gen::is_a($current, 'PntFormMtoNRelValue')) {
				$current->setItem($item);
				$success = $current->setConvertMarkup();
				if ($success) $current->commit();
				elseIf ($hideErrors) 
					$current->error = null; 
			}
		}
	}
			
	function printInformationPart() {
		$part = $this->getEditDetailsPart();
		$part->printInformationPart();
	}

	/** @return HTML String information for the end user
	* If no other information, Return the editInformation from the requestedObject
	*/
	function getInformation() {
		$info = parent::getInformation();
		if ($info)return $info;

		return $this->getObjectEditInfo();
	}
	
	/** Information for the user that is editing the object
	* @return String Html
	*/
	function getObjectEditInfo() {
		$obj = $this->getRequestedObject();
		if ($obj)
			return $obj->getEditInfo();
	}
	
	function printMainPart() {
		$this->printPart('DetailsFormPart');
	}
	
	/** Overridden to add subparts of EditDetailsPart 
	 * 2DO: add subparts of MultiPropsParts */
	function ajaxPrintUpdates($preFix='') {
		parent::ajaxPrintUpdates($preFix='');
		
		$part = $this->getEditDetailsPart();
		$part->ajaxUpdatePartIds = $this->getAjaxUpdateSubPartIds('EditDetailsPart.');
		$part->ajaxPrintUpdates('EditDetailsPart.'); //recurses into subpart
	}
	
	function printEditDetailsPart() {
		$part = $this->getEditDetailsPart();
		$part->printBody();
	}
	
	function getEditDetailsPart() {
		// if cached it is already initialized
		if (isSet($this->parts[$this->detailsPartName])) return $this->parts[$this->detailsPartName];
		
		$part = $this->getPart(array($this->detailsPartName));
		//for compatibility with older code
		$part->widgetDir = $this->widgetDir; //to be depricated
		$part->setDetailsLinkPntHandler($this->getDetailsLinkPntHandler());
		if (method_exists($this, 'getTextAreaTreshold'))
			$part->textAreaTreshold = $this->getTextAreaTreshold(); 
		if (method_exists($this, 'getDialogTreshold'))
			$part->dialogTreshold = $this->getDialogTreshold(); //for compatibility with older code
			
		//may be coming from SaveAction
		$part->setFormTexts($this->getFormTexts()); 
		$part->setInformation($this->information); 
		
		return $part;
	}

	function getButtonsList() {
		$actButs = array();
		if ($this->object) 
			$this->addActionButtons($actButs);
		
		$navButs=array();
		$this->addContextButtonTo($navButs);
		if ($this->object) {
			$this->addMultiValuePropertyButtons($navButs);
			$this->addReportButtons($navButs);
		}
		return array($actButs, $navButs);
	}
	
	function addActionButtons(&$actButs) {
		if (!$this->object) trigger_error('no object', E_USER_ERROR);
		$idParam = $this->getReqParam('id');
		$id = $this->object->get('id');
//Gen::show(array($idParam, $id));
		$errorsAfterSave = $idParam && isSet($this->object->pntOriginal)
			|| !$idParam && $id;
		$actButs[]=$this->getButton($this->getName($id), "pntDetailsSaveButtonPressed();");
		if ($id) {
//new button no longer common now copy button is available
//			$actButs[]=$this->getButton('Create New', "pntDetailsCreateNewButtonPressed(); ", $errorsAfterSave);

			$actButs[]=$this->getButton('Copy', "pntDetailsCopyButtonPressed(); ", $errorsAfterSave);
			$actButs[]=$this->getButton('Delete', "pntDeleteButtonPressed(); ");
		}
	}
	
	function getButtonsPanelHeight() {
		$list = $this->getButtonsList();
		return count($list) * 33;
	}
	
	function printBodyTagIeExtraPiece() {
		parent::printBodyTagIeExtraPiece();
		$this->printOnUnload();
	}
	
	function printOnUnload() {
		print " onbeforeunload='pntDetailsFormUnloadConfirm(event, 0)' onunload='pntDetailsFormUnloadConfirm(event, 1)' ";
	}
	
	/** Tell the scout how to interpret the requests
	* PRECONDITION: Session started
	*/
	function doScouting() {
		$scout = $this->getScout();
		$referrerId = $scout->getReferrerId($this->requestData);
		$direction = $this->getReqParam('pntScd'); 
		if ($direction || !$referrerId) 
			return parent::doScouting();
		
		$uris = $scout->getFootprintUris();
		$refUri = $uris[$referrerId];
		
		$refRequest = $this->request->getFunkyRequestData(null, $refUri); 
		
		$direction = 'down';
		$id = isSet($refRequest['id']) ? $refRequest['id'] : null;
		$pntHandler = isSet($refRequest['pntHandler']) ? $refRequest['pntHandler'] : '';
		$editfeedback = $this->getReqParam('pntEditFeedback');
		if (($id == $this->getReqParam('id') || ($editfeedback == 'create' || $editfeedback == 'copy'))
			&& isSet($refRequest['pntType']) && $refRequest['pntType'] == $this->getType()
			&& ($this->isSameContextHandler($pntHandler) || (!$pntHandler && $id)
				) || in_array($pntHandler, array('Dialog', 'MtoNSearchPage'))
		) $direction = null;
		$this->footprintId = $scout->moved($referrerId, $direction, $this->requestData);
	}

	function isSameContextHandler($pntHandler) {
		return in_array($pntHandler, array('EditDetailsPage', 'PropertyPage', 'MtoNPropertyPage'));
	}

	//DetailsFormStartPart
	function getBackToOrigin() {
		return false;
	}
	function getIgnoreMissingFields() {
		return false;
	}
	
	function printEventualMultiPropsPart() {
		//ignore
	}

	function getSubsaveActions() {
		return array();
	}
	
	function printDeleteScript() {
		$this->useClass('ObjectVerifyDeleteDialog', $this->getDir());
		$dialogSize = ObjectVerifyDeleteDialog::getMinWindowSize();
		$x = (int) $dialogSize->x;
		$y = (int) $dialogSize->y;
		$callbackFuncHead = ObjectVerifyDeleteDialog::getReplyScriptPiece();
		$conv = $this->getConverter();
		$delConfQLit = $conv->toJsLiteral($this->getDeleteConfirmationQuestion(), "'"); 
		$discardChangesMessageLit = $conv->toJsLiteral($this->getDiscardChangesMessage(), "'");
		$selObjOidLit = '';
		$obj = $this->getRequestedObject();
		if ($obj) 
			$selObjOidLit = $conv->toJsLiteral($obj->getOid(), "'");
		$clsDes = $this->getTypeClassDescriptor();
		$recusive = $clsDes->getVerifyOnDelete();
		$oidLit = $recusive ? $selObjOidLit : "''";
		print "
<SCRIPT>
	func"."tion pntDeleteButtonPressed() {
		if (pntFormRefData != null) {
			pntSaveEditors();
			if (!pntArraysEqual(pntFormRefData, pntGetFormValues(document.detailsForm)))
				if (!popUpYesNo($discardChangesMessageLit)) return;
		}
		var selectedParams = pntCollectSelectedParams(document.detailsForm);
		if (selectedParams=='')
			pntVerifyAndDelete('detailsForm', $oidLit, $delConfQLit, $x,$y);
		else 
			pntVerifyAndDeleteMarked('detailsForm', '', $x,$y);
	}
	$callbackFuncHead
		if (firstOid == $selObjOidLit)
			pntSubmitDelete();
		else 
			pntSubmitDeleteMarked();
	}
</SCRIPT>\n";		
	}
	
	function getDeleteConfirmationQuestion() {
		return 'Are you sure you want to delete this item from the database?';
	}
	
	//used only by PropertyPage and PntxObjectEditWithMultiPage
	function printDeleteMarkedScript() {
		$this->useClass('ObjectVerifyDeleteDialog', $this->getDir());
		$dialogSize = ObjectVerifyDeleteDialog::getMinWindowSize();
		$x = (int) $dialogSize->x;
		$y = (int) $dialogSize->y;
		$callbackFuncHead = ObjectVerifyDeleteDialog::getReplyScriptPiece();
		$conv = $this->getConverter();
		$noItemsMarkedMessageLit = $conv->toJsLiteral($this->getNoItemsMarkedMessage(), "'");
		$discardChangesMessageLit = $conv->toJsLiteral($this->getDiscardChangesMessage(), "'");
		print "
<SCRIPT>
	func"."tion pntDeleteMarkedButtonPressed() {
		if (pntFormRefData != null) {
			pntSaveEditors();
			if (!pntArraysEqual(pntFormRefData, pntGetFormValues(document.detailsForm)))
				if (!popUpYesNo($discardChangesMessageLit)) return;
		}		
		pntVerifyAndDeleteMarked('detailsForm', $noItemsMarkedMessageLit, $x,$y);
	}";
	$this->printDeleteMarkedCallback($callbackFuncHead);
		print "
</SCRIPT>\n";		
	}

	function printDeleteMarkedCallback($callbackFuncHead) {
		//ignore
	}
		
	function getNoItemsMarkedMessage() {
		return 'Please mark some items by clicking in the checkboxes in front of each row';
	}
	
	function getDiscardChangesMessage() {
		return 'The changes you made on this page will be lost';
	}
	
}
?>