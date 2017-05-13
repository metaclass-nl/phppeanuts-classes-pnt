<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntXmlTextPart', 'pnt/web/dom');

/** @package pnt/web/dom
*/
class PntXmlTotalText extends PntXmlTextPart {

	public $sum = 0;
	public $count = 0;
	public $bag= array();
	
	function totalize($value) {
		if ($this->contentType == 'number') {
			$this->sum += $value;
		} else {
			if (is_object($value))
				$value = Gen::is_a($value, 'PntDbObject')
					? $value->getOid()
					: Gen::valueToString($value);
			if (isSet($this->bag[$value])) 
				$this->bag[$value]++;
			else 
				$this->bag[$value]=0;
		}
		$this->totalizeContent($value);
		$this->count += 1;
	}
	
	function totalizeContent($value) {
		if ($this->contentType == 'number') 
			$this->content = $this->sum;
		else
			$this->content = count(array_keys($this->bag));
	}
	
	function getContentType() {
		return 'number';
	}
}
?>