<?php 
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObjectPropertyPage', 'pnt/web/pages');

/** Kind of PropertyPage specialized for n to m relationships.
* Contains an MtoNPropertyPart with a table from which the user can remove items by clicking their remove icons.
* and a SearchFrame with an MtoNSearchPage from which the user can search and add items
* by clicking in them. The table is adapted client-side to show the resulting related
* items. When the save button is pressed the id's of the added and removed items
* are sent to the server and processed to add and remove the relationship objects 
* according to the selections of the user.
* @see example13 to see how to use this class.
* @package pnt/web/pages
*/
class PntObjectMtoNPropertyPage extends PntObjectPropertyPage {

	public $itdWidthSubst=530; //ItemTableDiv Width Substractor
	public $nmsfLeftSubst=410; //Named SearchFrame left Substractor
	public $itdHeightSubst=158;//ItemTableDiv Height Substractor

	function getPropertyPartName() {
		return 'MtoNPropertyPart';
	}
	
	/** Check access to a $this with the SecrurityManager. 
	* Forward to Access Denied errorPage and die if check returns an error message.
	*/
	function checkAccess() {
		$err = $this->controller->checkAccessHandler($this, 'EditObject');
		if ($err) $this->controller->accessDenied($this, $err); //dies
		
		$err = $this->controller->checkAccessHandler($this, 'EditProperty', $this->getPropertyDescriptor()); 
		if ($err) $this->controller->accessDenied($this, $err); //dies
	}

	function printMainPart() {
		$this->printSaveScript();
		$this->printPart('DetailsFormStartPart');
		$this->printHandlerOrigin();
		$this->printBooleanProps();
		$this->printPart('PropertyPart');
		print "
			</form>";
		$this->printScaleContentScripts();
	}
	
	function getSearchFrameName() {
		$part = $this->getPropertyPart(); //has been chached
		return $part->getSearchFrameName();
	}

	/** Also prints openNewItemDialog */
	function printScaleContentScripts() {
		$itemTableDiv = $this->getPropertyName().'Div';
		$searchFrame = $this->getSearchFrameName();
		$dialogSize = $this->getNewItemDialogSize();
		$dialogX = (int) $dialogSize->x;
		$dialogY = (int) $dialogSize->y;
		print "
		<script>
			 function scaleContent() {
			 		itd = getElement('$itemTableDiv'); 
					nmsf = getElement('$searchFrame');";
		if (!Gen::isBrowserIE()) print "
					itd.style.height=0;
					nmsf.style.left=0; nmsf.style.height=0";
		print "
					itd.style.width=(document.body.clientWidth)-$this->itdWidthSubst; 
					nmsf.style.left=(document.body.clientWidth)- $this->nmsfLeftSubst; 
					nmsf.style.height=(document.body.clientHeight)-$this->itdHeightSubst; 
					itd.style.height=(document.body.clientHeight)-$this->itdHeightSubst; 
			}
			func"."tion openNewItemDialog(url, propName) {
				value = $searchFrame.document.simpleFilterForm.pntF1v1.value;
				if (propName && value)
					url = url + '&' + encodeURIComponent(propName) + '=' + encodeURIComponent(value);
				popUp(url,$dialogX,$dialogY,100,75);
			}
		</script>";
	}

	/** PntObjectSaveAction interprets missing formKeys for Properties with type = 'boolean'
	* as false values, so we must include them 
	*/
	function printBooleanProps() {
		$formTexts = $this->getFormTexts();
		foreach ($formTexts as $formKey => $current) {
			if (!$current->isReadOnly()) {
				$nav =  $current->getNavigation();
				if ($nav->getResultType() == 'boolean') {
					print '
					<input type="HIDDEN" name="';
					$this->htOut($formKey);
					print ' value="';
					print $current->getMarkupWith($this->object);
					print '">';
				}
			}
		}
	}

	function getButtonsList() {
		$arr = parent::getButtonsList();
		$actButs[]= $this->getButton("Save", "saveDetailsForm();");
		$actButs[]= $this->getNewItemButton();
		
		$arr[0] = $actButs;
		return $arr;
	}

	function getNewItemButton() { 
		$cnv = $this->getConverter();
		$part = $this->getPropertyPart(); //has been chached
		$type = $part->getPropertyType();
		
		$params = array('id' => ''
			, 'pntHandler' => 'EditDetailsDialog'
			, 'pntType' => $type
			, 'pntProperty' => $this->getPropertyName()
			, 'pntFormKey' => $this->getPropertyName()
			, 'pntRef' => $this->getFootprintId()
			); 
		$appName = $this->controller->getAppName($part->getPropertyClassDir(), $type, $params['pntHandler']);
		$urlLit = $cnv->toJsLiteral($this->controller->buildUrl($params, $appName), "'");

		$clsDes = PntClassDescriptor::getInstance($type);
		$labelSort = $clsDes->getLabelSort();
		$suggestPropLit = $cnv->toJsLiteral($labelSort->getNewItemPropName(), "'"); 

		return $this->getButton(
			$this->getNewItemButtonLabel($clsDes->getLabel())
			, "openNewItemDialog($urlLit, $suggestPropLit);");
	}
	/** @return string caption for the new item button */
	function getNewItemButtonLabel($typeLabel) {
		return "New $typeLabel";
	}
	
	function getNewItemDialogSize() {
		$part = $this->getPropertyPart(); //has been chached
		$className = $part->getPropertyType(). 'EditDetailsDialog';
		if (!$this->tryUseClass($className, $part->getPropertyClassDir())) {
			$className = 'ObjectEditDetailsDialog';
			$this->useClass($className, $part->getPropertyClassDir());
		}
		return pntCallStaticMethod($className, 'getMinWindowSize');
	}

	function getBackToOrigin() {
		return true;
	}
	
	/* Print script for saving the EditDetailsForm. 
	* creates array pntMtoNPropsEdited in which MtoNPropertyParts should register
	* their propertynames and ItemTables' ids.
	* This script must be executed by the browser before scripts of 
	* MtoNPropertyParts are executed */
	function printSaveScript() {
		print "<script>
			var pntMtoNPropsEdited = new Array();
			func"."tion saveDetailsForm() {
				var i;
				for (i in pntMtoNPropsEdited) {
					pntSaveMtoNPropTableState(pntMtoNPropsEdited[i]);
				}
				//alert(document.detailsForm.propName.value);
				document.detailsForm.pntIgnoreMissingFields.value = '1'; 
				document.detailsForm.submit();
			}
			</script>";
	}
	
	function printHandlerOrigin() {
		print "\t\t\t\t<input type=hidden name='pntHandlerOrigin' value='MtoNPropertyPage'>";
	}
}
?>