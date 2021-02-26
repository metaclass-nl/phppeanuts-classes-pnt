<?php
// Copyright (c) MetaClass Groningen, 2003-2012

//loaded after Site, so no includes here

/** Special controller for testing Security
* @package pnt/test/auth
*/
class TestSecurityController extends Site {

	public $sessionStarted = true;
	public $accessDeniedError;
	
	function __construct($dir) {
		//do not call parent constructors
		$this->setDir($dir);
		$this->setDomainDir($dir);
		$this->initConverter();
		$this->initScout();

		Gen::includeClass('ErrorHandler');
		$this->errorHandler = new ErrorHandler();
		$this->initHttpRequest();
	}
	
	function accessDenied($handler, $errorMessage) {
		$this->accessDeniedError = $errorMessage;
	}
	
	function getAdError() {
		return $this->accessDeniedError;
	}
	
	function forwardToHandler($handler) {
		$this->handlerForwardedTo = $handler;
	}
} 

?>
