<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntTextAreaWidget', 'pnt/web/widgets');

/** FormWidget that generates html specifying a TextArea.
 * Not Yet Implemented
*
* This abstract superclass provides behavior for the concrete
* subclass HtmlWidget in the widgets classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/widgets
*/
class PntHtmlWidget extends PntTextAreaWidget {

	public $cssClass = 'pntHtmlWidget';
	
	function getName() {
		return 'HtmlWidget';
	}

}