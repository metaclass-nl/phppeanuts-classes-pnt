<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntDetailsPart', 'pnt/web/parts');

/** Part showing property labels and editing property values of a single object
* By default shows properties specified by  getUiFieldPaths method 
* on the class of the shown object. Layout can be specialized, 
* @see http://www.phppeanuts.org/site/index_php/Pagina/150
*
* This abstract superclass provides behavior for the concrete
* subclass MenuPart in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/parts
*/
class PntEditDetailsPart extends PntDetailsPart {

	public $object;
	public $formTexts;
	public $textAreaTreshold = 120;
	public $dialogTreshold = 40;
	public $widgetDir = 'widgets';
	
	function getName() {
		return 'EditDetailsPart';
	}

	function printBody() {
		$this->includeOrPrintDetailsTable();
	}
	
	function printInformationPart() {
		print $this->getInformation();
		$this->printFormtextsInfo();

	}
	
	function printFormTextsInfo() {
		$formTexts = $this->getFormTexts();
		if (empty($formTexts))
			return;

		reset($formTexts);
		while (list($formKey) = each($formTexts)) {
			$current = $formTexts[$formKey];
			$error = $current->getError();
			if ($error) {
				print '<B>';
				$this->htOut($current->getPathLabel());
				print '</B><BR>';
				$this->htOut($error);
				print '<BR>';
			}
		}
	}

	/** @return HTML String information for the end user
	* If no other information, Return the editInformation from the requestedObject
	*/
	function getInformation() {
		$info = parent::getInformation();
		if ($info)return $info;

		$obj = $this->getRequestedObject();
		if ($obj)
			return $obj->getEditInfo();

	}
	
	function includeOrPrintDetailsTable() {

		$object = $this->getRequestedObject();
		if (!$object) return;

		$this->printOpenPageForScript();
		parent::includeOrPrintDetailsTable();
		$this->printOnUnloadScript();
	}

	function tryIncludeSkinReportDetailsTable($type) {
		return false; //never include ReportDetailsTable
	}

	function printOnUnloadScript() {
		$messageLit = $this->getConverter()->toJsLiteral($this->getOnUnloadMessage(), "'");
		print "
<SCRIPT>
			func"."tion pntDetailsFormUnloadConfirm(event, last) {
				if (pntFormRefData == null) return false;
				pntSaveEditors();
				if (!pntArraysEqual(pntFormRefData, pntGetFormValues(document.detailsForm))) {
					event.returnValue=$messageLit;
					return $messageLit;
				}
			}
			var pntFormRefData;
			pntInitAdd(pntInitDetailsFormRefData, 'pntInitDetailsFormRefData');
</SCRIPT>";		
	}
	
	function printOpenPageForScript() {
		print "
<SCRIPT>
			 func"."tion openPageFor(formKey, urlNoId) {
				objectId = document.detailsForm[formKey].value;
				str = urlNoId+encodeURIComponent(objectId);
				document.location.href=str;
			}
</SCRIPT>";		
	}

	function getOnUnloadMessage() {
		return "You have unsaved changes on this page";
	}

	function printFormWidget($formKey) {
		$text = $this->getFormText($formKey);
		if ($text === null) 
			return trigger_error("no formText for key: $formKey", E_USER_WARNING);

		if (!$this->willBeInput($text))
			return $this->printFormText($formKey);

		$widget = $this->getFormWidget($text);
		if ($widget)
			return $widget->printBody();
	}

	function getFormWidget($text) {
		$factory = $this->getWidgetFactory();
		return $factory->getDetailsFormWidget($text);
	}
	
	function getWidgetFactory() {
		if (isSet($this->widgetFactory)) return $this->widgetFactory;
		Gen::includeClass('WidgetFactory', $this->widgetDir);
		$this->widgetFactory = new WidgetFactory($this);
		$this->widgetFactory->setTextAreaTreshold($this->textAreaTreshold);
		$this->widgetFactory->setDialogTreshold($this->dialogTreshold);
		return $this->widgetFactory;
	}

	function printDetailsExtra($formKey) {
		$text = $this->getFormText($formKey);
		if ($text === null) {
			print "error no: ";
			return $this->htOut($formKey);
		}
		
		$nav = $text->getNavigation();
		if (!$nav->isSettedReadOnly() && $nav->isSettedCompulsory())
			$this->printCompulsorySign($text);
		else
			$this->printDetailsExtraEmpty($text);
	}
	
	function printCompulsorySign($formText) {
		print '*';
	}
	
	/** @return HTML string a hyperlink to the DetailsPage for the object $text 
	* refers to, or just the $content if $text does not refer to an object 
	* that has an id
	* @param PntFormNavValue $text DOM object for processing the form parameter
	* @param HTML string $content the content of the hyperlink
	* @param $hrefNoId HREF string the href ending with id= but without the id of the object
	*/
	function getDetailsLinkFromNavText($part, $text, $content, $hrefNoId=null) {
		if (!$this->willBeInput($text))
			return parent::getDetailsLinkFromNavText($part, $text, $content, $hrefNoId);

		$nav = $text->getNavigation();
		$setted = $nav->getSettedProp();
		if ($setted->isMultiValue()) return $content;

		$formKey = $text->getFormKey();
		if (!$hrefNoId) {
			$hrefNoId = $this->getDetailsHref(
				$this->getTargetAppName($nav, $this->getDetailsLinkPntHandler()),
				$nav->getResultType()
			);
		}
		$hrefNoIdLit = $this->getConverter()->toJsLiteral($hrefNoId, "'");
		$formKeyLit = $this->getConverter()->toJsLiteral($formKey, "'");
		$url = "javascript:openPageFor($formKeyLit, $hrefNoIdLit);";

		return "<A HREF=\"$url\">$content</A>";
	}

	function willBeInput($text) {
		return !$text->isReadOnly();
	}

}
?>