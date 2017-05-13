<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntDbError', 'pnt/db');

/** Objects of this class are used for generating and executing database queries.
* This class is the superclass for common functionality that applies to most of the databases
* Currently only subclasses for mySQL and SqlLight are available, but
* it is easy to add subclasses for the databases you need, als long as 
* these databases support explicit LEFT JOIN syntax (Oracle does not).
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
class PntDao {
	public $connection; //instance of DatabaseConnection
	public $query = '';
	public $rowCount=0; //assigned in _runQuery -- before 1.2 beta 1 this was: $aantalRecords 
	public $columnCount=0;  //assigned in getFieldNames() -- before 1.2 beta 1 this was: $aantalVelden 
	public $result;		  //assigned in _runQuery
	public $insertId;        //assigned in _runQuery
	public $error;           //errorMessage assigned in _runQuery
	public $errNo = 0; 		//assigned in _runQuery
	public $parameters;    //assigend in clearParams
	// we did not factor out the default error message because it is not meant for the end users anyway
	/** Index of current row (the one that can be fetched) in resultSet */
	public $rowIndex;
	/** $dbSource cache, see PntDatabasenConnection::getDBSource() */
	public $dbSource;
	
	function __construct($connection=null) {
		if ($connection)
			$this->setConnection($connection);
		else
			$this->setDefaultConnection();
		$this->clearParams();
	}
	
	function getDbmsName() {
		trigger_error('getDbmsName method should have been implemented by a subclass', E_USER_ERROR);
	}
	
	/** @return boolean wheather this does support rowcount for SELECT results
	* For some drivers/dsbs this may actually depend on the cursor type used.
	* If this function returns true, dataSeek should be supported too
	*/
	function supportsSelectRowCount() {
		return true;
	}

	function runQuery($query='', $error="Query error") {
		if ($query)
			$this->query = $query;
		$this->_runQuery($error);
		if (!$this->error) return;

		throw new PntDbError($this->error, $this->errNo);
	}

	function _runQuery($error="Query error") {
		trigger_error('_runQuery method should have been implemented by a subclass', E_USER_ERROR);
	}
	
	/* Return the field names.
	* this method may reset record pointer
	*/
	function getFieldNames() {
		trigger_error('getFieldNames method should have been implemented by a subclass', E_USER_ERROR);
	}
	
	/** Number of affected rows with INSERT, UPDATE or DELETE. 
	* If supportsSelectRowCount returns number of selected rows
	*/
	function getRowCount() {
		return $this->rowCount;	
	}
	
	function getColumnCount() {
		return $this->columnCount;	
	}

	/** Return the error message or null if no error */
	function getError() {
		return $this->error;
	}
	
	function getErrNo() { 
		return $this->errNo;
	}
	
	function dataSeek($index) {
		trigger_error('dataSeek method should have been implemented by a subclass', E_USER_ERROR);
		//trigger_error('data seek is not supported', E_USER_WARNING);
	}
	
	/* Ececutes the query and returns the value of the first column of the first row
	* or null if no row.
	* !does no longer reset the record pointer
	*/
	function getSingleValue($query='', $error="Query error") {
		trigger_error('getSingleValue method should have been implemented by a subclass', E_USER_ERROR);
	}
	
	/** Return the id of the new record after an insert */
	function getInsertId() {
		return $this->insertId;	
	}
	
	/** Return an new array with prefixes added to the supplied columnNames 
	* separate prefix and columnname by the separarator appropriate 
	* for this database (usually a dot).
	* Retain the keys so that if the columnNames array is a fielMap, 
	* the result will map the fields to prefixed columnNames
	* @param $colNames Array with columnnames as the values
	* @param $prefix, usually the table name
	* @result Array with prefixed columnNames
	*/
	function prefixColumnNames($colNames, $prefix) {
		$result = array();
		reset($colNames);
		while (list($key, $name) = each($colNames)) 
			$result[$key] = "$prefix.$name";
		return $result;
	}

	/** append a SQL string to the query field. Return the added SQL.
	* @param columnNames Array with columnNames. If the names need to be prefixed, they must already be prefixed.
	* @param tableName String May also hold a String with a Join of tablenames
	*/
	function select_from($columnNames, $tableName, $distinct=false) {	
		$sql  = 'SELECT ';
		if ($distinct) $sql .= 'DISTINCT ';
		$sql .= implode(', ', $columnNames);
		$sql .= " FROM $tableName";
		
		$this->query .= $sql;
		
		return $sql;
	}
		
	/** append a SQL string to the query field. Return the added SQL.
	 * As of version 2.1 this method assumes paramerized queries.
	* @param string columnName The name of the column. If the name needs to be prefixed, it must already be prefixed.
	* @param mixed $value 
	* @param mixed $placeholder to use (parameterized queries)
	*/
	function where_equals($columnName, $value, $placeholder='?') {
		$sql = " WHERE ($columnName ";
		$sql .= $value === null ? "IS " : "= ";
		$sql .= $this->param($value, $placeholder);  // $this->convertConditionArgumentToSql($value);
		$sql .= ')';
	
		$this->query .= $sql;
		
		return $sql;
	}
	
	/** append a SQL string to the query field. Return the added SQL.
	* @param number $rowCount The maximum number of rows to retrieve
	* @param number $offset The index of the first row to retrieve
	*/
	function limit($rowCount, $offset=0) {
		$sql = " LIMIT ";
		$sql .= (int) $offset;
		$sql .= ", ";
		$sql .= (int) $rowCount;
		$this->query .= $sql;
		return $sql;
	}

	function joinAllById($tableMap, $baseTable) {
		$sql = '';
		reset($tableMap);
		while (list($table) = each($tableMap))
			if ($table != $baseTable)
				$sql .= "\n INNER JOIN $table ON $table.id = $baseTable.id";
		$this->query .= $sql;
		return $sql;
	}


	function in($columnName, $values) {
		$sql = " ($columnName IN (";
		reset($values);
		$multi = false;
		while (list($key, $value) = each($values)) {
			if ($multi) $sql .= ', '; 
			else $multi = true;
			$sql .= $this->convertConditionArgumentToSql($value);
		}
		$sql .= '))';

		$this->query .= $sql;
		return $sql;
	}

	function convertToSql($value) {
		//date and timestamp are actually represented as strings

		if ($value === null) return "NULL";
		if ($value === true) return "TRUE";
		if ($value === false) return "FALSE";

		//allways use magic quotes in sql
		return $this->quote($value);
	}

	function convertConditionArgumentToSql($value) {
		//date and timestamp are actually represented as strings
		//relies on automatic type conversion for integers and numbers

		if ($value === null) return "NULL";
		if ($value === true) return "TRUE";
		if ($value === false) return "FALSE";

		//allways use magic quotes in sql
		return $this->quote(Gen::labelFrom($value));
	}
	
	function quote($string) {
		trigger_error('quote method should have been implemented by a subclass', E_USER_ERROR);
	}

	/** Set the query field to a SQL string that inserts the specified object field values in the database.
	* @param $anObject Object whose field values need to be saved
	* @param $tableName String
	* @param $fieldMap Associative Array mapping fieldName to columnName
	*/
	function setQueryToInsertObject_table_fieldMap($anObject, $tableName, $fieldMap) {
		$sep = '';
		$columns = '';
		$values = '';
		reset($fieldMap);
		forEach($fieldMap as $field => $column) {
			//insert of a not-new object is assumed to be insert in secondary table
			if ($field != 'id' || (!$anObject->isNew()) ) {
				$columns .= $sep;
				$columns .= $column;
				$values .= $sep;
				$values .= $this->param(isSet($anObject->$field) ? $anObject->$field : null);
				$sep = ', ';
			}
		}
		$this->query = "insert into $tableName ($columns) VALUES ($values)";
	}

	/** Set the query field to a SQL string that saves the specified object field values in the database.
	* @param anObject Object whose field values need to be saved
	* @tableName String
	* @param fieldMap Associative Array mapping fieldName to columnName
	* @param insert wheather a record for the object should be inserted. If false the objects record will be updated
	*/
	function setQueryToSaveObject_table_fieldMap($anObject, $tableName, $fieldMap, $insert) {
		if ($insert) return $this->setQueryToInsertObject_table_fieldMap($anObject, $tableName, $fieldMap);

		$sql  = "update $tableName SET ";

		$sep = "";
		reset($fieldMap);
		forEach($fieldMap as $field => $column) {
			//insert of a not-new object is assumed to be insert in secondary table
			if ($field != 'id' ) {
				$sql .= $sep;
				$sql .= $column;
				$sql .= '=';
				$sql .= $this->param(isSet($anObject->$field) ? $anObject->$field : null);
				$sep = ', ';
			}
		}
		$this->query = $sql;
		$this->where_equals(
			$fieldMap['id'] // the column name for field 'id'
			, $anObject->id
		);
	}

	/** Set the query field to a SQL string that saves the specified object field values in the database. 
	* @param anObject Object whose field values need to be saved
	* @tableName String
	* @param columnMap Array 
	*/
	function setQueryToDeleteFrom_where_equals($tableName, $columnName, $value) {
		// MySQL implementation
		$this->query = 'DELETE FROM '. $tableName;
		$this->where_equals($columnName, $value);
	}		

	/** Add both the join clauses, the WHERE clause and eventual ORDER BY clause
	* from the suppleid SQL spec.
	* @paranm PntSqlSpec $spec Object that sepecifies the query and may generate the SQL
	*/
	function addSqlFromSpec($spec, $groupBy=false) {
		$extra = $spec->getExtraSelectExpressions();
		$this->query = str_replace(' FROM ', $extra. ' FROM ', $this->query);
		$this->query .= $spec->getSqlForJoin();
		$this->query .= "\n WHERE ";
		$this->query .= $spec->getSql_WhereToLimit($groupBy);
		$spec->addParamsTo($this);
//Gen::show($this->query); Gen::show($this->parameters);
	}

	/** Gets rows starting at current pointer position 
	* !!Does no longer reset recordpointer
	* @param number $max The maximum number of rows to get. If null all remaining rows are returned.
	* Returns an array of associative row arrays (indexed[rowIndex][rowName])
	*/
	function getAssocRows($max=null) {
		$result = array();
		$i = 0;
		while (  ($max === null || $i < $max) && ($row = $this->getAssocRow()) ) {
			$result[] = $row;
			$i++;
		}
		return $result;						
	}
	
	/** Gets next row as associative array, or false if none */
	function getAssocRow() {
		trigger_error('getAssocRow method should have been implemented by a subclass', E_USER_ERROR);
	}
	
	function release() {
		trigger_error('release method should have been implemented by a subclass', E_USER_ERROR);
	}
	
	/** @returns DatabaseConnection on which queries will be executed. 
	*/
	function getConnection() {
		return $this->connection;
	}
	
	/** Sets the DatabaseConnection
	* @param DatabaseConnection $value
	*/
	function setConnection($value) {
		$this->connection = $value;
		if ($this->connection)
			$this->dbSource = $this->connection->getDBSource();
	}
	
	function setDefaultConnection() {
		$this->setConnection(DatabaseConnection::defaultConnection());
	}
	
	/** Database specific code to make a connection with the database. 
	* Sets the resulting dbSource on the connection and in $this->dbSource
	*/
	function connect() {
		trigger_error('connect method should have been implemented by a subclass', E_USER_ERROR);
	}

	function beginTransaction() {
		trigger_error('beginTransaction method should have been implemented by a subclass', E_USER_ERROR);
	}

	function commit() {
		trigger_error('commit method should have been implemented by a subclass', E_USER_ERROR);
	}
	
	function rollBack() {
		trigger_error('rollBack method should have been implemented by a subclass', E_USER_ERROR);
	}

	/** Add parameter value for later use with statement execute.
	 * Instead of a type parameter the actual type of $value will be used for correct binding. 
	 * I.e. booleans will be bound as booleans, strings as strings etc.
	 * However, not all subclasses support type specific binding. (PntPdoDao binds all as string)  
	 * @param mixed $value value to add
	 * @param string $placeholder. default '?' 
	 * @return String placehoder 
	 */
	function param($value, $placeholder='?') {
		if ($placeholder=='?')
			$this->parameters[] = $value;
		else
			$this->parameters[$placeholder] = $value;
		return $placeholder;
	}

	function clearParams() {
		$this->parameters = array();
	}
	
	/** Parameter binding emulation. Replace the placeolders by converted parameter values 
	 * @return string sql
	 * sets $this->error if wrong parameter count */
	function replacePlaceholders() {
		if (empty($this->parameters)) return $this->query;
		
		$named = key($this->parameters) != 0;
		forEach ($this->parameters as $value)
			$converted[] = $this->convertToSql($value);
		
		if ($named) {
			$count = null;
			$result = str_replace(array_keys($this->parameters), $converted, $this->query, $count);
			if ($count != count($converted)) 
				return $this->error = count($converted). " parameters but $count placeholders replaced in SQL query";
		} else {
			$pieces = explode('?', $this->query);
			if (count($pieces) != count($converted) +1)
				return $this->error = count($converted). ' parameters but '. (count($pieces) - 1). ' placehoders in SQL query';

			$result = $pieces[0];
			for ($i=1; $i<count($pieces); $i++)
				$result .= $converted[$i - 1]. $pieces[$i];
		}
//Gen::show($result);
		return $result;
	}
	
	/** Probably only works with MySQL 
	* Adds fieldProperties to the object for the columns from the database.
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
		$result = array();
		$clsDes = $obj->getClassDescriptor();
		$existingProps = $clsDes->refPropertyDescriptors(); 

		$propsByColumnName = array();
		reset($existingProps);
		forEach(array_keys($existingProps) as $key) {
			$colName = $existingProps[$key]->isFieldProperty() 
				? $existingProps[$key]->getColumnName() : $key;
			$propsByColumnName[$colName] = $existingProps[$key];
		}
	
		$this->runQuery("SHOW COLUMNS FROM $tableName", "Failed to get table metadata");
		if ($includeList) {
			$rows = array();
			while ($row = $this->getAssocRow()) 
				$rows[$row['Field']] = $row;
			forEach($includeList as $name) 
				$result[$name] = $this->addFieldPropTo_row($obj, $rows[$name]);
		} else {
			while ($row = $this->getAssocRow()) //mysql_fetch_row($this->result)) 
				if (!isSet($propsByColumnName[$row['Field']]))
					$result[$row['Field']] = $this->addFieldPropTo_row($obj, $row); 
		}
		return $result;
	}

	function addFieldPropTo_row($obj, $row) {
		$minValue = null;
		$typeInfo = $row['Type'];
		$iPar1 = strpos($typeInfo, '(');
		if ($iPar1===false) {
			$colType = $typeInfo;
			$maxLength = null;
		} else {
			$colType = subStr($typeInfo, 0, $iPar1);
			$iPar2 = strpos($typeInfo, ')');
			$maxLength = subStr($typeInfo, $iPar1 + 1, $iPar2 - $iPar1 -1);
			if (subStr($typeInfo, $iPar2 + 2) == 'unsigned')
				$minValue = 0;
		}
		$type = $this->getPropertyType($colType);
		if ($type == 'number' && $maxLength && strpos($maxLength, ',') ===false)
			$maxLength .= ',0';
		
		if ($row['Null'] == 'NO') $row['Null']= false;
		return $obj->addFieldProp($row['Field'], $type, false, $minValue, null 
			, ($row['Null'] ? 0 : 1) //minLength, if NULL is not allowed, set to 1 so that the property will be compulsory
			, $maxLength);
	}
	
	function getPropertyType($mySqlType) {
		if (in_array($mySqlType, array('int', 'mediumint', 'smallint', 'float', 'double', 'decimal', 'tinyint', 'bigint', 'year', 'float unsigned'))) return 'number';
		if (in_array($mySqlType, array('varchar', 'char', 'text', 'mediumtext', 'longtext', 'tinytext'))) return 'string';
		if (in_array($mySqlType, array('date', 'time', 'timestamp'))) return $mySqlType;
		if ($mySqlType == 'datetime') return 'timestamp';
		
		//if this error occurs, please post your solution on the phpPeanuts forum
		trigger_error("Unmapped MySQL type: $mySqlType", E_USER_ERROR);
	}
	
}
?>