<?php
//    MyDMS. Document Management System
//    Copyright (C) 2010 Uwe Steinmann
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

/**
 * Implementation of LetoDMS_DMS
 *
 * @category   DMS
 * @package    LetoDMЅ
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once("inc.ClassFolder.php");
require_once("inc.ClassDocument.php");
require_once("inc.ClassGroup.php");
require_once("inc.ClassUser.php");

/**
 * Class to represent the complete document management
 *
 * @category   DMS
 * @package    LetoDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_DMS {
	/**
	 * @var object $db reference to database object
	 * @access protected
	 */
	protected $db;

	/**
	 * @var object $user reference to currently logged in user
	 * @access public
	 */
	public $user;

	/**
	 * @var string $contentDir location in file system where all the
	 *      data stores are located.
	 * @access public
	 */
	public $contentDir;

	/**
	 * @var string $contentOffsetDir location in file system relative to
	 *      @var $contentDir where all the  documents belonging to a
	 *      data stored are saved
	 * @access public
	 */
	public $contentOffsetDir;

	/**
	 * @var integer $guestID ID of user treated as a guest with limited
	 *      access rights
	 * @access public
	 */
	public $guestID;

	/**
	 * @var integer $adminID ID of user treated as an administrator with full
	 *      access rights
	 * @access public
	 */
	public $adminID;

	/**
	 * @var integer $rootFolderID ID of root folder
	 * @access public
	 */
	public $rootFolderID;

	function __construct($db, $contentDir, $contentOffsetDir) { /* {{{ */
		$this->db = $db;
		$this->contentDir = $contentDir;
		$this->contentOffsetDir = $contentOffsetDir;
		$this->rootFolderID = 1;
		$this->adminID = 1;
		$this->guestID = 2;
	} /* }}} */

	function setRootFolderID($id) { /* {{{ */
		$this->rootFolderID = $id;
	} /* }}} */

	function setAdminID($id) { /* {{{ */
		$this->adminID = $id;
	} /* }}} */

	function setGuestID($id) { /* {{{ */
		$this->guestID = $id;
	} /* }}} */

	/**
	 * Login as a user
	 *
	 * Checks if the given credentials are valid returns a user object.
	 * It also sets the property $user for later access on the currently
	 * logged in user
	 *
	 * @param string $username login name of user
	 * @param string $password password of user
	 *
	 * @return object instance of class LetoDMS_User or false
	 */
	function login($username, $password) { /* {{{ */
	} /* }}} */

	/**
	 * Set the logged in user
	 *
	 * If user authentication was externally done, this function can
	 * be used to tell the dms who is currently logged in.
	 *
	 * @param object $user
	 *
	 */
	function setUser($user) { /* {{{ */
		$this->user = $user;
	} /* }}} */

	/**
	 * Return a document by its id
	 *
	 * This function retrieves a document from the database by its id.
	 *
	 * @param integer $id internal id of document
	 * @return object instance of LetoDMS_Document or false
	 */
	function getDocument($id) { /* {{{ */
		if (!is_numeric($id)) return false;
		
		$queryStr = "SELECT * FROM tblDocuments WHERE id = " . $id;
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];
	
		// New Locking mechanism uses a separate table to track the lock.
		$queryStr = "SELECT * FROM tblDocumentLocks WHERE document = " . $id;
		$lockArr = $this->db->getResultArray($queryStr);
		if ((is_bool($lockArr) && $lockArr==false) || (count($lockArr)==0)) {
			// Could not find a lock on the selected document.
			$lock = -1;
		}
		else {
			// A lock has been identified for this document.
			$lock = $lockArr[0]["userID"];
		}
	
		$document = new LetoDMS_Document($resArr["id"], $resArr["name"], $resArr["comment"], $resArr["date"], $resArr["expires"], $resArr["owner"], $resArr["folder"], $resArr["inheritAccess"], $resArr["defaultAccess"], $lock, $resArr["keywords"], $resArr["sequence"]);
		$document->setDMS($this);
		return $document;
	} /* }}} */

	/*
	 * Search the database for documents
	 *
	 * @param query string seach query with space separated words
	 * @param limit integer number of items in result set
	 * @param offset integer index of first item in result set
	 * @param mode string either AND or OR
	 * @param searchin array() list of fields to search in
	 * @param startFolder object search in the folder only (null for root folder)
	 * @param owner object search for documents owned by this user
	 * @param status array list of status
	 * @param creationstartdate array search for documents created after this date
	 * @param creationenddate array search for documents created before this date
	 * @return array containing the elements total and docs
	 */
	function search($query, $limit=0, $offset=0, $mode='AND', $searchin=array(), $startFolder=null, $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array()) { /* {{{ */
		// Split the search string into constituent keywords.
		$tkeys=array();
		if (strlen($query)>0) {
			$tkeys = split("[\t\r\n ,]+", $query);
		}
		
		// if none is checkd search all
		if (count($searchin)==0)
			$searchin=array( 0, 1, 2, 3);

		$searchKey = "";
		// Assemble the arguments for the concatenation function. This allows the
		// search to be carried across all the relevant fields.
		$concatFunction = "";
		if (in_array(1, $searchin)) {
			$concatFunction = "`tblDocuments`.`keywords`";
		}
		if (in_array(2, $searchin)) {
			$concatFunction = (strlen($concatFunction) == 0 ? "" : $concatFunction.", ")."`tblDocuments`.`name`";
		}
		if (in_array(3, $searchin)) {
			$concatFunction = (strlen($concatFunction) == 0 ? "" : $concatFunction.", ")."`tblDocuments`.`comment`";
		}
		
		if (strlen($concatFunction)>0 && count($tkeys)>0) {
			$concatFunction = "CONCAT_WS(' ', ".$concatFunction.")";
			foreach ($tkeys as $key) {
				$key = trim($key);
				if (strlen($key)>0) {
					$searchKey = (strlen($searchKey)==0 ? "" : $searchKey." ".$mode." ").$concatFunction." LIKE '%".$key."%'";
				}
			}
		}
		
		// Check to see if the search has been restricted to a particular sub-tree in
		// the folder hierarchy.
		$searchFolder = "";
		if ($startFolder) {
			$searchFolder = "`tblDocuments`.`folderList` LIKE '%:".$startFolder->getID().":%'";
		}
		
		// Check to see if the search has been restricted to a particular
		// document owner.
		$searchOwner = "";
		if ($owner) {
			$searchOwner = "`tblDocuments`.`owner` = '".$owner->getId()."'";
		}
		
		// Is the search restricted to documents created between two specific dates?
		$searchCreateDate = "";
		if ($creationstartdate) {
			$startdate = makeTimeStamp(0, 0, 0, $createstartdate["year"], $createstartdate["month"], $createstartdate["day"]);
			if ($startdate) {
				$searchCreateDate .= "`tblDocuments`.`date` >= ".$startdate;
			}
		}
		if ($creationenddate) {
			$stopdate = makeTimeStamp(23, 59, 59, $createenddate["year"], $createenddate["month"], $createenddate["day"]);
			if ($stopdate) {
				if($startdate)
					$searchCreateDate .= " AND ";
				$searchCreateDate = "`tblDocuments`.`date` <= ".$stopdate;
			}
		}
		
		// ---------------------- Suche starten ----------------------------------
		
		//
		// Construct the SQL query that will be used to search the database.
		//
		
		if (!$this->db->createTemporaryTable("ttcontentid") || !$this->db->createTemporaryTable("ttstatid")) {
			return false;
		}
		
		$searchQuery = "FROM `tblDocumentContent` ".
			"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
			"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
			"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusID` = `tblDocumentStatus`.`statusID` ".
			"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
			"AND `ttcontentid`.`maxVersion` = `tblDocumentContent`.`version`";
		
		if (strlen($searchKey)>0) {
			$searchQuery .= " AND (".$searchKey.")";
		}
		if (strlen($searchFolder)>0) {
			$searchQuery .= " AND ".$searchFolder;
		}
		if (strlen($searchOwner)>0) {
			$searchQuery .= " AND (".$searchOwner.")";
		}
		if (strlen($searchCreateDate)>0) {
			$searchQuery .= " AND (".$searchCreateDate.")";
		}

		// status
		if ($status) {
			$searchQuery .= " AND `tblDocumentStatusLog`.`status` IN (".implode(',', $status).")";
		}

		// Count the number of rows that the search will produce.
		$resArr = $this->db->getResultArray("SELECT COUNT(*) AS num ".$searchQuery);
		$totalDocs = 0;
		if (is_numeric($resArr[0]["num"]) && $resArr[0]["num"]>0) {
			$totalDocs = (integer)$resArr[0]["num"];
		}
		if($limit) {
			$totalPages = (integer)($totalDocs/$limit);
			if (($totalDocs%$limit) > 0) {
				$totalPages++;
			}
		} else {
			$totalPages = 1;
		}
		
		// If there are no results from the count query, then there is no real need
		// to run the full query. TODO: re-structure code to by-pass additional
		// queries when no initial results are found.

		// Prepare the complete search query, including the LIMIT clause.
		$searchQuery = "SELECT `tblDocuments`.*, ".
			"`tblDocumentContent`.`version`, ".
			"`tblDocumentStatusLog`.`status`, `tblDocumentLocks`.`userID` as `lockUser` ".$searchQuery;
		
		if($limit) {
			$searchQuery .= " LIMIT ".$offset.",".$limit;
		}
		
		// Send the complete search query to the database.
		$resArr = $this->db->getResultArray($searchQuery);
		
		// ------------------- Ausgabe der Ergebnisse ----------------------------
		$numResults = count($resArr);
		if ($numResults == 0) {
			return array('totalDocs'=>$totalDocs, 'totalPages'=>$totalPages, 'docs'=>array());
		}
		
		foreach ($resArr as $docArr) {
		
			$document = new LetoDMS_Document(
				$docArr["id"], $docArr["name"],
				$docArr["comment"], $docArr["date"],
				$docArr["expires"], $docArr["owner"],
				$docArr["folder"], $docArr["inheritAccess"],
				$docArr["defaultAccess"], $docArr["lockUser"],
				$docArr["keywords"], $docArr["sequence"]);
			$document->setDMS($this);
			$docs[] = $document;
		}
		return(array('totalDocs'=>$totalDocs, 'totalPages'=>$totalPages, 'docs'=>$docs));
	} /* }}} */

	/**
	 * Return a folder by its id
	 *
	 * This function retrieves a folder from the database by its id.
	 *
	 * @param integer $id internal id of folder
	 * @return object instance of LetoDMS_Folder or false
	 */
	function getFolder($id) { /* {{{ */
		if (!is_numeric($id)) return false;
		
		$queryStr = "SELECT * FROM tblFolders WHERE id = " . $id;
		$resArr = $this->db->getResultArray($queryStr);
	
		if (is_bool($resArr) && $resArr == false)
			return false;
		else if (count($resArr) != 1)
			return false;
			
		$resArr = $resArr[0];
		$folder = new LetoDMS_Folder($resArr["id"], $resArr["name"], $resArr["parent"], $resArr["comment"], $resArr["owner"], $resArr["inheritAccess"], $resArr["defaultAccess"], $resArr["sequence"]);
		$folder->setDMS($this);
		return $folder;
	} /* }}} */

	/**
	 * Return a user by its id
	 *
	 * This function retrieves a user from the database by its id.
	 *
	 * @param integer $id internal id of user
	 * @return object instance of LetoDMS_User or false
	 */
	function getUser($id) { /* {{{ */
		if (!is_numeric($id))
			return false;
		
		$queryStr = "SELECT * FROM tblUsers WHERE id = " . $id;
		$resArr = $this->db->getResultArray($queryStr);
		
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;
		
		$resArr = $resArr[0];
		
		$user = new LetoDMS_User($resArr["id"], $resArr["login"], $resArr["pwd"], $resArr["fullName"], $resArr["email"], $resArr["language"], $resArr["theme"], $resArr["comment"], $resArr["isAdmin"], $resArr["hidden"]);
		$user->setDMS($this);
		return $user;
	} /* }}} */

	/**
	 * Return a user by its login
	 *
	 * This function retrieves a user from the database by its login.
	 *
	 * @param integer $login internal login of user
	 * @return object instance of LetoDMS_User or false
	 */
	function getUserByLogin($login) { /* {{{ */
		$queryStr = "SELECT * FROM tblUsers WHERE login = '".$login."'";
		$resArr = $this->db->getResultArray($queryStr);
		
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;
			
		$resArr = $resArr[0];
		
		$user = new LetoDMS_User($resArr["id"], $resArr["login"], $resArr["pwd"], $resArr["fullName"], $resArr["email"], $resArr["language"], $resArr["theme"], $resArr["comment"], $resArr["isAdmin"], $resArr["hidden"]);
		$user->setDMS($this);
		return $user;
	} /* }}} */

	function getAllUsers() { /* {{{ */
		$queryStr = "SELECT * FROM tblUsers ORDER BY login";
		$resArr = $this->db->getResultArray($queryStr);
		
		if (is_bool($resArr) && $resArr == false)
			return false;
		
		$users = array();
		
		for ($i = 0; $i < count($resArr); $i++) {
			$user = new LetoDMS_User($resArr[$i]["id"], $resArr[$i]["login"], $resArr[$i]["pwd"], $resArr[$i]["fullName"], $resArr[$i]["email"], (isset($resArr["language"])?$resArr["language"]:NULL), (isset($resArr["theme"])?$resArr["theme"]:NULL), $resArr[$i]["comment"], $resArr[$i]["isAdmin"], $resArr[$i]["hidden"]);
			$user->setDMS($this);
			$users[$i] = $user;
		}
		
		return $users;
	} /* }}} */
	
	function addUser($login, $pwd, $fullName, $email, $language, $theme, $comment, $isAdmin=0, $isHidden=0) { /* {{{ */
		if (is_object($this->getUserByLogin($login))) {
			return false;
		}
		$queryStr = "INSERT INTO tblUsers (login, pwd, fullName, email, language, theme, comment, isAdmin, hidden) VALUES ('".$login."', '".$pwd."', '".$fullName."', '".$email."', '".$language."', '".$theme."', '".$comment."', '".$isAdmin."', '".$isHidden."')";
		$res = $this->db->getResult($queryStr);
		if (!$res)
			return false;
		
		return $this->getUser($this->db->getInsertID());
	} /* }}} */

	function getGroup($id) { /* {{{ */
		if (!is_numeric($id))
			die ("invalid groupid");
		
		$queryStr = "SELECT * FROM tblGroups WHERE id = " . $id;
		$resArr = $this->db->getResultArray($queryStr);
		
		if (is_bool($resArr) && $resArr == false)
			return false;
		else if (count($resArr) != 1) //wenn, dann wohl eher 0 als > 1 ;-)
			return false;
		
		$resArr = $resArr[0];
		
		$group = new LetoDMS_Group($resArr["id"], $resArr["name"], $resArr["comment"]);
		$group->setDMS($this);
		return $group;
	} /* }}} */

	function getGroupByName($name) { /* {{{ */
		$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` WHERE `tblGroups`.`name` = '".$name."'";
		$resArr = $this->db->getResultArray($queryStr);
		
		if (is_bool($resArr) && $resArr == false)
			return false;
		else if (count($resArr) != 1) //wenn, dann wohl eher 0 als > 1 ;-)
			return false;
		
		$resArr = $resArr[0];
		
		$group = new LetoDMS_Group($resArr["id"], $resArr["name"], $resArr["comment"]);
		$group->setDMS($this);
		return $group;
	} /* }}} */

	function getAllGroups() { /* {{{ */
		$queryStr = "SELECT * FROM tblGroups ORDER BY name";
		$resArr = $this->db->getResultArray($queryStr);
		
		if (is_bool($resArr) && $resArr == false)
			return false;
		
		$groups = array();
		
		for ($i = 0; $i < count($resArr); $i++) {
			
			$group = new LetoDMS_Group($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["comment"]);
			$group->setDMS($this);
			$groups[$i] = $group;
		}
		
		return $groups;
	} /* }}} */

	function addGroup($name, $comment) { /* {{{ */
		if (is_object($this->getGroupByName($name))) {
			return false;
		}

		$queryStr = "INSERT INTO tblGroups (name, comment) VALUES ('".$name."', '" . $comment . "')";
		if (!$this->db->getResult($queryStr))
			return false;
		
		return $this->getGroup($this->db->getInsertID());
	} /* }}} */

}
?>
