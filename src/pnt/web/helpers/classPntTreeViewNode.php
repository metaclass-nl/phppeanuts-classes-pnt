<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

/** @package pnt/web/helpers
*/
class PntTreeViewNode {

	public $parent;
	public $children;
	public $peanut;
	public $count;

	function __construct() {
		$this->children = array();
	}
	
}
?>