<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPagePart', 'pnt/web/parts');

/** Part showing property labels and property values of a single object
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
class PntDetailsPart extends PntPagePart {

	function __construct($whole, $requestData) {
		parent::__construct($whole, $requestData);
		$this->setHandlerGetDetailsLinkFromNavText($this);
	}
	
	function getName() {
		return 'DetailsPart';
	}

	function setHandlerGetDetailsLinkFromNavText($value) {
		$this->handlerGetDetailsLinkFromNavText = $value;
	}
	
	function printBody() {
		$this->getRequestedObject();
		$this->getFormTexts();
		
		$this->printPart('LabelPart');
		$this->includeOrPrintDetailsTable();
		//parent::printBody();
	}

	function willBeInput($text) { 
		return false; //no input widgets 
	}

	function printLabelPart() {
		$obj = $this->getRequestedObject();
		if ($obj !== null)
			print '<H1>'. $this->getConvert($obj, 'label').'</H1>';
		else 
			print $this->getEventualItemNotFoundMessage();
	}
	
	function includeOrPrintDetailsTable() {
		$object = $this->getRequestedObject();
		if (!$object) return;

		$type = $this->getType(); //already checkedAlphaNumeric
		
		if ($this->tryIncludeSkinReportDetailsTable($type)) return;;
		
		$filePath = "skin$type".'DetailsTable.php';
		if (file_exists($filePath)) return include($filePath); //$type checked
			
		$this->printPart('DetailsTablePart');
	}
	
	function tryIncludeSkinReportDetailsTable($type) {
		$filePath = "skin$type".'ReportDetailsTable.php';
		if (file_exists($filePath)) {
			include($filePath); //$type checked by getType
			return true;
		} else {
			return false;
		}
	}

	/** @return string HTML describing the active filter.
	 * default the active filter is the first global filter that applies to the type of the page.
	 * But some details of the requested object may respond to filters that apply to a property.
	 * For simplicty DetailsPart shows alle global filters. 
	 * To be overridden on a concrete subclass if you need a more specific description. 
	*/
	function getFilterPartString() {
		if (isSet($this->filterPartString)) return $this->filterPartString;

		Gen::includeClass('PntSqlFilter', 'pnt/db/query');
		$cnv = $this->getConverter();
		$this->filterPartString = '';
		forEach($this->controller->getGlobalFilters() as $filter)
			$this->filterPartString = $cnv->toHtml($filter->getDescription($cnv)). "<br>\n";

		return $this->filterPartString;
	}
		
	function printDetailsRows($readOnly, $printSkin=false) {
		$exclMulti = $this->getExcludedMultiValuePropButtonKeys();
		$formTexts = $this->getFormTexts();
		reset($formTexts);
		while (list($formKey) = each($formTexts)) {
			$current = $formTexts[$formKey];
			//only print row for MtoNDialogWidget if no multi value property button
			if (Gen::is_a($current, 'PntFormMtoNRelValue') && !isSet($exclMulti[$formKey])) continue;
			if ($current->isReadOnly() == $readOnly) {
				if ($printSkin)
					$this->printSkinDetailsRow($formKey);
				else
					$this->printDetailsRow($formKey);
			}
		}
	}
		
	function printDetailsRow($formKey) {
?>
                                <tr vAlign="top"> 
                                	<td class="pntHeader" id="labelcell-<?php $this->htOut($formKey) ?>"><?php $this->printFormLabel($formKey) ?></td>
                                	<td class="pntDetailsExtra" id="extracell-<?php $this->htOut($formKey) ?>"><?php $this->printDetailsExtra($formKey) ?></td>
                                	<td class="pntNormal" id="widgetcell-<?php $this->htOut($formKey) ?>"><?php $this->printFormWidget($formKey) ?></td>
                                </tr>
<?php				
	}

	function printSkinDetailsRow($formKey) {
		$formKeyEscaped = addSlashes($formKey);
		$formKey = $this->getConverter()->toHtml($formKey);
		print "
                                 <tr vAlign=\"top\"> 
                                	<td class=\"pntHeader\" id=\"labelcell-$formKey\"><?php \$this->printFormLabel('$formKeyEscaped') ?></TD>
                                	<td class=\"pntDetailsExtra\" id=\"extracell-$formKey\"><?php \$this->printDetailsExtra('$formKeyEscaped') ?></TD>
                                	<TD class=\"pntNormal\" id=\"widgetcell-$formKey\"><?php \$this->printFormWidget('$formKeyEscaped') ?></TD>
                                </TR>
";	
	}

	/** Overridden to include DetailsRow, FormLabel, DetailsExtra, FormWidget into ajax updates. 
	 * The formKey must be passed by the ajax request in the partId as for a subPart. 
	 * For example 'pntAjaxUpd=DetailsPart.DetailsRow.description passed with a request to 
	 * a ReportPage should result in the output of the DetaislRow with formkey == 'description'.
	 */
	function ajaxPrintUpdates($preFix='') {
		parent::ajaxPrintUpdates($preFix);
		
		forEach($this->getAjaxUpdateSubPartIds('DetailsRow.') as $formKey)
			$this->ajaxPrintPartUpdate('DetailsRow', $preFix.'DetailsRow.'.$formKey, $formKey);
		forEach($this->getAjaxUpdateSubPartIds('FormLabel.') as $formKey)
			$this->ajaxPrintPartUpdate('FormLabel', $preFix.'FormLabel.'.$formKey, $formKey);
		forEach($this->getAjaxUpdateSubPartIds('DetailsExtra.') as $formKey)
			$this->ajaxPrintPartUpdate('DetailsExtra', $preFix.'DetailsExtra.'.$formKey, $formKey);
		forEach($this->getAjaxUpdateSubPartIds('FormWidget.') as $formKey)
			$this->ajaxPrintPartUpdate('FormWidget', $preFix.'FormWidget.'.$formKey, $formKey);
	}
	
	function ajaxShouldUpdate($partId, $partName=null, $extraParam=null) {
		if (in_array($partName, array('DetailsRow', 'FormLabel', 'DetailsExtra', 'FormWidget'))) {
			return (boolean) $this->getFormText($extraParam);
		}
		return parent::ajaxShouldUpdate($partId, $partName, $extraParam);
	}
	
	function printDetailsExtra($formKey) {
		$text = $this->getFormText($formKey);
		if ($text !== null) 
			return $this->printDetailsExtraEmpty($text);
			
		print "error no: ";
		$this->htOut($formKey);
	}

	function printDetailsExtraEmpty($formText) {
		print '&nbsp;';
	}
	
	function getFormWidget($text) {
		return array($text); 
	}
	
	function getMarkupFromFormText($formKey) {
		$text = $this->getFormText($formKey);
		if ($text === null) {
			trigger_error("no formText for key: $formKey", E_USER_WARNING);
			return null;
		}
		return $text->getMarkupWith($this->getRequestedObject());
	}

	/** @return Array of PntNavValue
	 * meant for ReportPage. EditDetailsPage explicitly initializes $this->formTexts
	*/
	function getFormTexts() {
		if (isSet($this->formTexts)) return $this->formTexts;
		
		Gen::includeClass('PntFormNavValue', 'pnt/web/dom');
		$sm = $this->controller->getSecurityManager();
		$obj = $this->getRequestedObject();
		
		$fieldPaths = $this->getFormTextPaths();
		if ($fieldPaths === null && is_subclassOr($this->getType(), 'PntObject')) {
			$clsDes = PntClassDescriptor::getInstance($this->getType());
			$fieldPaths = $clsDes->getUiFieldPaths();
		} 
		$this->formTexts = array(); 
		forEach($fieldPaths as $label => $path) {
			if ($readOnly = $path[0]=='^') //assigns $readOnly
				$path = subStr($path,1);
			try {
				$nav = PntNavigation::getInstance($path, $this->getType());
			} catch (PntError $err) {
				trigger_error($err->getLabel(), E_USER_WARNING);
				continue;
			}
			$prop = $nav->getSettedProp();
			if (!$obj || $sm->checkViewProperty($obj, $prop)) continue; //don't show the prop
			$inst = new PntXmlNavValue(null, $this->getType(), $path);
			if ($readOnly) $inst->setReadOnly(true);
			$inst->setConverter($this->getConverter());
			if (is_string($label)) $inst->setPathLabel($label);
			$this->formTexts[$inst->getFormKey()] = $inst;
		} 

		return $this->formTexts;
	}

	function getFormText($formKey) {
		$formTexts = $this->getFormTexts();
		if (isSet($formTexts[$formKey])) return $formTexts[$formKey];
		
    	//PeportPage may use the same skin as the EditDetailsPage. 
		//But that skin may include formKeys for the id property.
		//We want to show the derived property whose idProperty that is.
		//HACK: should search through the derived proptertyDescriptors
		// or use default formTexts and produce correct output in other methods
		if (subStr($formKey, -2) == 'Id') {
			$key = subStr($formKey, 0, strLen($formKey) -2);
			if (isSet($formTexts[$key])) return $formTexts[$key];
		}		
		return null;
	}
	
	function printFormText($formKey, $empty='&nbsp;') {
		$markup = $this->getMarkupFromFormText($formKey);
		print strLen($markup) == 0 ? $empty : $markup;
	}
	
	function printFormLabel($formKey) {
		$text = $this->getFormText($formKey);
		if ($text === null) {
			print "error no: ";
			return $this->htOut($formKey);
		}

		$cnv = $text->getConverter();
		$label = $cnv->toHtml($text->getPathLabel());
		$nav = $text->getNavigation();
		if (!in_array($nav->getResultType(), PntPropertyDescriptor::primitiveTypes()))
			Gen::tryIncludeClass($nav->getResultType(), $nav->getResultClassDir()); //nav from formText
		if (!is_subclassOr($nav->getResultType(), 'PntDbObject')) 
			print $label;
		else
			print $this->handlerGetDetailsLinkFromNavText->getDetailsLinkFromNavText($this, $text, $label);
	}

	/** @depricated */
	function printDetailsLink($formKey) {
		$this->printDetailsExtra($formKey);
	}

	/** @return HTML string a hyperlink to the DetailsPage for the object $text 
	* refers to, or just the $content if $text does not refer to an object 
	* that has an id
	* @param PntPagePart $part the part calling this eventHanlder function
	* @param PntXmlNavValue $text DOM object for processing the form parameter
	* @param HTML string $content the content of the hyperlink
	* @param $hrefNoId HREF string the href ending with id= but without the id of the object
	* @throws PntError 
	*/
	function getDetailsLinkFromNavText($part, $text, $content, $hrefNoId=null) {
		if (!Gen::is_a($text, 'PntXmlNavValue')) throw new PntError('text parameter must be a PntXmlNavValue');
		$nav = $text->getNavigation();
		$setted = $nav->getSettedProp();
		if ($setted->isMultiValue()) return $content;
		
		if (!$hrefNoId) $hrefNoId = $this->getDetailsHref(
				$this->getTargetAppName($nav), 
				$nav->getResultType()
			);
		$idPath = $nav->getIdPath();
		if (!$idPath)
			$idPath = $nav->getPath() . '.id';
		$idNav = PntNavigation::getInstance($idPath, $nav->getItemType());
		$id = $idNav->evaluate($this->getRequestedObject());

		if (!$id) return $content; // no need to try, no link
		$obj = $nav->evaluate($this->getRequestedObject());
		if (!$obj) return $content; //no link to non existent object

		$id = $this->getConverter()->urlEncode($id);
		return "<A HREF=\"$hrefNoId$id\">$content</A>";
	}

	//only here for compatibilty with older skins
	function printFormWidget($formKey) {
		return $this->printFormText($formKey);
	}
	
	function setDetailsLinkPntHandler($value) {
		$this->detailLinkPntHandler = $value;
	}
	
	function getDetailsLinkPntHandler() {
		return isSet($this->detailLinkPntHandler) 
			? $this->detailLinkPntHandler
			: 'ReportPage';
	}
	
}
?>