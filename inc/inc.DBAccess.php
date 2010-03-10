<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include $settings->_ADOdbPath . "adodb.inc.php";

/**********************************************************************\
|                     Klasse zum Datenbankzugriff                      |
\**********************************************************************/

//Zugriff erfolgt auf MySQL-Server


class DatabaseAccess
{
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

	/**
	 * Konstruktor
	 */
	function DatabaseAccess($driver, $hostname, $user, $passw, $database = false)
	{
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
	 * Baut Verbindung zur Datenquelle auf und liefert
	 * true bei Erfolg, andernfalls false
	 */
	function connect()
	{
		$this->_conn = ADONewConnection($this->_driver);
		if ($this->_database)
			$this->_conn->Connect($this->_hostname, $this->_user, $this->_passw, $this->_database);
		else
			$this->_conn->Connect($this->_hostname, $this->_user, $this->_passw);

		if (!$this->_conn)
			return false;

		$this->_connected = true;
		return true;
	}

	/**
	 * Stellt sicher, dass eine Verbindung zur Datenquelle aufgebaut ist
	 * true bei Erfolg, andernfalls false
	 */
	function ensureConnected()
	{
		if (!$this->_connected) return $this->connect();
		else return true;
	}

	/**
	 * Führt die SQL-Anweisung $queryStr aus und liefert das Ergebnis-Set als Array (d.h. $queryStr
	 * muss eine select-anweisung sein).
	 * Falls die Anfrage fehlschlägt wird false geliefert
	 */
	function getResultArray($queryStr)
	{
		$resArr = array();
		
		$res = $this->_conn->Execute($queryStr);
		if (!$res) {
			print "<br>" . $this->getErrorMsg() . ": " . $queryStr . "</br>";
			return false;
		}
		$resArr = $res->GetArray();
		$res->Close();
		return $resArr;
	}

	/**
	 * Führt die SQL-Anweisung $queryStr aus (die kein ergebnis-set liefert, z.b. insert, del usw) und
	 * gibt das resultat zurück
	 */
	function getResult($queryStr, $silent=false)
	{
		$res = $this->_conn->Execute($queryStr);
		if (!$res && !$silent)
			print "<br>" . $this->getErrorMsg() . ": " . $queryStr . "</br>";
		
		return $res;
	}

	function getInsertID()
	{
		return $this->_conn->Insert_ID();
	}

	function getErrorMsg()
	{
		return $this->_conn->ErrorMsg();
	}

	function getErrorNo()
	{
		return $this->_conn->ErrorNo();
	}

	function createTemporaryTable($tableName, $override=false) {
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
	}
}

$db = new DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");

?>
