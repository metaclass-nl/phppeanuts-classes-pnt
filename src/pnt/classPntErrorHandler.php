<?php
/* Copyright (c) MetaClass, 2003-2013

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

// ValueValidator included by PntSite
Gen::includeClass('PntErrorException', 'pnt'); //also includes PntError

/**  Objects of this class log and handle errors using php's set_error_handler function
* @see http://www.phppeanuts.org/site/index_php/Pagina/32
* 
* This abstract superclass provides behavior for the concrete
* subclass StringConverter in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @package pnt
*/
class PntErrorHandler {

	public $logFilePath;
	public $oldHandler = null;
	public $reportingLevel;
	public $errorLevelMap;
	public $hasHandledError = false;
	public $stringConverter;
	public $developmentHost = 'development';
	public $emailAddress;
//	public $debugInfoBacktrace = true; defaults to $this->isDevelopment() if not set
	public $logBacktrace = false;  

	/** @param String $logFilePath if null the error info is written to the php's system logger,
	* @see http://www.php.net/manual/en/function.error-log.php
	* You may have to call ini_set('log_errors', '1');
	* if specified the error information is written to
	* the specified file. Php must have write access to that file, wich is considered
	* a security risk because malicious code that came in through an exploit may
	* use this file to get non-php code running on the webservers user account.
	* The preferred method is to pass null
	*/
	function __construct($logFilePath=null, $emailAddress=null) {
		$this->logFilePath = $logFilePath;
		$this->emailAddress = $emailAddress;
		$this->reportingLevel = $this->getDefaultReportingLevel();
		$this->initErrorLevelMap();
	}
	
	function initErrorLevelMap() {
		$this->errorLevelMap = array(
			E_ERROR => 'E_ERROR' 
			,E_WARNING => 'E_WARNING' 
			,E_PARSE => 'E_PARSE' 
			,E_NOTICE => 'E_NOTICE' 
			,E_CORE_ERROR => 'E_CORE_ERROR' 
			,E_CORE_WARNING => 'E_CORE_WARNING' 
			,E_COMPILE_ERROR => 'E_COMPILE_ERROR' 
			,E_COMPILE_WARNING => 'E_COMPILE_WARNING' 
			,E_USER_ERROR => 'E_USER_ERROR' 
			,E_USER_WARNING => 'E_USER_WARNING' 
			,E_USER_NOTICE => 'E_USER_NOTICE' 
			,E_STRICT => 'E_STRICT'
			,E_ALL => 'E_ALL'
			);
		if(defined('E_RECOVERABLE_ERROR')) $this->errorLevelMap[E_RECOVERABLE_ERROR] = 'E_RECOVERABLE_ERROR';
		if(defined('E_DEPRECATED')) $this->errorLevelMap[E_DEPRECATED] = 'E_DEPRECATED';
		if(defined('E_USER_DEPRECATED')) $this->errorLevelMap[E_USER_DEPRECATED] = 'E_USER_DEPRECATED';
	}
	
	function mapErrorLevel($level) {
		$levelName = $this->errorLevelMap[$level];
		if ($levelName)
			return $levelName;
		else
			return $level;
	}
	
	function getDefaultReportingLevel() {
		$result = E_ALL ^ E_NOTICE ^ E_USER_NOTICE;
//		if (defined('E_DEPRECATED')) 
//			$result = $result ^ E_DEPRECATED ^ E_USER_DEPRECATED;
		return $result;
	}
		
	function getLoggingLevel() { 
		return isSet($this->loggingLevel) ? $this->loggingLevel : $this->reportingLevel;
	}
	
	/** @return int The level of errors to be handled by throwing a PntErrorException */ 
	function getErrorExceptionLevel() {
		if (isSet($this->errorExceptionLevel))
			return $this->errorExceptionLevel;
		return $this->getDefaultErrorExceptionLevel();
	}
	
	/** The level of errors to be handled by throwing a PntErrorException
	 * Because exception handling set with set_exception_handler allways dies,
	 * the default is to include only errors are meant to die when handled. 
	 * @return int
	 */ 
	function getDefaultErrorExceptionLevel() {
		return E_ERROR | E_USER_ERROR | 
			(defined('E_RECOVERABLE_ERROR') ? E_RECOVERABLE_ERROR : 0);
	}

	function getStringConverter() {
		if ($this->stringConverter) return $this->stringConverter;
	
		if (!class_exists('StringConverter')) //other implementation may already have been included
			Gen::includeClass('StringConverter');		
		$this->stringConverter = new StringConverter();
		return $this->stringConverter;
	}
	
	function startHandling() {
		$GLOBALS['pntErrorHandler'] = $this;

		$this->oldHandler = set_error_handler(array($this, 'handleError'), $this->reportingLevel | $this->getLoggingLevel() );
		//on PHP 5.3.0 unhandled errors are still being reported
		error_reporting($this->reportingLevel);
		
		PntError::storeDebugTrace($this->isDevelopment() || $this->logBacktrace);
		$this->oldExceptionHandler = set_exception_handler(array($this, 'handleException'));
	}
	
	function handleError($level, $message, $filePath, $lineNumber) {
		$timeStamp = $this->getTimeStamp();
		$traces = array(debug_backtrace());
		if ($level & $this->getLoggingLevel()) {
			$this->logError($level, $message, $filePath, $lineNumber, $timeStamp, $traces);
		}
		if ($level & $this->getErrorExceptionLevel())
			throw new PntErrorException($message, 0, $level, $filePath, $lineNumber);
		if ($level & $this->reportingLevel) {
			if ($this->isDevelopment())
				$this->printDebugInfo($level, $message, $filePath, $lineNumber, $timeStamp, $traces);
			elseif ($this->errorInErrorPage())
				print "Error in ErrorPage";
			elseif (!$this->hasHandledError)
				$this->informUser($level, $message, $filePath, $lineNumber, $timeStamp);
			$this->hasHandledError = true;
		}
		$this->dieIfFatal($level, $message, $filePath, $lineNumber);
		return true;
	}
	
	function handleException($e) {
		$timeStamp = $this->getTimeStamp();
		$traces = $this->getTraces($e);
		if ($this->shouldLogException($e))
			$this->logError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $timeStamp, $traces, get_class($e));
		if (!$this->shouldReportException($e)) return true;

		if ($this->isDevelopment() )
			$this->printDebugInfo((Gen::is_a($e, 'PntErrorException') ? $e->getSeverity() : $e->getCode())
				, $e->getMessage(), $e->getFile(), $e->getLine(), $timeStamp, $traces, get_class($e));
		elseif ($this->errorInErrorPage())
			print "Error in ErrorPage";
		elseif (!$this->hasHandledError)
			$this->informUser($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $timeStamp, get_class($e));
		$this->hasHandledError = true;

		return true;
	}
	
	function getTraces($e) {
		$result = array();
		while ($e) {
			if (Gen::is_a($e, 'PntError')) {
				$trace = $e->getDebugTrace();
				if (!PntError::storeDebugTrace())
					$this->addFakeLineToTrace($e, $trace);
				$e = $e->getCause();
			} else {
				$trace = $e->getTrace();
				$this->addFakeLineToTrace($e, $trace);
				$e = method_exists($e, 'getPrevious')
					? $e->getPrevious() : null;
			}
			$result[] = $trace;
		}
		return $result;
	}
	
	function addFakeLineToTrace($exception, &$trace) {
		//fake traceline for Exception contructor call
		array_unshift($trace, array(
			'class' => get_class($exception),
			'func'.'tion' => '__construct',
			'line' => $exception->getLine(),
			'file' => $exception->getFile(),
			'args' => array($exception->getMessage(), $exception->getCode()) ));
	}
	
	function getTimeStamp() {
		$format = class_exists('ValueValidator')
			? ValueValidator::getInternalTimestampFormat()
			: 'Y-m-d H:i:s';
		return date($format, time());
	}
	
	function dieIfFatal($level, $message, $filePath, $lineNumber) {
			if ($level == E_USER_ERROR || $level == E_ERROR
					|| (defined('E_RECOVERABLE_ERROR') && $level == E_RECOVERABLE_ERROR) )
				die();		
	}

	function getRequestData($forLog=true) {
		 //do not include cookies
		$requestData = $this->isDevelopment() 
			? array_merge($_GET, $_POST)
			: $_GET; //do not log potentially sensitive data in production or test
		unSet($requestData[session_name()]); //never include session id
		
		if (!$forLog) return $requestData;
		
		//do not log security sensitive data
		unSet($requestData['username']);
		unSet($requestData['password']);
		unSet($requestData['ticket']);
		unSet($requestData['pntActionTicket']);
		unSet($requestData['WachtWoord']);
		return $requestData;
	}
	
	function logError($levelOrCode, $message, $filePath, $lineNumber, $timeStamp, $traces=array(), $type=null) {
		if (!$type)
			$type = $this->mapErrorLevel($levelOrCode);
		$requestData = $this->getRequestData();
		
		$someInfo['timeStamp'] = $timeStamp;
		$someInfo['levelOrCode'] = $levelOrCode;
		$someInfo['type'] = $type;
		$someInfo['message'] = $message;
		$someInfo['filePath'] = $filePath;
		$someInfo['lineNumber'] = $lineNumber;
		$someInfo['host'] = isSet($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
		$someInfo['user'] = isSet($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
		$someInfo['clientIp'] = isSet($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : null;
		$someInfo['clientHost'] = isSet($_SERVER["REMOTE_ADDR"]) ? gethostbyaddr($_SERVER["REMOTE_ADDR"]) : null;
		$someInfo['script'] = $_SERVER["SCRIPT_FILENAME"];
		$someInfo['requestParams'] = implode(Gen::assocsToStrings($requestData), ", ");
		$someInfo['referer'] = isSet($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'no referer';

		$logString = str_replace("\n", ' ', Gen::toCsvString($someInfo));
		if ($this->logBacktrace) {
			if (empty($traces))
				$traces[] = debug_backtrace();
			forEach ($traces as $i => $trace) {
				if ($i > 0) $logString .= "\n";
				$trace = $this->truncateBackTrace($trace);
				forEach ($trace as $frame) {
					$logString .= "\n\t";
					$logString .= Gen::toCsvString($frame);
				}
			}
		}	
		$logString = str_replace("\r", ' ', $logString);
		if ($this->logFilePath)
			error_log("$logString\r\n", 3, $this->logFilePath);
		else 
			error_log($logString, 0);
	}

	// If you override this method to redirect to your own error page or script, you need to
    //override errorInErrorPage() too
	function informUser($levelOrCode, $message, $filePath, $lineNumber, $timeStamp, $type=null) {
		$cnv = $this->getStringConverter();
		$timeStampString = $cnv->toLabel($timeStamp, 'timestamp');
		
		$errorText = $cnv->urlEncode($this->getUserErrorText($levelOrCode, $message, $filePath, $lineNumber, $timeStampString));
		$info = $cnv->urlEncode($this->getUserErrorInfo($levelOrCode, $message, $filePath, $lineNumber, $timeStampString));
		$cause = $cnv->urlEncode($this->getUserErrorCause($levelOrCode, $message, $filePath, $lineNumber, $timeStampString));
		$url = $this->getErrorPageUrl($errorText, $info, $cause); //can not contain \n or \r or ' because urlEncoded
		if (!headers_sent()) 
			header("Location: $url"); 
		$urlLit = $cnv->toJsLiteral($url);
		print "
			<script>
				document.location.href=$urlLit;
			</script>";
	}
	
	/** parameters must be url encoded */
	function getErrorPageUrl($errorText, $info, $cause) {
		return  "index.php?pntHandler=ErrorPage&errorMessage=$errorText&pntInfo=$info";
	}
	
	function errorInErrorPage() {
		return isSet($_REQUEST['pntHandler']) && $_REQUEST['pntHandler'] == 'ErrorPage';
	}
		
	function getUserErrorText($levelOrCode, $message, $filePath, $lineNumber, $timeStampString) {
		return "Software failure at $timeStampString";
	}
	
	function getUserErrorInfo($levelOrCode, $message, $filePath, $lineNumber, $timeStampString) {
		return "The failure has been logged for debugging. If you inform the application administrator or webmaster about the the date and time as printed on this page, the problem may be solved sooner";
	}
	
	function getUserErrorCause($levelOrCode, $message, $filePath, $lineNumber, $timeStampString) {
		return "Cause: ".$this->getErrorCause($levelOrCode, $message, $filePath, $lineNumber, $timeStampString);
	}

	function getErrorCause($levelOrCode, $message, $filePath, $lineNumber, $timeStampString) {
		$pieces = explode('/', $filePath);
		$fileName = $pieces[count($pieces)-1];
		return subStr($fileName, 0, strlen($fileName)-4).$lineNumber;
	}
	
	function printDebugInfo($levelOrCode, $message, $filePath, $lineNumber, $timeStamp, $traces, $type=null) {
		if (!$type || is_subclassOr($type, 'PntErrorException'))
			$type = $this->mapErrorLevel($levelOrCode);
		print "<br />\n<B>$type:</B> '";
        $cnv = $this->getStringConverter();
		print $cnv->toHtml($message);
		print "' in <B>$filePath</B><BR>\nat line <B>$lineNumber</B>";
		if (!$this->hasHandledError) {
			print "<BR>Request params: <BR>\n";
			forEach(Gen::assocsToStrings($this->getRequestData(false)) as $assoc)
				print $cnv->toHtml(stripSlashes($assoc)). ", \n";
		}
		print "<br />\n";
		if (isSet($this->debugInfoBacktrace)) {
			if (!$this->debugInfoBacktrace) return;
		} else {
			if (!$this->isDevelopment()) return; //does not occur, but just in case
		}
		forEach ($traces as $trace)
			Gen::printBacktrace($this->truncateBackTrace($trace));
	}
	
	function truncateBackTrace($traceArray) {
		for ($i=0; $i<count($traceArray); $i++) {
			$frame = $traceArray[$i];
			if (isSet($frame['class']) 
					&& ($frame['class'] == get_class($this) || $frame['class'] == 'PntErrorHandler')
					&& $frame['func'.'tion'] == 'handleError') {
				$traceArray = array_slice($traceArray, $i+1);
			}
		}
		//hide db connect arguments
		if (substr($traceArray[0]['func'.'tion'],-7,7) == 'connect' ||
			($traceArray[0]['func'.'tion'] == '__construct' && $traceArray[0]['class'] == 'PDO') )
			$traceArray[0]['args']=array();

		return $traceArray;
	}
	
	function isDevelopment() {
		if (!isSet($_SERVER['HTTP_HOST'])) return false;
		
		return $_SERVER['HTTP_HOST'] == $this->developmentHost;
	}
	
	//actually php5.1 and up also trigger these notifications
	function isPhp44RefNotice($level, $message, $filePath) {
		return $level == E_NOTICE && (
			$message == 'Only variable references should be returned by reference' || 
				$message == 'Only variables should be assigned by reference' 
		);
	}

	function isPntFile($filePath) {
		$classDir = realpath('../classes/pnt');
		$testDir = realpath('../classes/pnt/test');
		return strPos($filePath, $classDir) === 0 
			&& strPos($filePath, $testDir) !== 0;
	}

	function shouldLogException($e) {
		//the errors causing PntErrorException to be thrown are already logged if required 
		return !Gen::is_a($e, 'PntErrorException');
	}
	
	function shouldReportException($e) {
		return true;
	}
	
} 

?>