<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObjectSearchPage', 'pnt/web/pages');
Gen::includeClass('PntPoint', 'pnt/graphics');

/** Dialog with FilterFormPart for searching and selecting an object.
* Paging buttons are created by a PntPagerButtonsListBuilder, 
* whose classfolder is pnt/web/helpers.
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
class PntObjectDialog extends PntObjectSearchPage {

	public $advancedFilterOverlayLeft = 5;
	
// *****************  methods from PntDialog *******************

	/** @static 
	 * @return the piece of the javascript that will be called by the dialog
	 * the piece must deliver the id in variable pId and the label in pLabel
	 * @param string $formKey ignoored
	 */
	static function getReplyScriptPiece($formKey='') {
		return "func"."tion pntSetDialogResult(pId, pFormKey, pLabel) {
		";
	}
	
	// Return a PntPont with minimum width and height
	static function getMinWindowSize() {
		$result = new PntPoint(700,450);
		return $result;
	}

	/** Check access to a $this with the SecrurityManager. 
	* Forward to Access Denied errorPage and die if check returns an error message.
	*/
	function checkAccess() {
		$err = $this->controller->checkAccessHandler($this);
		if (!$err) {
			$sm = $this->controller->getSecurityManager();
			$err = $sm->checkSelectProperty(
				$this->getRequestedObject()
				, $this->getTypeClassDescriptor()
				, $this->getPropertyName() );
		}
		if ($err) $this->controller->accessDenied($this, $err); //dies
	}

	// use skinBody without menu
	function printBody() {
		$this->includeSkin('DialogBody');
	}

	/** This method should take care of update of an existing 
	* dialog. The default is to update the enire dialog with
	* all subParts in a single update element. In practice this
	* is used to load the contents of an overlay.
	* No context scouting is done on AJAX requests.
	*/
	function ajaxPrintUpdates($preFix='') {
		$this->ajaxPrintPartUpdateHeader($this->getName(), $this->getReqParam('pntAjaxUpd'));
		$this->printBody();
		$this->ajaxPrintPartUpdateFooter();
	}

	function printReturnFuncName() {
		print 'pntSetDialogResult';
	}

//  *********** specific and adapted methods ************************

	function getName() {
		return 'SearchDialog';
	}

	function getButtonsList() {

		$actButs[] = $this->getOkButton();
		$actButs[] = $this->getNewItemButton();
		$actButs[] = $this->getButton("Cancel", "window.close();");

		$navButs=array();
		$builder = $this->getPagerButtonsListBuilder();
		$builder->addPageButtonsTo($navButs);
		
		return array($actButs, $navButs);
	}

	function getOkButton() {
		//WARNING: you need to escape the alert text as jsLiteral  
		return $this->getButton("OK", "alert('click on an item in the table')");
	}
	
	function getNewItemButton() {
		$type = $this->getType();
		$params = array('id' => '', 'pntScd' => 'd'
			, 'pntHandler' => 'EditDetailsDialog'
			, 'pntType' => $type
			, 'pntProperty' => $this->getPropertyName()
			, 'pntRef' => $this->getFootprintId()
			, 'pntFormKey' => $this->getFormKey()
			); 
		$appName = $this->controller->getAppName('', $type, $params['pntHandler']);
		$urlLit = $this->getConverter()->toJsLiteral($this->controller->buildUrl($params, $appName), "'");
		return $this->getButton("Create New", "document.location.href=$urlLit");
	}

	//override PntObjectSearchPage method to get dialog skin
	function printMainPart() {
		$this->printPart($this->getName().'Part');
	}

	function getPropertyDescriptor() {
//PROBLEM we don't know the owner of the propertyDescriptor		
	}

	/** Override, no propertyFilter */
	function getPropertyFilter() {
		return null;
	}

	function getGlobalFilter() {
		return null;
	}
	
	
	/** default is the object currently selected
	 *
	 * @throws PntError
	 */
	function getRequestedObjectDefault() {
		$clsDes = $this->getTypeClassDescriptor();
		$cnv = $this->getConverter();
		$id = $cnv->fromRequestData( $this->getRequestParam('id') );
		$prop = $clsDes->getPropertyDescriptor('id');
		$cnv->initFromProp($prop);
		$converted = $cnv->fromLabel($id);
		if ($cnv->error) 
			throw new PntError('id conversion: '. $cnv->error);
		$this->object = $clsDes->getPeanutsWith('id', $converted);
		return $this->object;
	}

	function getLabel() {
		return $this->getTypeLabel()
			." - "
			.$this->getItemsInfo();
	}

	/** @return HTML info about the number of items and paging, here overridden to show errors if they exist */
	function getItemsInfo() {
		$filterFormPart = $this->getFilterFormPart();
		$filter = $filterFormPart->getRequestedObject();
		if ($filter)
			return parent::getItemsInfo();
			
		return $this->getCurrentValueDescription();
	}
	/** @return string HTML */
	function getCurrentValueDescription() {
		return 'Current Value';
	}

	function printItemTablePart() {
		$table = $this->getInitItemTable();
		$table->printBody();
	}

	function printSelectScriptPart() {
		if ($this->isMultiSelect())
			$this->printMultiSelectScript(); //notYetImplemented
		else
			$this->printSingleSelectScript();
	}
	
	function printSingleSelectScript() {
		$formKeyLit = $this->getConverter()->toJsLiteral($this->getFormKey(), "'");
		?>
			<script>
				function tdl(itemId, itemLabel) {
					window.opener.<?php $this->printReturnFuncName() ?>(itemId, <?php print $formKeyLit ?>, itemLabel);
					window.close();
				}
			</script>
		<?php
	}
	/** @return string according to ::checkAlphaNumeric */
	function getFormKey() {
		$param = $this->getReqParam('pntFormKey');
		return $param ? $this->checkAlphaNumeric($param) : $this->getPropertyName();
	}

	function getInitItemTable() {
		$table =  $this->getPart(array('TablePart'));
		$this->setTableHeaderSortParams($table);
		$table->setHandler_getCellOnClickParam($this); 
		$table->setItemSelectWidgets($this->isMultiSelect());
		return $table;
	}

	function isMultiSelect() {
		return false; 
		//multiselect not yet supported, plan is to use request parameter tot activate multiselect
		// implement printMultiSelectScript first
	}

	/** Return the onClick parameter for inmclusion in the TD tags by printItemCells
	* Eventhandler 
	* @argument PntTablePart $table $this, made explicit for copy&paste as event handler
	* @argument PntObject $item the item this row displays
	* @argument mixed $cellKey (optional) key of the cell content provider in $table->cells 
	*/
	function getCellOnClickParam($table, $item) {
		if (!$table->peanutItems) return ''; 
		
		$cnv = $this->controller->converter;
		$itemLabelLit = $cnv->toJsLiteral($item->get('label'), "'");
		$itemIdLit = $cnv->toJsLiteral($item->get('id'), "'");
		return "onClick=\"tdl($itemIdLit, $itemLabelLit);\"";;
	}
	
}	
?>