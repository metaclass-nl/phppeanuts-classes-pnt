<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


/** Superclass for evaluation objects.
* Specifies and performs a call of a regular funcions (not a method).
* The advantage of evaluation objects is that they can be handed around, 
* manipulated and be part of a data structure, to be executed on demand
* @package pnt/meta
*/
class PntEvaluation {	
	public $key;	
	
	/** get an instance of the proper subclass for 
	* evaluation with the specified key
	*
	* @static
	* @param key String the name of the func tion	
	* 	WARNING, do not set the func tion name from untrusted data as that could allow users to call insecure
	* 	or harmfull func tions
	* @return PntEvaluation
	*/   
	static function getInstance($key) {
		$evalObj = new PntEvaluation();
		$evalObj->setKey($key);
		return $evalObj;
	}

	function getKey() {
		return $this->key;
	}
	
	/** Sets the name of the func tion that will be called by ::evaluate
	 * @param string $value method name
	 * 	WARNING, do not set the func tion name from untrusted data as that could allow users to call insecure
	 * 	or harmfull func tions
	 */   
	function setKey($value) {
		$this->key = $value;		
	}	
	
	function getLabel() {
		return "::".$this->getKey();
	}
	 
	function getClass() {
		return get_class($this);
	}

	/** calls the func tion
	* @argument mixed $item the parameter of the func tion
	* @return mixed whatever the evaluated func tion returns
	* @throws whatever the evaluated function throws
	*/
	function evaluate($item, $extra1=null, $extra2=null, $extra3=null) {
		return call_user_func($this->getKey(), $item, $extra1, $extra2, $extra3); //warning in method comment ::setKey and ::__construct
	}
	
	/* Return the type of the navigation result according to the metadata
	* If no metadata, return null
	* @result String @see PntPropertyDescriptor::getType
	*/
	function getResultType() {
		// no metadata..
		return null;
	}
	
	function getLastProp()
	{
		// no metadata..
		return null;
	}
	
	/** @return String representation for debugging purposes */
	function __toString() {
		//combine class name and label
		$label = $this->getLabel();
		return get_class($this)."($label)";
    }

	/** @depricated */
	function toString() {
		return (string) $this;
	}

	/** @depricated */
	static function _getInstance($key) {
		return PntEvaluation::getInstance($key);
	}

	/** @depricated */
	function _evaluate($item) {
		try {
			return $this->evaluate($item);
		} catch (PntError $err) {
			return $err;
		}
	}
}
?>