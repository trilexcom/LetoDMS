<?php
/**
 * Implementation of database access
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include the adodb database abstraction
 */
require_once "adodb/adodb.inc.php";

/**
 * Class to represent the database access for the document management
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_DatabaseAccess {
	var $_driver;
	var $_hostname;
	var $_database;
	var $_user;
	var $_passw;
	var $_conn;
	var $_connected;
	var $_ttreviewid;
	var $_ttapproveid;
	var $_ttstatid;
	var $_ttcontentid;
	
	/*
	Backup functions
	*/

	/**
	 * Return list of all database tables
	 *
	 * This function is used to retrieve a list of database tables for backup
	 *
	 * @return array list of table names
	 */
	function TableList() {
		return $this->_conn->MetaTables("TABLES");
	}	

	/**
	 * Constructor of LetoDMS_Core_DatabaseAccess
	 *
	 * Sets all database parameters but does not connect.
	 *
	 * @param string $driver the database type e.g. mysql, sqlite
	 * @param string $hostname host of database server
	 * @param string $user name of user having access to database
	 * @param string $passw password of user
	 * @param string $database name of database
	 */
	function LetoDMS_Core_DatabaseAccess($driver, $hostname, $user, $passw, $database = false) {
		$this->_driver = $driver;
		$this->_hostname = $hostname;
		$this->_database = $database;
		$this->_user = $user;
		$this->_passw = $passw;
		$this->_connected = false;
		// $tt*****id is a hack to ensure that we do not try to create the
		// temporary table twice during a single connection. Can be fixed by
		// using Views (MySQL 5.0 onward) instead of temporary tables.
		// CREATE ... IF NOT EXISTS cannot be used because it has the
		// unpleasant side-effect of performing the insert again even if the
		// table already exists.
		//
		// See createTemporaryTable() method for implementation.
		$this->_ttreviewid = false;
		$this->_ttapproveid = false;
		$this->_ttstatid = false;
		$this->_ttcontentid = false;
	}

	/**
	 * Connect to database
	 *
	 * @return boolean true if connection could be established, otherwise false
	 */
	function connect() { /* {{{ */
		$this->_conn = ADONewConnection($this->_driver);
		if ($this->_database)
			$this->_conn->Connect($this->_hostname, $this->_user, $this->_passw, $this->_database);
		else
			$this->_conn->Connect($this->_hostname, $this->_user, $this->_passw);

		if (!$this->_conn)
			return false;

		$this->_conn->Execute('SET NAMES utf8');
		$this->_connected = true;
		return true;
	} /* }}} */

	/**
	 * Make sure a database connection exisits
	 *
	 * This function checks for a database connection. If it does not exists
	 * it will reconnect.
	 *
	 * @return boolean true if connection is established, otherwise false
	 */
	function ensureConnected() { /* {{{ */
		if (!$this->_connected) return $this->connect();
		else return true;
	} /* }}} */

	/**
	 * Execute SQL query and return result
	 *
	 * Call this function only with sql query which return data records.
	 *
	 * @param string $queryStr sql query
	 * @return array/boolean data if query could be executed otherwise false
	 */
	function getResultArray($queryStr) { /* {{{ */
		$resArr = array();
		
		$res = $this->_conn->Execute($queryStr);
		if (!$res) {
			print "<br>" . $this->getErrorMsg() . "<br>" . $queryStr . "</br>";
			return false;
		}
		$resArr = $res->GetArray();
		$res->Close();
		return $resArr;
	} /* }}} */

	/**
	 * Execute SQL query
	 *
	 * Call this function only with sql query which do not return data records.
	 *
	 * @param string $queryStr sql query
	 * @return boolean true if query could be executed otherwise false
	 */
	function getResult($queryStr, $silent=false) { /* {{{ */
		$res = $this->_conn->Execute($queryStr);
		if (!$res && !$silent)
			print "<br>" . $this->getErrorMsg() . "<br>" . $queryStr . "</br>";
		
		return $res;
	} /* }}} */

	/**
	 * Return the id of the last instert record
	 *
	 * @return integer id used in last autoincrement
	 */
	function getInsertID() { /* {{{ */
		return $this->_conn->Insert_ID();
	} /* }}} */

	function getErrorMsg() { /* {{{ */
		return $this->_conn->ErrorMsg();
	} /* }}} */

	function getErrorNo() { /* {{{ */
		return $this->_conn->ErrorNo();
	} /* }}} */

	/**
	 * Create various temporary tables to speed up and simplify sql queries
	 */
	function createTemporaryTable($tableName, $override=false) { /* {{{ */
		if (!strcasecmp($tableName, "ttreviewid")) {
			$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttreviewid` (PRIMARY KEY (`reviewID`), INDEX (`maxLogID`)) ".
				"SELECT `tblDocumentReviewLog`.`reviewID`, ".
				"MAX(`tblDocumentReviewLog`.`reviewLogID`) AS `maxLogID` ".
				"FROM `tblDocumentReviewLog` ".
				"GROUP BY `tblDocumentReviewLog`.`reviewID` ".
				"ORDER BY `tblDocumentReviewLog`.`reviewLogID`";
			if (!$this->_ttreviewid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttreviewid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DELETE FROM `ttreviewid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttreviewid;
		}
		else if (!strcasecmp($tableName, "ttapproveid")) {
			$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttapproveid` (PRIMARY KEY (`approveID`), INDEX (`maxLogID`)) ".
				"SELECT `tblDocumentApproveLog`.`approveID`, ".
				"MAX(`tblDocumentApproveLog`.`approveLogID`) AS `maxLogID` ".
				"FROM `tblDocumentApproveLog` ".
				"GROUP BY `tblDocumentApproveLog`.`approveID` ".
				"ORDER BY `tblDocumentApproveLog`.`approveLogID`";
			if (!$this->_ttapproveid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttapproveid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DELETE FROM `ttapproveid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttapproveid;
		}
		else if (!strcasecmp($tableName, "ttstatid")) {
			$queryStr = "CREATE TEMPORARY TABLE IF NOT EXISTS `ttstatid` (PRIMARY KEY (`statusID`), INDEX (`maxLogID`)) ".
				"SELECT `tblDocumentStatusLog`.`statusID`, ".
				"MAX(`tblDocumentStatusLog`.`statusLogID`) AS `maxLogID` ".
				"FROM `tblDocumentStatusLog` ".
				"GROUP BY `tblDocumentStatusLog`.`statusID` ".
				"ORDER BY `tblDocumentStatusLog`.`statusLogID`";
			if (!$this->_ttstatid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttstatid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DELETE FROM `ttstatid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttstatid;
		}
		else if (!strcasecmp($tableName, "ttcontentid")) {
			$queryStr = "CREATE TEMPORARY TABLE `ttcontentid` (PRIMARY KEY (`document`), INDEX (`maxVersion`)) ".
				"SELECT `tblDocumentContent`.`document`, ".
				"MAX(`tblDocumentContent`.`version`) AS `maxVersion` ".
				"FROM `tblDocumentContent` ".
				"GROUP BY `tblDocumentContent`.`document` ".
				"ORDER BY `tblDocumentContent`.`document`";
			if (!$this->_ttcontentid) {
				if (!$this->getResult($queryStr))
					return false;
				$this->_ttcontentid=true;
			}
			else {
				if (is_bool($override) && $override) {
					if (!$this->getResult("DELETE FROM `ttcontentid`"))
						return false;
					if (!$this->getResult($queryStr))
						return false;
				}
			}
			return $this->_ttcontentid;
		}
		return false;
	} /* }}} */
}

?>
