<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

/** Instance of this class desrcibes a database connection.
*
* This abstract superclass provides behavior for the concrete
* subclass DatabaseConnection in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @package pnt/db
*/
class PntDatabaseConnection {
	public $dsnPrefix='mysql:';
	public $dsnBody;
	public $username;
	public $password;
	
	public $host;
	public $port;
	public $databaseName;
	public $charset = 'latin1';

	public $dbSource;
	
	/** @static sets/gets the default DatabaseConnection 
	* @param DatabaseConnection $value 
	* @return DatabaseConnection
	*/
	static function defaultConnection($value=null) {
		static $default;
		if ($value) $default = $value;
		return $default;
	}
	
	/** Makes the default connection. 
	@param PntDbQueryHandler $qh The QueryHandler that will make the connection,
	*	or null if an arbitrary instance of QueryHandler may be used.
	* @param boolean $makeDefault Wheather to make the connection the default
	* 	*/
	function makeConnection($qh=null, $makeDefault=true) {
		if (!$qh) $qh = new QueryHandler();
		$qh->setConnection($this);
		$qh->connect(); //sets $this->dbSource
		if ($this->dbSource === false) return;
		
		//connection succeeded
		if ($makeDefault)
			DatabaseConnection::defaultConnection($this);
	}
	
	/** Sets the dsn prefix for DSO. This is everything up to and including the : */
	function setDsnPrefix($value) {
		$this->dsnPrefix = $value;
	}
	
	/** Sets dsn body for PDO. This is everything behind the :
	*/
	function setDsnBody($value) {
		$this->dsnBody = $value;
	}
	
	function setUsername($value) {
		// sets the username
		$this->username=$value;
	}
	
	function setPassword($value) {
		// sets the password
		$this->password=$value;
	}
	
	function setHost($value) {
		// sets the host
		$this->host=$value;
	}
	
	function setPort($value) {
		// sets the port#
		$this->port=$value;
	}

	function setDatabaseName($value) {
		// sets the databaseName
		$this->databaseName=$value;
	}
	
	function setCharset($value) {
		$this->charset = $value;;
	}
	
	/** @param resource or DBO the database connection resource
	*/
	function setDBSource($value) {
		$this->dbSource = $value;
	}

	/** Gets the dsn for DSO */
	function getDsn() {
		return $this->getDsnPrefix().$this->getDsnBody();
	}
	
	/** Gets the dsn prefix for DSO. This is everything up to and including the : */
	function getDsnPrefix() {
		return $this->dsnPrefix;
	}
	
	/** Gets dsn body for PDO. This is everything behind the :
	*/
	function getDsnBody() {
		return $this->dsnBody;
	}
	
	function getUsername() {
		//returns the  username
		return $this->username;		
	}
	
	function getPassword() {
		//returns the password
		return $this->password;
	}
	
	function getHost() {
		// returns the host without the port#
		return $this->host;
	}
	
	function getPort() {
		//returns the port#
		return $this->port;
	}
	
	function getDatabaseName() {
		// returns the databasename
		return $this->databaseName;
	}
	
	function getDBSource() {
		// returns the dbSource
		return $this->dbSource;
	}
	
	function getCharset() {
		return $this->charset;
	}
}
?>