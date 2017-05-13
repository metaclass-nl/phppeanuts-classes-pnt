<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntFilterFormPart', 'pnt/web/parts');

/** Part used by PntObjectMtoNSearchPage.
* Specialization of PntFilterFormPart that produces a smaller layout
* to fit into the smaller space available to PntObjectMtoNSearchPage
*
* This abstract superclass provides behavior for the concrete
* subclass MtoNFilterFormPart in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/parts
*/
class PntMtoNFilterFormPart extends PntFilterFormPart {

	function printAdvancedFilterDiv($num) {
		$this->includeSkin('MtoNAdvFilterDiv', $num);
	}

}
?>