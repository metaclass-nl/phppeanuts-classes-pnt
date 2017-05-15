<?php
/* Copyright (c) MetaClass, 2003-2017

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntDao', 'pnt/db/dao');

/** Objects of this class are used for generating and executing database queries.
* This class implements access through the Php Data Object (PDO) interface.   
* Instances of this class use the default cursor type, which is forward-only
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
class PntPdoDao extends PntDao {
	
	/** Holds parameter values that have been collected through 
	* calls to ::convertToSql and ::convertConditionArgumentToSql
	*/
	public $parameters;
	/** PDOStatement Prepared statement */
	public $statement;
	public $preparedQuery;
	
	function __construct($connection=null) {
		$this->parameters = array();
		return parent::__construct($connection);
	}
	
	function getDbmsName() {
		return substr($this->connection->getDsnPrefix(),0, -1);
	}

	/** @return boolean wheather this does support rowcount with SELECT
	* For some drivers/dsbs this may actually depend on the cursor type used.
	* If this function returns true, dataSeek should be supported too
	*/
	function supportsSelectRowCount() {
		return false;
	}

	function _runQuery($error="Query error") {
		global $queryCount;
		$queryCount++;
//print "<BR>\n $queryCount $this->query";
		if ($this->statement && $this->preparedQuery) 
			$this->statement->closeCursor();
		$this->error = null;
		if (empty($this->parameters)) {
			$this->preparedQuery = null;
			$this->statement = $this->dbSource->query($this->query);
			$this->result = (boolean) $this->statement;
		} else {
			//PROBLEM: if : or ? inside quotes, mysql driver tries to bind parameters
			//  therefore prepared statements must not contain quoted strings
			if (!$this->statement || $this->preparedQuery != $this->query) {
				$this->statement = $this->dbSource->prepare($this->query);
				$this->preparedQuery = $this->query;
			}
			$this->result = $this->statement->execute($this->parameters); //problem: does not work for LIMIT params
		}				
		
		if ($this->result) {
			$this->rowCount = $this->statement->rowCount();
			$this->rowIndex = 0;
//printDebug($this);
		} else {
			$this->error = $error;
			$this->errorInfo = $this->statement 
				? $this->statement->errorInfo() 
				: $this->dbSource->errorInfo();
			if ($this->errorInfo) {
				$this->error .= ' '. $this->errorInfo[2];
				$this->errNo = $this->errorInfo[0];
			}
			$this->error .= "<BR>$this->query<BR>". Gen::toString($this->parameters, 20);
			$this->rowIndex = null;
//print $this->error;
		}
		$this->clearParams();
	}

	/* Return the field names. Initializes $this->columnCount. 
	* Will not work if driver does not support getColumnMeta 
	*/
	function getFieldNames() {
		$this->columnCount=$this->statement->columnCount();
		$this->columnsMeta = array();
		$result = null; //if no meta data null is returned
		for ($i=0; $i<$this->columnCount; $i++) {
			$meta = $this->statement->getColumnMeta($i);
			if ($meta) {
				$result[] = $meta['name'];
				$this->columnsMeta[] = $meta;
			}
		}
		return $result;
	}

	/** Return the id of the new record after an insert, 
	* or the last value from a sequence object, depending on the underlying driver
	* @param String $sequenceName name of sequence */
	function getInsertId($sequenceName=null) {
		return $sequenceName === null
			? $this->dbSource->lastInsertId()
			: $this->dbSource->lastInsertId($sequenceName);
	}

	/** Not supported */
	function dataSeek($index) {
		trigger_error('data seek is not supported', E_USER_WARNING);
	}

	/** Ececutes the query and returns the value of the first column of the first row
	* or null if no row.
	* !does no longer reset the record pointer
	*/
	function getSingleValue($query='', $error="Query error") {
		if ($query) $this->query = $query;
		$this->_runQuery($error);
		
		if ($this->error) {
			throw new PntDbError($this->error, $this->errNo);
		}	
		//fetchColumn returns false if no next row, but whe have no way
		//to distinguish it from false actually being te retrieved value
		//so we use fetch here
		$row = $this->statement->fetch(PDO::FETCH_NUM);
		if ($row) {
			$this->rowIndex++; 
			return $row[0];	
		} 
		$this->rowIndex = null;
		return null;
	}
	
	/** Return the $value quoted and with special characters escaped
	* Not all PDO drivers implement this method (notably PDO_ODBC). 
	* For those drivers subclasses will have to be used overriding this method
	*/
	function quote($value) {
		return $this->dbSource->quote($value);
	}
	
	static function getParamType($prop) {
		//	'string' => PDO::PARAM_STR
		//	, 'boolean', PDO::PARAM_BOOL 
		
		return PDO::PARAM_STR;
	}
	

	/** Gets rows starting at current rerord position 
	* @param number $max The maximum number of rows to get. If null all remaining rows are returned.
	* Returns an array of associative row arrays (indexed[rowIndex][rowName])
	*/
	function getAssocRows($max=null) {
		if ($this->rowIndex===null) return array(); //all have been retrieved
		
		if (!$this->statement) throw new PntDbError('No statement');
		if ($max !== null || $this->rowIndex) return parent::getAssocRows($max);
		
		$this->rowIndex = null;
		return $this->statement->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/** Gets next row as associative array, or false if none */
	function getAssocRow() {
		$row = $this->statement->fetch(PDO::FETCH_ASSOC);
		if ($row) $this->rowIndex++; 
		else $this->rowIndex = null;
		return $row;
	}

	/** Gets next row as associative array, or false if none */
	function getRow() {
		$row = $this->statement->fetch(PDO::FETCH_NUM);
		if ($row) $this->rowIndex++; 
		else $this->rowIndex = null;
		return $row;
	}
	
	function release() {
		if (!$this->result) return;
		
		$this->statement->closeCursor();
		$this->result = false;
		$this->statement = null;
	}
	
	/** Connects to the DBMS and selects the database 
	* Stores the resulting resource on $this->connection
	* PRECONDITION: $this->connection must be a valid DatabaseConnection instance
	*/
	function connect() {
		try {
			$pdo = new PDO($this->connection->getDsn(),$this->connection->getUsername(),$this->connection->getPassword());
		} catch (PDOException $e) {
			$this->error = 'Connection failed: ' . $e->getMessage();
			trigger_error($this->error, E_USER_ERROR);
		}
	
		$this->dbSource = $pdo;
		$this->connection->setDBSource($pdo);
	}

	function beginTransaction() {
		if ($this->dbSource->beginTransaction()) return;
		
		Gen::includeClass('PntDbError', 'pnt/db');
		$errCode = $this->dbSource->errorCode();
		$errorInfo = $this->dbSource->errorInfo();
		throw new PntDbError(isSet($errorInfo[2]) ? $errorInfo[2]: 'Error beginning transaction', $errCode);
	}

	function commit() {
		if ($this->dbSource->commit()) return;

		Gen::includeClass('PntDbError', 'pnt/db');
		$errorInfo = $this->dbSource->errorInfo();
		$errCode = $this->dbSource->errorCode();
		throw new PntDbError(isSet($errorInfo[2]) ? $errorInfo[2]: 'Error committing transaction', $errCode);
	}
	
	function rollBack() {
		if ($this->dbSource->rollBack()) return;
		
		Gen::includeClass('PntDbError', 'pnt/db');
		$errorInfo = $this->dbSource->errorInfo();
		$errCode = $this->dbSource->errorCode();
		throw new PntDbError(isSet($errorInfo[2]) ? $errorInfo[2]: 'Error roling back transaction', $errCode);
	}

}
?>