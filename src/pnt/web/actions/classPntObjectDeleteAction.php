<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntAction', 'pnt/web/actions');

/** Action that deletes one object. Requires id and pntType request parameters.
* Used by form from EditDetailsPage when Delete button is pressed.
* Redirects to pntContext or if none, to ObjectIndexPage 
* @see http://www.phppeanuts.org/site/index_php/Pagina/158
*
* This abstract superclass provides behavior for the concrete
* subclass DeleteAction in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @package pnt/web/actions
*/
class PntObjectDeleteAction extends PntAction {

	function handleRequest() {
		$this->startSession();
		$success = $this->beginTransaction(); //adds message to $this->errors if it fails
		try {
			if ($success) {
				$obj = $this->getRequestedObject();
				if (!$obj) {
					$typeLabel =  $this->getTypeLabel();
					return trigger_error($typeLabel. ' not found: id='.$this->getReqParam('id'), E_USER_ERROR);
				}
				$this->checkAccess();
			}
			if (empty($this->errors)) 
				$this->errors = $obj->getDeleteErrorMessages();
			if (empty($this->errors)) 
				$this->deleteObject();
		} catch (Exception $e) {
			$this->rollBack();
			throw $e; //rethrow
		}

		if (empty($this->errors)) 
			$this->commit();
		if (empty($this->errors)) 
			$this->finishSuccess($obj);

		if ($this->inTransaction) 
			$this->rollBack();
		$this->finishFailure($this->errors);
	}
	
	function checkAccess() {
		$err = $this->controller->checkAccessHandler($this, 'DeleteObject'); 
		if (!$err) $err = $this->checkActionTicket();
		if ($err) {
			$this->rollBack();
			$this->errors[] = $err;
		}
		return $err;
	}

	function deleteObject() {
		$this->objectLabel = $this->object->getLabel();
		$result = $this->object->delete();
		if (Gen::is_a($result, 'PntError'))
			$this->errors[] = $result->getLabel();
	}

	function finishSuccess($obj) {
		$this->finishAndRedirectToContext($obj, $this->getOKMessage($obj) );
	}

	function finishFailure($errors) {
		
		//error(s), forward to detailsPage
		$newReq = $this->requestData; //makes a copy
		$newReq['pntHandler'] = 'EditDetailsPage';
		$handler = $this->getRequestHandler($newReq);
		$handler->setInformation(
			$this->getDeleteErrorInformation($errors)
		);
		$handler->setInfoStyle($handler->getInfoStyleError());
		$this->controller->forwardToHandler($handler);
	}

	function getDeleteErrorInformation($errors) {
		$result = $this->getDeleteErrorMessage();
		$result .= "\n<lu>";
		forEach($errors as $message)
			$result .= "\n<li>$message</li>";
		$result .= "\n</lu>";
		return $result;
	}
	
	function getDeleteErrorMessage() {
		$typeLabel = $this->getTypeLabel();
		return "<B>This $typeLabel can not be deleted because:</B> ";
	}
	
	function getOKMessage($obj) {
		$typeLabel = $this->getTypeLabel();
		$label = isSet($this->objectLabel) ? $this->objectLabel : $obj->getLabel(); //support for old overrides that do not set objectLabel 
		return "$typeLabel '$label' has been deleted";
	}
}
?>