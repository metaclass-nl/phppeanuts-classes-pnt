<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


/** Generic error exception class 
* Depricated support: Objects of this class may be answered by framework methods 
* whose name starts with an underscore to signal and describe
* an error.  PntErrors may be nested to describe errors that are
* caused by other errors. Warning: constructor and fields are not
* included in depricated support. 
* @see http://www.phppeanuts.org/site/index_php/Pagina/92
* @package pnt
*/
class PntError extends Exception {
	
	public $origin;
	public $cause;
    public $debugTrace;
	
	function __construct($message="" , $code=0, $previous=NULL) {
		if (method_exists($this, 'getPrevious')) {
			parent::__construct($message, 0, $previous);	
		} else {
			parent::__construct($message, 0);
			$this->cause = $previous;
		}
		$this->code = $code;
		if (PntError::storeDebugTrace()) {
			$this->debugTrace = debug_backtrace(); //includes object elements
			if (isSet($this->debugTrace[1]['object']))
				$this->origin = $this->debugTrace[1]['object'];
		}
	}
	
	static function storeDebugTrace($aBoolean=null) {
		static $storeDebugTrace;
		if ($aBoolean !== null)
			$storeDebugTrace = $aBoolean;
		return $storeDebugTrace;
	}
	
	/** Replacement for getPrevious that also works on PHP versions < 5.3.0 */
	function getCause() {
		return method_exists($this, 'getPrevious')
			? $this->getPrevious()
			: $this->cause;
	}
	
	/** Replacement for getTrace that includes object elements */
	function getDebugTrace() {
		return isSet($this->debugTrace) 
			? $this->debugTrace
			: $this->getTrace();
	}

	// ------------------------- old pntError

	/** @return Object the object that created the exception 
	* (and probably threw it) 
	* WARNING: this value is only available if PntError::storeDebugTrace()
	* */
	function getOrigin() {
		return $this->origin;
	}

	function setOrigin($value) {
		$this->origin = $value;
	}

	/** @depricated */
	function getErrorTypeLabel() {
		return get_class($this);
	}
	
	function getCauseDescription() {
		$cause = $this->getCause();
		if ($cause)
			return $cause->getMessage();
			
		return '';
	}

	function getLabel() {
		$result = $this->getMessage();
		$causeDescription = $this->getCauseDescription();
		if ($causeDescription) 
			$result .= " because: $causeDescription";
		return $result;
	}
}
?>