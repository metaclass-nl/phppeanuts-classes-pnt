<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntAction', 'pnt/web/actions');

/** Action that deletes multiple objects.
* Used by form from ObjectIndexPage, ObjectSearchPage and ObjectPropertyPage
* when Delete button is pressed and items are marked.
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
class PntObjectDeleteMarkedAction extends PntAction {

	function handleRequest() {
		$this->startSession();
		$success = $this->beginTransaction();
		try {
			if ($success) {
				$marked = $this->getRequestedObject();
				$success = !$this->checkAccess();
			}
			if ($success) {
			    foreach ($marked as $value) {
					$this->errors = array_merge($this->errors, $this->getDeleteErrorMessagesFrom($value) );
				}
			}
			// we COULD delete the objects that had no errors and redirect,
			// if we could clean up the copy of the request and make sure
			// the error messages + markedOids fit into an url
			// but that is a not yet implemented,
			// so for now we do not delete any object if
			// there are errors
	
			if (empty($this->errors)) {
				$this->deleteObjects();
			}
	
			$newReq = $this->getContextRequest($this->errors);
	
			if (empty($this->errors))
				$this->commit();
		} catch (Exception $e) {
			$this->rollBack();
			throw $e; //rethrow
		}
		if (empty($this->errors))
			return $this->finishSuccess($newReq);
		
		if ($this->inTransaction) 
			$this->rollBack();
		$this->finishFailure($newReq);
	}

	function getDeleteErrorMessagesFrom($item) {
		return $item->getDeleteErrorMessages();
	}

	function checkAccess() {
		$err = $this->controller->checkAccessHandler($this, 'DeleteClass', $this->getTypeClassDescriptor()); 
		if (!$err) $err = $this->checkActionTicket();
		if ($err) {
			$this->rollBack();
			$this->errors[] = $err;
		}
		return $err;
	}

	/** Returns the request the action should allways use for redirect or forwarding
	* Usually this request gets the page that issued the markDeletedAction,
	* But if there are no errors it is posible that the context of that page is to be used
	*
	* @return String or array, url or requestData
	*/
	function getContextRequest($errors) {

		$scout = $this->getScout();
		$referrerId = $scout->getReferrerId($this->requestData);
		// normally the request is POSTed by a form that was already
		// in de edit details context, but theoretically it is possible
		// that the sender of the request want to move the delete action 'up'
		// so that it redirects to the context
//print "<BR>referrerId: $referrerId";
		$context = empty($errors) && $this->getReqParam('pntScd') == 'u'
			? $this->getContextHref($referrerId)
			: $scout->getFootprintHref($referrerId);
		if ($context) {
			$newReq = $context;
		} else {
			// forward to same type listpage
			$newReq = $this->requestData; //makes a copy
			$newReq['pntType'] = $this->getType();
			$newReq['pntHandler'] = 'IndexPage';
		}
		return $newReq;
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
		return "<B>Delete canceled because:</B> ";
	}

	function getOKMessage($marked) {
		$typeLabel = $this->getTypeLabel();
		$count = count($marked);
		return "$count $typeLabel(s) have been deleted";
	}

	//returns Array of objects
	function getRequestedObject() {
		if (isSet($this->object)) return $this->object;
		
		$this->collector = $this->getMarkedItemsCollector();
		return $this->object = $this->collector->getMarkedObjects($this->requestData);
	}

	function deleteObjects() {
		reset($this->object);
		foreach ($this->object as $each) {
			$result = $this->deleteObject($each);
			if (Gen::is_a($result, 'PntError'))
				$this->errors[] = $result->getLabel();
		}
	}
	
	function deleteObject($obj) {
		return $obj->delete();
	}

	function finishSuccess($newReq) {
		$this->redirectRequest($newReq, $this->getOKMessage($this->object));
	}
	
	function finishFailure($newReq) {
		//error(s), forward to page
		// we need newReq to be an arrray, not an url
		$baseUrl = $this->getBaseUrl();
		if (is_string($newReq)) {
			if (subStr($newReq, 0, 3) == '../')
				$baseRel = subStr($newReq, 3);
			elseIf (subStr($newReq, 0, strLen($baseUrl)) == $baseUrl )
				$baseRel = subStr($newReq, strLen($baseUrl));

			if (isSet($baseRel)) {
				$slashPos = strPos($baseRel, '/');
				if ($slashPos !== false)
					$dir = subStr($baseRel, 0, $slashPos + 1);
			}
			//HACK: does not work if the request was already forwarded
			$newReq = $this->request->getFunkyRequestData(null, $newReq);
		}

		$newReq = array_diff_key($newReq, $this->collector->getMarkedItemParams($newReq));
		$newReq = array_merge($newReq, $this->collector->getMarkedItemParams($this->requestData));

		if (isSet($dir) && $dir != $this->getDir()) { //other application, can not forward
			$this->redirectRequest($newReq, $this->getDeleteErrorInformation($this->errors), $dir);
		}

		$handler = $this->getRequestHandler($newReq);
		$handler->setInformation(
			$this->getDeleteErrorInformation($this->errors)
		);
		$handler->setInfoStyle($handler->getInfoStyleError());
		$this->controller->forwardToHandler($handler);
	}
}
?>