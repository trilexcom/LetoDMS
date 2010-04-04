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

function getFolder($id)
{
	GLOBAL $db;

	if (!is_numeric($id))
		die ("invalid folderid");
	
	$queryStr = "SELECT * FROM tblFolders WHERE id = " . $id;
	$resArr = $db->getResultArray($queryStr);

	if (is_bool($resArr) && $resArr == false)
		return false;
	else if (count($resArr) != 1)
		return false;
		
	$resArr = $resArr[0];
	return new Folder($resArr["id"], $resArr["name"], $resArr["parent"], $resArr["comment"], $resArr["owner"], $resArr["inheritAccess"], $resArr["defaultAccess"], $resArr["sequence"]);
}

function getFolderPathHTML($folder, $tagAll=false) {
	$path = $folder->getPath();
	$txtpath = "";
	for ($i = 0; $i < count($path); $i++) {
		if ($i +1 < count($path)) {
			$txtpath .= "<a href=\"../out/out.ViewFolder.php?folderid=".$path[$i]->getID()."\">".
				$path[$i]->getName()."</a> / ";
		}
		else {
			$txtpath .= ($tagAll ? "<a href=\"../out/out.ViewFolder.php?folderid=".$path[$i]->getID()."\">".
									 $path[$i]->getName()."</a>" : $path[$i]->getName());
		}
	}
	return $txtpath;
}

function getFolderPathPlain($folder) {
	$path="";
	$folderPath = $folder->getPath();
	for ($i = 0; $i  < count($folderPath); $i++) {
		$path .= $folderPath[$i]->getName();
		if ($i +1 < count($folderPath))
			$path .= " / ";
	}
	return $path;
}


/**********************************************************************\
|                            Folder-Klasse                             |
\**********************************************************************/

class Folder
{
	var $_id;
	var $_name;
	var $_parentID;
	var $_comment;
	var $_ownerID;
	var $_inheritAccess;
	var $_defaultAccess;
	var $_sequence;

	function Folder($id, $name, $parentID, $comment, $ownerID, $inheritAccess, $defaultAccess, $sequence)
	{
		$this->_id = $id;
		$this->_name = $name;
		$this->_parentID = $parentID;
		$this->_comment = $comment;
		$this->_ownerID = $ownerID;
		$this->_inheritAccess = $inheritAccess;
		$this->_defaultAccess = $defaultAccess;
		$this->_sequence = $sequence;
	}

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) {
		GLOBAL $db, $user, $settings;
		
		$queryStr = "UPDATE tblFolders SET name = '" . $newName . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("folder_renamed_email");
		$message = getMLText("folder_renamed_email")."\r\n";
		$message .= 
			getMLText("old").": ".$this->_name."\r\n".
			getMLText("new").": ".$newName."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this)."\r\n".
			getMLText("comment").": ".$this->getComment()."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}
		
		$this->_name = $newName;
		
		return true;
	}

	function getComment() { return $this->_comment; }

	function setComment($newComment) {
		GLOBAL $db, $user, $settings;
		
		$queryStr = "UPDATE tblFolders SET comment = '" . $newComment . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("comment_changed_email");
		$message = getMLText("comment_changed_email")."\r\n";
		$message .= 
			getMLText("name").": ".$this->_name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this)."\r\n".
			getMLText("comment").": ".$newComment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}

		$this->_comment = $newComment;
		return true;
	}

	function getParent()
	{
		global $settings;

		if ($this->_id == $settings->_rootFolderID || !isset($this->_parentID) || ($this->_parentID == null) || ($this->_parentID == "") || ($this->_parentID == 0)) {
			return false;
		}

		if (!isset($this->_parent)) {
			$this->_parent = getFolder($this->_parentID);
		}
		return $this->_parent;
	}

	function setParent($newParent) {
		global $db, $user, $settings;

		if ($this->_id == $settings->_rootFolderID || !isset($this->_parentID) || ($this->_parentID == null) || ($this->_parentID == "") || ($this->_parentID == 0)) {
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

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("folder_moved_email");
		$message = getMLText("folder_moved_email")."\r\n";
		$message .= 
			getMLText("name").": ".$this->_name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this)."\r\n".
			getMLText("comment").": ".$this->_comment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}

		return true;
	}

	function getOwner()
	{
		if (!isset($this->_owner))
			$this->_owner = getUser($this->_ownerID);
		return $this->_owner;
	}

	function setOwner($newOwner) {
		GLOBAL $db, $user, $settings;

		$oldOwner = $this->getOwner();

		$queryStr = "UPDATE tblFolders set owner = " . $newOwner->getID() . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("ownership_changed_email");
		$message = getMLText("ownership_changed_email")."\r\n";
		$message .= 
			getMLText("name").": ".$this->_name."\r\n".
			getMLText("old").": ".$oldOwner->getFullName()."\r\n".
			getMLText("new").": ".$newOwner->getFullName()."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this)."\r\n".
			getMLText("comment").": ".$this->_comment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}

		$this->_ownerID = $newOwner->getID();
		$this->_owner = $newOwner;
		return true;
	}

	function getDefaultAccess()
	{
		if ($this->inheritsAccess())
		{
			$res = $this->getParent();
			if (!$res) return false;
			return $this->_parent->getDefaultAccess();
		}
		
		return $this->_defaultAccess;
	}

	function setDefaultAccess($mode) {
		GLOBAL $db, $user, $settings;

		$queryStr = "UPDATE tblFolders set defaultAccess = " . $mode . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("access_permission_changed_email");
		$message = getMLText("access_permission_changed_email")."\r\n";
		$message .= 
			getMLText("name").": ".$this->_name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this)."\r\n".
			getMLText("comment").": ".$this->_comment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}

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
	}

	function inheritsAccess() { return $this->_inheritAccess; }

	function setInheritAccess($inheritAccess) {
		GLOBAL $db, $user, $settings;

		$inheritAccess = ($inheritAccess) ? "1" : "0";

		$queryStr = "UPDATE tblFolders SET inheritAccess = " . $inheritAccess . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_inheritAccess = $inheritAccess;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("access_permission_changed_email");
		$message = getMLText("access_permission_changed_email")."\r\n";
		$message .= 
			getMLText("name").": ".$this->_name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this)."\r\n".
			getMLText("comment").": ".$this->_comment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}

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
	}

	function getSequence() { return $this->_sequence; }

	function setSequence($seq)
	{
		GLOBAL $db;
		
		$queryStr = "UPDATE tblFolders SET sequence = " . $seq . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		$this->_sequence = $seq;
		return true;
	}

	function getSubFolders() {
		GLOBAL $db;
		
		if (!isset($this->_subFolders))
		{
			$queryStr = "SELECT * FROM tblFolders WHERE parent = " . $this->_id . " ORDER BY sequence";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;
			
			$this->_subFolders = array();
			for ($i = 0; $i < count($resArr); $i++)
				$this->_subFolders[$i] = new Folder($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["parent"], $resArr[$i]["comment"], $resArr[$i]["owner"], $resArr[$i]["inheritAccess"], $resArr[$i]["defaultAccess"], $resArr[$i]["sequence"]);
		}
		
		return $this->_subFolders;
	}

	function addSubFolder($name, $comment, $owner, $sequence) {
		GLOBAL $db, $user, $settings;

		//inheritAccess = true, defaultAccess = M_READ
		$queryStr = "INSERT INTO tblFolders (name, parent, comment, owner, inheritAccess, defaultAccess, sequence) ".
					"VALUES ('".$name."', ".$this->_id.", '".$comment."', ".$owner->getID().", 1, ".M_READ.", ".$sequence.")";
		if (!$db->getResult($queryStr))
			return false;
		$newFolder = getFolder($db->getInsertID());
		unset($this->_subFolders);

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("new_subfolder_email");
		$message = getMLText("new_subfolder_email")."\r\n";
		$message .= 
			getMLText("name").": ".$name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($newFolder)."\r\n".
			getMLText("comment").": ".$comment."\r\n".
			getMLText("user").": ".$owner->getFullName()."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$newFolder->getID()."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}

		return $newFolder;
	}

	/**
	 * Gibt ein Array mit allen Eltern, "Großelter" usw bis zum RootFolder zurück
	 * Der Ordner selbst ist das letzte Element dieses Arrays
	 */
	function getPath() {
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
	}

	/**
	 * Überprüft, ob dieser Ordner ein Unterordner von $folder ist
	 */
	function isDescendant($folder)
	{
		if ($this->_parentID == $folder->getID())
			return true;
		else if (isset($this->_parentID))
		{
			$res = $this->getParent();
			if (!$res) return false;
			
			return $this->_parent->isDescendant($folder);
		}
		else
			return false;
	}

	function getDocuments()
	{
		GLOBAL $db;
		
		if (!isset($this->_documents))
		{
			$queryStr = "SELECT tblDocuments.*, tblDocumentLocks.userID as lockUser ".
				"FROM tblDocuments ".
				"LEFT JOIN tblDocumentLocks ON tblDocuments.id=tblDocumentLocks.document ".
				"WHERE folder = " . $this->_id . " ORDER BY sequence";

			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;
			
			$this->_documents = array();
			foreach ($resArr as $row) {
				array_push($this->_documents, new Document($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]));
			}
		}
		return $this->_documents;
	}

	function addDocument($name, $comment, $expires, $owner, $keywords, $tmpFile, $orgFileName, $fileType, $mimeType, $sequence, $reviewers=array(), $approvers=array()) {
		GLOBAL $db, $user, $settings;
		
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
		
		$document = getDocument($db->getInsertID());
		$res = $document->addContent($comment, $owner, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers, $approvers,FALSE);
		if (is_bool($res) && !$res)
		{
			$queryStr = "DELETE FROM tblDocuments WHERE id = " . $document->getID();
			$db->getResult($queryStr);
			return false;
		}

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("new_document_email");
		$message = getMLText("new_document_email")."\r\n";
		$message .= 
			getMLText("name").": ".$name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this)."\r\n".
			getMLText("comment").": ".$comment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID()."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}

		return array($document, $res);
	}

	
	function remove($send_email=TRUE) {
		global $db, $user, $settings;

		// Do not delete the root folder.
		if ($this->_id == $settings->_rootFolderID || !isset($this->_parentID) || ($this->_parentID == null) || ($this->_parentID == "") || ($this->_parentID == 0)) {
			return false;
		}

		//Entfernen der Unterordner und Dateien
		$res = $this->getSubFolders();
		if (is_bool($res) && !$res) return false;
		$res = $this->getDocuments();
		if (is_bool($res) && !$res) return false;
		
		foreach ($this->_subFolders as $subFolder)
		{
			$res = $subFolder->remove(FALSE);
			if (!$res) return false;
		}
		
		foreach ($this->_documents as $document)
		{
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

		// Send notification to subscribers.
		if ($send_email){
		
			$this->getNotifyList();
			$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("folder_deleted_email");
			$message = getMLText("folder_deleted_email")."\r\n";
			$message .= 
				getMLText("name").": ".$this->_name."\r\n".
				getMLText("folder").": ".getFolderPathPlain($this)."\r\n".
				getMLText("comment").": ".$this->_comment."\r\n".
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$this->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			Email::toList($user, $this->_notifyList["users"], $subject, $message);
			foreach ($this->_notifyList["groups"] as $grp) {
				Email::toGroup($user, $grp, $subject, $message);
			}
		}

		$queryStr = "DELETE FROM tblNotify WHERE target = ". $this->_id. " AND targetType = " . T_FOLDER;
		if (!$db->getResult($queryStr))
			return false;
		
		return true;
	}


	function getAccessList($mode = M_ANY, $op = O_EQ)
	{
		GLOBAL $db;

		if ($this->inheritsAccess())
		{
			$res = $this->getParent();
			if (!$res) return false;
			return $this->_parent->getAccessList($mode, $op);
		}

		if (!isset($this->_accessList[$mode]))
		{
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
			foreach ($resArr as $row)
			{
				if ($row["userID"] != -1)
					array_push($this->_accessList[$mode]["users"], new UserAccess($row["userID"], $row["mode"]));
				else //if ($row["groupID"] != -1)
					array_push($this->_accessList[$mode]["groups"], new GroupAccess($row["groupID"], $row["mode"]));
			}
		}

		return $this->_accessList[$mode];
	}

	function clearAccessList()
	{
		GLOBAL $db;
		
		$queryStr = "DELETE FROM tblACLs WHERE targetType = " . T_FOLDER . " AND target = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		unset($this->_accessList);
		return true;
	}

	function addAccess($mode, $userOrGroupID, $isUser) {
		GLOBAL $db;

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
	}

	function changeAccess($newMode, $userOrGroupID, $isUser) {
		GLOBAL $db;

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
	}

	function removeAccess($userOrGroupID, $isUser) {
		GLOBAL $db;

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		$queryStr = "DELETE FROM tblACLs WHERE targetType = ".T_FOLDER." AND target = ".$this->_id." AND ".$userOrGroup." = " . $userOrGroupID;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		$mode = ($isUser ? $this->getAccessMode(getUser($userOrGroupID)) : $this->getGroupAccessMode(getGroup($userOrGroupID)));
		if ($mode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	}

	/*
	 * Liefert die Art der Zugriffsberechtigung für den User $user; Mögliche Rechte: n (keine), r (lesen), w (schreiben+lesen), a (alles)
	 * Zunächst wird Geprüft, ob die Berechtigung geerbt werden soll; in diesem Fall wird die Anfrage an den Eltern-Ordner weitergeleitet.
	 * Ansonsten werden die ACLs durchgegangen: Die höchstwertige Berechtigung gilt.
	 * Wird bei den ACLs nicht gefunden, wird die Standard-Berechtigung zurückgegeben.
	 * Ach ja: handelt es sich bei $user um den Besitzer ist die Berechtigung automatisch "a".
	 */
	function getAccessMode($user)
	{
		GLOBAL $settings;
		
		//Admin??
		if ($user->isAdmin()) return M_ALL;
		
		//Besitzer ??
		if ($user->getID() == $this->_ownerID) return M_ALL;
		
		//Gast-Benutzer??
		if (($user->getID() == $settings->_guestID) && ($settings->_enableGuestLogin))
		{
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

		//ACLs durchforsten
		$accessList = $this->getAccessList();
		if (!$accessList) return false;

		foreach ($accessList["users"] as $userAccess)
		{
			if ($userAccess->getUserID() == $user->getID())
			{
				return $userAccess->getMode();
			}
		}
		foreach ($accessList["groups"] as $groupAccess)
		{
			if ($user->isMemberOfGroup($groupAccess->getGroup()))
			{
				return $groupAccess->getMode();
			}
		}
		return $this->getDefaultAccess();
	}

	function getGroupAccessMode($group) {
		GLOBAL $settings;

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
	}

	function getNotifyList()
	{
		if (!isset($this->_notifyList))
		{
			GLOBAL $db;

			$queryStr ="SELECT * FROM tblNotify WHERE targetType = " . T_FOLDER . " AND target = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_notifyList = array("groups" => array(), "users" => array());
			foreach ($resArr as $row)
			{
				if ($row["userID"] != -1)
					array_push($this->_notifyList["users"], getUser($row["userID"]) );
				else //if ($row["groupID"] != -1)
					array_push($this->_notifyList["groups"], getGroup($row["groupID"]) );
			}
		}
		return $this->_notifyList;
	}

	function addNotify($userOrGroupID, $isUser) {

		// Return values:
		// -1: Invalid User/Group ID.
		// -2: Target User / Group does not have read access.
		// -3: User is already subscribed.
		// -4: Database / internal error.
		//  0: Update successful.

		GLOBAL $db, $settings, $user;

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		//
		// Verify that user / group exists.
		//
		$obj = ($isUser ? getUser($userOrGroupID) : getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		//
		// Verify that the requesting user has permission to add the target to
		// the notification system.
		//
		if ($user->getID() == $settings->_guestID) {
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

		// Email user / group, informing them of subscription.
		$path="";
		$folderPath = $this->getPath();
		for ($i = 0; $i  < count($folderPath); $i++) {
			$path .= $folderPath[$i]->getName();
			if ($i +1 < count($folderPath))
				$path .= " / ";
		}
		$subject = $settings->_siteName.": ".$this->getName()." - ".getMLText("notify_added_email");
		$message = getMLText("notify_added_email")."\r\n";
		$message .= 
			getMLText("name").": ".$this->getName()."\r\n".
			getMLText("folder").": ".$path."\r\n".
			getMLText("comment").": ".$this->getComment()."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$this->_id."\r\n";


		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		if ($isUser) {
			Email::toIndividual($user, $obj, $subject, $message);
		}
		else {
			Email::toGroup($user, $obj, $subject, $message);
		}

		unset($this->_notifyList);
		return 0;
	}

	function removeNotify($userOrGroupID, $isUser) {

		// Return values:
		// -1: Invalid User/Group ID.
		// -3: User is not subscribed. No action taken.
		// -4: Database / internal error.
		//  0: Update successful.

		GLOBAL $db, $settings, $user;
		
		//
		// Verify that user / group exists.
		//
		$obj = ($isUser ? getUser($userOrGroupID) : getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		$userOrGroup = ($isUser) ? "userID" : "groupID";

		//
		// Verify that the requesting user has permission to add the target to
		// the notification system.
		//
		if ($user->getID() == $settings->_guestID) {
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

		// Email user / group, informing them of subscription.
		$path="";
		$folderPath = $this->getPath();
		for ($i = 0; $i  < count($folderPath); $i++) {
			$path .= $folderPath[$i]->getName();
			if ($i +1 < count($folderPath))
				$path .= " / ";
		}
		$subject = $settings->_siteName.": ".$this->getName()." - ".getMLText("notify_deleted_email");
		$message = getMLText("notify_deleted_email")."\r\n";
		$message .= 
			getMLText("name").": ".$this->getName()."\r\n".
			getMLText("folder").": ".$path."\r\n".
			getMLText("comment").": ".$this->getComment()."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		if ($isUser) {
			Email::toIndividual($user, $obj, $subject, $message);
		}
		else {
			Email::toGroup($user, $obj, $subject, $message);
		}

		unset($this->_notifyList);
		return 0;
	}

	function getApproversList() {
		GLOBAL $db, $settings;

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
				if ($user->getUserID()!=$settings->_guestID) {
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
						"AND `tblUsers`.`id` !='".$settings->_guestID."')";
				}
				$queryStr .= (strlen($queryStr)==0 ? "" : " UNION ").
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`id` !='".$settings->_guestID."') ".
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
						"AND `tblUsers`.`id` != '".$settings->_guestID."' ".
						(strlen($userIDs) == 0 ? ")" : " AND (`tblUsers`.`id` NOT IN (". $userIDs .")))");
				}
				$queryStr .= (strlen($queryStr)==0 ? "" : " UNION ").
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`isAdmin` = 1))".
					"UNION ".
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE `tblUsers`.`id` != '".$settings->_guestID."' ".
					(strlen($userIDs) == 0 ? ")" : " AND (`tblUsers`.`id` NOT IN (". $userIDs .")))").
					" ORDER BY `login`";
			}
			$resArr = $db->getResultArray($queryStr);
			if (!is_bool($resArr)) {
				foreach ($resArr as $row) {
					$this->_approversList["users"][] = new User($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $row["isAdmin"]);
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
						$this->_approversList["groups"][] = new Group($row["id"], $row["name"], $row["comment"]);
					}
				}
			}
		}
		return $this->_approversList;
	}
}

?>
