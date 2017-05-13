<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPage', 'pnt/web/pages');

/** Dialog vor verifying a recursive delete op one or more objects.
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectDialog in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/dialogs
*/
class PntObjectVerifyDeletePage extends PntPage {

	/** @var array of string errormessages from marked objects */
	public $errorMessages;
	
	/** @return the label from which this can be recognized by the user */
	function getLabel() {
		$clsDes = $this->getTypeClassDescriptor();
		if (count($this->getDeleteErrorMessages()) > 0)
			return $this->getDeleteAnnouncement($clsDes->getVerifyOnDelete());
		
		return ($clsDes->getVerifyOnDelete()
			? $this->getDeleteAnnouncement($clsDes->getVerifyOnDelete()). ' - '
			: '') . $this->getDeleteQuestion();
	}
		
	function getDeleteAnnouncement($recursive) {
		return $recursive ? 'Recursive delete' : 'Delete';
	}
	
	function getDeleteQuestion() {
		return 'Are you sure you want to delete all these items?';
	}
	
	//returns Array of objects
	function getRequestedObject() {
		return $this->object;
	}
	
	function printMainPart() {
		$this->printPart('VerifyDeleteFormPart');
	}

	function getBackToOrigin() {
		return $this->getReqParam('pntBackToOrigin', true);
	}
	function getIgnoreMissingFields() {
		return $this->getReqParam('pntIgnoreMissingFields', true);
	}
	
	//do not register a move with the scout
	function doScouting() {
		$scout = $this->getScout();
		$this->footprintId = $scout->getReferrerId($this->requestData);
	}
	
	function hasFilterForm() {
		return false;
	}

	function getDeleteErrorMessages() {
		return $this->errorMessages;
	}
	
	function printVerifyDeletePart() {
		if (count($this->getDeleteErrorMessages()) > 0)
			return $this->printDeleteErrorInformation();
					
		$this->printDetailsFieldsHidden();
		$this->printSelectScriptPart();
		$table = $this->getInitItemTable();
		$table->printBody();
	}

	function printDetailsFieldsHidden() {
		$exclMulti = $this->getExcludedMultiValuePropButtonKeys();
		$formTexts = $this->getFormTexts();
		$this->printHiddenFieldsForFormTexts($this->getFormTexts(), $this->savedObject, $exclMulti);
		
		//older saveaction overrides may not set subsaveActions
		if (isSet($this->subsaveActions) && !empty($this->subsaveActions)) 
			$this->printSubsaveactionFieldsHidden();
	}
	
	/** Requires PntxGridPart */
	function printSubsaveactionFieldsHidden() {
		$this->useClass('GridPart', $this->getDir());
		forEach($this->subsaveActions as $i => $action) {
			if (!array_key_exists('id', $action->requestData)) 
				continue; //ignore the extra item added for new item function
			$formKey = GridPart::composeFormKey($action->getReqParam('pntProperty'), $action->subActionIndex, 'id');
			$this->printHiddenField($formKey, $action->getReqParam('id', true));
			$this->printHiddenFieldsForFormTexts($action->formTexts, $action->object);
		}
	}
	
	function printHiddenFieldsForFormTexts($formTexts, $obj, $exclMulti=array()) {
		reset($formTexts);
		forEach($formTexts as $key => $current) {
			//only print row for MtoNDialogWidget if no multi value property button
			if (Gen::is_a($current, 'PntFormMtoNRelValue') && !isSet($exclMulti[$key])) continue;
			if (!$current->isReadOnly()) {
				$this->printHiddenField($current->getFormKey(), $current->getMarkupWith($obj));
			}
		}
	}
	
	/** 
	 * @param string $formKey (untrusted)
	 * @param string $value HTML with eventual quotes encoded
	 */
	function printHiddenField($formKey, $value) {
		print "\t<input type=\"hidden\" name=\"";
		$this->htOut($formKey);
		print '" id="';
		$this->htOut($formKey);
		print "\" value=\"$value\">\n";
	}
	
	function printDeleteErrorInformation() {
		print $this->getDeleteErrorMessage();
		print "\n<lu>";
		forEach($this->errorMessages as $message)
			print "\n<li>";
			$this->htOut($message);
			print "</li>";
		print "\n</lu>";
	}
		
	function getDeleteErrorMessage() {
		return "<B>Item(s) can not be deleted because:</B> ";
	}

	function printSelectScriptPart() {
		?>
			<script>
				function okButtonPressed() {
					document.detailsForm.submit(); 
				}
				function tdl(obj, itemId) {
					popUpWindowAutoSizePos(tdlGetHref(obj, itemId)+'&pntHandler=ReportPage');
				}
				function tdlGetHref(obj, itemId) {
					arr = itemId.split('*');
					link = '../'+arr[0]+'index.php?pntType='+encodeURIComponent(arr[1])+'&id='+encodeURIComponent(arr[2]);
					if (pntFootprintId != null)
						link = link + '&pntRef=' + pntFootprintId; //important because IE does not pass HTTP_REFERER from javascript
					return link;
				}				
			</script>
		<?php
	}
	
	function getInitItemTable() {
		$table = $this->getPart(array(
			'TablePart'
			, $this->getType()
			, array('type' => 'classDescriptor', 'item' => 'label')
		));
		$table->setItemSelectWidgets(false);
		$table->setHandler_printTableHeaders($this);
		$table->setHandler_printItemCells($this);
		$table->setHandler_getCellOnClickParam($this);
		return $table;
	}

	function printTableHeaders($table) {
		$table->printTableHeaders($table);
		forEach($this->getRequestedObject() as $item) 
			if (Gen::is_a($item, 'HistoricalVersion')) {
				$this->printValidFrom = true;
				print "
					<TD class=pntIth></TD>";
				return; 
			}
	}
	
	function printItemCells($table, $item, $key) {
		$table->printItemCells($table, $item, $key);
		if (!isSet($this->printValidFrom)) return;
		
		$onClick = $this->getCellOnClickParam($table, $item, $key);
		print "
			<TD class=\"validFrom\" $onClick>";
			if (Gen::is_a($item, 'HistoricalVersion'))
				print $this->getConvert($item, 'validFrom');
			print "</TD>";
		
	}
	
	function getCellOnClickParam($table, $item) {
			$itemKey = $this->controller->getAppName($item->getClassDir(), get_class($item), 'ReportPage');
			$itemKey .= '/*'. $item->getOid();
			
			$itemKey = $this->getConverter()->toJsLiteral($itemKey, "'");
			return "onClick=\"tdl(this,$itemKey);\"";
	}
	
	function getOkButton() {
		if (count($this->getDeleteErrorMessages()) == 0)
			return $this->getButton("OK", "okButtonPressed();");
	}
	
	function getButtonsList() {
		$okBut = $this->getOkButton();
		if ($okBut) $actButs[] = $okBut;
		$actButs[] = $this->getCancelButton();
		return array($actButs);
	}
	
	function getCancelButton() {
		return $this->getButton("Cancel", "history.go(-1);");
	}
}	
?>