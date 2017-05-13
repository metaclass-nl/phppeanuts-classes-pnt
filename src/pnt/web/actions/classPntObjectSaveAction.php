<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntAction', 'pnt/web/actions');

/** Action that saves an object to the database. 
* Used by form from ObjectEditDetailsPage 
* when Insert or Update button is pressed. 
* Calls save method on the object.
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
class PntObjectSaveAction extends PntAction {

	public $objectSaved = false;
	public $copy = false;
	
	function handleRequest() { 
//Gen::show($this->requestData); die();
		$success = $this->beginTransaction();
		try {
			$success = $this->initialize() && $success;
			if ($success) 
				$success = $this->preprocessObject();
			if ($success) 
				$success = $this->verifyDelete();
			if ($success) 
				$success = $this->finishObject();
		} catch (Exception $e) {
			$this->rollBack();
			$this->errors[] = Gen::toString($e);
			throw $e; //rethrow
		}
				
		if ($success) 
			$success = $this->commit();
		if ($success) 
			return $this->finishSuccess($this->object);
		
		//error(s), forward to detailsPage
		if ($this->inTransaction) 
			$this->rollBack();
		$this->finishFailure();
	}

	function initialize() {
		$this->useClass($this->getType(), $this->getDomainDir());
		$this->startSession();
		$success = $this->getOriginal(); //gives $this->original value if copy or new from exising object 
		if ($success && isSet($this->original) &&  $this->getReqParam('id')) 
			$this->initForCopy();
		//else param id is already empty/0 if create new 
		$this->getRequestedObject(); // initializes $this->object
	
		if ($this->copy)
			$this->object->pntOriginal = $this->original; //must be done before getFormTexts
		else 
			unSet($this->object->pntOriginal);
		
		$this->getFormTexts(); //initializes $this->formTexts
		
		//meant to happen before object modifies anything edited by subSaveActions
		if (!isSet($this->subActionIndex))  {
			$handler = $this->getFailureHandler();
			$this->subActions = method_exists($handler, 'getSubsaveActions')
				? $handler->getSubsaveActions()
				: array(); 
		}
		return $success;
	}
	
	function initForCopy() {
			$this->copy = true;
			$this->requestData['id']=''; //create new object to copy to
	}
	
	function preprocessObject() {
		$success = $this->convertCommitAndCheck();
		if ($success) {
			$this->errors = $this->getSaveErrorMessages();
			$success = $success && empty($this->errors); 
		}
		return $success;
	}
	
	function convertCommitAndCheck() {
		$success = $this->convertAndValidateFormValues($this->formTexts);
		$success = $this->commitFormValues($this->formTexts) && $success;
		if ($success) 
			$success = !$this->checkAccess();
		return $success;
	}

	function finishObject($asSubaction=false) {
		$success = $this->saveObject();
		if ($success) 
			$success = $this->processMtoNproperties();
		if ($success && !$asSubaction)
			$success = $this->processSubsaveActions();
		return $success;
	}
	
	function checkAccess() {
		if (!isSet($this->object)) throw new PntError('requested object missing or not initialized');
		$err = $this->controller->checkAccessHandler($this, $this->object->isNew() ? 'CreateObject' : 'EditObject'); 
		if (!$err) $err = $this->checkActionTicket();
		if ($err) {
			$this->rollBack();
			$this->errors[] = $err;
		}
		return $err;
	}

	function convertAndValidateFormValues(&$formTexts) {
		$success = true;
		$ignoreMissing = $this->getReqParam('pntIgnoreMissingFields');
		//reset($formTexts);
		while (list($formKey) = each($formTexts)) {
			$current = $formTexts[$formKey];
			if ($this->shouldProcess($current, $formKey)) {
				$err = $this->checkProcessFormValue($current);
				if ($err) {
					$success = false;
				} else {
					$current->setItem($this->object);
					$success = $current->setConvertMarkup() && $success;
				}
			} else {
				if (!($current->isReadOnly() || $ignoreMissing || Gen::is_a($current, 'PntFormMtoNRelValue') ))
					trigger_error("RequestData not set for formKey: $formKey", E_USER_WARNING);
			}
		}
		return $success;
	}
	
	function commitFormValues(&$formTexts) {
		$success = true;
		reset($formTexts);
		while (list($formKey) = each($formTexts)) {
			$current = $formTexts[$formKey];
			if ($this->shouldProcess($current, $formKey) 
					&& !Gen::is_a($current, 'PntFormMtoNRelValue')
					&& !$current->getError()) {
				$success = $success && $current->commit();
			} 
		}
		return $success;
	}

	function getSaveErrorMessages() {
		$result = $this->object->getSaveErrorMessages();
		forEach($this->getOtherObjectsToSave() as $obj) {
			$result = array_merge($result, $obj->getSaveErrorMessages());
		}
		return $result;
	}
	
	function verifyDelete() {
		return true;
	}
	
	function saveObject() {
		$result = $this->object->save();
		if (Gen::is_a($result, 'PntError')) {
			$this->errors[] = $result->getLabel();
			return false;
		}
		if (isSet($this->original)) {
			$this->object->pntOriginal = $this->original; //for createNew, may be used by subSaveActions
			$this->original->pntCopyId = $this->object->get('id');
		}
		$this->objectSaved = true;
		if ($this->copy)
			$this->recurseCopyObject();
		forEach($this->getOtherObjectsToSave() as $obj) {
			$result = $obj->save();
			if (Gen::is_a($result, 'PntError')) {
				$this->errors[] = $result->getLabel();
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Copy dependents. Be aware that values may have been committed
	 * to dependents before this recursive copying takes place, and 
	 * getOtherObjectsToSave may have cached those, als well as the properties. 
	 * Override this method if you need to fix that. 
	 */
	function recurseCopyObject() {
		$this->object->recurseCopyFrom($this->original);
	}
	
	function getOriginal() {
		$originalId = $this->getReqParam('pntOriginalId');
		if (!$originalId) return true;
		
		$clsDes = $this->getTypeClassDescriptor();
		$this->original = $clsDes->getPeanutWithId($originalId);
		if ($this->original) return true;
		
		$this->errors[] = "Original not found with id '$originalId'";
		return false;
	}
	
	function getOtherObjectsToSave() {
		if (isSet($this->otherObjectsToSave)) return $this->otherObjectsToSave;
		$this->otherObjectsToSave = array();
		reset($this->formTexts);
		$requestedObjectOid = $this->object->getOid();
		forEach($this->formTexts as $formKey => $formText) {
			if ($this->shouldProcess($formText, $formKey)) {
				$setOn = $formText->getItemToSetOn($this->object);
				if ($setOn->getOid() != $requestedObjectOid)
					$this->otherObjectsToSave[$setOn->getOid()]=$setOn;
			}
		}
		return $this->otherObjectsToSave;
	}
	
	function finishFailure() {
		//error(s), forward to detailsPage
		$handler = $this->getFailureHandler();
		$handler->setInformation($this->getErrorInformation());
		$handler->setInfoStyle($handler->getInfoStyleError());
		$this->controller->forwardToHandler($handler);
	}

	function getFailureHandler() {
		if (!isSet($this->failureHandler)) {
			$newReq = $this->requestData; //makes a copy
			$handlerOrigin = isSet($newReq['pntHandlerOrigin']) ? $newReq['pntHandlerOrigin'] : null;
			$newReq['pntHandler'] = $handlerOrigin ? $handlerOrigin : 'EditDetailsPage';
			$obj = $this->object;
			if ($this->copy) {
				//make request different from create new
				if ($this->object->isNew()) {
					//only happens if called from finishFailure
					//restore id and object 
					$newReq['id'] = $this->requestData['pntOriginalId'];
					$obj = $this->original;
				} else { 
					$newReq['id'] = $this->object->get('id');
				}
			}
			$this->failureHandler = $this->getRequestHandler($newReq);
			$this->failureHandler->setFormTexts($this->getFormTexts());
			$this->failureHandler->setRequestedObject($obj);
		}
		return $this->failureHandler;
	}

	/* Returns wheather the argument should be processed as a single value
	*/
	function shouldProcess($formNavValue, $formKey=null) {
		if ($formNavValue->isReadOnly())
			return false;

		if ($formKey===null) $formKey = $formNavValue->getFormKey();
		if (isSet($this->requestData[$formKey]))
			return true;

		$nav =  $formNavValue->getNavigation();
		if ($nav->getResultType() != 'boolean')
			return false;

		//checkboxes do not send their key&value if not checked, so we add that here
		$cnv = $formNavValue->getConverter();
		$false = false;
		$false = $cnv->toLabel($false, 'boolean');
		$formNavValue->setContentLabel($false);
		$this->requestData[$formKey] = $false;
		return true;
	}

	function finishSuccess($obj) {
		$message = $this->getOKMessage($obj);
		if ($this->getReqParam('pntBackToOrigin')) 
			$this->redirectRequest($this->getBackToOriginRequestData($obj), $message);
		else
			$this->finishAndRedirectToContext($obj, $message);
	}
	
	function getBackToOriginRequestData($obj) {
		$newReq['pntType'] = $this->getType();
		$newReq['id'] = $obj->get('id');
		$newReq['pntHandler'] = $this->getReqParam('pntHandlerOrigin'); //stips slashes but there are no slashes anyway
		$newReq['pntRef'] = $this->getReqParam('pntRef'); //stips slashes but there are no slashes anyway
		$newReq['pntEditFeedback'] = $this->getEditType($obj); //only to be included if redirect to same EditDetailsPage
		if (isSet($this->requestData['pntProperty']) && $this->requestData['pntProperty']) {
			$newReq['pntProperty'] = $this->requestData['pntProperty'];
			if (!isSet($newReq['pntHandler']))
				$newReq['pntHandler'] = 'PropertyPage';
		}

		if (isSet($this->requestData['pntContext']))
			$newReq['pntContext'] = $this->requestData['pntContext'];
		return $newReq;
	}
	
	function getErrorMessage() {
		return "<B>Errors in value of:</B><BR>";
	}

	function getErrorInformation() {
		if (empty($this->errors) )
			return $this->getErrorMessage();
			
		//Save errors
		$cnv = $this->getConverter();
		$result = $this->getSaveErrorMessage();
		$result .= "\n<lu>";
		forEach($this->errors as $message)
			$result .= "\n<li>". $cnv->toHtml($message). "</li>";
		$result .= "\n</lu>";
		return $result;
	}
	
	function getSaveErrorMessage() {
		$typeLabel = $this->getTypeLabel();
		$doneMessage = $this->getActionDoneMessage($this->object);
		return $this->objectSaved
			? "<B>$doneMessage, but error(s) in related item(s):</B> "
			: "<B>This $typeLabel can not be saved because:</B> ";
	}

	function getEditType() {
		if ($this->copy) return 'copy';
		return $this->getReqParam('id') ? 'update' : 'create';
	}
	
	function getOKMessage($obj) {
		return "OK, ". $this->getActionDoneMessage($obj);
	}
	
	function getActionDoneMessage($obj) {
		$done = $this->copy ? 'copied' :
			($this->getReqParam('id') ? 'updated' : 'created');

		return "the "
			. $this->getTypeLabel()
			. " has been $done";
	}

	/* To use the new MtoNPropertyPage support a formMtoNRelValue
	* for the ntoMProperty must be in the formTexts
	* For that the method getUiFieldPaths on the domain object class must be overridden
	*/
	function processMtoNproperties() {
		$reqObj =  $this->getRequestedObject();
		$clsDes = $reqObj->getClassDescriptor();
		$props = $clsDes->getMultiValuePropertyDescriptors();
		$reqObjModified = false;
		$success = true;
		forEach (array_keys($props) as $propName) {
			if (!$props[$propName]->getReadOnly() && isSet($this->formTexts[$propName])) {
				$formMtoNRelValue = $this->formTexts[$propName];
				$reqObjModified = $this->processMtoNRelValue($formMtoNRelValue)
					|| $reqObjModified;
				if ($formMtoNRelValue->getError())
					$success = false;
			}
		}
		if ($reqObjModified) {
			$this->errors = $this->object->getSaveErrorMessages();
			if (empty($this->errors))
				$reqObj->save();
			else 
				return false;
		}
	
		return $success;
	}

	/** new MtoNPropertyPage support */
	function processMtoNRelValue($formMtoNRelValue) {
		if (!isSet($this->requestData[$formMtoNRelValue->getFormKey()]))
			return false;
		if (!Gen::is_a($formMtoNRelValue, 'PntFormMtoNRelValue'))
			trigger_error('wrong Formtext type: '. get_class($formMtoNRelValue), E_USER_ERROR);

		return $formMtoNRelValue->commit(); 
	}
	
	/** Check with the SecurityManager if the property setted by 
	* the $formNavValue may be edited. If not set the error from the
	* SecurityManager on the MtoNRelValue and return the error.
	* Checking if the object may be created or edited is not repeated here
	* @param PntFormNavValue $formNavValue to be checked
	* @return String error message
	*/
	function checkProcessFormValue(&$formNavValue) {
		$sm = $this->controller->getSecurityManager();
		$nav = $formNavValue->getNavigation();
		$err = $sm->checkEditProperty($this->object, $nav->getSettedProp());
		if (!$err) return null;
		
		$formNavValue->error = $err;
		return $err;
	}
	
	function processSubsaveActions() {
		if ($this->copy) { //not for copy
			$this->subActions = array();
			return true;
		}
		//if copy has been made, each subsaveAction holds a copy or a new object and requestData with the objects id
		//if new created, normally no subsaveactions are obtained
		$success = true;
		forEach($this->subActions as $key => $subAction) { 
			if (!array_key_exists('id', $subAction->requestData)) 
				continue; //ignore the extra item added for new item function
			if (!$subAction->convertCommitAndCheck()) {
				$this->addSubsaveActionError($subAction);
				$success = false;
			}
		}

		reset($this->subActions);
		forEach($this->subActions as $key => $subAction) {
			if (!array_key_exists('id', $subAction->requestData)) 
				continue; //ignore the extra item added for new item function
			$subAction->errors = $subAction->getSaveErrorMessages();
			if ($subAction->errors) {
				$this->addSubsaveActionError($subAction);
				$success = false;
			}
		}
		if (!$success) return $success; //only save if all objects have been successfully preProcessed

		reset($this->subActions);
		forEach($this->subActions as $key => $subAction) {
			if (!array_key_exists('id', $subAction->requestData)) 
				continue; //ignore the extra item added for new item function
			if (!$subAction->finishObject(true)) {
				$this->addSubsaveActionError($subAction);
				$success = false;
			}
		}
		
		return $success;
	}
	
	function addSubsaveActionError($subAction) {
		$propName = $subAction->getReqParam('pntProperty');
		$clsDes = $subAction->getTypeClassDescriptor();
		$prop = $clsDes->getPropertyDescriptor($propName);
		$propLabel = $prop ? $prop->getLabel() : $propName;
		$this->errors[$propName] = ucFirst($propLabel);
	}
}
?>