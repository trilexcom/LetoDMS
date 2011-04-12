<?php
/**
 * Implementation of a folder in the document management system
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @license    GPL2
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a folder in the document management system
 *
 * A folder in LetoDMS is equivalent to a directory in a regular file
 * system. It can contain further subfolders and documents. Each folder
 * has a single parent except for the root folder which has no parent.
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_Folder {
	/**
	 * @var integer unique id of folder
	 */
	var $_id;

	/**
	 * @var string name of folder
	 */
	var $_name;

	/**
	 * @var integer id of parent folder
	 */
	var $_parentID;

	/**
	 * @var string comment of document
	 */
	var $_comment;

	/**
	 * @var integer id of user who is the owner
	 */
	var $_ownerID;

	/**
	 * @var boolean true if access is inherited, otherwise false
	 */
	var $_inheritAccess;

	/**
	 * @var integer default access if access rights are not inherited
	 */
	var $_defaultAccess;

	/**
	 * @var array list of notifications for users and groups
	 */
	var $_notifyList;

	/**
	 * @var integer position of folder within the parent folder
	 */
	var $_sequence;

	/**
	 * @var object back reference to document management system
	 */
	var $_dms;

	function LetoDMS_Core_Folder($id, $name, $parentID, $comment, $date, $ownerID, $inheritAccess, $defaultAccess, $sequence) { /* {{{ */
		$this->_id = $id;
		$this->_name = $name;
		$this->_parentID = $parentID;
		$this->_comment = $comment;
		$this->_date = $date;
		$this->_ownerID = $ownerID;
		$this->_inheritAccess = $inheritAccess;
		$this->_defaultAccess = $defaultAccess;
		$this->_sequence = $sequence;
		$this->_notifyList = array();
		$this->_dms = null;
	} /* }}} */

	/*
	 * Set dms this folder belongs to.
	 *
	 * Each folder needs a reference to the dms it belongs to. It will be
	 * set when the folder is created by LetoDMS::getFolder(). The dms has a
	 * references to the currently logged in user and the database connection.
	 *
	 * @param object $dms reference to dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/*
	 * Get the internal id of the folder.
	 *
	 * @return integer id of folder
	 */
	function getID() { return $this->_id; }

	/*
	 * Get the name of the folder.
	 *
	 * @return string name of folder
	 */
	function getName() { return $this->_name; }

	/*
	 * Set the name of the folder.
	 *
	 * @param string $newName set a new name of the folder
	 */
	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "UPDATE tblFolders SET name = '" . $newName . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_name = $newName;
		
		return true;
	} /* }}} */

	function getComment() { return $this->_comment; }

	function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "UPDATE tblFolders SET comment = '" . $newComment . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	/**
	 * Return creation date of folder
	 *
	 * @return integer unix timestamp of creation date
	 */
	function getDate() { /* {{{ */
		return $this->_date;
	} /* }}} */

	/**
	 * Returns the parent
	 *
	 * @return object parent folder or false if there is no parent folder
	 */
	function getParent() { /* {{{ */
		if ($this->_id == $this->_dms->rootFolderID || empty($this->_parentID)) {
			return false;
		}

		if (!isset($this->_parent)) {
			$this->_parent = $this->_dms->getFolder($this->_parentID);
		}
		return $this->_parent;
	} /* }}} */

	/**
	 * Set a new folder
	 *
	 * This function moves a folder from one parent folder into another parent
	 * folder. It will fail if the root folder is moved.
	 *
	 * @param object new parent folder
	 * @return boolean true if operation was successful otherwise false
	 */
	function setParent($newParent) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->_id == $this->_dms->rootFolderID || empty($this->_parentID)) {
			return false;
		}

		$queryStr = "UPDATE tblFolders SET parent = " . $newParent->getID() . " WHERE id = ". $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;
		$this->_parentID = $newParent->getID();
		$this->_parent = $newParent;

		// Must also ensure that any documents in this folder tree have their
		// folderLists updated.
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		$queryStr = "SELECT `tblDocuments`.`id`, `tblDocuments`.`folderList` FROM `tblDocuments` WHERE `folderList` LIKE '%:".$this->_id.":%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		foreach ($resArr as $row) {
			$newPath = ereg_replace("^.*:".$this->_id.":(.*$)", $pathPrefix."\\1", $row["folderList"]);
			$queryStr="UPDATE `tblDocuments` SET `folderList` = '".$newPath."' WHERE `tblDocuments`.`id` = '".$row["id"]."'";
			$res = $db->getResult($queryStr);
		}

		return true;
	} /* }}} */

	/**
	 * Returns the owner
	 *
	 * @return object owner of the folder
	 */
	function getOwner() { /* {{{ */
		if (!isset($this->_owner))
			$this->_owner = $this->_dms->getUser($this->_ownerID);
		return $this->_owner;
	} /* }}} */

	/**
	 * Set the owner
	 *
	 * @param object new owner of the folder
	 * @return boolean true if successful otherwise false
	 */
	function setOwner($newOwner) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblFolders set owner = " . $newOwner->getID() . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_ownerID = $newOwner->getID();
		$this->_owner = $newOwner;
		return true;
	} /* }}} */

	function getDefaultAccess() { /* {{{ */
		if ($this->inheritsAccess()) {
			$res = $this->getParent();
			if (!$res) return false;
			return $this->_parent->getDefaultAccess();
		}
		
		return $this->_defaultAccess;
	} /* }}} */

	function setDefaultAccess($mode) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblFolders set defaultAccess = " . $mode . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_defaultAccess = $mode;

		// If any of the notification subscribers no longer have read access,
		// remove their subscription.
		if (empty($this->_notifyList))
			$this->getNotifyList();
		foreach ($this->_notifyList["users"] as $u) {
			if ($this->getAccessMode($u) < M_READ) {
				$this->removeNotify($u->getID(), true);
			}
		}
		foreach ($this->_notifyList["groups"] as $g) {
			if ($this->getGroupAccessMode($g) < M_READ) {
				$this->removeNotify($g->getID(), false);
			}
		}

		return true;
	} /* }}} */

	function inheritsAccess() { return $this->_inheritAccess; }

	function setInheritAccess($inheritAccess) { /* {{{ */
		$db = $this->_dms->getDB();

		$inheritAccess = ($inheritAccess) ? "1" : "0";

		$queryStr = "UPDATE tblFolders SET inheritAccess = " . $inheritAccess . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_inheritAccess = $inheritAccess;

		// If any of the notification subscribers no longer have read access,
		// remove their subscription.
		if (empty($this->_notifyList))
			$this->getNotifyList();
		foreach ($this->_notifyList["users"] as $u) {
			if ($this->getAccessMode($u) < M_READ) {
				$this->removeNotify($u->getID(), true);
			}
		}
		foreach ($this->_notifyList["groups"] as $g) {
			if ($this->getGroupAccessMode($g) < M_READ) {
				$this->removeNotify($g->getID(), false);
			}
		}

		return true;
	} /* }}} */

	function getSequence() { return $this->_sequence; }

	function setSequence($seq) { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "UPDATE tblFolders SET sequence = " . $seq . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		$this->_sequence = $seq;
		return true;
	} /* }}} */

	/**
	 * Returns a list of subfolders
	 * This function does not check for access rights. Use
	 * {@link LetoDMS_Core_DMS::filterAccess} for checking each folder against
	 * the currently logged in user and the access rights.
	 *
	 * @param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 * @return array list of folder objects or false in case of an error
	 */
	function getSubFolders($orderby="") { /* {{{ */
		$db = $this->_dms->getDB();
		
		if (!isset($this->_subFolders)) {
			if ($orderby=="n") $queryStr = "SELECT * FROM tblFolders WHERE parent = " . $this->_id . " ORDER BY name";
			else $queryStr = "SELECT * FROM tblFolders WHERE parent = " . $this->_id . " ORDER BY sequence";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;
			
			$this->_subFolders = array();
			for ($i = 0; $i < count($resArr); $i++)
//				$this->_subFolders[$i] = new LetoDMS_Core_Folder($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["parent"], $resArr[$i]["comment"], $resArr[$i]["owner"], $resArr[$i]["inheritAccess"], $resArr[$i]["defaultAccess"], $resArr[$i]["sequence"]);
				$this->_subFolders[$i] = $this->_dms->getFolder($resArr[$i]["id"]);
		}
		
		return $this->_subFolders;
	} /* }}} */

	function addSubFolder($name, $comment, $owner, $sequence) { /* {{{ */
		$db = $this->_dms->getDB();

		//inheritAccess = true, defaultAccess = M_READ
		$queryStr = "INSERT INTO tblFolders (name, parent, comment, date, owner, inheritAccess, defaultAccess, sequence) ".
					"VALUES ('".$name."', ".$this->_id.", '".$comment."', ".mktime().", ".$owner->getID().", 1, ".M_READ.", ".$sequence.")";
		if (!$db->getResult($queryStr))
			return false;
		$newFolder = $this->_dms->getFolder($db->getInsertID());
		unset($this->_subFolders);

		return $newFolder;
	} /* }}} */

	/*
	 * Returns an array of all parents, grand parent, etc. up to root folder.
	 * The folder itself is the last element of the array.
	 *
	 * @return array Array of parents
	 */
	function getPath() { /* {{{ */
		if (!isset($this->_parentID) || ($this->_parentID == "") || ($this->_parentID == 0)) {
			return array($this);
		}
		else {
			$res = $this->getParent();
			if (!$res) return false;
			
			$path = $this->_parent->getPath();
			if (!$path) return false;
			
			array_push($path, $this);
			return $path;
		}
	} /* }}} */

	function getFolderPathPlain() { /* {{{ */
		$path="";
		$folderPath = $this->getPath();
		for ($i = 0; $i  < count($folderPath); $i++) {
			$path .= $folderPath[$i]->getName();
			if ($i +1 < count($folderPath))
				$path .= " / ";
		}
		return $path;
	} /* }}} */

	/**
	 * Check, if this folder is a subfolder of a given folder
	 *
	 * @param object $folder parent folder
	 * @return boolean true if folder is a subfolder
	 */
	function isDescendant($folder) { /* {{{ */
		if ($this->_parentID == $folder->getID())
			return true;
		elseif (isset($this->_parentID)) {
			$res = $this->getParent();
			if (!$res) return false;
			
			return $this->_parent->isDescendant($folder);
		} else
			return false;
	} /* }}} */

	/**
	 * Get all documents of the folder
	 * This function does not check for access rights. Use
	 * {@link LetoDMS_Core_DMS::filterAccess} for checking each document against
	 * the currently logged in user and the access rights.
	 *
	 * @param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 * @return array list of documents or false in case of an error
	 */
	function getDocuments($orderby="") { /* {{{ */
		$db = $this->_dms->getDB();
		
		if (!isset($this->_documents)) {
			if ($orderby=="n") $queryStr = "SELECT * FROM tblDocuments WHERE folder = " . $this->_id . " ORDER BY name";
			else $queryStr = "SELECT * FROM tblDocuments WHERE folder = " . $this->_id . " ORDER BY sequence";

			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;
			
			$this->_documents = array();
			foreach ($resArr as $row) {
//				array_push($this->_documents, new LetoDMS_Core_Document($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], isset($row["lockUser"])?$row["lockUser"]:NULL, $row["keywords"], $row["sequence"]));
				array_push($this->_documents, $this->_dms->getDocument($row["id"]));
			}
		}
		return $this->_documents;
	} /* }}} */

	// $comment will be used for both document and version leaving empty the version_comment 
	/**
	 * Add a new document to the folder
	 * This function will add a new document and its content from a given file. 
	 * It does not check for access rights on the folder. The new documents
	 * default access right is read only and the access right is inherited.
	 *
	 * @param string $name name of new document
	 * @param string $comment comment of new document
	 * @param integer $expires expiration date as a unix timestamp or 0 for no
	 *        expiration date
	 * @param object $owner owner of the new document
	 * @param string $keywords keywords of new document
	 * @param string $tmpFile the path of the file containing the content
	 * @param string $orgFileName the original file name
	 * @param string $fileType usually the extension of the filename
	 * @param string $mimeType mime type of the content
	 * @param integer $sequence position of new document within the folder
	 * @param array $reviewers list of users who must review this document
	 * @param array $approvers list of users who must approve this document
	 * @param string $reqversion version number of the content
	 * @param string $version_comment comment of the content. If left empty
	 *        the $comment will be used.
	 * @return array/boolean false in case of error, otherwise an array
	 *        containing two elements. The first one is the new document, the
	 *        second one is the result set returned when inserting the content.
	 */
	function addDocument($name, $comment, $expires, $owner, $keywords, $categories, $tmpFile, $orgFileName, $fileType, $mimeType, $sequence, $reviewers=array(), $approvers=array(),$reqversion,$version_comment="") { /* {{{ */
		$db = $this->_dms->getDB();
		
		$expires = (!$expires) ? 0 : $expires;
		
		// Must also ensure that the document has a valid folderList.
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}
		
		$queryStr = "INSERT INTO tblDocuments (name, comment, date, expires, owner, folder, folderList, inheritAccess, defaultAccess, locked, keywords, sequence) VALUES ".
					"('".$name."', '".$comment."', " . mktime().", ".$expires.", ".$owner->getID().", ".$this->_id.",'".$pathPrefix."', 1, ".M_READ.", -1, '".$keywords."', " . $sequence . ")";
		if (!$db->getResult($queryStr))
			return false;
		
		$document = $this->_dms->getDocument($db->getInsertID());
		
		if ($version_comment!="")
			$res = $document->addContent($version_comment, $owner, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers, $approvers,$reqversion);
		else $res = $document->addContent($comment, $owner, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers, $approvers,$reqversion);

		if (is_bool($res) && !$res) {
			$queryStr = "DELETE FROM tblDocuments WHERE id = " . $document->getID();
			$db->getResult($queryStr);
			return false;
		}

		if($categories) {
			$document->setCategories($categories);
		}
		return array($document, $res);
	} /* }}} */
	
	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		// Do not delete the root folder.
		if ($this->_id == $this->_dms->rootFolderID || !isset($this->_parentID) || ($this->_parentID == null) || ($this->_parentID == "") || ($this->_parentID == 0)) {
			return false;
		}

		//Entfernen der Unterordner und Dateien
		$res = $this->getSubFolders();
		if (is_bool($res) && !$res) return false;
		$res = $this->getDocuments();
		if (is_bool($res) && !$res) return false;
		
		foreach ($this->_subFolders as $subFolder) {
			$res = $subFolder->remove(FALSE);
			if (!$res) return false;
		}
		
		foreach ($this->_documents as $document) {
			$res = $document->remove(FALSE);
			if (!$res) return false;
		}
		
		//Entfernen der Datenbankeinträge
		$queryStr = "DELETE FROM tblFolders WHERE id =  " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblACLs WHERE target = ". $this->_id. " AND targetType = " . T_FOLDER;
		if (!$db->getResult($queryStr))
			return false;

		$queryStr = "DELETE FROM tblNotify WHERE target = ". $this->_id. " AND targetType = " . T_FOLDER;
		if (!$db->getResult($queryStr))
			return false;
		
		return true;
	} /* }}} */

	function getAccessList($mode = M_ANY, $op = O_EQ) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->inheritsAccess()) {
			$res = $this->getParent();
			if (!$res) return false;
			return $this->_parent->getAccessList($mode, $op);
		}

		if (!isset($this->_accessList[$mode])) {
			if ($op!=O_GTEQ && $op!=O_LTEQ && $op!=O_EQ) {
				return false;
			}
			$modeStr = "";
			if ($mode!=M_ANY) {
				$modeStr = " AND mode".$op.$mode;
			}
			$queryStr = "SELECT * FROM tblACLs WHERE targetType = ".T_FOLDER.
				" AND target = " . $this->_id .	$modeStr . " ORDER BY targetType";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$this->_accessList[$mode] = array("groups" => array(), "users" => array());
			foreach ($resArr as $row) {
				if ($row["userID"] != -1)
					array_push($this->_accessList[$mode]["users"], new LetoDMS_Core_UserAccess($this->_dms->getUser($row["userID"]), $row["mode"]));
				else //if ($row["groupID"] != -1)
					array_push($this->_accessList[$mode]["groups"], new LetoDMS_Core_GroupAccess($this->_dms->getGroup($row["groupID"]), $row["mode"]));
			}
		}

		return $this->_accessList[$mode];
	} /* }}} */

	function clearAccessList() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "DELETE FROM tblACLs WHERE targetType = " . T_FOLDER . " AND target = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		unset($this->_accessList);
		return true;
	} /* }}} */

	/**
	 * Add access right to folder
	 * This function may change in the future. Instead of passing the a flag
	 * and a user/group id a user or group object will be expected.
	 *
	 * @param integer $mode access mode
	 * @param integer $userOrGroupID id of user or group
	 * @param integer $isUser set to 1 if $userOrGroupID is the id of a
	 *        user
	 */
	function addAccess($mode, $userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		$queryStr = "INSERT INTO tblACLs (target, targetType, ".$userOrGroup.", mode) VALUES 
					(".$this->_id.", ".T_FOLDER.", " . $userOrGroupID . ", " .$mode. ")";
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		if ($mode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	/**
	 * Change access right of folder
	 * This function may change in the future. Instead of passing the a flag
	 * and a user/group id a user or group object will be expected.
	 *
	 * @param integer $newMode access mode
	 * @param integer $userOrGroupID id of user or group
	 * @param integer $isUser set to 1 if $userOrGroupID is the id of a
	 *        user
	 */
	function changeAccess($newMode, $userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		$queryStr = "UPDATE tblACLs SET mode = " . $newMode . " WHERE targetType = ".T_FOLDER." AND target = " . $this->_id . " AND " . $userOrGroup . " = " . $userOrGroupID;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		if ($newMode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	function removeAccess($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		$queryStr = "DELETE FROM tblACLs WHERE targetType = ".T_FOLDER." AND target = ".$this->_id." AND ".$userOrGroup." = " . $userOrGroupID;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		$mode = ($isUser ? $this->getAccessMode($this->_dms->getUser($userOrGroupID)) : $this->getGroupAccessMode($this->_dms->getGroup($userOrGroupID)));
		if ($mode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	/**
	 * Get the access mode of a user on the folder
	 * This function returns the access mode for a given user. An administrator
	 * and the owner of the folder has unrestricted access. A guest user has
	 * read only access or no access if access rights are further limited
	 * by access control lists. All other users have access rights according
	 * to the access control lists or the default access. This function will
	 * recursive check for access rights of parent folders if access rights
	 * are inherited.
	 *
	 * @param object $user user for which access shall be checked
	 * @return integer access mode
	 */
	function getAccessMode($user) { /* {{{ */
		/* Admins have full access */
		if ($user->isAdmin()) return M_ALL;
		
		/* User has full access if he/she is the owner of the document */
		if ($user->getID() == $this->_ownerID) return M_ALL;
		
		/* Guest has read access by default, if guest login is allowed at all */
		if ($user->isGuest()) {
			$mode = $this->getDefaultAccess();
			if ($mode >= M_READ) return M_READ;
			else return M_NONE;
		}
		
		/* check ACLs */
		$accessList = $this->getAccessList();
		if (!$accessList) return false;

		foreach ($accessList["users"] as $userAccess) {
			if ($userAccess->getUserID() == $user->getID()) {
				return $userAccess->getMode();
			}
		}
		$result = $this->getDefaultAccess();
		foreach ($accessList["groups"] as $groupAccess) {
			if ($user->isMemberOfGroup($groupAccess->getGroup())) {
				if ($groupAccess->getMode()>$result)
					$result = $groupAccess->getMode();
			}
		}
		return $result;
	} /* }}} */

	/**
	 * Get the access mode for a group on the folder
	 * This function returns the access mode for a given group. The algorithmn
	 * applied to get the access mode is the same as describe at
	 * {@link getAccessMode}
	 *
	 * @param object $group group for which access shall be checked
	 * @return integer access mode
	 */
	function getGroupAccessMode($group) { /* {{{ */
		$highestPrivileged = M_NONE;
		$foundInACL = false;
		$accessList = $this->getAccessList();
		if (!$accessList)
			return false;

		foreach ($accessList["groups"] as $groupAccess) {
			if ($groupAccess->getGroupID() == $group->getID()) {
				$foundInACL = true;
				if ($groupAccess->getMode() > $highestPrivileged)
					$highestPrivileged = $groupAccess->getMode();
				if ($highestPrivileged == M_ALL) /* no need to check further */
					return $highestPrivileged;
			}
		}
		if ($foundInACL)
			return $highestPrivileged;

		/* Take default access */
		return $this->getDefaultAccess();
	} /* }}} */

	/**
	 * Get a list of all notification
	 * This function returns all users and groups that have registerd a
	 * notification for the folder
	 *
	 * @return array array with a the elements 'users' and 'groups' which
	 *        contain a list of users and groups.
	 */
	function getNotifyList() { /* {{{ */
		if (empty($this->_notifyList)) {
			$db = $this->_dms->getDB();

			$queryStr ="SELECT * FROM tblNotify WHERE targetType = " . T_FOLDER . " AND target = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_notifyList = array("groups" => array(), "users" => array());
			foreach ($resArr as $row)
			{
				if ($row["userID"] != -1)
					array_push($this->_notifyList["users"], $this->_dms->getUser($row["userID"]) );
				else //if ($row["groupID"] != -1)
					array_push($this->_notifyList["groups"], $this->_dms->getGroup($row["groupID"]) );
			}
		}
		return $this->_notifyList;
	} /* }}} */

	/*
	 * Add a user/group to the notification list
	 * This function does not check if the currently logged in user
	 * is allowed to add a notification. This must be checked by the calling
	 * application.
	 *
	 * @param integer $userOrGroupID
	 * @param boolean $isUser true if $userOrGroupID is a user id otherwise false
	 * @return integer error code
	 *    -1: Invalid User/Group ID.
	 *    -2: Target User / Group does not have read access.
	 *    -3: User is already subscribed.
	 *    -4: Database / internal error.
	 *     0: Update successful.
	 */
	function addNotify($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		/* Verify that user / group exists */
		$obj = ($isUser ? $this->_dms->getUser($userOrGroupID) : $this->_dms->getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		/* Verify that the requesting user has permission to add the target to
		 * the notification system.
		 */
		/*
		 * The calling application should enforce the policy on who is allowed
		 * to add someone to the notification system. If is shall remain here
		 * the currently logged in user should be passed to this function
		 *
		GLOBAL $user;
		if ($user->isGuest()) {
			return -2;
		}
		if (!$user->isAdmin()) {
			if ($isUser) {
				if ($user->getID() != $obj->getID()) {
					return -2;
				}
			}
			else {
				if (!$obj->isMember($user)) {
					return -2;
				}
			}
		}
		*/

		//
		// Verify that user / group has read access to the document.
		//
		if ($isUser) {
			// Users are straightforward to check.
			if ($this->getAccessMode($obj) < M_READ) {
				return -2;
			}
		}
		else {
			// Groups are a little more complex.
			if ($this->getDefaultAccess() >= M_READ) {
				// If the default access is at least READ-ONLY, then just make sure
				// that the current group has not been explicitly excluded.
				$acl = $this->getAccessList(M_NONE, O_EQ);
				$found = false;
				foreach ($acl["groups"] as $group) {
					if ($group->getGroupID() == $userOrGroupID) {
						$found = true;
						break;
					}
				}
				if ($found) {
					return -2;
				}
			}
			else {
				// The default access is restricted. Make sure that the group has
				// been explicitly allocated access to the document.
				$acl = $this->getAccessList(M_READ, O_GTEQ);
				if (is_bool($acl)) {
					return -4;
				}
				$found = false;
				foreach ($acl["groups"] as $group) {
					if ($group->getGroupID() == $userOrGroupID) {
						$found = true;
						break;
					}
				}
				if (!$found) {
					return -2;
				}
			}
		}
		//
		// Check to see if user/group is already on the list.
		//
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_FOLDER."' ".
			"AND `tblNotify`.`".$userOrGroup."` = '".$userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)>0) {
			return -3;
		}

		$queryStr = "INSERT INTO tblNotify (target, targetType, " . $userOrGroup . ") VALUES (" . $this->_id . ", " . T_FOLDER . ", " . $userOrGroupID . ")";
		if (!$db->getResult($queryStr))
			return -4;

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/*
	 * Removes notify for a user or group to folder
	 * This function does not check if the currently logged in user
	 * is allowed to remove a notification. This must be checked by the calling
	 * application.
	 *
	 * @param integer $userOrGroupID
	 * @param boolean $isUser true if $userOrGroupID is a user id otherwise false
	 * @return integer error code
	 *    -1: Invalid User/Group ID.
	 *    -3: User is not subscribed.
	 *    -4: Database / internal error.
	 *     0: Update successful.
	 */
	function removeNotify($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();
		
		/* Verify that user / group exists. */
		$obj = ($isUser ? $this->_dms->getUser($userOrGroupID) : $this->_dms->getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		/* Verify that the requesting user has permission to add the target to
		 * the notification system.
		 */
		/*
		 * The calling application should enforce the policy on who is allowed
		 * to add someone to the notification system. If is shall remain here
		 * the currently logged in user should be passed to this function
		 *
		GLOBAL  $user;
		if ($user->isGuest()) {
			return -2;
		}
		if (!$user->isAdmin()) {
			if ($isUser) {
				if ($user->getID() != $obj->getID()) {
					return -2;
				}
			}
			else {
				if (!$obj->isMember($user)) {
					return -2;
				}
			}
		}
		*/

		//
		// Check to see if the target is in the database.
		//
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_FOLDER."' ".
			"AND `tblNotify`.`".$userOrGroup."` = '".$userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)==0) {
			return -3;
		}

		$queryStr = "DELETE FROM tblNotify WHERE target = " . $this->_id . " AND targetType = " . T_FOLDER . " AND " . $userOrGroup . " = " . $userOrGroupID;
		if (!$db->getResult($queryStr))
			return -4;

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	function getApproversList() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_approversList)) {
			$this->_approversList = array("groups" => array(), "users" => array());
			$userIDs = "";
			$groupIDs = "";
			$defAccess  = $this->getDefaultAccess();

			if ($defAccess<M_READ) {
				// Get the list of all users and groups that are listed in the ACL as
				// having write access to the folder.
				$tmpList = $this->getAccessList(M_READ, O_GTEQ);
			}
			else {
				// Get the list of all users and groups that DO NOT have write access
				// to the folder.
				$tmpList = $this->getAccessList(M_NONE, O_LTEQ);
			}
			foreach ($tmpList["groups"] as $groupAccess) {
				$groupIDs .= (strlen($groupIDs)==0 ? "" : ", ") . $groupAccess->getGroupID();
			}
			foreach ($tmpList["users"] as $userAccess) {
				$user = $userAccess->getUser();
				if (!$user->isGuest()) {
					$userIDs .= (strlen($userIDs)==0 ? "" : ", ") . $userAccess->getUserID();
				}
			}

			// Construct a query against the users table to identify those users
			// that have write access to this folder, either directly through an
			// ACL entry, by virtue of ownership or by having administrative rights
			// on the database.
			$queryStr="";
			if ($defAccess < M_READ) {
				if (strlen($groupIDs)>0) {
					$queryStr = "(SELECT `tblUsers`.* FROM `tblUsers` ".
						"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
						"WHERE `tblGroupMembers`.`groupID` IN (". $groupIDs .") ".
						"AND `tblUsers`.`role` != ".LetoDMS_Core_User::role_guest.")";
				}
				$queryStr .= (strlen($queryStr)==0 ? "" : " UNION ").
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`role` != ".LetoDMS_Core_User::role_guest.") ".
					"AND ((`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`role` = ".LetoDMS_Core_User::role_admin.")".
					(strlen($userIDs) == 0 ? "" : " OR (`tblUsers`.`id` IN (". $userIDs ."))").
					")) ORDER BY `login`";
			}
			else {
				if (strlen($groupIDs)>0) {
					$queryStr = "(SELECT `tblUsers`.* FROM `tblUsers` ".
						"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
						"WHERE `tblGroupMembers`.`groupID` NOT IN (". $groupIDs .")".
						"AND `tblUsers`.`role` != ".LetoDMS_Core_User::role_guest." ".
						(strlen($userIDs) == 0 ? ")" : " AND (`tblUsers`.`id` NOT IN (". $userIDs .")))");
				}
				$queryStr .= (strlen($queryStr)==0 ? "" : " UNION ").
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`role` = ".LetoDMS_Core_User::role_admin."))".
					"UNION ".
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE `tblUsers`.`role` != ".LetoDMS_Core_User::role_guest." ".
					(strlen($userIDs) == 0 ? ")" : " AND (`tblUsers`.`id` NOT IN (". $userIDs .")))").
					" ORDER BY `login`";
			}
			$resArr = $db->getResultArray($queryStr);
			if (!is_bool($resArr)) {
				foreach ($resArr as $row) {
					$user = $this->_dms->getUser($row['id']);
					if (!$this->_dms->enableAdminRevApp && $user->isAdmin()) continue;					
					$this->_approversList["users"][] = $user;
				}
			}

			// Assemble the list of groups that have write access to the folder.
			$queryStr="";
			if ($defAccess < M_READ) {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` ".
						"WHERE `tblGroups`.`id` IN (". $groupIDs .")";
				}
			}
			else {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` ".
						"WHERE `tblGroups`.`id` NOT IN (". $groupIDs .")";
				}
				else {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups`";
				}
			}
			if (strlen($queryStr)>0) {
				$resArr = $db->getResultArray($queryStr);
				if (!is_bool($resArr)) {
					foreach ($resArr as $row) {
						$group = $this->_dms->getGroup($row["id"]);
						$this->_approversList["groups"][] = $group;
					}
				}
			}
		}
		return $this->_approversList;
	} /* }}} */
}

?>
