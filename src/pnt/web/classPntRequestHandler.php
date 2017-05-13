<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntValidationException', 'pnt/secu');
 
/** Abstract superclass of all http request handlers.
* @package pnt/web
*/
class PntRequestHandler {

	/** PntRequestHandler $whole from which this is be a part */
	public $whole;
	/** HTML String information for the end user */
	public $information;
	public $controller;
	public $requestData;
	public $request;
	
	/**  
	 * @param PntRequestHandler $whole from which this will be a part
	 * @param array $requestData lime $_REQUEST
	 * (Does not add this to the parts cache of $whole)
	 */
	function __construct($whole, $requestData) {
		$this->whole = $whole;
		$this->controller = $this->whole->controller;
		$this->requestData = $requestData;
		$this->request = $this->controller->request;
	}

	/** The application folder, where application specific skins are included from, 
	 * also whithin the classes folder where user interface components are includen from.
	 * Is set to Site from the index.php script.
	 * @return string relatice path with trailing slash */
	function getDir() {
		if (isSet($this->dir) && $this->dir) 
			return $this->dir;
			
		return $this->whole->getDir();
	}

	/** folder whithin the classes folder where domain classes are included from.
	 * If not set to Site from the index.php script, this defaults to getDir without trailing slash
	 * @return string relatice path without trailing slash */
	function getDomainDir($type=null) {
		return $this->whole->getDomainDir($type);
	}

	/** @return string With absloute url that maps to the folder where whpPeanuts was installed in, 
	 *     and in which the application folders and the classes folder reside */
	function getBaseUrl() {
		return $this->whole->getBaseUrl();
	}

	/** @return PntStringConverter properly initialized for the language currenly used 
	 * Default is an instance of StringConverter from the classes folder.
	 */
	function getConverter() {
		return $this->whole->getConverter();
	}

	/** @return string one from '', 'short', 'verbose' */
	function getDebugMode() {
		return $this->whole->getDebugMode();
	}

	/** Initialize this for handling a HTTP request 
	 * Default implementation is to include the requested object class and start the session.
 	 * Most RequestHandlers override this to do context scouting and load the requested object(s).
	 */
	function initForHandleRequest() {
		// may be overridden by subclass
		$this->useClass($this->getType(), $this->getDomainDir());
		
		$this->startSession();
	}

	/** Start a session if not already started */
	function startSession() {
		$this->whole->startSession();
	}
	
	/** Context Scouting
	* @param int $footprintId the id of the footprint to get the context href from
	* @return String absolute url to context, or null if none
	*/
	function getContextHref($footprintId) {
		return $this->whole->getContextHref($footprintId);
	}
	
	/** @return PntSite the front controller. Can also be obtained by $this->controller directly */ 
	function getController() {
		return $this->controller;
	}

	/** Context Scouting
	* @return PntSessionBasedScout that keeps track of the contexts
	*/
	function getScout() {
		return $this->whole->getScout();
	}
	
	/** @return array of PntSqlFilter to be used in the entire application where applicable
	 * These filers are usually stored in te session.
	 * SearchPages will try to use the global filters, propertypages and parts will not,
	 * they may have to be overridden or the domain model has to be made global filter sensitive. 
	 * Make sure to include eventual additional filter classes before calling this method
	 */
	function getGlobalFilters() {
		return $this->whole->getGlobalFilters();
	}

	/** Include and instantiate a PntRequestHandler and let it handle the request
	 * @param array $requestData like $_REQUEST used to decide which PntRequestHandler 
	 * 	    to include and instantiate, and passed to the PntRequestHandler as the requst data.
	 * @param string $information to be passed to the PntRequestHandler, 
	 *      usually overrides the pntInfo parameter if not null (this behavior depends on the actual PntRequestHandler)  
	 */
	function forwardRequest($requestData, $information=null) {
		$this->whole->forwardRequest($requestData, $information);
	}

	/** Redirect the request to a different server, page or handler
	* This only works if NOTHING has been printed yet!!
	* @param Array or String $requestData if Array redirects to handler in same application.
	*	otherwise redirects to the url it assumes is in $requestData
	* @param String $information information to be passed as a request parameter
	*   this will replace eventual information parameter from $requestData 
	*   but not inf the information param is in the funky part of a funky url
	* @param String $dir directory to redirect to. If null $this->getDir() will be used.
	*/ 
	function redirectRequest($requestData, $information=null, $dir=null) {
		if (is_array($requestData)) {
			if ($information !== null)
				$requestData["pntInfo"] = $information;
			if ($dir==null) $dir = $this->getDir();
			$url = $this->getBaseUrl();
			$url .= $dir. 'index.php?';
			$url .= $this->queryStringFrom($requestData);
		} else {
			if ($information === null) {
				$url = $requestData;
			} else {
				$url = Gen::stripQueryParam($requestData, 'pntInfo');
				$url .= (strPos($url, '?') === false) ? '?' : '&';
				$url .= "pntInfo=".$this->getConverter()->urlEncode($information);
			}
		}
		if (preg_match('/[\n\r]/', $url)) throw new PntError('Url with Cr or Lf');
		header("Location: $url"); /* Redirect browser */
		exit;
	}
	
	/** @return string http query string
	 * @param array $requestData associative, of string, possibly nested, like $_REQUEST
	 * @param string $param to be used for creating nested multiple values: <param>[<key>]=<value>
	 */ 
	function queryStringFrom($requestData, $param=null) {
		$keyValueSep = '=';
		$paramSep = '&';
		$params = array();
		$cnv = $this->getConverter();
		forEach($requestData as $key => $value) {
			if ($param!==null)
				$key = $param.'['. $key .']';
			
			$params[] = is_array($value) 
				? $this->queryStringFrom($value, $key) 
				: $cnv->urlEncode($key). $keyValueSep. $cnv->urlEncode($value);
		}
	
		return implode($paramSep, $params);
	}
	
	/** @return value of request parameter as if magic_quotes_gpc is OFF,
	 * 		validated or eventually sanitized with respect to character encoding 
	 * 		or null if the parameter does not exist or sanitation failed.
	 * @param string $name key in $_REQUEST (without cookies) 
	 * @see HttpValidator and pnt.web.PntHttpValidator 
	 */ 
	function getRequestParam($key) {
		return isSet($this->requestData[$key]) ? $this->requestData[$key] : null;
	}
	
	/** @return value of request parameter as if magic_quotes_gpc is OFF and sanitized from html tags
	* @param boolean $asHtml as html (html_entities)
	*/
	function getReqParam($key, $asHtml=false) {
		$value = $this->controller->converter->fromRequestData($this->getRequestParam($key));
		return $asHtml ? $this->controller->converter->toHtml($value) : $value;
	}
	
	/** htOut Outputs (prints) a string converting it to html (htmlEntities)
	* The controllers StringConverter is used so its character set setting will be used */
	function htOut($aString) {
		print $this->controller->converter->toHtml($aString);
	}

	/** @return PntRequestHandler appropriate to handle a request. 
	 * First a class named <pntType><pntHandler> is tried. 
	 * If it can not be included, Object<pntHandler> will be used. 
	 * If pntHandler param is missing in $requestData a default will be used, see implementation fo this method.
	 * A special case is if pntHandler=(MtoN)PropertyPage: then <pntType>Property<pntProperty>Page is tried first, 
	 * @param array $requestData associative, of string like $_REQUEST, 
	 *      used to decide which class to include and instantiate, 
	 *      and passed to the PntRequestHandler as the request data.
	 * @param string $dir appears to be ignored.  $this->getDir() will be used.
	 * For each class name inclusion is first tried from $this->getDir(), 
	 * 	    If the class is not found, a class from the classes root folder is tried
	 * @throws PntValidationException is no class could be included
	 */
	function getRequestHandler($requestData, $dir=null) {
		if (!$dir) $dir = $this->getDir();
		$id = isSet($requestData["id"]) ? $requestData["id"] : null;
		$specifiedHandler = $handler = isSet($requestData["pntHandler"])
			?  $requestData["pntHandler"] : null;
		$property = ucFirst(isSet($requestData["pntProperty"]) ? $requestData["pntProperty"] : '');
		$type = isSet($requestData["pntType"]) ? $requestData["pntType"] : null;
		if ($handler=='PropertyPage' || $handler=='MtoNPropertyPage')
			$handler = "Property$property".'Page';
		elseif (!$handler) {  //no pntHandler param, use default:
			if ($property == 'pntList')
				$handler = 'IndexPage';
			elseif ($id !== null)
				$handler = 'EditDetailsPage';
			else
				$handler = 'IndexPage';
			$specifiedHandler = $handler;
		}

		$attempted = array();
		$info = null;
		$handlerClass = $type
			? $this->tryUseHandlerClass("$type$handler", $attempted)
			: null;
		if (!$handlerClass && $property) {
			//there is no specific handler for this type and property, try type-handler
			$handler = $specifiedHandler;
			$handlerClass = $this->tryUseHandlerClass("$type$handler", $attempted);
		}
		if (!$handlerClass) 
			//there is no specific handler for this type, try generic handler from same dir
			$handlerClass = $this->controller->tryUseGenericHandlerClass($this, $requestData, $handler, $attempted);

		if (!$handlerClass) {
			$name = $this->getName();
			$errorMessage = "$name - handler not found: $handler, tried: <BR>\n";
			$errorMessage .= $this->getHandlersTriedString($attempted);
			throw new PntValidationException($errorMessage);
		}
//  print "<BR>Handler: $handlerClass $included";
		if (!is_subclassOr($handlerClass, 'PntRequestHandler'))
			throw new PntValidationException($handlerClass. ' does not inherit from PntRequestHandler');
		$result = new $handlerClass($this, $requestData);

		if ($this->getDebugMode() == 'verbose') {
			$info = 'Handlers tried<BR>(one of last two succeeded): ';
			$info .= $this->getHandlersTriedString($attempted);
			$result->setInformation($info);
		}
		return $result;
	}

	/** If the class does not exist, try to include a PntRequestHanlder. 
	 * For each class name inclusion is first tried from $this->getDir(), 
	 * 	    If the class is not found, a class from the classes root folder is tried
	 * @param string $handlerClass name of the class 
	 * @param array $attempted to add info to about the class names and folders that where tried
	 * @return boolean wheather a class file was included. Also true if the class already existed.
	 */
	function tryUseHandlerClass($handlerClass, &$attempted) {
		$attempted = array_merge($attempted, $this->getTryUseClassTryParams($handlerClass, $this->getDir()));
		$included = $this->tryUseClass($handlerClass, $this->getDir());
		return $included ? $handlerClass : null;
	}
	
	/** @return string with html describing the attempted class names and folders 
	 * @param array $attempted that was filled by ::tryUseHandlerClass
	 */
	function getHandlersTriedString($attempted) {
		$result = "<TABLE>\n";
		while(list($key, $params) = each($attempted))
			$result .= "<TR><TD class=pntNormal>$params[1]$params[0]</TD></TR>\n";
		$result .= "</TABLE>\n";
		return $result;
	}

	/** @return String representation for debugging purposes */
	function __toString() {
		//combine class name and label
		$label = $this->getLabel();
		return get_class($this)."($label)";
    }

	/** @depricated */
	function toString() {
		return (string) $this;
	}
	
	/** @return string the label from which this can be recognized by the user */
	function getLabel() {
		return $this->getTypeLabel()
			." - "
			.$this->getName();
	}

	/** Used for skin inclusion. Also used in the label. If you do not like the label,
	 * override ::getLabel, not ::getName
	 * @return string
	 */ 
	function getName() {
		return get_class($this);
	}

	/** @return the pntHandler by which this can be re-included in a new request 
	*/
	function getThisPntHandlerName() {
		$pntHandler = $this->getReqParam('pntHandler');
		if ($pntHandler) return $this->checkAlphaNumeric($pntHandler);

		return $this->getName().'Page';
	}

	/** @return HTML String information for the end user */
	function getInformation() {
		if ($this->information !== null)
			return $this->information;

		if (isSet($this->requestData['pntInfo'])) {
			return $this->getReqParam('pntInfo', true);
		}
		return $this->getEventualItemNotFoundMessage();
	}
	
	/** @return string html message in case the requested object was not found */
	function getEventualItemNotFoundMessage() {
		$obj = $this->getRequestedObject();
		$id = $this->getReqParam('id', true);
		if ($id && !is_array($obj) && !$obj) //no message for empty array
			return '<B>'.get_class($this).' Error:</B><BR>Item not found: id='. $id;
	}

	/** Overrides the information from the pntIfo request parameter  
	 * @param String HTML information for the end user */
	function setInformation ($value) {
		$this->information = $value;
	}

	/** @return string the label of the type of the requested object 
	 * Default implementation: the label from the ClassDescriptor of the type of the requested object 
	 * Which defaults to the result of the static method getClassLabel on the type of the requested object
	 * Which defaults to the type of the requested object (pntType)
	 * */
	function getTypeLabel() {
		try {
			$type = $this->getType();
		} catch (PntValidationException $e) {
			$type = 'Invalid pntType';
		}

		if (is_subclassOr($type, 'PntObject')) {
			$clsDes = PntClassDescriptor::getInstance($type);
			return $clsDes->getLabel();
		} 

		return $type;
	}

	/** @return string the type of the requested object 
	 * Default implementation: value of pntType from the request */
	function getType() {
		if (!isSet($this->requestData['pntType'])) return null;
		$type = $this->requestData['pntType'];
		return $this->checkAlphaNumeric($type);
	}

	/** ALLWAYS call this method before including a class
    * by name from a request parameter, unless you use
	* this-> useClass or this-> tryUseClass
	* @param String $value the value to check
	* @return the value that was passed or trigger an error if not alphanumeric
	* 	_ and - are allowed because of formKey paths  
	*/
	function checkAlphaNumeric($value) {
		if ($value && preg_match("'[^A-Za-z0-9_\-]'", $value))
			throw new PntValidationException("Non alphanumerical characters in propertyname, type, handler or skinName: $value", E_USER_ERROR);
		else
			return $value;
	}

	
	/** @return array of arrays with class names and folder paths used 
	 * for defaulted class loading by PntRequestHandler::TryUseClass
	 * delegates to $this->whole so that it can be overridden on Site */
	function getTryUseClassTryParams($className, $dir) {
		return $this->whole->getTryUseClassTryParams($className, $dir);
	}

	/** @return string path to the folder from which skins are included if 
	 * no specific skin is found in the application folder. The path may be relative
	 * to the folder phpPeanuts was installed in, and in which the application folders 
	 * and the classes folder reside. 
	 * delegates to $this->whole so that it can be overridden on Site */
	function getIncludesDir() {
		return $this->whole->getIncludesDir();
	}

	/** If the class does not exist, try to include it 
	 * @param string $className name of the class
	 * @param $dir application folder path
	 * For each class name inclusion is first tried from $this->getDir(), 
	 * 	    If the class is not found, a class from the classes root folder is tried.
	 *      However, this behavior may be overridden on Site::getTryUseClassTryParams
	 * @return boolean wheather a class file was included. Also true if the class already existed.
	 */
	function tryUseClass($className, $dir) {
		$this->checkAlphaNumeric($className);
		pntCheckIncludePath($dir);
		$params = $this->getTryUseClassTryParams($className, $dir);
		$included = Gen::tryIncludeClass($params[0][0], $params[0][1]); //tryUseClass
//print "<BR>tryIncludeClass(".$params[0][0].", ". $params[0][1].") $included";
		if (!$included) {
			$included = Gen::tryIncludeClass($params[1][0], $params[1][1]); //tryUseClass
//print "<BR>tryIncludeClass(".$params[1][0].", ". $params[1][1].") $included";
		}
		return $included;
	}

	/** If the class does not exist, try to include it 
	 * If it could not be included, trigger a warning.
	 * @param string $className name of the class
	 * @param $dir application folder path
	 * For each class name inclusion is first tried from $this->getDir(), 
	 * 	    If the class is not found, a class from the classes root folder is tried.
	 *      However, this behavior may be overridden on Site::getTryUseClassTryParams
	 * @return boolean wheather a class file was included. Also true if the class already existed.
	 * @throw PntValidationException if no class included
	 */
	function useClass($className, $dir) {
		if (!$this->tryUseClass($className, $dir)) {
			$params = $this->getTryUseClassTryParams($className, $dir);
			throw new PntValidationException(
				"$this - useClass: class not found: "
					.$params[0][1]. $params[0][0].", "
					. $params[1][1]. $params[1][0]);
			return false;
		}
		return true;
	}

	/** @return PntClassDescriptor the descriptor of the class of the requested object,
	 * 	or null if none.
	 * Includes the class from the domain folder.
	 */
	function getTypeClassDescriptor() {
		$type = $this->getType();
		$usable = $this->useClass($type, $this->getDomainDir());
		
		return PntClassDescriptor::getInstance($type);
	}		

	/** @return mixed the (array of) PntObject that is/are shown and/or modified 
	 * by this requesthandler. 
	 * Default implementation is, if it is not cached, to try to load it from the database using the value 
	 * of the id parameter from the request. Returns a new object if none.
	 * @throws PntError */
	function getRequestedObject() {
		if (isSet($this->object))
			return $this->object;

		$type = $this->getType();
		if (!class_exists($type)) return null;

		$cnv = $this->getConverter();
		$id = $cnv->fromRequestData( $this->getRequestParam('id') );
		$clsDes = PntClassDescriptor::getInstance($type);
		$prop = $clsDes->getPropertyDescriptor('id');
		$cnv->initFromProp($prop);
		$converted = $cnv->fromLabel($id);
		if ($cnv->error) 
			throw new PntValidationException('id conversion: '. $cnv->error);

		if (empty($converted))
			return $this->object = new $type();
			
		return $this->object = $clsDes->getPeanutWithId($converted);
	}

	/** Sets that is/are shown and/or modified by this requesthandler.
	 * @param mixed $value the (array of) PntObject 
	*/
	function setRequestedObject($value) {
		$this->object = $value;
	}

	/** Initializes and gets the document objects used by PntObjectSaveAction,
	 * PntObjectDetailsPage and PntObjectDetailsPart to convert, validate and communicate 
	 * request data, meta data (type, path) and validation errors
	 * @return Array of PntXmlNavValue 
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
			if ($readOnly || $nav->isSettedReadOnly() || $sm->checkEditProperty($obj, $prop)) {
				$inst = new PntXmlNavValue(null, $this->getType(), $path);
				if ($readOnly) $inst->setReadOnly(true);
			} else {
				if ($nav->getFirstProp()->isMultiValue()) {
					Gen::includeClass('PntFormMtoNRelValue', 'pnt/web/dom');
					$inst = new PntFormMtoNRelValue(null, $this->getType(), $path);
				} else {
					$inst = new PntFormNavValue(null, $this->getType(), $path);
				}
			}
			$inst->setConverter($this->getConverter());
			if (is_string($label)) $inst->setPathLabel($label);
			if (isSet($this->requestData[$inst->getFormKey()]))
				$inst->setRequestString($this->getRequestParam($inst->getFormKey()));
			$this->formTexts[$inst->getFormKey()] = $inst;
		} 

		return $this->formTexts;
	}

	/** Returns array with paths for getFormTexts.
	* @return Array of String
	*/
	function getFormTextPaths() {
		return null;
	}

	/** Sets array with paths for getFormTexts.
	* @param Array of String $value 
	*/
	function setFormTexts($value) {
		$this->formTexts = $value;
	}

	/** @eturn PntMarkedItemsCollector helper object for collecting items that where marked by the user */
	function getMarkedItemsCollector() {
		Gen::includeClass('PntMarkedItemsCollector', 'pnt/web/helpers');
		$result = new PntMarkedItemsCollector($this);
		return $result;
	}
}
?>