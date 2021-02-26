<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


/** Objects of this class are designed to give detailed control over what 
* a user van see and do. Currently the default user interface does check on
* invocation of Pages, Dialogs and Actions, hides multi value property buttons and
* tables, but does not hide or make readOnly widgets, fields and columns
* and does not ghost Create, Update and Delete buttons.
* 
* Check methods should return an appropriate error message to be displayed
* in the access denied error page
* 
* This abstract superclass provides default behavior for the concrete
* subclass SecurityManager in the root classFolder or in the application classFolder. 
* By default its more detailed checks delegate to broader checks, eventually ending
* in checking access on domainDir level, there allowing all.
* you may override methods in the concrete subclass, don't modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @package pnt
*/
class PntSecurityManager {
	
	public $baseUrl, $tokenSalt, $authenticator;
	
	function __construct($baseUrl, $tokenSalt) {
		$this->baseUrl = $baseUrl;
		$this->tokenSalt = $tokenSalt;
	}
	
	
	function getAuthenticator() {
		if (!isSet($this->authenticator)) $this->initAuthenticator();
		return $this->authenticator;
	}
	
	/** Initialize the authenticator. Override this method to 
	* initialize it to an authenticator that actually does authentication
	*/
	function initAuthenticator() {
		Gen::includeClass('PntNoAuthenticator', 'pnt/secu');
		$this->authenticator = new PntNoAuthenticator($this->baseUrl, $this->tokenSalt);
	}
	
	/** Initializes the authenticator if not yet initialized
	* @return wheather the user is authenticated, or true if
	* authentication is not required.
	* @param PntHttpRequest $request 
	* @param ScoutInterface $scout 
	* @precondition session has been started
	*/
	function isAuthenticated($request, $scout) {
		$auth = $this->getAuthenticator();
		return $auth->isAuthenticated($request, $scout);
	}
	
	/** Authenticate the user. If authenticated, register the user session
	* @param String $username The username  
	* @param String $password The password
	* @return true if the user could be authenticated.
	*/
	function authenticate($username, $password) {
		return $this->authenticator->authenticate($username, $password);
	}
	
	/** @return string a new footPrint */
	function newFootprintId() {
		$auth = $this->getAuthenticator();
		return $auth->newFootprintId();
	}
	
	/** Check the referrer info and token.
	 * @param PntRequestHandler asking for access
	 * @param PntHttpRequest $request
	 * @param ScoutInterface $scout 
	 * @return string error if not OK 
	 * @trhows PntValidationException if HTTP_REFERER and scouting footprint don't match */
	function checkAccessRef($handler, $request, $scout) {
		$pntRef = $request->getRequestParam('pntRef');
		$handler->checkAlphaNumeric($pntRef); //trhows PntValidationException for bad ref
		
		if (Gen::is_a($handler, 'PntErrorPage')) return null; //ErrorPages are allowed
		if ($this->isEntryPage($handler, $request) && !$pntRef ) return null; //entry page access without pntRef is allowed. 

		$auth = $this->getAuthenticator();
		if (!$auth->isValidFootprint($pntRef)) return $this->getMessageDeniedAccessRef($pntRef);
		
		//footprint is valid, but is it from the referring page?
		$footprint = $scout->getFootprintHref($pntRef);
		if (!$footprint) return null; //may have scrolled
		
		$httpRef = $request->getServerValue('HTTP_REFERER');
		if (!$httpRef) return null; //not allways available
		
		if ($this->checkRefEqual($scout->getFootprintUri($httpRef), $footprint))
			throw new PntValidationException($this->getMessageFootprintMismatch($httpRef, $footprint));			
		
		return null;
	}
	
	/** @param string $httpRef footprint from HTTP_REFERER
	 *  @param string $footprint from scouting
	 * @return boolean wheather the footprints should be considered equal */
	function checkRefEqual($httpRef, $footprint) {
		return $httpRef == $footprint;
	}
	
	/** To be overridden for aditional entry pages if no authentication
	 * Default is to delegate to the authenticator 
	 * @param PntRequestHandler $handler
	 * @param PntHttpRequest $request
	 * @return boolean whether $handler is an entry page that does not require a valid pntRef if no footprints
	 */
	function isEntryPage($handler, $request) {
		$auth = $this->getAuthenticator();
		return $auth->isEntryPage($handler, $request);
	}
	
	function checkAccessApp($path) {
		//default is to allow all
		return null;
	}
	

	function checkViewInDomainDir($path) {
		//default is to allow all
		return null;
	}

	function checkModifyInDomainDir($path) {
		return $this->checkViewInDomainDir($path);
	}

	
	function checkViewClass($objects, $clsDesc) {
		return $this->checkViewInDomainDir($clsDesc->getClassDir());
	}

	function checkModifyClass($objects, $clsDesc) {
		$result = $this->checkViewClass($objects, $clsDesc);
		if ($result) return $result;
		
		return $this->checkModifyInDomainDir($clsDesc->getClassDir());
	}

	function checkCreateClass($objects, $clsDesc) {
		return $this->checkModifyClass($objects, $clsDesc);
	}

	function checkEditClass($objects, $clsDesc) {
		return $this->checkModifyClass($objects, $clsDesc);
	}

	function checkDeleteClass($objects, $clsDesc) {
		return $this->checkModifyClass($objects, $clsDesc);
	}


	function checkViewObject($object, $clsDesc) {
		if (Gen::is_a($object, 'PntObject'))
			$clsDesc = $object->getClassDescriptor();
		return $this->checkViewClass(array($object), $clsDesc);
	}

	function checkCreateObject($object, $clsDesc) {
		$err = $this->checkViewObject($object, $clsDesc);
		if ($err) return $err;
		
		if (Gen::is_a($object, 'PntObject'))
			$clsDesc = $object->getClassDescriptor();
		return $this->checkCreateClass(array($object), $clsDesc);
	}

	function checkEditObject($object, $clsDesc) {
		$err = $this->checkViewObject($object, $clsDesc);
		if ($err) return $err;

		if (Gen::is_a($object, 'PntObject'))
			$clsDesc = $object->getClassDescriptor();
		return $this->checkEditClass(array($object), $clsDesc);
	}

	function checkDeleteObject($object, $clsDesc) {
		$err = $this->checkViewObject($object, $clsDesc);
		if ($err) return $err;

		if (Gen::is_a($object, 'PntObject'))
			$clsDesc = $object->getClassDescriptor();
		return $this->checkDeleteClass(array($object), $clsDesc);
	}


	/** A property by default may be viewed if the object may be viewed  
	* and the properties values may be viewed. However, it is more efficient
	* to check if the object may be viewed only once for each page, therefore
	* that is not checked here. Getting the properties values
	* for each multi value property button thkes too much time,
	* so for multi value properties only the type is checked.
	*/
	function checkViewProperty($object, $propDesc) {
		//if (!Gen::is_a($propDesc, 'PntPropertyDescriptor')) throw new PntError('Bad or no property descriptor');
		if (!is_subclassOr($propDesc->getType(), 'PntObject')) return null; 
		
		$typeClsDesc = PntClassDescriptor::getInstance($propDesc->getType());
		if ($propDesc->isMultiValue() || null === $object) {
			$values = array();
		} else {
			try {
				$values = array($propDesc->getValueFor($object));
			} catch (PntError $err) { 
				trigger_error($err->getLabel(), E_USER_WARNING);
				return false;
			} 
		} 
		return $this->checkViewClass($values, $typeClsDesc);
	}
	
	/** A property by default may be edited if the object may be edited  
	* and the property may be viewed. However, it is more efficient
	* to check if the object may be edited only once for each page, therefore
	* that is not checked here. 
	* Currently the user interface only adapts to checkEditProperty by 
	* hiding buttons to and accessing MtoNPropertyPages and processing their forms.
	* EditDetailsPages do not (yet) replace (MtoN)DialogWidgets by text, and SaveActions do
	* not checkEditProperty for single value properties, so the user should be allowed 
	* to select a value for a property whose type he may not view. 
	* For the time being this is to be resolved
	* by the application developer overriding this method and ::checkSelectProperty
	* or overriding getFormWidget on EditDetailsPage.
	*/
	function checkEditProperty($object, $propDesc) {
		return $this->checkViewProperty($object, $propDesc);
	}

	/** By default the selection of values is only allowed if the properties 
	* type may be viewed. PROBLEM: Form fields are currently not made readOnly 
	* in EditDetailsPages if the property is not editable, so the user can 
	* try to select a value for it but may not be allowed to do so. 
	* It would be nice to by default allow selection too, 
	* but we can not verify the properties existence and type.
	* For the time being this is to be resolved by the application developer 
	* overriding this method or overriding getFormWidget on EditDetailsPage.
	*/
	function checkSelectProperty($objects, $clsDesc, $propertyName) {
		return $this->checkViewClass($objects, $clsDesc);
	}
	
//Default messeges that may be used by overrides of the corresponding check.. methods

	function getMessageDeniedAccessApp($path) {
		return "You are not authorized to access the application in $path";
	}
	function getMessageDeniedViewInDomainDir($path) {
		return "You are not authorized to view items in $path";
	}
	function getMessageDeniedModifyInDomainDir($path) {
		return "You are not authorized to modify items in $path";
	}
	function getMessageDeniedViewClass($objects, $clsDesc) {
		return "You are not authorized to view items of type '". $clsDesc->getLabel(). "'";
	}
	function getMessageDeniedModifyClass($objects, $clsDesc) {
		return "You are not authorized to modify items of type '". $clsDesc->getLabel(). "'";
	}
	function getMessageDeniedCreateClass($objects, $clsDesc) {
		return "You are not authorized to create items of type '". $clsDesc->getLabel(). "'";
	}
	function getMessageDeniedEditClass($objects, $clsDesc) {
		return "You are not authorized to modify items of type '". $clsDesc->getLabel(). "'";
	}
	function getMessageDeniedDeleteClass($objects, $clsDesc) {
		return "You are not authorized to delete items of type '". $clsDesc->getLabel(). "'";
	}
	function getMessageDeniedViewObject($object, $clsDesc) {
		return "You are not authorized to view this ". $clsDesc->getLabel();
	}
	function getMessageDeniedCreateObject($object, $clsDesc) {
		return "You are not authorized to create this ". $clsDesc->getLabel();
	}
	function getMessageDeniedEditObject($object, $clsDesc) {
		return "You are not authorized to update this ". $clsDesc->getLabel();
	}
	function getMessageDeniedDeleteObject($object, $clsDesc) {
		return "You are not authorized to delete this ". $clsDesc->getLabel();
	}
	function getMessageDeniedViewProperty($object, $propDesc) {
		return "You are not authorized to view the property '". $propDesc->getLabel(). "'";
	}
	function getMessageDeniedViewPropertyValues($object, $propDesc) {
		return "You are not authorized to view the values of property '". $propDesc->getLabel(). "'";
	}
	function getMessageDeniedEditProperty($object, $propDesc) {
		return "You are not authorized to edit the property '". $propDesc->getLabel(). "'";
	}
	function getMessageDeniedSelectProperty($objects, $clsDesc, $propertyName) {
		return "You are not authorized to select from ". $clsDesc->getLabel();
	}
	function getMessageDeniedAccessRef($pntRef) {
		return "Url corrupt or outdated. Advice: close the tab or return to entry page";
	}
	function getMessageFootprintMismatch($httpRef, $footprint) {
		return "Referrer does not match footprint: '$httpRef', '$footprint'";
	}
}
?>