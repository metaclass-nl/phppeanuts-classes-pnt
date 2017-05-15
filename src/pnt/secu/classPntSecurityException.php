<?php
/* Copyright (c) MetaClass, 2003-2013

Distributed and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntError', 'pnt');

/** PntError PntError that signals a possible security threat. Caught by pnt.web.PntSite::handleRequest 
 * that will call logout and retrhow the exception so that it will be logged.
* @package pnt/secu
*/
class PntSecurityException extends PntError {

}
?>