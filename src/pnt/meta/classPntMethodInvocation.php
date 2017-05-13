<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

	
Gen::includeClass('PntEvaluation', 'pnt/meta');

/** An object of this class represents method call. 
* it can make the call too.
* @package pnt/meta
*/
class PntMethodInvocation extends PntEvaluation{		
	public $receiver;	
	
	/** get an instance of the proper subclass for 
	* evaluation with the specified key
	*
	* @static
	* @param key String the name of the method 	
	* @param receiver Object the object who's method will be invocated
	* @return PntMethodInvocation
	*/   
	static function getInstance($key, $receiver) {
		$obj = new PntMethodInvocation();
		$obj->setKey($key);
		$obj->setReceiver($receiver);
		return $obj;
	}	

	function getReceiver() {
		return $this->receiver;
	}	
	
	function setReceiver($value) {
		$this->receiver = $value;		
	}	
	
	function getLabel() {
		return Gen::toString($this->getReceiver())."::".$this->getKey();
	}
	
	/** calls the method
	* @argument mixed $item the parameter of the method	
	* @return mixed whatever the evaluated method returns
	* @throws whatever the evaluated method throws
	*/
	function evaluate($item, $extra1=null, $extra2=null, $extra3=null) {		
		$obj = $this->getReceiver();
		$func = $this->getKey();						
		return $obj->$func($item, $extra1, $extra2, $extra3);
	}

	/** @depricated */
	static function _getInstance($key, $receiver) {
		return PntMethodInvocation::getInstance($key, $receiver);
	}
}
?>