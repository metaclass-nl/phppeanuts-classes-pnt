<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


/** Authenticator that does not do authentication. 
* Allows everybody is allowed to use the application without
* login. All users have all credentials.
*
* This class also defines the interface for PntAuthenticator
* @package pnt/secu
*/
class PntNoAuthenticator {
	
	public $actionTicketLifeTime = 300; //5 minutes
	public $footprintLifeTime = 1800; //30 minutes
	public $footprintsLimit = 30;
	public $baseUrl;
	public $tokenSalt;
    public $hsalg;
	
	function __construct($baseUrl, $tokenSalt) {
		$this->baseUrl = $baseUrl;
		$this->tokenSalt = $tokenSalt;
		$this->initHsalg();
	}
	
	/** @return wheather the user is authenticated, or true if
	* authentication is not required (default).
	* @param PntHttpRequest $request
	* @param ScoutInterface $scout 
	* @precondition session has been started
	*/
	function isAuthenticated($request, $scout) {
		return true;
	}
	
	/** Authenticate the user. If authenticated, register the user session
	* @param String $username The username  
	* @param String $password The password
	* @return true if the user could be authenticated.
	*/
	function authenticate($username, $password) {
		trigger_error('authenticate method should have been implemented by a subclass', E_USER_ERROR);
	}

	/** Default implementation allways returns true.
	* @param string $code Code that identifies the credential, 
	*	or null if only a valid user is required
	* @return boolean Wheather the user has the specified credential 
	*/
	function userHasCredential($code) {
		return true;
	}
	
	/** Log out the user. Default implementation is no authentication so logout is ignored
	* @param Exception $exception if security threat, or null if normal logout
	*/ 
	function logOut($exception=null) {
			if ($exception) {
			$actionTickets =& $this->sessionVar('pntActionTickets');
			$actionTickets = null;
			//? $scout->clearFootprints(); NYI, may not be a good idea
		}
	}

	/** @return string a new token */
	function newToken() {
		$toHash = $this->tokenSalt. pack('l', mt_rand()). $this->getVarSalt();  
		$hashed = $this->hsalg == 'sha1' 
			? sha1($toHash) 
			: hash($this->hsalg, $toHash);
		$hashed = str_shuffle($hashed);
		return subStr($hashed, 0, 16);
	}
	
	function newFootprintId() {
		$result = $this->newToken();
		$footprintIds =& $this->sessionVar('pntFootprintIds');
		if ($footprintIds === null)
			$footprintIds = array();
		$footprintIds[$result] = time(); //resets current pointer
		if (count($footprintIds) > $this->getFootprintsLimit()) {
			$oldestId = key($footprintIds);
			unSet($footprintIds[$oldestId]);
		}

		return $result;
	}
	
	function getFootprintsLimit() {
		return $this->footprintsLimit;
}
	
	function isValidFootprint($footprintId) {
		$footprintIds =& $this->sessionVar('pntFootprintIds');
		if ($footprintIds === null || !isSet($footprintIds[$footprintId]) ) return false;
		
		$limit = time() - $this->getFootprintLifeTime();
		return $footprintIds[$footprintId] >= $limit;
	}
	
	function getVarSalt() {
		$varSalt =& $this->sessionVar('pntVarSalt');
		if ($varSalt === null)
			$varSalt = mt_rand();
		else
			$varSalt -= 1;
		
		return pack('l', $varSalt);
	}
	
	/** To be overridden for LoginPage if used to be the only entry page,
	 * PntNoAuthenticator allows urls with no requestData
	 * @param PntRequestHandler $handler
	 * @param PntHttpRequest $request
	 * @return boolean whether $handler is an entry page that does not require a valid pntRef 
	 */
	function isEntryPage($handler, $request) {
		return count($request->getRequestData()) == 0;
	}
	
	function initHsalg() {
		$this->hsalg = 'sha1';
		if (function_exists('hash')) {
			$algos = hash_algos();
			if (in_array('sha512', $algos)) $this->hsalg = 'sha512';
			elseif (in_array('sha256', $algos)) $this->hsalg = 'sha256';
		} 
	}
	
	/** To prevent Cross-site request forgery each form has a parameter pntActionTicket
	* that will change each time the form is printed. It is generated here and 
	* stored in the session. When an action is invoked, the ticket is checked and removed
	* from the session. 
	* @param string $footprintId the token of the page on which the form is situated
	* @return string the ticket value
	*/
	function getAndCreateNextActionTicket($footprintId) {
		$ticket = $this->newToken();
		$actionTickets =& $this->sessionVar('pntActionTickets');
		if ($actionTickets === null)
			$actionTickets = array();
		$actionTickets[$footprintId][$ticket] = time();
		return $ticket;
	}

	/** To prevent Cross-site request forgery each form has a parameter pntActionTicket
	* that will change each time the form is printed. It is stored in the session. 
	* When an action is invoked, the ticket is checked and removed.
	* All other tickets printed on the page are also removed.
	* Outdated tickets are removed.
	* @param string $ticket the ticket to be checked
	* @param string $footprintId the token of the page on which the form is situated
	* @return boolean wheather the ticket is valid for the page token
	* @throws PntValidationException if footprint exists but does not have the ticket
	*/
	function isValidActionTicket($ticket, $footprintId) {
		$actionTickets =& $this->sessionVar('pntActionTickets');
		if ($actionTickets === null) return false;

		//throw PntValidationException if footprint exists but does not have this ticket
		$ex = isSet($actionTickets[$footprintId]) && !isSet($actionTickets[$footprintId][$ticket]) 
			 ? new PntValidationException("Invalid ticket '$ticket' for '$footprintId'")
			 : null;

		//remove footprintIds with outdated tickets
		$this->removeOutdatedActionFootprints($actionTickets);
		
		//check if the ticket still exists for the footprintId
		$result = isSet($actionTickets[$footprintId][$ticket]);
		
		//remove footprintId
		unSet($actionTickets[$footprintId]);

		if ($ex) throw $ex;
		
		return $result;
	}
	
	function removeOutdatedActionFootprints(&$footPrints) {
		$limit = time() - $this->getActionTicketLifeTime();
		forEach(array_keys($footPrints) as $eachFootprintId) {
			if (current($footPrints[$eachFootprintId]) < $limit )
				unSet($footPrints[$eachFootprintId]);
			else
				return;
		}
	}
	
	/** @return a reference to a session variable for this baseUrl */
	function &sessionVar($key) {
		if (!isSet($_SESSION[$this->baseUrl][$key]))
			$_SESSION[$this->baseUrl][$key] = null;
		
		return $_SESSION[$this->baseUrl][$key];
	}
	
	function getActionTicketLifeTime() {
		return $this->actionTicketLifeTime; 
	}
	
	function getFootprintLifeTime() {
		return $this->footprintLifeTime;
	}
}
?>