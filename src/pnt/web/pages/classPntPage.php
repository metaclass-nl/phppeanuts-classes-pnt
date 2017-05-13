<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntRequestHandler', 'pnt/web');

/** Abstract superclass of Page classes. 
* @see http://www.phppeanuts.org/site/index_php/Menu/241
* @package pnt/web/pages
*/
class PntPage extends PntRequestHandler {

	
	public $infoStyle;
	public $converters;
	public $parts;
	public $filterPartString;
	public $footprintId;

	/** @return string the information style for when a transaction has succeeded */
	static function getInfoStyleOk() {
		return 'pntInfoOk';
	}
	
	/** @return string the information style for when a transaction erorred */
	static function getInfoStyleError() {
		return 'pntInfoError';
	}

	/** @return string the information style for when a transaction warning */
	static function getInfoStyleWarning() {
		return 'pntInfoWarning';
	}

	/** @return string the css style for the InformationPart */
	function getInfoStyle() {
		if ($this->infoStyle)
			return $this->infoStyle;
			
		if (subStr($this->getInformation(), 0, 2) == 'OK')
			return $this->getInfoStyleOk();
			
		return 'pntInfo';
	}
	
	/** @param string $value the css style for the InformationPart */
	function setInfoStyle($value) {
		$this->infoStyle = $value;
	}
		
	/** Print the start of the HTML document - output starts here 
	 * Default implementation is to include skinHeader.
	 * In the default layout this includes the Body tag and the Page label */
	function printHeader() {
			//output starts here
			$this->includeSkin('Header');
	}
	
	/** Print the end of the HTML document. 
	 * Default implementation is to include skinFooter
	 */
	function printFooter() {
			$this->includeSkin('Footer');
	}

	/** Print the extra piece of the Body tag, for Internet Explorer only */
	function printBodyTagIeExtraPiece() {
		print 'scroll=no onResize="scaleContent()"  ONKEYDOWN="metKD(event);" ONKEYPRESS="metKP(event);"';
	}

	
	function includeSkin($name, $param=null) {
		$this->checkAlphaNumeric($name);
		$dir = $this->getDir();
		pntCheckIncludePath($dir);
		$filePath = "../$dir"."skin$name.php";
// print "<BR>includeSkin($name) $dir";
		if (file_exists($filePath)) {
			$included = include($filePath); //includeSkin from app folder
			if (!$included) 
				print "\n<BR>last warning was from includeSkin in: ". $this->getName();
		} else {
			$includesDir = $this->getIncludesDir();
			$included = include("../$includesDir/skin$name.php"); //includeSkin from includes folder
			if (!$included) 
				print "\n<BR>". $this->getName(). ' includeSkin could not include just before failure: '. $filePath;
		}
		return $included;
	}

	/** Legacy support, only with CMS beheer.css */
	function printSetTitle($title=null) {
		if ($title===null)
			$title = $this->getLabel();
		$titleLit = $this->getConverter()->toJsLiteral(str_replace("<BR>","",$title), "'");
	print "
		<script>
		document.title=$titleLit;
		getElement('titel').innerHTML=$titleLit;		
		</script>
		
	";
			
	}

	/* 
	* @param $script onClick script for the button
	* The caption and len parameters should not contain HTML 
	* as they are converted to html when te button is printed 
	*/
	function getButton($caption, $script, $ghost=false, $len=null) {
		return $this->getPart(
			array('ButtonPart'
				, $caption
				, $script
				, $ghost
				, $len
			)
			, false //no cache
		);
	}

	function handleRequest() {
		$this->initForHandleRequest();
		$this->checkAccess();

		$this->printHeaders(); 

		$this->printHeader();
		$this->printBody();
		$this->printFooter();
	}
	
	function getPartId() {
		if (isSet($this->partId)) return $this->partId;
		
		return $this->getName();
	}
	
	/** This method should take care of update of an existing 
	* page given the new requestData. 
	* To know what needs to be updated we would need some
	* form of scouting from which we can reconstruct the page state.
	* So for the moment the InformationPart, MainPart and ButtonsPanel are all updated
	* No context scouting is done on AJAX requests.
	*/
	function ajaxHandleRequest() {
		$this->initForHandleRequest();
		$this->checkAccess();
		
		$this->ajaxPrintHeaders();
		$this->includeSkin('AjaxPage');
	}
	
	function ajaxPrintHeaders() {
		header('Content-type: text/xml');
		header('X-Content-Type-Options: nosniff');
		header('X-Frame-Options: DENY');
	}
	
	/** For each of the parts of this page, print the ajax part update if necessary.
	 * Default impleentation is for Body, InformationPart, MainPart and subparts of ButtonsPanel.
	 * To be overridden by subclasses to accomodate their specific composition.
	 * PartNames should be coded literally or composed from strings coded literally to prevent out of context part access.
	 * Developers can browse the applicable overrides of this method to see which parts can be adressed.
	 * Be aware that recursive subparts can be addressed, @see PntObjectEditDetailsPage::ajaxPrintUpdates for an example
	 */ 
	function ajaxPrintUpdates($preFix='') {
		$this->ajaxPrintPartUpdate('Body', $preFix.'Body');
		$this->ajaxPrintPartUpdate('InformationPart', $preFix.'InformationPart');
		$this->ajaxPrintPartUpdate('MainPart', $preFix.'MainPart');
		//2DO: adapt to new subpart update feature
		$buttonsPanel = $this->getPart(array('ButtonsPanel'));
		$buttonsPanel->ajaxPrintUpdates();
	}
	
	/** @return the ids of the parts to be updated by ajax. 
	 * NB fiddling with the ajaxUpdatePartIds cache using ::getAjaxUpdateSubPartIds results
	 * WARNING, part ids are not yet checked to be alphaNumeric,
	 * including without check would be a serious security threat
	 */
	function getAjaxUpdatePartIds() {
		if (!isSet($this->ajaxUpdatePartIds))
			$this->ajaxUpdatePartIds = explode(',', $this->getReqParam('pntAjaxUpd'));
		return $this->ajaxUpdatePartIds;
	}

	/** @return array with ajaxUpdatePartIds for updating subparts by selecting 
	 * the getAjaxUpdatePartIds that are prefixed and removing the prefixes.  
	 * After setting the results of this method in a parts ajaxUpdatePartIds member
	 * the whole can delegate updating subparts to the part by calling ::ajaxPrintUpdates
	 * ::ajaxPrintUpdates must be overridden for the part to call ::ajaxPrintPartUpdate 
	 * for the subParts of the part.
	 * @param string $prefix of partIds to be selected and de-prefixed
	 * 
	 */
	function getAjaxUpdateSubPartIds($prefix) {
		$parentIds = $this->getAjaxUpdatePartIds();
		$result = array();
		forEach ($parentIds as $id) {
			$start = subStr($id, 0, strLen($prefix));
			if ($start == $prefix && $id != $prefix)
				$result[] = subStr($id, strLen($prefix));
		}
		return $result;
	}

	
	/** @return boolean wheather this should print an ajax update for the specified Part. 
	 * @param string $partId the id of the part in the pntAjaxUpd request param
	 * @param string $partName the name of the part that may be printed
	 * @param mixed $extraParam to be passed to ::printPart
	 * 
	 */ 
	function ajaxShouldUpdate($partId, $partName=null, $extraParam=null) {
		return in_array($partId, $this->getAjaxUpdatePartIds());
	}
	
	/** Prints an AJAX update for the named part.
	* all subParts are updated in a single update element
	* @param string $partName the name of the part to print inside the CDATA element of the updateElement
	* @param string $partId the id of the part in the pntAjaxUpd request param
	*/
	function ajaxPrintPartUpdate($partName, $partId=null, $extraParam=null) {
		if (!$partId) $partId = $partName;
		if (!$this->ajaxShouldUpdate($partId, $partName, $extraParam)) return;

		$includesDir = $this->getIncludesDir();
		include("../$includesDir/skinAjaxUpdateElement.php"); //does $this->printPart($partName)
	}
	
	/** @return boolean wheather the request is an AJAX request */
	function isRequestAjax() {
		return (boolean) $this->getReqParam('pntAjaxUpd');
	}
	
	/** Print the ajax part atttibutes. Currently the PhpPeanuts user interface componnents do not print any. */
	function ajaxPrintPartAttributes() {
		//ignore
	}

	/** @return string HTML the message to be printed inside a placeholder whose contents will
	 * be AJAX-loaded. The idea is that this message is only visible for the short time after 
	 * the placeholder is made visble, until AJAX loader replaces it by its dynamic content.
	 */
	function getAjaxLoadingMessage() {
		return ' - Loading, please wait - ';
	}

	/** initualize this for handling a HTTP request. 
	 * Overrides PntRequestHandler to add context scouting.
	 * Most RequestHandlers override this to lead the requested object(s) */
	function initForHandleRequest() {
		parent::initForHandleRequest();
		if (!$this->isRequestAjax())
			$this->doScouting(); // must be done after starting the session!!
	}  

	/** Check access to a $this with the SecrurityManager. 
	* Forward to Access Denied errorPage and die if check returns an error message.
	*/
	function checkAccess() {
		$err = $this->controller->checkAccessHandler($this, 'ViewClass');
		if ($err) $this->controller->accessDenied($this, $err); //dies
	}

	/** @return boolean wheather this page is to be shown in a layous suitable for reports.
	 * if true the default implementation of printBody will include skinReportBody instread of skinBody.
	 */
	function isLayoutReport() {
		return $this->getReqParam('pntLayout') == 'Report';
	}

	/** Print the HTTP Headers. This method is called before output starts.
	 * The default implementation is for no page caching by browser and proxy servers,
	 * but what really happens depends on the  browser and proxy server(s) */
	function printHeaders() {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                                                      // always modified
		header("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
		header("Pragma: no-cache");
		
		header("content-type: text/html; charset=". $this->getCharset());
		header('X-Content-Type-Options: nosniff');
		$this->printHeaderXframeOptions();
	}
	
	function printHeaderXframeOptions() {
		header('X-Frame-Options: '. $this->controller->xFrameOptions);		
	}
	
	/** @return string the identifier of the character set and encoding used
	 * for output of content, expected for input and specified for accept */
	function getCharset() {
		return $this->getConverter()->getLabelCharset();
	}
	
	/** Print the middle of the HTML document. 
	 * Default implementation is to include skinBody, but if isLayoutReport skinReportBody
	 */
	function printBody() {
		$this->includeSkin(
			  $this->isLayoutReport() ? 'ReportBody': 'Body');
	}

	/** Print the part that contains most of the document. 
	 * Is often specific for the specific RequestHandler.
	 * Default implementaion is to print the <pageName>Part. 
	 */ 
	function printMainPart() {
		$this->printPart($this->getName().'Part');
	}

	/** (Inlcude and) print a part.
	* The actual part class inclusion and instatiation itself is tried like this:
	* - print<partName> method on $this
	* $this->getPart(<partName>)->printBody(), which does:
	* 	- <pntType><partName> from application folder
	* 	- <pntType><partName> from classes folder
	* 	- <partName> from application folder
	* 	- <partName> from classes folder
	* - skin<pntType><partName>.php from application folder
	* $this->IncludeSkin(<partName), which does:
	* 	- skin<partName>.php from application folder
	* 	- skin<partName>.php from classes folder
	* Where <pntType> may be replaced by overriding ::getSpecificPartPrefix().
	* What actually happened may be inferred from the debug comments printed when Site>>debugMode is set to verbose
	* @param string $partName the name of the part to print.
	* more params will be passed to ::getPart.
	*/
	function printPart($partName) {
		$this->checkAlphaNumeric($partName); //prevent debug comments with uncontrolled content
		$debug = $this->getDebugMode();
		if (!$debug) 
			return $this->imp_printPart(func_get_args());

		$this->printPartDebugComment($partName, $debug);
		$this->imp_printPart(func_get_args());
		print "\n<!-- /$partName -->\n";
	}
	
	/* implementation of printPart to facilitate printing debug comments, do not call this method directly!
	 * WARNING, partName from first argument is nog checked */
	function imp_printPart($args) {
		$partName = $args[0];
		$methodName = "print$partName"; 
		if (method_exists($this, $methodName)) {
			$extraArgs = $args; //implicit copy
			array_shift($extraArgs);
			return call_user_func_array(array($this,$methodName), $extraArgs); //partName in methodname checked by ::printPart 
		}
		$part = $this->getPart($args);
		if ($part)
			return $part->printBody($args);
	
		
		$dir = $this->getDir();
		$skinName = $this->getSpecificPartPrefix().$partName;
		$filePath = "../$dir"."skin$skinName.php";
		if (file_exists($filePath)) {
			$this->checkAlphaNumeric($skinName);
			pntCheckIncludePath($dir);
			return include($filePath); //imp_printpart $skinName and $dir checked by ::printPart
		}
		if ($this->includeSkin($partName))
			return;
		
		// report all includes that went wrong
		$params = $this->getPartIncludeTryParams($partName);
		print "\n<BR>". $this->getName(). " could not include first:\n<BR>";
		while (list($key, $paramSet) = each($params)) 
			print "$paramSet[0], $paramSet[1],\n<BR>";
		print $filePath;
	}

	/** Gets a part, either from the cache or newly included and instantiated.
	* The actual part class inclusion and instatiation itself is tried like this:
	* - <pntType><partName> from application folder
	* - <pntType><partName> from classes folder
	* - <partName> from application folder
	* - <partName> from classes folder
	* Where <pntType> may be replaced by overriding ::getSpecificPartPrefix()
	* @param array $args with 0 => $partName (string) and the rest (mixed) to be passed as extra parameters to the part constructor.
	* @param boolean $cache wheather to try cache first and cache the part by $partName
	* @return PntPagePart the part, from the cache if available, or newly included and instantiated
	*/
	function getPart($args, $cache=true) {
		$partName = $args[0];
		// try cache first
		if ($cache) {
			$key = $cache===true ? $partName : $cache;
		 	if (isSet($this->parts[$key])) return $this->parts[$key];
		}

		$className = $this->getSpecificPartPrefix($partName).$partName;
		$included = $this->tryUseClass($className, $this->getDir()); //does checkAlphaNumeric

		if (!$included) {
			$className = $partName;
			$included = $this->tryUseClass($className, $this->getDir());
		}
		if ($included) {
			//2DO: use reflection, see http://www.phphulp.nl/php/forum/topic/class-instantieren-met-variabel-aantal-parameters/88097/
			switch (count($args)) {
				case 1: $part = new $className($this, $this->requestData); break;
				case 2: $part = new $className($this, $this->requestData, $args[1]); break;
				case 3: $part = new $className($this, $this->requestData, $args[1], $args[2]); break;
				case 4: $part = new $className($this, $this->requestData, $args[1], $args[2], $args[3]); break;
				case 5: $part = new $className($this, $this->requestData, $args[1], $args[2], $args[3], $args[4]); break;
				case 6: $part = new $className($this, $this->requestData, $args[1], $args[2], $args[3], $args[4], $args[5]); break;
				default: throw new PntError('too many part arguments: '. count($args));
			}
			if ($cache)
				$this->parts[$key] = $part;
			return $part;
		} else {
			return null; 
		}
	}
	
	/** @return Array of arrays with class name and path relative to ../classes
	 * describing the actual part inclusion that will be tried for the specified Part.
	 * @param string $partName
	 */
	function getPartIncludeTryParams($partName) {
		$paths = array();
		$paths = array_merge(
			$paths 
			, $this->getTryUseClassTryParams($this->getSpecificPartPrefix($partName).$partName, $this->getDir())
			, $this->getTryUseClassTryParams($partName, $this->getDir())
		);
		return $paths;
	}	

	/** @return string the prefix for type-specific parts. 
	 * Default implementation is to return the type of the requested object.
	 * @param string $partName
	 */
	function getSpecificPartPrefix($partName=null) {
		return $this->getType($partName);
	}
	
	/** Print the part inclusion debug comment 
	 * @param string the name of the part
	 * @param string the debug mode
	 */
	function printPartDebugComment($partName, $debug) {
		print "\n<!-- $partName in $this";
		if ($debug == 'verbose') {
			print "\n";
			print "options: (first * = succeeded)\n";
			$bullet = method_exists($this, "print$partName") ? '*' : '-';
			print $bullet.' $this->print'."$partName();\n";
			$params = $this->getPartIncludeTryParams($partName);
			$info = '';
			while (list($key, $paramSet) = each($params)) {
				$bullet = file_exists("../classes/$paramSet[1]class$paramSet[0].php")
					? '*' : '-';
				$info .= "$bullet Gen::tryIncludeClass('$paramSet[0]', '$paramSet[1]');\n"; //printPartDebugComment
			}
			print $info;
			$dir = $this->getDir();
			$fileName = "../$dir"."skin".$this->getSpecificPartPrefix()."$partName.php";
			$bullet = file_exists($fileName) ? '*' : '-';
			print "$bullet tryInclude('$fileName');\n";
			$fileName = "../$dir"."skin$partName.php";
			$bullet = file_exists($fileName) ? '*' : '-';
			print "$bullet tryInclude('$fileName');\n";
			$fileName = "../includes/skin$partName.php";
			$bullet = file_exists($fileName) ? '*' : '-';
			print "$bullet include('$fileName');\n"; //printPartDebugComment
		}
			
		print "-->\n";
	}
	
	/** Print the InformationPart, which informs the user about the processing results */
	function printInformationPart() {
		print $this->getInformation();
	}
	
	/** @return string HTML describing the active filter.
	 * default the active filter is the first global filter that applies to the type of the page. */
	function getFilterPartString() {
		if (isSet($this->filterPartString)) return $this->filterPartString;

		Gen::includeClass('PntSqlFilter', 'pnt/db/query');
		$cnv = $this->getConverter();
		$filter = $this->controller->getGlobalFilterFor($this->getType());
		$this->filterPartString = $filter
			? $cnv->toHtml($filter->getDescription($cnv)) : '';

		return $this->filterPartString;
	}
	
	/** @return array of arrays of PntButtonPart.
	 * Overridden by most pages and dialogs to define their specific buttons.
	 * Default is to return an empty array, so than no buttons will be printen.
	 */
	function getButtonsList() {
		return array();
	}

	/** Add a button for each multi value property except those in whose names 
	 * are in ::getExcludedMultiValuePropButtonKeys.
	 * Each button will hold a javascript from ::getMultiValuePropertyButtonScript, 
	 * whose default implementation is to retrieve an (MtoN)PropertyPage for the property.
	 * @param array $buttons to add the butons to.
	 */
	function addMultiValuePropertyButtons(&$buttons) {
		$excludedPropKeys = $this->getExcludedMultiValuePropButtonKeys();
		$obj = $this->getRequestedObject();
		$ghost = !$obj || !$obj->get('id');
		$sm = $this->controller->getSecurityManager();
		$formTexts = $this->getFormTexts();
		
		$clsDes = PntClassDescriptor::getInstance($this->getType());
		$multiProps = $clsDes->getMultiValuePropertyDescriptors();
		forEach(array_keys($multiProps) as $key)
			if (!isSet($excludedPropKeys[$key]) && $multiProps[$key]->getVisible() ) {
				$edit = isSet($formTexts[$key]);
				if ($edit) $edit = !$sm->checkEditProperty($obj, $multiProps[$key]);
				if (!$sm->checkViewProperty($obj, $multiProps[$key]))
					$buttons[]=$this->getButton(
						ucfirst($multiProps[$key]->getLabel()),
						$this->getMultiValuePropertyButtonScript($key, $edit),
						$ghost
					);
			}
	}
	
	/** Return an array with as keys the names of the multi value properties 
    * for which no buttons should be added by addMultiValuePropertyButtons
	* to be overridden by subclasses
	*/
	function getExcludedMultiValuePropButtonKeys() {
		return array();
	}

	function getMultiValuePropertyButtonScript($propName, $edit=false) {
		$type = $this->getType();
		$params = array(
			'pntHandler' => ($edit ? 'MtoNPropertyPage' : 'PropertyPage')
			, 'pntRef' => $this->getFootprintId()
			, 'pntProperty' => $propName
			, 'pntType' => $type
			, 'id' => $this->getReqParam('id')
			);
		$urlLit = $this->getConverter()->toJsLiteral($this->controller->buildUrl($params), "'");
		return  "document.location.href=$urlLit";
	}

	/** @return the value of the pntContext parameter for this page. 
	 * Before context scouting this parameter was used to return to the context
	 * after a SaveAction or when the user pressed the Context button. 
	 * nowadays it seldomly used.
	 */ 
	function getThisPntContext() {
		return '';
	}
	
	/** @return string an url to an (Edit)DetailtPage to wchich the id of 
	 * the object to edit/show details from can be concatenated.  
	 * @param string $appName nme of applcation folder where the DetailtPage should be retrieved from
	 * @param string $pntType the type of the object to be edite/shown
	 */
	function getDetailsHref($appName, $pntType) {
		$params = array('pntType' => $pntType);
		// pntRef is important because IE does not include HTTP_REFERRER if document.location.href from javascript
		if ($this->getFootprintId())
			$params['pntRef'] = $this->getFootprintId();
		$pntHandler = $this->getDetailsLinkPntHandler();
		if ($pntHandler)
		 	$params['pntHandler'] = $pntHandler;
		 	
		$params['id'] = ''; //must be last parameter
		return $this->controller->buildUrl($params, $appName);
	}
	
	/** @depricated
	 * @return the directory in which RequestHandlers can be retieved from
	 * for the result of the navigation staring my the requested object 
	 * @param PntObjectNavigation $nav 
	 * @param PntRequestHandler $pntHandler
	 */
	function getLinkDirFromNav($nav, $pntHandler='') {
		return $this->getTargetAppName($nav, $pntHandler) . '/';
	}

	/** @return the directory in which RequestHandlers can be retieved from
	 * for the result of the navigation staring my the requested object 
	 * @param PntObjectNavigation $nav 
	 * @param PntRequestHandler $pntHandler
	 */
	function getTargetAppName($nav, $pntHandler='') {
		$navDir = $nav->getResultClassDir();
		return $this->controller->getAppName($navDir, $nav->getResultType(), $pntHandler);
	}
	
	function getDetailsLinkPntHandler() {
		return '';
	}
	
	/** @return HTML string message replacing itemtable when no items */
	function getNoItemsMessage() {
		return "<font class=pntNormal>No Items</font><BR>\n";
	}
	
	function getRequestDuration() {
		$timeArr = explode(" ",microtime());
		$endTime = $timeArr[1].substr($timeArr[0],1,3);
		return round ($endTime - $this->controller->requestStartTime,2);
	}
	
	/** Navigate from $obj through $path. Convert the result to a (label)string.
	* @return string The (label)string, for $kind >= 0 as HTML.
	* @param PntObject $obj the object to start the navigation from 
	* @param string $path the path to navigate, as a series of property names separated by a dot
	* @param int $kind if < 0 the label is returend. Otherwise this decides wheather line feeds should be converted into <BR>
	* @param int $preformatAndTab the number of non breaking spaces to use for a tab.
		if > 0 existing multiple spaces will be converted into non-breaking spaces 
	*/
	function getConvert($obj, $path, $kind=0, $preformatAndTab=0) {
		if (!$obj) return '';
		$cls = $obj->getClass();
		$pathId = "$cls>>$path";
		if (!isSet($this->navs[$pathId])) {
			Gen::includeClass('PntNavigation', 'pnt/meta');
			try {
				$nav = PntNavigation::getInstance($path, $cls);
			} catch (PntError $err) {
				trigger_error($err->getLabel(), E_USER_WARNING);
				return '';
			}
			
			try {
				$value = $nav->evaluate($obj);
			} catch (PntError $err) {
				trigger_error($err->getLabel(), E_USER_WARNING);
				return '';
			}
		}
		$prop = $nav->getLastProp();
		$conv = $this->getInitConverter($prop);
		$label = $conv->toLabel($value, $prop->getType());
		return $kind < 0
			? $label
			: $conv->toHtml($label, $kind, $preformatAndTab);
	}
	
	function getInitConverter($prop) {
		$propId = (string) $prop;
		if (isSet($this->converters[$propId])) return $this->converters[$propId];
		
		$conv = $this->getConverter();
		$conv->initFromProp($prop);
		
		$this->converters[$propId] = $conv;
		return $conv;
	}

	function addContextButtonTo(&$buttons) {
		$contextHref = $this->getContextHref($this->getFootprintId());
		if (!$contextHref) return;
		$qSep = strPos($contextHref, '?') ? '&' : '?';
		$contextHref = $contextHref. $qSep. 'pntScd=u&pntRef='
			. $this->controller->converter->urlEncode($this->getFootprintId());
		
		$contextHref = $this->getConverter()->toJsLiteral($contextHref, "'");
		$buttons[]=$this->getButton('Context',	"document.location.href=$contextHref;");
	}

	/** Tell the scout how to interpret the requests
	* PRECONDITION: Session started
	*/
	function doScouting() {
		$scout = $this->getScout();
		$referrerId = $scout->getReferrerId($this->requestData);
		$direction = $this->getReqParam('pntScd'); 

		switch ($direction) {
			case "d": 
				$this->footprintId = $scout->moved($referrerId, 'down', $this->requestData);
			break; case "u": 
				$this->footprintId = $scout->moved($referrerId, 'up', $this->requestData);
			break; default: 
				if ($direction && $direction != 'h')
					throw new PntValidationException('Bad scouting direction: '.$direction);
				$this->footprintId = $scout->moved($referrerId, null, $this->requestData);
		}
	}

	/** @return string token identifying this page in context scouting */
	function getFootprintId() {
		return $this->footprintId;
	}
	
	function printFootprintJsLiteral($quote="'") {
		 print $this->controller->converter->toJsLiteral($this->getFootprintId(), $quote);
	}

	/** return string alphanumeric the name of the property, or null if not applicable */
	function getPropertyName() {
		if (!isSet($this->propertyName)) {
			$this->propertyName = $this->checkAlphaNumeric($this->getReqParam('pntProperty'));
		}
		return $this->propertyName;
	}
	
	function printNextActionTicket() {
		$this->htOut($this->getAndCreateNextActionTicket());
	}

	/** To prevent Cross-site request forgery each form has a parameter pntActionTicket
	* that will change each time the form is printed. It is generated here and 
	* stored in the session. When an action is invoked, the ticket is checked and removed
	* from the session. 
	* @return int the ticket value
	*/
	function getAndCreateNextActionTicket() {
		$sm = $this->controller->getSecurityManager();
		return $sm->getAuthenticator()->getAndCreateNextActionTicket($this->getFootprintId());
	}

	/** This method is called from skinHeaderPart
	*	it's purpose is to be able to append extra stylesheets or scripts to the head section of specific pages
	*/
	function printExtraHeaders() {
		//may be overridden by subclass
	}

	/** @return string with the relative path to the folder where the browser can retieve images from by HTTP. */
	function getImagesDir() {
		return $this->controller->getImagesDir();
	}	
}
?>