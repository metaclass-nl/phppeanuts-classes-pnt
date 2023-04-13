<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntError', 'pnt');

/**
 * Similar to ErrorException, @see http://www.php.net/manual/en/class.errorexception.php
 * @package pnt
 */
class PntErrorException extends PntError {

    public $severity;

	 function __construct($message, $code, $level, $filePath, $lineNumber) {
	 	parent::__construct($message, $code);
	 	$this->severity = $level;
        $this->file = $filePath;
        $this->line = $lineNumber;
	 }

	 function getSeverity() {
	 	return $this->severity;
	 }
	
	
}

?>