<?php
/* Copyright (c) MetaClass, 2003-2017

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntDao', 'pnt/db/dao');

/** Objects of this class are used for generating and executing database queries.
* This class is the database abstraction layer for mySQL.   
* @see http://www.phppeanuts.org/site/index_php/Pagina/50
*
* This abstract superclass provides behavior for the concrete
* subclass QueryHandler in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @package pnt/db/dao
*/
class PntMysqlDao extends PntDao {
	
	/** connect can take long to fail on PHP5.3.0, and on windows 
	 * the time spent is counted as execution time. This value will be set 
	 * using set_time_limit if time_limit is lower on windows */
	public $winMinTimeLimit = 65;
	
	function getDbmsName() {
		return 'mysql';
	}

	function _runQuery($error="Query error") {
		global $queryCount;
		$queryCount++;
		$this->error = null;
//print "<BR>\n $queryCount $this->query";
		$sql = $this->replacePlaceholders();
		if ($this->error) return; //wrong parameter count
		
		$this->result = mysql_query($sql, $this->dbSource);
		if ($this->result) {
			if (strtolower(substr(trim($this->query),0,6))=="select") {			
				$this->rowCount = mysql_num_rows($this->result);
			} else {
				$this->logQuery($sql);
				$this->rowCount = mysql_affected_rows($this->dbSource);
				if (strtolower(substr(trim($sql),0,11))=="insert into") {			
					$this->insertId = mysql_insert_id($this->dbSource);
				}
			}
			$this->rowIndex = 0;
		} else {
			$this->error = $error."<BR>$sql<BR>".mysql_error($this->dbSource);
			$this->errNo = mysql_errno($this->dbSource);
			$this->rowIndex = null;
//			print $this->error;
		}
		$this->clearParams();
	}
	
	function logQuery($sql) {
		//default is not to log
	}
	
	/* Return the field names.
	* this method resets record pointer
	*/
	function getFieldNames() {
			
		if ($this->rowCount>0) {
			$row=mysql_fetch_assoc($this->result);
			$names = array_keys($row);
			$this->columnCount=count($names);
			//restore result to first record 
			$this->dataSeek(0);
			return $names;
		}			
	}
	
	function dataSeek($index) {
		mysql_data_seek($this->result,$index);
	}
	
	/* Ececutes the query and returns the value of the first column of the first row
	* or null if no row.
	* !does no longer reset the record pointer
	* @throws PntDbError
	*/
	function getSingleValue($query='', $error="Query error") {
		global $queryCount;
		$queryCount++;
		
		if ($query)
			$this->query = $query;
		$this->_runQuery($error);
		
		if ($this->error) {
			throw new PntDbError($this->error, $this->errNo);
		}	
		if ($this->rowCount>0) {
			$row=mysql_fetch_row($this->result);
			return $row[0];	
		} 
		return null;
	}
	
	function convertToSql($value) {
		//date and timestamp are actually represented as strings

		if ($value === null) return "NULL";
		if ($value === true) return "'1'";
		if ($value === false) return "'0'";

		//allways use magic quotes in sql
		return "'".mysql_real_escape_string($value)."'";
	}

	function convertConditionArgumentToSql($value) {
		//date and timestamp are actually represented as strings

		if ($value === null) return "NULL";
		if ($value === true) return "'1'";
		if ($value === false) return "'0'";

		//allways use magic quotes in sql
		return "'".mysql_real_escape_string(Gen::labelFrom($value))."'";
	}

	function quote($string) {
		return "'".mysql_real_escape_string($string, $this->dbSource)."'";
	}

	/** Gets rows starting at current pointer position 
	* !Does no longer Reset record pointer
	* @param number $max The maximum number of rows to get. If null all remaining rows are returned.
	* Returns an array of associative row arrays (indexed[rowIndex][rowName])
	*/
	function getAssocRows($max=null) {
		$result = array();
		if ($this->rowCount>0) {
			$i = 0;
			while ( ($max === null || $i < $max) && ($row = mysql_fetch_assoc($this->result)) ) {
				$result[] = $row;
				$i++;
			}
		}
		return $result;						
	}
	
	/** Gets next row as associative array, or false if none */
	function getAssocRow() {
		$row = mysql_fetch_assoc($this->result);
		if ($row) $this->rowIndex++; 
		else $this->rowIndex = null;
		return $row;
	}
	
	function getRow() {
	    $row = mysql_fetch_row($this->result);
	    if ($row) $this->rowIndex++;
	    else $this->rowIndex = null;
	    return $row;
	}
	
	function release() {
		if ($this->result)
			mysql_free_result($this->result);
	}
	
	/** Connects to the DBMS and selects the database 
	* Stores the resulting resource on $this->connection
	* PRECONDITION: $this->connection must be a valid DatabaseConnection instance
	*/
	function connect() {
		//connect can take long to fail on PHP5.3.0 and on windows the time spent is counted as execution time 
		if (strtoupper(substr(php_uname('s'), 0, 3)) == 'WIN'
				&& ini_get('max_execution_time') < $this->winMinTimeLimit)
			set_time_limit($this->winMinTimeLimit);

		$hostport=$this->connection->getHost(). ':'. $this->connection->getPort();
		$resource = mysql_connect($hostport,$this->connection->getUsername(),$this->connection->getPassword(), true);
		if (!$resource) {
			$this->error = 'Connection failed: ' . mysql_error();
			trigger_error($this->error, E_USER_ERROR);
		}
		$this->dbSource = $resource;
		$this->connection->setDBSource($this->dbSource);
		mysql_select_db($this->connection->getDatabaseName(),$this->dbSource);
		$result = function_exists('mysql_set_charset') 
			 ? mysql_set_charset($this->connection->getCharset(), $this->dbSource)
			 : false;
		if (!$result && $this->connection->getCharset() != 'latin1') {
			$this->error = 'Could not set character set';
			trigger_error($this->error, E_USER_WARNING);
		}
	}
	
	function beginTransaction() {
		mysql_unbuffered_query('BEGIN', $this->dbSource);
		$errNr = mysql_errno();
		if (!$errNr) return;
		
		Gen::includeClass('PntDbError', 'pnt/db');
		throw new PntDbError(mysql_error($this->dbSource), $errNr);
	}

	function commit() {
		mysql_unbuffered_query('COMMIT', $this->dbSource);
		$errNr = mysql_errno();
		if (!$errNr) return;
		
		Gen::includeClass('PntDbError', 'pnt/db');
		throw new PntDbError(mysql_error($this->dbSource), $errNr);
	}
	
	function rollBack() {
		mysql_unbuffered_query('ROLLBACK ', $this->dbSource);
		$errNr = mysql_errno();
		if (!$errNr) return;
		
		Gen::includeClass('PntDbError', 'pnt/db');
		throw new PntDbError(mysql_error($this->dbSource), $errNr);
	}
	
}
?>