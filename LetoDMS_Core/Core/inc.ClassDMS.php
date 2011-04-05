<?php
/**
 * Implementation of the document management system
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include some files
 */
require_once("inc.DBAccess.php");
require_once("inc.AccessUtils.php");
require_once("inc.FileUtils.php");
require_once("inc.ClassAccess.php");
require_once("inc.ClassFolder.php");
require_once("inc.ClassDocument.php");
require_once("inc.ClassGroup.php");
require_once("inc.ClassUser.php");
require_once("inc.ClassKeywords.php");
require_once("inc.ClassNotification.php");

/**
 * Class to represent the complete document management system.
 * This class is needed to do most of the dms operations. It needs
 * an instance of {@link LetoDMS_Core_DatabaseAccess} to access the
 * underlying database. Many methods are factory functions which create
 * objects representing the entities in the dms, like folders, documents,
 * users, or groups.
 *
 * Each dms has its own database for meta data and a data store for document
 * content. Both must be specified when creating a new instance of this class.
 * All folders and documents are organized in a hierachy like
 * a regular file system starting with a {@link $rootFolderID}
 *
 * This class does not enforce any access rights on documents and folders
 * by design. It is up to the calling application to use the methods
 * {@link LetoDMS_Core_Folder::getAccessMode} and
 * {@link LetoDMS_Core_Document::getAccessMode} and interpret them as desired.
 * Though, there are two convinient functions to filter a list of
 * documents/folders for which users have access rights for. See
 * {@link LetoDMS_Core_DMS::filterAccess}
 * and {@link LetoDMS_Core_DMS::filterUsersByAccess}
 *
 * Though, this class has two methods to set the currently logged in user
 * ({@link setUser} and {@link login}), none of them need to be called, because
 * there is currently no class within the LetoDMS core which needs the logged
 * in user.
 *
 * <code>
 * <?php
 * include("inc/inc.ClassDMS.php");
 * $db = new LetoDMS_Core_DatabaseAccess($type, $hostname, $user, $passwd, $name);
 * $db->connect() or die ("Could not connect to db-server");
 * $dms = new LetoDMS_Core_DMS($db, $contentDir);
 * $dms->setRootFolderID(1);
 * ...
 * ?>
 * </code>
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_DMS {
	/**
	 * @var object $db reference to database object. This must be an instance
	 *      of {@link LetoDMS_Core_DatabaseAccess}.
	 * @access protected
	 */
	protected $db;

	/**
	 * @var object $user reference to currently logged in user. This must be
	 *      an instance of {@link LetoDMS_Core_User}. This variable is currently not
	 *      used. It is set by {@link setUser}.
	 * @access private
	 */
	private $user;

	/**
	 * @var string $contentDir location in the file system where all the
	 *      document data is located. This should be an absolute path.
	 * @access public
	 */
	public $contentDir;

	/**
	 * @var integer $rootFolderID ID of root folder
	 * @access public
	 */
	public $rootFolderID;

	/**
	 * @var boolean $enableConverting set to true if conversion of content
	 *      is desired
	 * @access public
	 */
	public $enableConverting;

	/**
	 * @var array $convertFileTypes list of files types that shall be converted
	 * @access public
	 */
	public $convertFileTypes;

	/**
	 * @var array $viewOnlineFileTypes list of files types that can be viewed
	 *      online
	 * @access public
	 */
	public $viewOnlineFileTypes;

	/**
	 * @var string $version version of pear package
	 * @access public
	 */
	public $version;

	/**
	 * Filter objects out which are not accessible in a given mode by a user.
	 *
	 * @param array $objArr list of objects (either documents or folders)
	 * @param object $user user for which access is checked
	 * @param integer $minMode minimum access mode required
	 * @return array filtered list of objects
	 */
	static function filterAccess($objArr, $user, $minMode) { /* {{{ */
		if (!is_array($objArr)) {
			return array();
		}
		$newArr = array();
		foreach ($objArr as $obj) {
			if ($obj->getAccessMode($user) >= $minMode)
				array_push($newArr, $obj);
		}
		return $newArr;
	} /* }}} */

	/**
	 * Filter users out which cannot access an object in a given mode.
	 *
	 * @param object $obj object that shall be accessed
	 * @param array $users list of users which are to check for sufficient
	 *        access rights
	 * @param integer $minMode minimum access right on the object for each user
	 * @return array filtered list of users
	 */
	static function filterUsersByAccess($obj, $users, $minMode) { /* {{{ */
		$newArr = array();
		foreach ($users as $currUser) {
			if ($obj->getAccessMode($currUser) >= $minMode)
				array_push($newArr, $currUser);
		}
		return $newArr;
	} /* }}} */

	/**
	 * Create a new instance of the dms
	 *
	 * @param object $db object to access the underlying database
	 * @param string $contentDir path in filesystem containing the data store
	 *        all document contents is stored
	 * @return object instance of LetoDMS_Core_DMS
	 */
	function __construct($db, $contentDir) { /* {{{ */
		$this->db = $db;
		if(substr($contentDir, -1) == '/')
			$this->contentDir = $contentDir;
		else
			$this->contentDir = $contentDir.'/';
		$this->rootFolderID = 1;
		$this->enableAdminRevApp = false;
		$this->enableConverting = false;
		$this->convertFileTypes = array();
		$this->version = '@package_version@';
		if($this->version[0] == '@')
			$this->version = '3.0.0';
	} /* }}} */

	function getDB() { /* {{{ */
		return $this->db;
	} /* }}} */

	/**
	 * Return the database version
	 *
	 * @return array array with elements major, minor, subminor, date
	 */
	function getDBVersion() { /* {{{ */
		$tbllist = $this->db->TableList();
		$tbllist = explode(',',strtolower(join(',',$tbllist)));
		if(!array_search('tblversion', $tbllist))
			return false;
		$queryStr = "SELECT * FROM tblVersion order by major,minor,subminor limit 1";
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];
		return $resArr;
	} /* }}} */

	/**
	 * Check if the version in the database is the same as of this package
	 * Only the major and minor version number will be checked.
	 *
	 * @return boolean returns false if versions do not match
	 */
	function checkVersion() { /* {{{ */
		$tbllist = $this->db->TableList();
		$tbllist = explode(',',strtolower(join(',',$tbllist)));
		if(!array_search('tblversion', $tbllist))
			return false;
		$queryStr = "SELECT * FROM tblVersion order by major,minor,subminor limit 1";
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];
		$ver = explode('.', $this->version);
		if(($resArr['major'] != $ver[0]) || ($resArr['minor'] != $ver[1]))
			return false;
		return true;
	} /* }}} */

	/**
	 * Set id of root folder
	 * This function must be called right after creating an instance of
	 * LetoDMS_Core_DMS
	 *
	 * @param interger $id id of root folder
	 */
	function setRootFolderID($id) { /* {{{ */
		$this->rootFolderID = $id;
	} /* }}} */

	/**
	 * Get root folder
	 *
	 * @return object/boolean return the object of the root folder or false if
	 *        the root folder id was not set before with {@link setRootFolderID}.
	 */
	function getRootFolder() { /* {{{ */
		if(!$this->rootFolderID) return false;
		return $this->getFolder($this->rootFolderID);
	} /* }}} */

	function setEnableAdminRevApp($enable) { /* {{{ */
		$this->enableAdminRevApp = $enable;
	} /* }}} */

	function setEnableConverting($enable) { /* {{{ */
		$this->enableConverting = $enable;
	} /* }}} */

	function setConvertFileTypes($types) { /* {{{ */
		$this->convertFileTypes = $types;
	} /* }}} */

	function setViewOnlineFileTypes($types) { /* {{{ */
		$this->viewOnlineFileTypes = $types;
	} /* }}} */

	/**
	 * Login as a user
	 *
	 * Checks if the given credentials are valid and returns a user object.
	 * It also sets the property $user for later access on the currently
	 * logged in user
	 *
	 * @param string $username login name of user
	 * @param string $password password of user
	 *
	 * @return object instance of class LetoDMS_Core_User or false
	 */
	function login($username, $password) { /* {{{ */
	} /* }}} */

	/**
	 * Set the logged in user
	 *
	 * If user authentication was done externally, this function can
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
	 * @return object instance of LetoDMS_Core_Document or false
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

		$document = new LetoDMS_Core_Document($resArr["id"], $resArr["name"], $resArr["comment"], $resArr["date"], $resArr["expires"], $resArr["owner"], $resArr["folder"], $resArr["inheritAccess"], $resArr["defaultAccess"], $lock, $resArr["keywords"], $resArr["sequence"]);
		$document->setDMS($this);
		return $document;
	} /* }}} */

	/**
	 * Returns all documents of a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocumentsByUser($user) { /* {{{ */
		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocuments` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"WHERE `tblDocuments`.`owner` = " . $user->getID() . " ORDER BY `sequence`";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$documents = array();
		foreach ($resArr as $row) {
			$document = new LetoDMS_Core_Document($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
			$document->setDMS($this);
			$documents[] = $document;
		}
		return $documents;
	} /* }}} */

	/**
	 * Returns a document by its name
	 *
	 * This function searches a document by its name and restricts the search
	 * to given folder if passed as the second parameter.
	 *
	 * @param string $name
	 * @param object $folder
	 * @return object/boolean found document or false
	 */
	function getDocumentByName($name, $folder=null) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocuments` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"WHERE `tblDocuments`.`name` = '" . $name . "'";
		if($folder)
			$queryStr .= " AND `tblDocuments`.`folder` = ". $folder->getID();
		$queryStr .= " LIMIT 1";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		if(!$resArr)
			return false;

		$row = $resArr[0];
		$document = new LetoDMS_Core_Document($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
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
			$startdate = makeTimeStamp(0, 0, 0, $creationstartdate["year"], $creationstartdate["month"], $creationstartdate["day"]);
			if ($startdate) {
				$searchCreateDate .= "`tblDocuments`.`date` >= ".$startdate;
			}
		}
		if ($creationenddate) {
			$stopdate = makeTimeStamp(23, 59, 59, $creationenddate["year"], $creationenddate["month"], $creationenddate["day"]);
			if ($stopdate) {
				if($startdate)
					$searchCreateDate .= " AND ";
				$searchCreateDate .= "`tblDocuments`.`date` <= ".$stopdate;
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
			$docs[] = $this->getDocument($docArr['id']);
		}
		return(array('totalDocs'=>$totalDocs, 'totalPages'=>$totalPages, 'docs'=>$docs));
	} /* }}} */

	/**
	 * Return a folder by its id
	 *
	 * This function retrieves a folder from the database by its id.
	 *
	 * @param integer $id internal id of folder
	 * @return object instance of LetoDMS_Core_Folder or false
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
		$folder = new LetoDMS_Core_Folder($resArr["id"], $resArr["name"], $resArr["parent"], $resArr["comment"], $resArr["date"], $resArr["owner"], $resArr["inheritAccess"], $resArr["defaultAccess"], $resArr["sequence"]);
		$folder->setDMS($this);
		return $folder;
	} /* }}} */

	/**
	 * Return a folder by its name
	 *
	 * This function retrieves a folder from the database by its name. The
	 * search covers the whole database. If
	 * the parameter $folder is not null, it will search for the name
	 * only within this parent folder. It will not be done recursively.
	 *
	 * @param string $name name of the folder
	 * @param object $folder parent folder
	 * @return object/boolean found folder or false
	 */
	function getFolderByName($name, $folder=null) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM tblFolders WHERE name = '" . $name . "'";
		if($folder)
			$queryStr .= " AND `parent` = ". $folder->getID();
		$queryStr .= " LIMIT 1";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return false;

		$resArr = $resArr[0];
		$folder = new LetoDMS_Core_Folder($resArr["id"], $resArr["name"], $resArr["parent"], $resArr["comment"], $resArr["date"], $resArr["owner"], $resArr["inheritAccess"], $resArr["defaultAccess"], $resArr["sequence"]);
		$folder->setDMS($this);
		return $folder;
	} /* }}} */

	/**
	 * Return a user by its id
	 *
	 * This function retrieves a user from the database by its id.
	 *
	 * @param integer $id internal id of user
	 * @return object instance of LetoDMS_Core_User or false
	 */
	function getUser($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM tblUsers WHERE id = " . $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$user = new LetoDMS_Core_User($resArr["id"], $resArr["login"], $resArr["pwd"], $resArr["fullName"], $resArr["email"], $resArr["language"], $resArr["theme"], $resArr["comment"], $resArr["role"], $resArr["hidden"]);
		$user->setDMS($this);
		return $user;
	} /* }}} */

	/**
	 * Return a user by its login
	 *
	 * This function retrieves a user from the database by its login.
	 *
	 * @param integer $login internal login of user
	 * @return object instance of LetoDMS_Core_User or false
	 */
	function getUserByLogin($login) { /* {{{ */
		$queryStr = "SELECT * FROM tblUsers WHERE login = '".$login."'";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$user = new LetoDMS_Core_User($resArr["id"], $resArr["login"], $resArr["pwd"], $resArr["fullName"], $resArr["email"], $resArr["language"], $resArr["theme"], $resArr["comment"], $resArr["role"], $resArr["hidden"]);
		$user->setDMS($this);
		return $user;
	} /* }}} */

	/**
	 * Return list of all users
	 *
	 * @return array of instances of LetoDMS_Core_User or false
	 */
	function getAllUsers() { /* {{{ */
		$queryStr = "SELECT * FROM tblUsers ORDER BY login";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$users = array();

		for ($i = 0; $i < count($resArr); $i++) {
			$user = new LetoDMS_Core_User($resArr[$i]["id"], $resArr[$i]["login"], $resArr[$i]["pwd"], $resArr[$i]["fullName"], $resArr[$i]["email"], (isset($resArr["language"])?$resArr["language"]:NULL), (isset($resArr["theme"])?$resArr["theme"]:NULL), $resArr[$i]["comment"], $resArr[$i]["role"], $resArr[$i]["hidden"]);
			$user->setDMS($this);
			$users[$i] = $user;
		}

		return $users;
	} /* }}} */

	/**
	 * Add a new user
	 *
	 * @param string $login login name
	 * @param string $pwd password of new user
	 * @param string $email Email of new user
	 * @param string $language language of new user
	 * @param string $comment comment of new user
	 * @param integer $role role of new user (can be 0=normal, 1=admin, 2=guest)
	 * @param integer $isHidden hide user in all lists, if this is set login
	 *        is still allowed
	 * @return object of LetoDMS_Core_User
	 */
	function addUser($login, $pwd, $fullName, $email, $language, $theme, $comment, $role=0, $isHidden=0) { /* {{{ */
		if (is_object($this->getUserByLogin($login))) {
			return false;
		}
		$queryStr = "INSERT INTO tblUsers (login, pwd, fullName, email, language, theme, comment, role, hidden) VALUES ('".$login."', '".$pwd."', '".$fullName."', '".$email."', '".$language."', '".$theme."', '".$comment."', '".$role."', '".$isHidden."')";
		$res = $this->db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getUser($this->db->getInsertID());
	} /* }}} */

	/**
	 * Get a group by its id
	 *
	 * @param integer $id id of group
	 * @return object/boolean group or false if no group was found
	 */
	function getGroup($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM tblGroups WHERE id = " . $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;
		else if (count($resArr) != 1) //wenn, dann wohl eher 0 als > 1 ;-)
			return false;

		$resArr = $resArr[0];

		$group = new LetoDMS_Core_Group($resArr["id"], $resArr["name"], $resArr["comment"]);
		$group->setDMS($this);
		return $group;
	} /* }}} */

	/**
	 * Get a group by its name
	 *
	 * @param string $name name of group
	 * @return object/boolean group or false if no group was found
	 */
	function getGroupByName($name) { /* {{{ */
		$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` WHERE `tblGroups`.`name` = '".$name."'";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;
		else if (count($resArr) != 1) //wenn, dann wohl eher 0 als > 1 ;-)
			return false;

		$resArr = $resArr[0];

		$group = new LetoDMS_Core_Group($resArr["id"], $resArr["name"], $resArr["comment"]);
		$group->setDMS($this);
		return $group;
	} /* }}} */

	/**
	 * Get a list of all groups
	 *
	 * @return array array of instances of {@link LetoDMS_Core_Group}
	 */
	function getAllGroups() { /* {{{ */
		$queryStr = "SELECT * FROM tblGroups ORDER BY name";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$groups = array();

		for ($i = 0; $i < count($resArr); $i++) {

			$group = new LetoDMS_Core_Group($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["comment"]);
			$group->setDMS($this);
			$groups[$i] = $group;
		}

		return $groups;
	} /* }}} */

	/**
	 * Create a new user group
	 *
	 * @param string $name name of group
	 * @param string $comment comment of group
	 * @return object/boolean instance of {@link LetoDMS_Core_Group} or false in
	 *         case of an error.
	 */
	function addGroup($name, $comment) { /* {{{ */
		if (is_object($this->getGroupByName($name))) {
			return false;
		}

		$queryStr = "INSERT INTO tblGroups (name, comment) VALUES ('".$name."', '" . $comment . "')";
		if (!$this->db->getResult($queryStr))
			return false;

		return $this->getGroup($this->db->getInsertID());
	} /* }}} */

	function getKeywordCategory($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM tblKeywordCategories WHERE id = " . $id;
		$resArr = $this->db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;

		$resArr = $resArr[0];
		$cat = new LetoDMS_Core_Keywordcategory($resArr["id"], $resArr["owner"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getKeywordCategoryByName($name, $owner) { /* {{{ */
		$queryStr = "SELECT * FROM tblKeywordCategories WHERE name = '" . $name . "' AND owner = '" . $owner. "'";
		$resArr = $this->db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;

		$resArr = $resArr[0];
		$cat = new LetoDMS_Core_Keywordcategory($resArr["id"], $resArr["owner"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getAllKeywordCategories($userIDs = array()) { /* {{{ */
		$queryStr = "SELECT * FROM tblKeywordCategories";
		if ($userIDs)
			$queryStr .= " WHERE owner in (".implode(',', $userIDs).")";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new LetoDMS_Core_KeywordCategory($row["id"], $row["owner"], $row["name"]);
			$cat->setDMS($this);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

	function getAllUserKeywordCategories($userID) { /* {{{ */
		$queryStr = "SELECT * FROM tblKeywordCategories";
		if ($userID != -1)
			$queryStr .= " WHERE owner = " . $userID;

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new LetoDMS_Core_KeywordCategory($row["id"], $row["owner"], $row["name"]);
			$cat->setDMS($this);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

	function addKeywordCategory($owner, $name) { /* {{{ */
		if (is_object($this->getKeywordCategoryByName($name, $owner))) {
			return false;
		}
		$queryStr = "INSERT INTO tblKeywordCategories (owner, name) VALUES ($owner, '$name')";
		if (!$this->db->getResult($queryStr))
			return false;

		return $this->getKeywordCategory($this->db->getInsertID());
	} /* }}} */

	/**
	 * Get all notifications for a group
	 *
	 * @param object $group group for which notifications are to be retrieved
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return array array of notifications
	 */
	function getNotificationsByGroup($group, $type=0) { /* {{{ */
		$queryStr = "SELECT `tblNotify`.* FROM `tblNotify` ".
		 "WHERE `tblNotify`.`groupID` = ". $group->getID();
		if($type) {
			$queryStr .= " AND `tblNotify`.`targetType` = ".$type;
		}

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$notifications = array();
		foreach ($resArr as $row) {
			$not = new LetoDMS_Core_Notification($row["target"], $row["targetType"], $row["userID"], $row["groupID"]);
			$not->setDMS($this);
			array_push($notifications, $cat);
		}

		return $notifications;
	} /* }}} */

	/**
	 * Get all notifications for a user
	 *
	 * @param object $user user for which notifications are to be retrieved
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return array array of notifications
	 */
	function getNotificationsByUser($user, $type) { /* {{{ */
		$queryStr = "SELECT `tblNotify`.* FROM `tblNotify` ".
		 "WHERE `tblNotify`.`userID` = ". $user->getID();
		if($type) {
			$queryStr .= " AND `tblNotify`.`targetType` = ".$type;
		}

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$notifications = array();
		foreach ($resArr as $row) {
			$not = new LetoDMS_Core_Notification($row["target"], $row["targetType"], $row["userID"], $row["groupID"]);
			$not->setDMS($this);
			array_push($notifications, $cat);
		}

		return $notifications;
	} /* }}} */

}
?>
