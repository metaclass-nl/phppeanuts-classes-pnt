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
class PntSqliteDao extends PntDao {
	
	function getDbmsName() {
		return 'sqlite';
	}

	function _runQuery($error="Query error") {
		global $queryCount;
		$queryCount++;
		
		$this->error = null;
		$this->rowIndex = 0;
//print "<BR>\n $queryCount $this->query";
		$sql = $this->replacePlaceholders();
		if ($this->error) return; //wrong parameter count
		
		$select = strtolower(substr(trim($sql),0,6))=="select";
		
		$this->result = $select
			? sqlite_query($this->dbSource, $sql, SQLITE_NUM, $this->error)
			: sqlite_exec($this->dbSource, $sql, $this->error);
		if ($this->result !== false) {
			if ($select) {			
				$this->rowCount = sqlite_num_rows($this->result);
				$this->initFieldNames();
			} else {
				$this->rowCount = sqlite_changes($this->dbSource);
				if (strtolower(substr(trim($sql),0,11))=="insert into") {			
					$this->insertId = sqlite_last_insert_rowid($this->dbSource);
				}
			}
		} else {
			$this->errNo = sqlite_last_error($this->dbSource);
			$this->error = $error."<BR>$this->query<BR>".$this->error;
//			print $this->error;
		}
		$this->clearParams();
	}
	
	/* Return the field names.
	*/
	function getFieldNames() {
		return $this->fieldNames;
	}
	
	function initFieldNames() {
		$this->fieldNames = array();
		$this->columnCount = sqlite_num_fields($this->result);
		for ($i = 0; $i<$this->columnCount; $i++) {
			 $prefixed = sqlite_field_name($this->result, $i);
			 $dotPos = strrpos($prefixed, '.');
			 $this->fieldNames[$i] = $dotPos 
			 	? substr($prefixed, $dotPos+1)
			 	: $prefixed;
		}
	}

	function dataSeek($index) {
		sqlite_seek($this->result,$index);
	}
	
	/* Ececutes the query and returns the value of the first column of the first row
	* or null if no row.
	* !does no longer reset the record pointer
	*/
	function getSingleValue($query='', $error="Query error") {
		if ($query) $this->query = $query;
		$this->_runQuery($error);
		
		if ($this->error)
			throw new PntDbError($this->error, $this->errNo);
		
		if ($this->rowCount>0) {
			$value=sqlite_fetch_single($this->result);
			$this->rowIndex++; 
			return $value;	
		}
		$this->rowIndex = null;
		return null;
	}
	
	function convertToSql($value) {
		//date and timestamp are actually represented as strings

		if ($value === null) return "NULL";
		if ($value === true) return "'1'";
		if ($value === false) return "'0'";

		//allways use magic quotes in sql
		return "'".sqlite_escape_string($value)."'";
	}

	function convertConditionArgumentToSql($value) {
		//date and timestamp are actually represented as strings

		if ($value === null) return "NULL";
		if ($value === true) return "'1'";
		if ($value === false) return "'0'";

		//allways use magic quotes in sql
		return "'".sqlite_escape_string(Gen::labelFrom($value))."'";
	}

	function quote($string) {
		return "'".sqlite_escape_string($string)."'";
	}

	
	/** Gets next row as associative array, or false if none 
	* Associative rows must not be prefixed, like the ones resulting from SQLITE_ASSOC.
	* Therefore they are built from php using $this->fieldNames which is initialized in _runQuery
	* @return Array Associative array with data from row fetch
	*/
	function getAssocRow() {
		$numRow = sqlite_fetch_array($this->result, SQLITE_NUM);
		if ($numRow === false) return false;
		
		$result = array();
		reset($this->fieldNames);
		forEach ($this->fieldNames as $i => $fieldName)
			$result[$fieldName] = $numRow[$i];
		return $result;
	}
	
	function getRow() {
		return sqlite_fetch_array($this->result, SQLITE_NUM);
	}
	
	/** Adds fieldProperties to the object for the columns from the database.
	* This method assumes column names to be equal to the names 
	* of their corresponding field properties. 
	* @param PntDbObject $obj the object to add fieldProperties to
	* @param String $tableName the name of the table whose columns to use.
	* @param array $includeList names of properties to include
	*	If omitted, all columns will be used in the order they appear in the table, 
	* 	but if a fieldProperty is already defined it is left untouched.
	* @return array propertyDescriptors that where added, by property name
	*/
	function addFieldPropsTo_table($obj, $tableName, $includeList=null) {

	}
	
	function release() {
		//ignore
	}
	
	/** Connects to the DBMS and selects the database 
	* Stores the resulting resource on $this->connection
	* PRECONDITION: $this->connection must be a valid DatabaseConnection instance
	*/
	function connect() {
		if ($$this->connection->getCharset() == 'utf8')
			trigger_error('Only single byte character sets are supported', E_USER_WARNING);
		$err = null;
		$result = sqlite_open($this->connection->getHost(), 0666, $err);
		if ($result === false) {
			$this->error = 'Connection failed: ' . $err;
			trigger_error($this->error, E_USER_ERROR);
		}
		$this->dbSource = $result;
		$this->connection->setDBSource($result);
	}

	function beginTransaction() {
		sqlite_exec($this->dbSource, 'BEGIN', $this->error);
		if (!$this->error) return;
		
		Gen::includeClass('PntDbError', 'pnt/db');
		throw new PntDbError($this->error, sqlite_last_error($this->dbSource));
	}

	function commit($seconds=15) {
		for ($i=0; $i<$seconds; $i++) {
			sqlite_exec($this->dbSource, 'COMMIT', $this->error);
			$errNr = sqlite_last_error($this->dbSource);
			if (!$errNr) return;

			if ($errNr != SQLITE_BUSY) {
				Gen::includeClass('PntDbError', 'pnt/db');
				throw new PntDbError($this->error, $errNr);
			}
			sleep(1);
		}
		Gen::includeClass('PntDbError', 'pnt/db');
		throw new PntDbError('Transaction commit failed - Busy too long', $errNr);
	}
	
	function rollBack() {
		sqlite_exec($this->dbSource, 'ROLLBACK', $this->error);
		if (!$this->error) return;

		Gen::includeClass('PntDbError', 'pnt/db');
		throw new PntDbError($this->error, sqlite_last_error($this->dbSource));
	}

}
?>