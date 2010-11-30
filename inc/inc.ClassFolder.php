<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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
 * Class to represent the complete document management
 *
 * @category   DMS
 * @package    LetoDMS
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Folder {
	var $_id;
	var $_name;
	var $_parentID;
	var $_comment;
	var $_ownerID;
	var $_inheritAccess;
	var $_defaultAccess;
	var $_sequence;
	var $_dms;

	function LetoDMS_Folder($id, $name, $parentID, $comment, $ownerID, $inheritAccess, $defaultAccess, $sequence) { /* {{{ */
		$this->_id = $id;
		$this->_name = $name;
		$this->_parentID = $parentID;
		$this->_comment = $comment;
		$this->_ownerID = $ownerID;
		$this->_inheritAccess = $inheritAccess;
		$this->_defaultAccess = $defaultAccess;
		$this->_sequence = $sequence;
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

	function getParent() { /* {{{ */
		if ($this->_id == $this->_dms->rootFolderID || !isset($this->_parentID) || ($this->_parentID == null) || ($this->_parentID == "") || ($this->_parentID == 0)) {
			return false;
		}

		if (!isset($this->_parent)) {
			$this->_parent = $this->_dms->getFolder($this->_parentID);
		}
		return $this->_parent;
	} /* }}} */

	function setParent($newParent) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->_id == $this->_dms->rootFolderID || !isset($this->_parentID) || ($this->_parentID == null) || ($this->_parentID == "") || ($this->_parentID == 0)) {
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

	function getOwner() { /* {{{ */
		if (!isset($this->_owner))
			$this->_owner = $this->_dms->getUser($this->_ownerID);
		return $this->_owner;
	} /* }}} */

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
//				$this->_subFolders[$i] = new LetoDMS_Folder($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["parent"], $resArr[$i]["comment"], $resArr[$i]["owner"], $resArr[$i]["inheritAccess"], $resArr[$i]["defaultAccess"], $resArr[$i]["sequence"]);
				$this->_subFolders[$i] = $this->_dms->getFolder($resArr[$i]["id"]);
		}
		
		return $this->_subFolders;
	} /* }}} */

	function addSubFolder($name, $comment, $owner, $sequence) { /* {{{ */
		$db = $this->_dms->getDB();

		//inheritAccess = true, defaultAccess = M_READ
		$queryStr = "INSERT INTO tblFolders (name, parent, comment, owner, inheritAccess, defaultAccess, sequence) ".
					"VALUES ('".$name."', ".$this->_id.", '".$comment."', ".$owner->getID().", 1, ".M_READ.", ".$sequence.")";
		if (!$db->getResult($queryStr))
			return false;
		$newFolder = $this->_dms->getFolder($db->getInsertID());
		unset($this->_subFolders);

		return $newFolder;
	} /* }}} */

	/*
	 * Returns a array of all parents, grand parent, etc. up to root folder.
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

	function getFolderPathHTML($tagAll=false) { /* {{{ */
		$path = $this->getPath();
		$txtpath = "";
		for ($i = 0; $i < count($path); $i++) {
			if ($i +1 < count($path)) {
				$txtpath .= "<a href=\"../out/out.ViewFolder.php?folderid=".$path[$i]->getID()."&showtree=".showtree()."\">".
					$path[$i]->getName()."</a> / ";
			}
			else {
				$txtpath .= ($tagAll ? "<a href=\"../out/out.ViewFolder.php?folderid=".$path[$i]->getID()."&showtree=".showtree()."\">".
										 $path[$i]->getName()."</a>" : $path[$i]->getName());
			}
		}
		return $txtpath;
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
	 * Überprüft, ob dieser Ordner ein Unterordner von $folder ist
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
//				array_push($this->_documents, new LetoDMS_Document($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], isset($row["lockUser"])?$row["lockUser"]:NULL, $row["keywords"], $row["sequence"]));
				array_push($this->_documents, $this->_dms->getDocument($row["id"]));
			}
		}
		return $this->_documents;
	} /* }}} */

	// $comment will be used for both document and version leaving empty the version_comment 
	function addDocument($name, $comment, $expires, $owner, $keywords, $tmpFile, $orgFileName, $fileType, $mimeType, $sequence, $reviewers=array(), $approvers=array(),$reqversion,$version_comment="") { /* {{{ */
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
					array_push($this->_accessList[$mode]["users"], new LetoDMS_UserAccess($this->_dms->getUser($row["userID"]), $row["mode"]));
				else //if ($row["groupID"] != -1)
					array_push($this->_accessList[$mode]["groups"], new LetoDMS_GroupAccess($this->_dms->getGroup($row["groupID"]), $row["mode"]));
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

	/*
	 * Liefert die Art der Zugriffsberechtigung für den User $user; Mögliche Rechte: n (keine), r (lesen), w (schreiben+lesen), a (alles)
	 * Zunächst wird Geprüft, ob die Berechtigung geerbt werden soll; in diesem Fall wird die Anfrage an den Eltern-Ordner weitergeleitet.
	 * Ansonsten werden die ACLs durchgegangen: Die höchstwertige Berechtigung gilt.
	 * Wird bei den ACLs nicht gefunden, wird die Standard-Berechtigung zurückgegeben.
	 * Ach ja: handelt es sich bei $user um den Besitzer ist die Berechtigung automatisch "a".
	 */
	function getAccessMode($user) { /* {{{ */
		/* Admins have full access */
		if ($user->isAdmin()) return M_ALL;
		
		/* User has full access if he/she is the owner of the document */
		if ($user->getID() == $this->_ownerID) return M_ALL;
		
		/* Guest has read access by default, if guest login is allowed at all */
		if (($user->getID() == $this->_dms->guestID) && ($this->_dms->enableGuestLogin)) {
			$mode = $this->getDefaultAccess();
			if ($mode >= M_READ) return M_READ;
			else return M_NONE;
		}
		
		//Berechtigung erben??
		// wird über GetAccessList() bereits realisiert.
		// durch das Verwenden der folgenden Zeilen wären auch Owner-Rechte vererbt worden.
		/*
		if ($this->inheritsAccess())
		{
			if (isset($this->_parentID))
			{
				if (!$this->getParent())
					return false;
				return $this->_parent->getAccessMode($user);
			}
		}
		*/

		/* check ACLs */
		$accessList = $this->getAccessList();
		if (!$accessList) return false;

		foreach ($accessList["users"] as $userAccess) {
			if ($userAccess->getUserID() == $user->getID()) {
				return $userAccess->getMode();
			}
		}
		foreach ($accessList["groups"] as $groupAccess) {
			if ($user->isMemberOfGroup($groupAccess->getGroup())) {
				return $groupAccess->getMode();
			}
		}
		return $this->getDefaultAccess();
	} /* }}} */

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
				if ($highestPrivileged == M_ALL) //höher geht's nicht -> wir können uns die arbeit schenken
					return $highestPrivileged;
			}
		}
		if ($foundInACL)
			return $highestPrivileged;

		//Standard-Berechtigung verwenden
		return $this->getDefaultAccess();
	} /* }}} */

	function getNotifyList() { /* {{{ */
		if (!isset($this->_notifyList)) {
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
	 * Adds notify for a user or group to folder
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
		GLOBAL $user;
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
		if ($user->getID() == $this->_dms->guestID) {
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
		GLOBAL  $user;
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
		if ($user->getID() == $this->_dms->guestID) {
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
			foreach ($tmpList["groups"] as $group) {
				$groupIDs .= (strlen($groupIDs)==0 ? "" : ", ") . $group->getGroupID();
			}
			foreach ($tmpList["users"] as $user) {
				if ($user->getUserID()!=$this->_dms->guestID) {
					$userIDs .= (strlen($userIDs)==0 ? "" : ", ") . $user->getUserID();
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
						"AND `tblUsers`.`id` !='".$this->_dms->guestID."')";
				}
				$queryStr .= (strlen($queryStr)==0 ? "" : " UNION ").
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`id` !='".$this->_dms->guestID."') ".
					"AND ((`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`isAdmin` = 1)".
					(strlen($userIDs) == 0 ? "" : " OR (`tblUsers`.`id` IN (". $userIDs ."))").
					")) ORDER BY `login`";
			}
			else {
				if (strlen($groupIDs)>0) {
					$queryStr = "(SELECT `tblUsers`.* FROM `tblUsers` ".
						"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
						"WHERE `tblGroupMembers`.`groupID` NOT IN (". $groupIDs .")".
						"AND `tblUsers`.`id` != '".$this->_dms->guestID."' ".
						(strlen($userIDs) == 0 ? ")" : " AND (`tblUsers`.`id` NOT IN (". $userIDs .")))");
				}
				$queryStr .= (strlen($queryStr)==0 ? "" : " UNION ").
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`isAdmin` = 1))".
					"UNION ".
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE `tblUsers`.`id` != '".$this->_dms->guestID."' ".
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
						$this->_approversList["groups"][] = new LetoDMS_Group($row["id"], $row["name"], $row["comment"]);
					}
				}
			}
		}
		return $this->_approversList;
	} /* }}} */
}

?>
