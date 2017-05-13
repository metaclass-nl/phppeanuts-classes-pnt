<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntRequestHandler', 'pnt/web');

/** Abstract Action superclass.
* @see http://www.phppeanuts.org/site/index_php/Pagina/158
* @package pnt/web/actions
*/
class PntAction extends PntRequestHandler {

	public $inTransaction = false;
	
	function __construct($whole, $requestData) {
		parent::__construct($whole, $requestData);
		$this->errors = array();
	}

	function beginTransaction() {
		$clsDes = $this->getTypeClassDescriptor();
		$this->queryHandler = $clsDes->getSimpleQueryHandler();
		try {
			$this->queryHandler->beginTransaction();
		} catch (Exception $e) {
			$this->errors[] = Gen::toString($e);
			return false;
		}
		$this->inTransaction = true;
		return true;
	}
	
	function commit() {
		try {
			$this->queryHandler->commit();
		} catch (Exception $e) {
			$this->errors[] = Gen::toString($e);
			return false;
		}
		$this->inTransaction = false;
		return true;
	}
	
	/** @throws PntDbError if rollback fails */
	function rollBack() {
		$this->queryHandler->rollBack();
		$this->inTransaction = false;
		return true;
	}

	function finishAndRedirectToContext($obj, $message) {
		$scout = $this->getScout();
		$referrerId = $scout->getReferrerId($this->requestData);
		// normally the request is POSTed by a form that was already
		// in de edit details context, but theoretically it is possible
		// that the sender of the request want to move the action 'up'
		// so that it redirects back to itself
		$context = $this->getReqParam('pntScd') == 'u'
			? $scout->getFootprintHref($referrerId)
			: $this->getContextHref($referrerId);
		if ($context) {
			$newReq = $context;
		} else {
			// No context. forward to same type listpage
			$newReq = array('pntType' => $this->getType() );
		}			
		
		return $this->redirectRequest($newReq, $message);
	}
	
	/** To prevent Cross-site request forgery each form has a parameter pntActionTicket
	* that will change each time the form is printed. It is stored in the session. 
	* When an action is invoked, the ticket is checked and removed from the session. 
	* @return string null if OK, or errormessage if the pntRef is unknown or the ticket is no longer valid 
	* @throws PntValidationException if no ticket, no pntRef or if pntRef is registered but ticket is not
	*/
	function checkActionTicket() {
		$this->checkRequestMethod();
		
		$ticket = $this->getReqParam('pntActionTicket');
		if (!$ticket) throw new PntValidationException('No Action Ticket');
		$this->checkAlphaNumeric($ticket); //throws PntValidationException if bad ticket
		$footprintId = $this->getReqParam('pntRef');
		if (!$footprintId) throw new PntValidationException('No pntRef');
		$this->checkAlphaNumeric($footprintId); //throws PntValidationException if bad ticket

		$sm = $this->controller->getSecurityManager();
		if ($sm->getAuthenticator()->isValidActionTicket($ticket, $footprintId)) return null; //OK
		
	 	return $this->controller->getInvalidActionTicketMessage();
	}
	
	function checkRequestMethod() {
		$reqMth = $this->request->getServerValue('REQUEST_METHOD');
		if ($reqMth == 'POST') return;

		
		throw new PntError('Illegal Request Method for '
			. $this->getName(). ': '. $reqMth);
	}

	/** Route error handling over this. 
	* Exception handling is not rerouted, exceptions are cought
	* @depricated PntErrorHandler now throws PntErrorException for fatal errors
	*    Warning: restoreErrorHandling is no longer called by default on the end of the transaction
	*/
	function rerouteErrorHandling() {
		$this->errorHandler = $GLOBALS['pntErrorHandler'];
		$GLOBALS['pntErrorHandler'] = $this; 
		set_error_handler(array($this, 'handleError'), $this->errorHandler->reportingLevel | $this->errorHandler->getLoggingLevel() );
	}
	
	/** @depricated */
	function restoreErrorHandling() {
		$GLOBALS['pntErrorHandler'] = $this->errorHandler;
		set_error_handler(array($this->errorHandler, 'handleError'), $this->errorHandler->reportingLevel | $this->errorHandler->getLoggingLevel() );		
	}

	/* @depricated PntErrorHandler now throws PntErrorException for fatal errors
	* Handle errorevent. If errorevent is reported, rollback the transaction, 
	* forward it to the errorhandler and die. Otherwise just forward it to
	* the errorhandler. 
	* REMARK: to avoid furter changes to the database after rollBack, 
	* not only fatal errors will lead to die, but all reported errors, 
	* i.e. all that will lead to the error page in a production environment.
	*/
	function handleError($level, $message, $filePath, $lineNumber) {
		$reported = $level & $this->errorHandler->reportingLevel;
		//ignore E_STRICT
		$reported = $reported & ~constant('E_STRICT');
		if ($reported) {
			$this->rollBack();
			$this->errors[] = $this->errorHandler->mapErrorLevel($level);
		}

		$this->errorHandler->handleError($level, $message, $filePath, $lineNumber);
		if (!$reported) return;
		
//Gen::printBacktrace(debug_backtrace());
		die('errorevent reported during transaction ');
	}
	
}
?>