<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntSqlCombiFilter', 'pnt/db/query');

/** Specifies the combination of mutliple PntSqlFilters by OR. 
* Used by FilterFormPart in the simple search.
* part for navigational query specification, part of a PntSqlSpec
* @see http://www.phppeanuts.org/site/index_php/Pagina/170
*
* PntSqlFilters produce what comes after the WHERE clause to retrieve
* some objects as well as a JOIN clause to access related tables.
* Objects of this class combine the JOIN clauses from multiple PntSqlFilters
* from $this->parts and combine their WHERE expressions using their combinator field
*
* Current version is MySQL specific. In future, all SQL generating methods should 
* delegate to PntQueryHandler to support other databases
* @package pnt/db/query
*/
class PntSqlMultiOrFilter extends PntSqlCombiFilter {

	public $combinator = 'OR';

}
?>