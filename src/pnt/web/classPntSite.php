<?php
/* Copyright (c) MetaClass, 2003-2017

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPage', 'pnt/web/pages');
 
/** Objects of this class are the single entrypoint for handling http requests.
* Site connects to the database as specified in scriptMakeSettings.php and
* sets the ErrorHandler, the debugMode, specifies application folder and domain folder, 
* supplies StringConverters,  baseUrl and takes care of sessions. 
* 
* This abstract superclass provides behavior for the concrete
* subclass StringConverter in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @package pnt/web
*/
class PntSite extends PntPage {
// subclassed from Pnt Page instead of PntRequestHandler becuase of depricated support

	/** string @see ::getBaseUrl - may be set directly in the field from scriptMakeSettings.php  */
	public $baseUrl;
	/** string @see ::getDebugMode -  may be set directly in the field */
	public $debugMode = ''; //options: '', 'short', 'verbose'
	/** boolean @see isFunkyUrls -  may be set directly in the field */
	public $funkyUrls = false;
	public $funkyAlias = 'index.php?';
	public $absoluteUrls = false;
	/** string default value for PntPage::printHeaderXframeOptions */
	public $xFrameOptions = 'DENY';
	public $httpRequestThrowsExp = true; 
	/** var string $tokenSalt to be set in scriptMakeSettings */
	//public $tokenSalt; 

	/** string @see ::setDir */
	public $dir; 
	/** string @see ::setDomainDir */
	public $domainDir; //may be set from the applications index.php
	
	/** @see constructor and PntPage::getRequestDuration */
	public $requestStartTime;
	/** @private @see ::getGlobalFilters */
	public $filters;
	/** @private @see ::getConverter  */
	public $converter;
	/** @private @see ::startSession  */
	public $sessionStarted = false;
	
	//depricated support
	public $isSiteRunning=true;
	public $name="site"; 
	public $os="linux";
	public $dbUser;
	public $dbPwd;
	public $dbAddress;
	public $dbPort;
	public $dbName;

	
	 
	function __construct($dir="beheer") {
		$timeArr = explode(" ",microtime());
		$this->requestStartTime = $timeArr[1].substr($timeArr[0],1,3);

		//replaces parent Constructor call
		$this->controller = $this;

		$this->setDir($dir);
		$this->setErrorHandler();
		$this->setDomainDir($dir);

		$this->importLibraries();
		$this->loadSettings();
		$this->initHttpRequest();
		$this->createObjects();
	}
	
	function isWindows() {
		if (substr(PHP_OS,0,3) == 'WIN') return true;
		if ($this->os=="windows") {
			return true;
		} else {
			return false;
		}
	}
	
	function setErrorHandler() {
		Gen::includeClass('ErrorHandler');
		$this->errorHandler = new ErrorHandler();
		$this->errorHandler->startHandling();	
	}
	
	/** 
	* @result ErrorHandler the current one that is handling errors and exceptions,
	* created by ::setErrorHandler
	*/
	function getErrorHandler() {
		return $this->errorHandler;
	}
	
	/** Configures some fields on this and maybe on some other objects, like
	 * on the DataBaseConnection. Default implentation is to require 
	 * ../classes/scriptMakeSettings.php
	 */
	function loadSettings() {
		require('../classes/scriptMakeSettings.php');
	}
	
	/** Includes some general libraries and classes. 
	 * Most class loading is done Dynamically on a need-to-include basis
	 * by the RequestHandlers and the files and classes they include.
	 * Default implementation is to include DatabaseConnection, QueryHandler 
	 * May be overridden on Site  
	 */
	function importLibraries() {
		// require_once("../classes/classGen.php"); already included by classSite.php
		$this->useClass("DatabaseConnection", $this->getDir());
		$this->useClass("QueryHandler", $this->getDir());
		$this->useClass("ValueValidator", $this->getDir()); //required by StringConverter
	}
	
	/** inlcudes, instantiates and confiures  some general purpose objects.
	 * Most class loading and instattiation is done Dynamically on a need-to-use basis
	 * by the RequestHandlers and the files and classes they include.
	 * Default implementation does include ValueValidator and StringConverter
	 * and instiantiates StringConverter and the context scout (see ::initScout)
	 */ 
	function createObjects() {
		$this->initScout();
	}
	
	/** Instantiates and initializes the StringConverter 
	 * and if its dateTimezone is set, sets its through date_default_timezone_set.
	 */
	function initConverter() {
		$this->useClass("StringConverter", $this->getDir());
		$this->converter = new StringConverter();
		if ($this->converter->dateTimezone && function_exists('date_default_timezone_set'))
			date_default_timezone_set($this->converter->dateTimezone);
	}

	function initHttpRequest() {
		$this->initConverter();
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$this->useClass('HttpRequest', $this->getDir());
			$this->request = new HttpRequest(
				$this->getErrorHandler(),
				$this->converter->getLabelCharset(),
				$this->httpRequestThrowsExp
			);
			$alias = $this->isFunkyUrls() ? $this->funkyAlias : null;
			$this->request->initHttpData($alias);
			$this->requestData = $this->request->getRequestData();
		}
	}

	/** The application folder, where application specific skins are included from, 
	 * also whithin the classes folder the application classes folder where user interface 
	 * components are includen from. 
	 * Must be set through the constructor on Site from the index.php script.
	 * @param string relative path  
	 */
	function setDir($value){
		$this->dir = ($value && substr($value, -1) != '/')
			? $value.'/'
			: $value;
	}
	
	/** The application folder, where application specific skins are included from, 
	 * also whithin the classes folder where user interface components are includen from.
	 * Is set to Site from the index.php script.
	 * @return string relative path with trailing slash 
	 */
	function getDir() {
		return $this->dir;
	}

	/** Folder whithin the classes folder where domain classes are included from.
	 * If not set to Site from the index.php script, this defaults to getDir without trailing slash
	 * @param string relatice path without trailing slash 
	 */
	function setDomainDir($value) {
		$this->domainDir = ($value && substr($value, -1) != '/')
			? $value.'/'
			: $value;
	}
	
	/** folder whithin the classes folder where domain classes are included from.
	* If not set to Site from the index.php script, this defaults to getDir without trailing slash
	* @return string relatice path without trailing slash 
	* To be overridden if PntMarkedItemsCollector can not guess some types
	* because of polymorpism 
	*/
	function getDomainDir($type=null) {
		return $this->domainDir;
	}

	/** @depricated
	 * Derives a user interface folder (application folder) from domain folder.  
	 *
	 * @param string $type pntType 
	 * @param string $domainDir where the type class is
	 * @param string $pntHandler of the user interface 
	 * @return string User interface directory name with / on the end
	 */
	function getUiDir($type, $domainDir, $pntHandler) {
		return $this->getAppName($domainDir, $type, $pntHandler)
			.'/';
	}

	/** @return string the application name. This is the application dir without the trailing slash 
	 * To be overridden if domain folders are not (all) equal to appNames folders
	 * @param string $domainDir where the type class is
	 * @param string $type pntType 
	 * @param string $pntHandler of the user interface 
	 */
	function getAppName($domainDir='', $type='', $pntHandler='') {
		return (!$domainDir || $this->getDomainDir() == $domainDir)
			? subStr($this->getDir(), 0, -1)
			: $domainDir;
	}
	
	/** The baseUrl is the the url to the folder that holds all applications folders
	* and the classes folder etc. Normally this is the parent folder of the folder where the
	* current script is running. This method initializes the baseUrl field if it
	* has not been set using @see Gen::getBaseUrl() in ../classes/baseUrlFunc tions.php
	* PRECONDITION: $this->request is initialized
	* If the assumptions of Gen::getBaseUrl are incorrect this method must either be overridden or
	* $this->baseUrl must be set, for example from classes/scriptMakeSettings.php
	* @return String The baseUrl
	*/
	function getBaseUrl() {
		if (!isSet($this->baseUrl) && isset($this->request)) {
			$cnv = $this->getConverter();
			$beforeFunky = $this->isFunkyUrls() 
				? '/'. $cnv->urlEncode($this->getAppName()). '/'. $this->funkyAlias
				: null;
			$this->baseUrl = Gen::getBaseUrl($this->request->serverVars, $beforeFunky);
		}
		return $this->baseUrl;
	}
	
	/** @return string with the relative path to the folder where the browser can retieve images from by HTTP. 
	 * 		path components must be rawurlencoded and in the charset used by the webserver to retrieve www files */
	function getImagesDir() {
		return '../images/';
	}
	
	/** returns funkyUrls setting
	* funkyUrls are search engine and user friendly urls.
	* index.php is replaced by $this->funkyAlias, equals and ampersands become forward slashes
	* if $this->funkyAlias does end with a / instead of a ? these are 
	* rewritten by the url rewriting feature of the webserver.
	* RequestData then is parsed explicitly by getFunkyRequestData. 
	* With Funky Urls all urls (including image src, stylesheets and scripts) must be absolute.
	* You must make these urls yourself, the framework will still output normal urls.
	* (Meant to be used in public sites, where Pages usually are only inheriting some very generic methods from PntPage and PntRequestHandler)
	*/
	function isFunkyUrls() {
		return $this->funkyUrls;	
	}
	
	function setFunkyAlias($value) {
		$this->funkyAlias = $value;
		$this->absoluteUrls = $value[strLen($value)-1] != '?';
	} 
	
	/** @return PntStringConverter properly initialized for the language currenly used 
	 * Default is a clone of the one set by initConverter,
	 * which is an instance of StringConverter from the classes folder.
	 */
	function getConverter() {
		// answer a copy so field values won't get mixed up
		return clone $this->converter;
	}

	/** @return string one from '', 'short', 'verbose' 
 	* if set to equal false, no debug comments are included by printPart
	* if set to 'verbose' printPart includes comments that show all its options.
	* otherwise printPart inlcudes short comments at start and end of each part
	*/
	function getDebugMode() {
		return $this->debugMode;
	}

	/** Handle a HTTP request. Called from index.php in the application folder 
	 * after instantiation and setting dir and eventually domainDir.
	 * 
	 */
	function handleRequest() {
		$this->startSession();
		$sm = $this->controller->getSecurityManager(); //in php4 controller is a copy of $this :-(
		try {
			if (!$sm->isAuthenticated($this->request, $this->scout))
				return $this->forwardToLoginPage($this->requestData);  //error page if no loginPage
				
			$this->forwardRequest($this->requestData);
		} catch (PntSecurityException $e) {
			if (!$this->errorHandler->isDevelopment()) {
				$auth = $sm->getAuthenticator();
				$auth->logOut($e);
			}
			throw $e; //rethrow, results in error page unless in development
		}
	}
	
	/** Include and instantiate a PntRequestHandler and let it handle the request.
	 * For compatibility with older versions op phpPeanuts, the default database connection
	 * is initialized if that has not already been done.
	 * @param array $requestData like $_REQUEST used to decide which PntRequestHandler 
	 * 	    to include and instantiate, and passed to the PntRequestHandler as the requst data.
	 * @param string $information to be passed to the PntRequestHandler, 
	 *      usually overrides the pntInfo parameter if not null (this behavior depends on the actual PntRequestHandler)  
	 */
	function forwardRequest($requestData, $information=null) {
		if (DatabaseConnection::defaultConnection() === null)
			$this->initDatabaseConnection();
		$handler = $this->getRequestHandler($requestData);
		if ($information)
			$handler->setInformation($information);
		$this->forwardToHandler($handler);
	}
	
	/** Let the requestHandler handle the HTTP request.
	 * the request handler must already be properly initialized,
	 * @param PntRequestHandler $requestHandler 
	 */  
	function forwardToHandler($requestHandler) {
		if ($this->isRequestAjax())
			$requestHandler->ajaxHandleRequest();
		else
			$requestHandler->handleRequest();
	}

	/** try to include a generic handler class if not yet included.
	 * Generic implementation, includes Object<pntHandler> for pages and <pntHandler> for parts.
	 * To be overriden for the inclusion of more specific (but not fully specific) handlers if domain objects are polymorpic
	 * @param PntRequestHandler $forHandler calling this method
	 * @param array $requestData like $_REQUEST
	 * @param string $handler pntHandler string (may be empty)
	 * @param array $attempted reference to array with results of getTryUseClassTryParams (for debugging)
	 */
	function tryUseGenericHandlerClass($forHandler, $requestData, $handler, &$attempted) {
		$type = isSet($requestData["pntType"]) ? $requestData["pntType"] : null;
		$handlerClass = subStr($handler, -4) == 'Part' || !$type
			? $handler : "Object$handler";
		return $forHandler->tryUseHandlerClass($handlerClass, $attempted);
	}	
	
	/** @depricated. Properly intiialize a StringConverter and use its ::fromRequestData
	*/
	function sanitize($requestString) {
		$this->converter->sanitizeString($requestString);
	}

	/** Start a session if not already started */
	function startSession() {
		//must be done before any output is written. 
		//do not register objects in $_SESSION, or serialize them first
		if ($this->sessionStarted) return;
		
		session_start();
		$this->sessionStarted = true;
	}
	
	/** @return array of PntSqlFilter to be used in the entire application where applicable
	 * These filers are usually stored in te session.
	 * SearchPages will try to use the global filters, propertypages and parts will not,
	 * they may have to be overridden or the domain model has to be made global filter sensitive. 
	 * Make sure to include eventual additional filter classes before calling this method
	 */
	function getGlobalFilters() {
		if ($this->filters === null) {
			Gen::includeClass('PntSqlFilter', 'pnt/db/query');

			$this->filters = array();
			if (isSet($_SESSION[$this->getBaseUrl()]['pntGlobalFilters'])) {
				$filterArrays = $_SESSION[$this->getBaseUrl()]['pntGlobalFilters'];
				while (list($key) = each($filterArrays)) {
					$this->filters[$key] = PntSqlFilter::instanceFromPersistArray($filterArrays[$key]);
				}
			}
		}
		return $this->filters;		
	}
	
	/** @param array of PntSqlFilter to be used in the entire application where applicable
	 * Default implementation is to store these filters in te session.
	 * SearchPages will try to use the global filters, propertypages and parts will not,
	 * they may have to be overridden or the domain model has to be made global filter sensitive. 
	 * Make sure to include eventual additional filter classes before calling this method
	 */
	function setGlobalFilters(&$filters) {
		$this->filters = $filters;

		$filterArrays = array();
		reset($filters);
		while (list($key) = each($filters)) {
			$filterArrays[$key] = $filters[$key]->getPersistArray();
		}
		$_SESSION[$this->getBaseUrl()]['pntGlobalFilters'] = $filterArrays;
	}

	/** Returns a clone of the first global filter that applies to the type, with the itemType set
	 * @param string $type the type name
	 * @return PntSqlFilter|NULL */ 
	function getGlobalFilterFor($type, $persistent=false) {
		forEach($this->getGlobalFilters() as $filter)
			if ($filter->appliesTo($type, $persistent)) {
				$copy = clone $filter;
				$copy->set('itemType', $type);
				return $copy;
			}
		return null;
	}
	
	/** Returns the filter to apply to the options of the type
	 * @param string $type the type name
	 * $param $propName the name of the property whose options to filter
	 * 		NB, the property is not situated on $type but having $type as its type!
	 * @return PntSqlFilter|NULL */ 
	function getGlobalOptionsFilter($type, $propName) {
		return $this->getGlobalFilterFor($type);
	}
	

	/** @return array of arrays with class names and folder paths used 
	 * for defaulted class loading by PntRequestHandler::TryUseClass
	 * Override this to modify default class loading */
	function getTryUseClassTryParams($className, $dir) {
		$params = array();
		$params[] = array($className, $dir);
		$params[] = array($className, '');
		return $params;
	}

	/** @return string path to the folder from which skins are included if 
	 * no specific skin is found in the application folder. The path may be relative
	 * to the folder phpPeanuts was installed in, and in which the application folders 
	 * and the classes folder reside. 
	 * Override this to modify default skin inclusion */
	function getIncludesDir() {
		return 'includes';
	}
	
	/** Context Scouting
	 * Initializes the context scout */
	function initScout() {
		Gen::includeClass('PntSessionBasedScout', 'pnt/web/helpers');
		$this->scout = new PntSessionBasedScout($this); //limit can be set from here, @see SessionBasedScout()
	}
	
	/** Context Scouting
	* @return PntSessionBasedScout that keeps track of the contexts
	*/
	function getScout() {
		return $this->scout;
	}

	/** Context Scouting
	* @param int $footprintId the id of the footprint to get the context href from
	* @return String url to context, or null if none
	*/
	function getContextHref($footprintId) {
		$params = array();		
		//for the time being, let pntContext parameter from the request precede
		//if it references a propertypage and a folder can be guessed.
		$context = $this->getReqParam('pntContext');
		if ($context) {
			$arr = explode('*', $context);
			$type = $arr[0];
	
			$params['pntType'] = $type;
			if (isSet($arr[1])) 
				$params['id'] = $arr[1];
			$propName = isSet($arr[2]) ? $arr[2] : '';
			if ($propName) {
				$params['pntHandler'] = 'PropertyPage';
				$params['pntProperty'] = $propName;
			}
			$domainDir = $this->guessContextTypeFolder($type); //data comes from propertyDescriptor
			$appName = $this->getAppName($domainDir, $type, isSet($params['pntHandler']) ? $params['pntHandler'] : '');
			if ($propName && $appName) 
				return $this->buildUrl($params, $appName);
		}

		$scout = $this->getScout();
		return $scout->getContextHref($footprintId);
	}

	/** Guess the domain folder for the type
	 * @param string $type like a class name
	 */
	function guessContextTypeFolder($type) {
		$clsDes = PntClassDescriptor::getInstance($this->getType());
		$props = $clsDes->getPropertyDescriptors();
		forEach(array_keys($props) as $key) {
			if ($props[$key]->isDerived() && $props[$key]->getType() == $type) 
				return $props[$key]->getClassDir();
		}
		return null;
	}

	/** @return The object that takes care of authentication and authorization
	*/
	function getSecurityManager() {
		if (!isSet($this->securityManager)) {
			$this->useClass('SecurityManager', $this->getDir());
			$this->securityManager = new SecurityManager($this->getBaseUrl(), $this->tokenSalt);
		}
		return $this->securityManager;
	}

	/** Forward to the login page. 
	* @param array $request request data to pass to the login page
	* @param string $errorMessage HTML, only to be passed if login failed.
	*/
	function forwardToLoginPage($requestData, $errorMessage=null) {
		$this->useClass('LoginPage', $this->getDir());
		$page = new LoginPage($this, $requestData);
		$page->setInformation($errorMessage);
		$page->handleRequest();
	}

//  not possible - declaration itself triggers E_STRICT
//	/** @depricated */
//	function checkAccess($handler, $checkType=null, $prop=null) {
//		$this->checkAccessHandler($handler, $checkType, $prop);
//	}

	/** Check access to a RequestHandler with the SecrurityManager. 
	* @param PntRequestHandler $handler The RequestHandler being accessed
	* @parem String $type the type of access, corrsponds to the SecurityManager 
	* 	function to be called
	* @param $prop PntPropertyDescriptor $prop Teh property being accessed (if any)
	* @return String Error message, or null if access was granted
	*/
	function checkAccessHandler($handler, $checkType=null, $prop=null) {
		$sm = $this->getSecurityManager();

		$err = $sm->checkAccessApp($this->getDir());
		if ($err) return $err;

		$err = $sm->checkAccessRef($handler, $this->request, $this->scout);
		if ($err) return $err;
		
		if (!$checkType) return;
		$checkFunc = "check$checkType";
		if ($prop)
			$meta = $prop;
		else 
			$meta = $handler->getTypeClassDescriptor();
		$obj = $handler->getRequestedObject();
		return $sm->$checkFunc($obj, $meta);
	}

	/** Forward to Access Denied ErrorPage.
	* Die afterwards
	*/
	function accessDenied($handler, $errorMessage) {
		$newReq = array('status' => 401
			, 'pntHandler' => 'ErrorPage'
			, 'errorMessage' => $errorMessage);
		$this->forwardRequest($newReq);
		die();
	}

	/** Returns the maximum diffence between the pntActionTicket from the request 
	 * and from the session.
	 * @see PntAction::checkActionTicket
	 * @return int default is to return 100
	 */
	function getMaxTicketDiff() {
		return 20;
	}
	
	/** @return string the error message to show if the action ticket is invalid. 
	 * @see PntAction::checkActionTicket
	 */
	function getInvalidActionTicketMessage() {
		return 'Form corrupt or outdated, please check all fields and try again';
	}

	/** @depricated Only used if no database connection has been made before ::forwardRequest.
	* does not support PntPdoDao */
	function initDatabaseConnection() {
		$dbc = new DatabaseConnection();
		$dbc->setUserName($this->dbUser);
		$dbc->setPassword($this->dbPwd);
		$dbc->setHost($this->dbAddress);
		$dbc->setPort($this->dbPort);
		$dbc->setDatabaseName($this->dbName);
		$dbc->makeConnection();		
	}
	
	function buildUrl($params, $appName=null) {
		return ($this->absoluteUrls ? $this->getBaseUrl() : '../')
			. $this->converter->urlEncode($appName ? $appName : $this->getAppName())
			. '/'. $this->funkyAlias
			. $this->queryStringFrom($params); //NB: not adapted to funky urls!!
	}
} 
?>
