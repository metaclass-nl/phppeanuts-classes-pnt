<?php
/* Copyright (c) MetaClass, 2003-2013

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntSecurityException', 'pnt/secu');

/** PntError PntSecurityException that is thown when a validation failure can not be caused by the user 
* entering invalid data nor by non-existence of domain objects. The two remaining causes ar:
* - a bug in the software that created the url, form or AJAX request or
* - a user manipulating with the request, possibly to find exploits (this includes the use of exploit scanners)
* @package pnt/secu
*/
class PntValidationException extends PntSecurityException {

}
?>