<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
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

define("S_DRAFT_REV", 0);
define("S_DRAFT_APP", 1);
define("S_RELEASED",  2);
define("S_REJECTED", -1);
define("S_OBSOLETE", -2);
define("S_EXPIRED",  -3);

function getDocument($id)
{
	GLOBAL $db;
	
	if (!is_numeric($id)) return false;
	
	$queryStr = "SELECT * FROM tblDocuments WHERE id = " . $id;
	$resArr = $db->getResultArray($queryStr);
	if (is_bool($resArr) && $resArr == false)
		return false;
	if (count($resArr) != 1)
		return false;
	$resArr = $resArr[0];

	// New Locking mechanism uses a separate table to track the lock.
	$queryStr = "SELECT * FROM tblDocumentLocks WHERE document = " . $id;
	$lockArr = $db->getResultArray($queryStr);
	if ((is_bool($lockArr) && $lockArr==false) || (count($lockArr)==0)) {
		// Could not find a lock on the selected document.
		$lock = -1;
	}
	else {
		// A lock has been identified for this document.
		$lock = $lockArr[0]["userID"];
	}

	return new Document($resArr["id"], $resArr["name"], $resArr["comment"], $resArr["date"], $resArr["expires"], $resArr["owner"], $resArr["folder"], $resArr["inheritAccess"], $resArr["defaultAccess"], $lock, $resArr["keywords"], $resArr["sequence"]);
}


// these are the document information (all versions)
class Document
{
	var $_id;
	var $_name;
	var $_comment;
	var $_ownerID;
	var $_folderID;
	var $_expires;
	var $_inheritAccess;
	var $_defaultAccess;
	var $_locked;
	var $_keywords;
	var $_sequence;
	
	function Document($id, $name, $comment, $date, $expires, $ownerID, $folderID, $inheritAccess, $defaultAccess, $locked, $keywords, $sequence)
	{
		$this->_id = $id;
		$this->_name = $name;
		$this->_comment = $comment;
		$this->_date = $date;
		$this->_expires = $expires;
		$this->_ownerID = $ownerID;
		$this->_folderID = $folderID;
		$this->_inheritAccess = $inheritAccess;
		$this->_defaultAccess = $defaultAccess;
		$this->_locked = ($locked == null || $locked == '' ? -1 : $locked);
		$this->_keywords = $keywords;
		$this->_sequence = $sequence;
	}
	
	function getDir() {
		global $settings;
		return $settings->_contentOffsetDir."/".$this->_id."/";
	}


	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) {
		GLOBAL $db, $user, $settings;
		
		$queryStr = "UPDATE tblDocuments SET name = '" . $newName . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("document_renamed_email");
		$message = getMLText("document_renamed_email")."\r\n";
		$message .= 
			getMLText("old").": ".$this->_name."\r\n".
			getMLText("new").": ".$newName."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this->getFolder())."\r\n".
			getMLText("comment").": ".$this->getComment()."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}
		
		// if user is not owner send notification to owner
		if ($user->getID()!= $this->_ownerID) 
			Email::toIndividual($user, $this->getOwner(), $subject, $message);		

		$this->_name = $newName;
		return true;
	}

	function getComment() { return $this->_comment; }

	function setComment($newComment) {
		GLOBAL $db, $user, $settings;

		$queryStr = "UPDATE tblDocuments SET comment = '" . $newComment . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("comment_changed_email");
		$message = getMLText("comment_changed_email")."\r\n";
		$message .= 
			getMLText("document").": ".$this->_name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this->getFolder())."\r\n".
			getMLText("comment").": ".$newComment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}

		$this->_comment = $newComment;
		return true;
	}

	function getKeywords() { return $this->_keywords; }

	function setKeywords($newKeywords)
	{
		GLOBAL $db, $user, $settings;
		
		$queryStr = "UPDATE tblDocuments SET keywords = '" . $newKeywords . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		$this->_keywords = $newKeywords;
		return true;
	}

	function getDate()
	{
		return $this->_date;
	}

	function getFolder()
	{
		if (!isset($this->_folder))
			$this->_folder = getFolder($this->_folderID);
		return $this->_folder;
	}

	function setFolder($newFolder)
	{
		GLOBAL $db, $user, $settings;
		
		$queryStr = "UPDATE tblDocuments SET folder = " . $newFolder->getID() . " WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$this->_folderID = $newFolder->getID();
		$this->_folder = $newFolder;

		// Make sure that the folder search path is also updated.
		$path = $newFolder->getPath();
		$flist = "";
		foreach ($path as $f) {
			$flist .= ":".$f->getID();
		}
		if (strlen($flist)>1) {
			$flist .= ":";
		}
		$queryStr = "UPDATE tblDocuments SET folderList = '" . $flist . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("document_moved_email");
		$message = getMLText("document_moved_email")."\r\n";
		$message .= 
			getMLText("document").": ".$this->_name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this->getFolder())."\r\n".
			getMLText("comment").": ".$newComment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}
		
		// if user is not owner send notification to owner
		if ($user->getID()!= $this->_ownerID) 
			Email::toIndividual($user, $this->getOwner(), $subject, $message);		

		return true;
	}

	function getOwner() {
		if (!isset($this->_owner))
			$this->_owner = getUser($this->_ownerID);
		return $this->_owner;
	}

	function setOwner($newOwner) {
		GLOBAL $db, $user, $settings;

		$oldOwner = $this->getOwner();

		$queryStr = "UPDATE tblDocuments set owner = " . $newOwner->getID() . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("ownership_changed_email");
		$message = getMLText("ownership_changed_email")."\r\n";
		$message .= 
			getMLText("document").": ".$this->_name."\r\n".
			getMLText("old").": ".$oldOwner->getFullName()."\r\n".
			getMLText("new").": ".$newOwner->getFullName()."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this->getFolder())."\r\n".
			getMLText("comment").": ".$this->_comment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}
		// Send notification to previous owner.
		Email::toIndividual($user, $oldOwner, $subject, $message);

		$this->_ownerID = $newOwner->getID();
		$this->_owner = $newOwner;
		return true;
	}

	function getDefaultAccess()
	{
		if ($this->inheritsAccess())
		{
			$res = $this->getFolder();
			if (!$res) return false;
			return $this->_folder->getDefaultAccess();
		}
		return $this->_defaultAccess;
	}

	function setDefaultAccess($mode) {
		GLOBAL $db, $user, $settings;
		
		$queryStr = "UPDATE tblDocuments set defaultAccess = " . $mode . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("access_permission_changed_email");
		$message = getMLText("access_permission_changed_email")."\r\n";
		$message .= 
			getMLText("document").": ".$this->_name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this->getFolder())."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_id."\r\n";

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
		
		$queryStr = "UPDATE tblDocuments SET inheritAccess = " . ($inheritAccess ? "1" : "0") . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		$this->_inheritAccess = ($inheritAccess ? "1" : "0");

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("access_permission_changed_email");
		$message = getMLText("access_permission_changed_email")."\r\n";
		$message .= 
			getMLText("document").": ".$this->_name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this->getFolder())."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_id."\r\n";

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

	function expires()
	{
		if (intval($this->_expires) == 0)
			return false;
		else
			return true;
	}

	function getExpires()
	{
		if (intval($this->_expires) == 0)
			return false;
		else
			return $this->_expires;
	}

	function setExpires($expires) {
		GLOBAL $db, $user, $settings;
		
		$expires = (!$expires) ? 0 : $expires;

		if ($expires == $this->_expires) {
			// No change is necessary.
			return true;
		}

		$queryStr = "UPDATE tblDocuments SET expires = " . $expires . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("expiry_changed_email");
		$message = getMLText("expiry_changed_email")."\r\n";
		$message .= 
			getMLText("document").": ".$this->_name."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this->getFolder())."\r\n".
			getMLText("comment").": ".$this->getComment()."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}

		$this->_expires = $expires;
		return true;
	}

	function hasExpired(){
	
		if (intval($this->_expires) == 0) return false;
		if (time()>$this->_expires+24*60*60) return true;
		return false;
	}
	
	// return true if status has changed (to reload page)
	function verifyLastestContentExpriry(){
		
		$lc=$this->getLatestContent();
		$st=$lc->getStatus();
		
		if (($st["status"]==S_DRAFT_REV || $st["status"]==S_DRAFT_APP) && $this->hasExpired()){
			$lc->setStatus(S_EXPIRED,"");
			return true;
		}
		else if ($st["status"]==S_EXPIRED && !$this->hasExpired() ){
			$lc->verifyStatus(true);
			return true;
		}
		return false;
	}
	
	function isLocked() { return $this->_locked != -1; }

	function setLocked($falseOrUser)
	{
		GLOBAL $db;
		
		$lockUserID = -1;
		if (is_bool($falseOrUser) && !$falseOrUser) {
			$queryStr = "DELETE FROM tblDocumentLocks WHERE document = ".$this->_id;
		}
		else if (is_object($falseOrUser)) {
			$queryStr = "INSERT INTO tblDocumentLocks (document, userID) VALUES (".$this->_id.", ".$falseOrUser->getID().")";
			$lockUserID = $falseOrUser->getID();
		}
		else {
			return false;
		}
		if (!$db->getResult($queryStr)) {
			return false;
		}
		unset($this->_lockingUser);
		$this->_locked = $lockUserID;
		return true;
	}

	function getLockingUser()
	{
		if (!$this->isLocked())
			return false;
		
		if (!isset($this->_lockingUser))
			$this->_lockingUser = getUser($this->_locked);
		return $this->_lockingUser;
	}

	function getSequence() { return $this->_sequence; }

	function setSequence($seq)
	{
		GLOBAL $db;
		
		$queryStr = "UPDATE tblDocuments SET sequence = " . $seq . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		$this->_sequence = $seq;
		return true;
	}

	function clearAccessList()
	{
		GLOBAL $db;
		
		$queryStr = "DELETE FROM tblACLs WHERE targetType = " . T_DOCUMENT . " AND target = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		unset($this->_accessList);
		return true;
	}

	function getAccessList($mode = M_ANY, $op = O_EQ)
	{
		GLOBAL $db;
		
		if ($this->inheritsAccess())
		{
			$res = $this->getFolder();
			if (!$res) return false;
			return $this->_folder->getAccessList($mode, $op);
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
			$queryStr = "SELECT * FROM tblACLs WHERE targetType = ".T_DOCUMENT.
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

	function addAccess($mode, $userOrGroupID, $isUser) {
		GLOBAL $db;
		
		$userOrGroup = ($isUser) ? "userID" : "groupID";
		
		$queryStr = "INSERT INTO tblACLs (target, targetType, ".$userOrGroup.", mode) VALUES 
					(".$this->_id.", ".T_DOCUMENT.", " . $userOrGroupID . ", " .$mode. ")";
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

		$queryStr = "UPDATE tblACLs SET mode = " . $newMode . " WHERE targetType = ".T_DOCUMENT." AND target = " . $this->_id . " AND " . $userOrGroup . " = " . $userOrGroupID;
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

		$queryStr = "DELETE FROM tblACLs WHERE targetType = ".T_DOCUMENT." AND target = ".$this->_id." AND ".$userOrGroup." = " . $userOrGroupID;
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
		
		//Administrator??
		if ($user->isAdmin()) return M_ALL;
		
		//Besitzer??
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
			if (!$this->getFolder())
				return false;
			return $this->_folder->getAccessMode($user);
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
		global $settings;

		$highestPrivileged = M_NONE;
		
		//ACLs durchforsten
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

	function getNotifyList() {
		if (!isset($this->_notifyList))
		{
			GLOBAL $db;
			
			$queryStr ="SELECT * FROM tblNotify WHERE targetType = " . T_DOCUMENT . " AND target = " . $this->_id;
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

	function addNotify($userOrGroupID, $isUser,$send_email=TRUE) {

		// Return values:
		// -1: Invalid User/Group ID.
		// -2: Target User / Group does not have read access.
		// -3: User is already subscribed.
		// -4: Database / internal error.
		//  0: Update successful.

		global $db, $settings, $user;

		$userOrGroup = ($isUser ? "userID" : "groupID");

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
		// Verify that target user / group has read access to the document.
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
			"AND `tblNotify`.`targetType` = '".T_DOCUMENT."' ".
			"AND `tblNotify`.`".$userOrGroup."` = '".$userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)>0) {
			return -3;
		}

		$queryStr = "INSERT INTO tblNotify (target, targetType, " . $userOrGroup . ") VALUES (" . $this->_id . ", " . T_DOCUMENT . ", " . $userOrGroupID . ")";
		if (!$db->getResult($queryStr))
			return -4;

		// Email user / group, informing them of subscription.
		if ($send_email){
			$path="";
			$folder = $this->getFolder();
			$folderPath = $folder->getPath();
			for ($i = 0; $i  < count($folderPath); $i++) {
				$path .= $folderPath[$i]->getName();
				if ($i +1 < count($folderPath))
					$path .= " / ";
			}
			$subject = $settings->_siteName.": ".$this->getName()." - ".getMLText("notify_added_email");
			$message = getMLText("notify_added_email")."\r\n";
			$message .= 
				getMLText("document").": ".$this->getName()."\r\n".
				getMLText("folder").": ".$path."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			if ($isUser) {
				Email::toIndividual($user, $obj, $subject, $message);
			}
			else {
				Email::toGroup($user, $obj, $subject, $message);
			}
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
			"AND `tblNotify`.`targetType` = '".T_DOCUMENT."' ".
			"AND `tblNotify`.`".$userOrGroup."` = '".$userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)==0) {
			return -3;
		}

		$queryStr = "DELETE FROM tblNotify WHERE target = " . $this->_id . " AND targetType = " . T_DOCUMENT . " AND " . $userOrGroup . " = " . $userOrGroupID;
		if (!$db->getResult($queryStr))
			return -4;
		
		// Email user / group, informing them of subscription change.
		$path="";
		$folder = $this->getFolder();
		$folderPath = $folder->getPath();
		for ($i = 0; $i  < count($folderPath); $i++) {
			$path .= $folderPath[$i]->getName();
			if ($i +1 < count($folderPath))
				$path .= " / ";
		}
		$subject = $settings->_siteName.": ".$this->getName()." - ".getMLText("notify_deleted_email");
		$message = getMLText("notify_deleted_email")."\r\n";
		$message .= 
			getMLText("document").": ".$this->getName()."\r\n".
			getMLText("folder").": ".$path."\r\n".
			getMLText("comment").": ".$this->getComment()."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_id."\r\n";

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
	
	function addContent($comment, $user, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers=array(), $approvers=array(),$version=0,$send_email=TRUE)
	{
		GLOBAL $db, $settings;
		
		// the doc path is id/version.filetype
		$dir = $this->getDir();

		//Eintrag in tblDocumentContent
		$date = mktime();
		
		if ((int)$version<1){

			$queryStr = "INSERT INTO tblDocumentContent (document, comment, date, createdBy, dir, orgFileName, fileType, mimeType) VALUES ".
						"(".$this->_id.", '".$comment."', ".$date.", ".$user->getID().", '".$dir."', '".$orgFileName."', '".$fileType."', '" . $mimeType . "')";
			if (!$db->getResult($queryStr)) return false;

			$version = $db->getInsertID();
		
		}else{		
			$queryStr = "INSERT INTO tblDocumentContent (document, version, comment, date, createdBy, dir, orgFileName, fileType, mimeType) VALUES ".
						"(".$this->_id.", ".(int)$version.",'".$comment."', ".$date.", ".$user->getID().", '".$dir."', '".$orgFileName."', '".$fileType."', '" . $mimeType . "')";
			if (!$db->getResult($queryStr)) return false;
		}

		// copy file
		if (!makeDir($settings->_contentDir . $dir)) return false;
		if (!copyFile($tmpFile, $settings->_contentDir . $dir . $version . $fileType)) return false;

		unset($this->_content);
		unset($this->_latestContent);
		$docResultSet = new AddContentResultSet(new DocumentContent($this->_id, $version, $comment, $date, $user->getID(), $dir, $orgFileName, $fileType, $mimeType));

		// TODO - verify
		if ($settings->_enableConverting && in_array($docResultSet->_content->getFileType(), array_keys($settings->_convertFileTypes)))
			$docResultSet->_content->convert(); //Auch wenn das schiefgeht, wird deswegen nicht gleich alles "hingeschmissen" (sprich: false zurückgegeben)

		$queryStr = "INSERT INTO `tblDocumentStatus` (`documentID`, `version`) ".
			"VALUES ('". $this->_id ."', '". $version ."')";
		if (!$db->getResult($queryStr))
			return false;

		$statusID = $db->getInsertID();

		// Add reviewers into the database. Reviewers must review the document
		// and submit comments, if appropriate. Reviewers can also recommend that
		// a document be rejected.
		$pendingReview=false;
		$reviewRes = array();
		foreach (array("i", "g") as $i){
			if (isset($reviewers[$i])) {
				foreach ($reviewers[$i] as $reviewerID) {
					$reviewer=($i=="i" ?getUser($reviewerID) : getGroup($reviewerID));
					$res = ($i=="i" ? $docResultSet->_content->addIndReviewer($reviewer, $user, true) : $docResultSet->_content->addGrpReviewer($reviewer, $user, true));
					$docResultSet->addReviewer($reviewer, $i, $res);
					// If no error is returned, or if the error is just due to email
					// failure, mark the state as "pending review".
					if ($res==0 || $res=-3 || $res=-4) {
						$pendingReview=true;
					}
				}
			}
		}
		// Add approvers to the database. Approvers must also review the document
		// and make a recommendation on its release as an approved version.
		$pendingApproval=false;
		$approveRes = array();
		foreach (array("i", "g") as $i){
			if (isset($approvers[$i])) {
				foreach ($approvers[$i] as $approverID) {
					$approver=($i=="i" ? getUser($approverID) : getGroup($approverID));
					$res=($i=="i" ? $docResultSet->_content->addIndApprover($approver, $user, !$pendingReview) : $docResultSet->_content->addGrpApprover($approver, $user, !$pendingReview));
					$docResultSet->addApprover($approver, $i, $res);
					if ($res==0 || $res=-3 || $res=-4) {
						$pendingApproval=true;
					}
				}
			}
		}

		// If there are no reviewers or approvers, the document is automatically
		// promoted to the released state.
		if ($pendingReview) {
			$status = S_DRAFT_REV;
			$comment = "";
		}
		else if ($pendingApproval) {
			$status = S_DRAFT_APP;
			$comment = "";
		}
		else {
			$status = S_RELEASED;
			$comment="";
		}
		$queryStr = "INSERT INTO `tblDocumentStatusLog` (`statusID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $statusID ."', '". $status."', 'New document content submitted". $comment ."', NOW(), '". $user->getID() ."')";
		if (!$db->getResult($queryStr))
			return false;

		$docResultSet->setStatus($status,$comment,$user);

		// Send notification to subscribers.
		if ($send_email){
			$this->getNotifyList();
			$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("document_updated_email");
			$message = getMLText("document_updated_email")."\r\n";
			$message .= 
				getMLText("document").": ".$this->_name."\r\n".
				getMLText("folder").": ".getFolderPathPlain($this->getFolder())."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			Email::toList($user, $this->_notifyList["users"], $subject, $message);
			foreach ($this->_notifyList["groups"] as $grp) {
				Email::toGroup($user, $grp, $subject, $message);
			}
		}
		
		// if user is not owner send notification to owner
		if ($user->getID()!= $this->_ownerID) 
			Email::toIndividual($user, $this->getOwner(), $subject, $message);

		return $docResultSet;
	}

	function getContent()
	{
		GLOBAL $db;
		
		if (!isset($this->_content))
		{
			$queryStr = "SELECT * FROM tblDocumentContent WHERE document = ".$this->_id." ORDER BY version";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$res)
				return false;
			
			$this->_content = array();
			foreach ($resArr as $row)
				array_push($this->_content, new DocumentContent($row["document"], $row["version"], $row["comment"], $row["date"], $row["createdBy"], $row["dir"], $row["orgFileName"], $row["fileType"], $row["mimeType"]));
		}
		
		return $this->_content;
	}

	function getContentByVersion($version)
	{
		if (!is_numeric($version)) return false;
		
		if (isset($this->_content))
		{
			foreach ($this->_content as $revision)
			{
				if ($revision->getVersion() == $version)
					return $revision;
			}
			return false;
		}
		
		GLOBAL $db;
		$queryStr = "SELECT * FROM tblDocumentContent WHERE document = ".$this->_id." AND version = " . $version;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$res)
			return false;
		if (count($resArr) != 1)
			return false;
		
		$resArr = $resArr[0];
		return new DocumentContent($resArr["document"], $resArr["version"], $resArr["comment"], $resArr["date"], $resArr["createdBy"], $resArr["dir"], $resArr["orgFileName"], $resArr["fileType"], $resArr["mimeType"]);
	}

	function getLatestContent()
	{
		if (!isset($this->_latestContent))
		{
			GLOBAL $db;
			$queryStr = "SELECT * FROM tblDocumentContent WHERE document = ".$this->_id." ORDER BY version DESC LIMIT 0,1";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;
			if (count($resArr) != 1)
				return false;
			
			$resArr = $resArr[0];
			$this->_latestContent = new DocumentContent($resArr["document"], $resArr["version"], $resArr["comment"], $resArr["date"], $resArr["createdBy"], $resArr["dir"], $resArr["orgFileName"], $resArr["fileType"], $resArr["mimeType"]);
		}
		return $this->_latestContent;
	}

	function getDocumentLinks()
	{
		if (!isset($this->_documentLinks))
		{
			GLOBAL $db;
			
			$queryStr = "SELECT * FROM tblDocumentLinks WHERE document = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;
			$this->_documentLinks = array();
			
			foreach ($resArr as $row)
				array_push($this->_documentLinks, new DocumentLink($row["id"], $row["document"], $row["target"], $row["userID"], $row["public"]));
		}
		return $this->_documentLinks;
	}

	function addDocumentLink($targetID, $userID, $public)
	{
		GLOBAL $db;
		
		$public = ($public) ? "1" : "0";
		
		$queryStr = "INSERT INTO tblDocumentLinks(document, target, userID, public) VALUES (".$this->_id.", ".$targetID.", ".$userID.", " . $public.")";
		if (!$db->getResult($queryStr))
			return false;
		
		unset($this->_documentLinks);
		return true;
	}

	function removeDocumentLink($linkID)
	{
		GLOBAL $db;
		
		$queryStr = "DELETE FROM tblDocumentLinks WHERE id = " . $linkID;
		if (!$db->getResult($queryStr)) return false;
		unset ($this->_documentLinks);
		return true;
	}
	
	function getDocumentFiles()
	{
		if (!isset($this->_documentFiles))
		{
			GLOBAL $db;
			
			$queryStr = "SELECT * FROM tblDocumentFiles WHERE document = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr) return false;
				
			$this->_documentFiles = array();
			
			foreach ($resArr as $row)
				array_push($this->_documentFiles, new DocumentFile($row["id"], $row["document"], $row["userID"], $row["comment"], $row["date"], $row["dir"], $row["fileType"], $row["mimeType"], $row["orgFileName"], $row["name"]));
		}
		return $this->_documentFiles;		
	}

	function addDocumentFile($name, $comment, $user, $tmpFile, $orgFileName,$fileType, $mimeType )
	{
		GLOBAL $db, $settings;
		
		$dir = $settings->_contentOffsetDir."/".$this->_id."/";
	
		$queryStr = "INSERT INTO tblDocumentFiles (comment, date, dir, document, fileType, mimeType, orgFileName, userID, name) VALUES ".
			"('".$comment."', '".mktime()."', '" . $dir ."', " . $this->_id.", '".$fileType."', '".$mimeType."', '".$orgFileName."',".$user->getID().",'".$name."')";
		if (!$db->getResult($queryStr)) return false;
			
		$id = $db->getInsertID();
		
		$file = getDocumentFile($id);
		if (is_bool($file) && !$file) return false;

		// copy file
		if (!makeDir($settings->_contentDir . $dir)) return false;
		if (!copyFile($tmpFile, $settings->_contentDir . $file->getPath() )) return false;
		
		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("new_file_email");
		$message = getMLText("new_file_email")."\r\n";
		$message .= 
			getMLText("name").": ".$name."\r\n".
			getMLText("comment").": ".$comment."\r\n".
			getMLText("user").": ".$user->getFullName()." <". $user->getEmail() .">\r\n".	
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->getID()."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}
		
		return true;
	}
	
	function removeDocumentFile($ID)
	{
		global $settings,$user;
	
		$file=getDocumentFile($ID);
		if (is_bool($file) && !$file) return false;
					
		if (file_exists( $settings->_contentDir . $file->getPath() )){
			if (!removeFile( $settings->_contentDir . $file->getPath() ))
				return false;
		}
		
		$name=$file->getName();
		$comment=$file->getcomment();
		
		$file->remove();
			
		unset ($this->_documentFiles);
		
		// Send notification to subscribers.
		$this->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_name." - ".getMLText("removed_file_email");
		$message = getMLText("removed_file_email")."\r\n";
		$message .= 
			getMLText("name").": ".$name."\r\n".
			getMLText("comment").": ".$comment."\r\n".
			getMLText("user").": ".$user->getFullName()." <". $user->getEmail() .">\r\n".	
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->getID()."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $this->_notifyList["users"], $subject, $message);
		foreach ($this->_notifyList["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}
	
		return true;
	}

	function remove($send_email=TRUE)
	{
		GLOBAL $db, $user, $settings;
		
		$res = $this->getContent();
		if (is_bool($res) && !$res) return false;
		
		for ($i = 0; $i < count($this->_content); $i++)
			if (!$this->_content[$i]->remove(FALSE))
				return false;
				
		// remove document file
		$res = $this->getDocumentFiles();
		if (is_bool($res) && !$res) return false;
		
		for ($i = 0; $i < count($this->_documentFiles); $i++)
			if (!$this->_documentFiles[$i]->remove())
				return false;
				
		// TODO: versioning file?
				
		if (file_exists( $settings->_contentDir . $this->getDir() ))
			if (!removeDir( $settings->_contentDir . $this->getDir() ))
				return false;
		
		$queryStr = "DELETE FROM tblDocuments WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblACLs WHERE target = " . $this->_id . " AND targetType = " . T_DOCUMENT;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblDocumentLinks WHERE document = " . $this->_id . " OR target = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblDocumentLocks WHERE document = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblDocumentFiles WHERE document = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$path = "";
		$folder = $this->getFolder();
		$folderPath = $folder->getPath();
		for ($i = 0; $i  < count($folderPath); $i++) {
			$path .= $folderPath[$i]->getName();
			if ($i +1 < count($folderPath))
				$path .= " / ";
		}
		
		if ($send_email){
	
			$subject = $settings->_siteName.": ".$this->getName()." - ".getMLText("document_deleted_email");
			$message = getMLText("document_deleted_email")."\r\n";
			$message .= 
				getMLText("document").": ".$this->getName()."\r\n".
				getMLText("folder").": ".$path."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				getMLText("user").": ".$user->getFullName()." <". $user->getEmail() ."> ";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			// Send notification to subscribers.
			$this->getNotifyList();
			Email::toList($user, $this->_notifyList["users"], $subject, $message);
			foreach ($this->_notifyList["groups"] as $grp) {
				Email::toGroup($user, $grp, $subject, $message);
			}
		}
		
		// Delete the notification list.
		$queryStr = "DELETE FROM tblNotify WHERE target = " . $this->_id . " AND targetType = " . T_DOCUMENT;
		if (!$db->getResult($queryStr))
			return false;

		return true;
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
				// having read access to the document.
				$tmpList = $this->getAccessList(M_READ, O_GTEQ);
			}
			else {
				// Get the list of all users and groups that DO NOT have read access
				// to the document.
				$tmpList = $this->getAccessList(M_NONE, O_LTEQ);
			}
			foreach ($tmpList["groups"] as $group) {
				$groupIDs .= (strlen($groupIDs)==0 ? "" : ", ") . $group->getGroupID();
			}
			foreach ($tmpList["users"] as $c_user) {
			
				if (!$settings->_enableAdminRevApp && $c_user->getUserID()==$settings->_adminID) continue;
				$userIDs .= (strlen($userIDs)==0 ? "" : ", ") . $c_user->getUserID();
			}

			// Construct a query against the users table to identify those users
			// that have read access to this document, either directly through an
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
					if ((!$settings->_enableAdminRevApp) && ($row["id"]==$settings->_adminID)) continue;					
					$this->_approversList["users"][] = new User($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $row["isAdmin"]);
				}
			}

			// Assemble the list of groups that have read access to the document.
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

 /* ---------------------------------------------------------------------------------------------------- */
 
/**
 * Die Datei wird als "data.ext" (z.b. data.txt) gespeichert. Getrennt davon wird in der DB der ursprüngliche
 * Dateiname festgehalten (-> $orgFileName). Die Datei wird deshalb nicht unter diesem ursprünglichen Namen
 * gespeichert, da es zu Problemen mit verschiedenen Dateisystemen kommen kann: Linux hat z.b. Probleme mit
 * deutschen Umlauten, während Windows wiederum den Doppelpunkt in Dateinamen nicht verwenden kann.
 * Der ursprüngliche Dateiname wird nur zum Download verwendet (siehe op.Download.pgp)
 */
 
// these are the version information
class DocumentContent
{

	// if status is released and there are reviewers set status draft_rev	
	// if status is released or draft_rev and there are approves set status draft_app
	// if status is draft and there are no approver and no reviewers set status to release	
	function verifyStatus($ignorecurrentstatus=false,$user=null){
	
		unset($this->_status);
		$st=$this->getStatus();
		
		if (!$ignorecurrentstatus && ($st["status"]==S_OBSOLETE || $st["status"]==S_REJECTED || $st["status"]==S_EXPIRED )) return;
		
		$pendingReview=false;
		unset($this->_reviewStatus);  // force to be reloaded from DB
		$reviewStatus=$this->getReviewStatus(true);	
		if (is_array($reviewStatus) && count($reviewStatus)>0) {
			foreach ($reviewStatus as $r){
				if ($r["status"]==0){	
					$pendingReview=true;
					break;
				}
			}
		}
		$pendingApproval=false;		
		unset($this->_approvalStatus);  // force to be reloaded from DB
		$approvalStatus=$this->getApprovalStatus(true);
		if (is_array($approvalStatus) && count($approvalStatus)>0) {
			foreach ($approvalStatus as $a){
				if ($a["status"]==0){
					$pendingApproval=true;
					break;
				}
			}
		}
		if ($pendingReview) $this->setStatus(S_DRAFT_REV,"",$user);
		else if ($pendingApproval) $this->setStatus(S_DRAFT_APP,"",$user);
		else $this->setStatus(S_RELEASED,"",$user);
	}

	function DocumentContent($documentID, $version, $comment, $date, $userID, $dir, $orgFileName, $fileType, $mimeType)
	{
		$this->_documentID = $documentID;
		$this->_version = $version;
		$this->_comment = $comment;
		$this->_date = $date;
		$this->_userID = $userID;
		$this->_dir = $dir;
		$this->_orgFileName = $orgFileName;
		$this->_fileType = $fileType;
		$this->_mimeType = $mimeType;
	}

	function getVersion() { return $this->_version; }
	function getComment() { return $this->_comment; }
	function getDate() { return $this->_date; }
	function getOriginalFileName() { return $this->_orgFileName; }
	function getFileType() { return $this->_fileType; }
	function getFileName(){ return "data" . $this->_fileType; }
	function getDir() { return $this->_dir; }
	function getMimeType() { return $this->_mimeType; }
	function getUser()
	{
		if (!isset($this->_user))
			$this->_user = getUser($this->_userID);
		return $this->_user;
	}
	function getPath() { return $this->_dir . $this->_version . $this->_fileType; }
	
	function setComment($newComment) {
	
		GLOBAL $db, $user, $settings;

		$queryStr = "UPDATE tblDocumentContent SET comment = '" . $newComment . "' WHERE `document` = " . $this->_documentID .	" AND `version` = " . $this->_version;
		if (!$db->getResult($queryStr))
			return false;

		$this->_comment = $newComment;
		
		// Send notification to subscribers.
		if (!isset($this->_document)) {
			$this->_document = getDocument($this->_documentID);
		}	

		$nl=$this->_document->getNotifyList();

		$subject = $settings->_siteName.": ".$this->_document->getName().", v.".$this->_version." - ".getMLText("comment_changed_email");
		$message = getMLText("comment_changed_email")."\r\n";
		$message .= 
			getMLText("document").": ".$this->_document->getName()."\r\n".
			getMLText("version").": ".$this->_version."\r\n".
			getMLText("comment").": ".$newComment."\r\n".
			getMLText("user").": ".$user->getFullName()." <". $user->getEmail() .">\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_documentID."&version=".$this->_version."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($user, $nl["users"], $subject, $message);
		foreach ($nl["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}

		return true;
	}


	function convert()
	{
		GLOBAL $settings;
		
		if (file_exists($settings->_contentDir . $this->_dir . "index.html"))
			return true;
		
		if (!in_array($this->_fileType, array_keys($settings->_convertFileTypes)))
			return false;
		
		$source = $settings->_contentDir . $this->_dir . $this->getFileName();
		$target = $settings->_contentDir . $this->_dir . "index.html";
	//	$source = str_replace("/", "\\", $source);
	//	$target = str_replace("/", "\\", $target);
		
		$command = $settings->_convertFileTypes[$this->_fileType];
		$command = str_replace("{SOURCE}", "\"$source\"", $command);
		$command = str_replace("{TARGET}", "\"$target\"", $command);
		
		$output = array();
		$res = 0;
		exec($command, $output, $res);
		
		if ($res != 0)
		{
			print (implode("\n", $output));
			return false;
		}
		return true;
	}

	function viewOnline()
	{
		GLOBAL $settings;

		if (!isset($settings->_viewOnlineFileTypes) || !is_array($settings->_viewOnlineFileTypes)) {
			return false;
		}

		if (in_array(strtolower($this->_fileType), $settings->_viewOnlineFileTypes)) return true;
		
		if ($settings->_enableConverting && in_array($this->_fileType, array_keys($settings->_convertFileTypes)))
			if ($this->wasConverted()) return true;
		
		return false;
	}

	function wasConverted()
	{
		GLOBAL $settings;
		
		return file_exists($settings->_contentDir . $this->_dir . "index.html");
	}

	function getURL()
	{
		GLOBAL $settings;
		
		if (!$this->viewOnline())return false;
		
		if (in_array(strtolower($this->_fileType), $settings->_viewOnlineFileTypes))
			return "/" . $this->_documentID . "/" . $this->_version . "/" . $this->getOriginalFileName();
		else
			return "/" . $this->_documentID . "/" . $this->_version . "/index.html";
	}

	// $send_email=FALSE is used when removing entire document 
	// to avoid one email for every version
	function remove($send_email=TRUE)
	{
		GLOBAL $settings, $db, $user;

		$emailList = array();
		$emailList[] = $this->_userID;

		if (file_exists( $settings->_contentDir.$this->getPath() ))
			if (!removeFile( $settings->_contentDir.$this->getPath() ))
				return false;
			
		$status = $this->getStatus();
		$stID = $status["statusID"];
			
		$queryStr = "DELETE FROM tblDocumentContent WHERE `document` = " . $this->_documentID .	" AND `version` = " . $this->_version;
		if (!$db->getResult($queryStr))
			return false;
		
		$queryStr = "DELETE FROM `tblDocumentStatusLog` WHERE `statusID` = '".$stID."'";
		if (!$db->getResult($queryStr))
			return false;
			
		$queryStr = "DELETE FROM `tblDocumentStatus` WHERE `documentID` = '". $this->_documentID ."' AND `version` = '" . $this->_version."'";
		if (!$db->getResult($queryStr))
			return false;

		$status = $this->getReviewStatus();
		$stList = "";
		foreach ($status as $st) {
			$stList .= (strlen($stList)==0 ? "" : ", "). "'".$st["reviewID"]."'";
			if ($st["status"]==0 && !in_array($st["required"], $emailList)) {
				$emailList[] = $st["required"];
			}
		}
		if (strlen($stList)>0) {
			$queryStr = "DELETE FROM `tblDocumentReviewLog` WHERE `tblDocumentReviewLog`.`reviewID` IN (".$stList.")";
			if (!$db->getResult($queryStr))
				return false;
		}
		$queryStr = "DELETE FROM `tblDocumentReviewers` WHERE `documentID` = '". $this->_documentID ."' AND `version` = '" . $this->_version."'";
		if (!$db->getResult($queryStr))
			return false;
		$status = $this->getApprovalStatus();
		$stList = "";
		foreach ($status as $st) {
			$stList .= (strlen($stList)==0 ? "" : ", "). "'".$st["approveID"]."'";
			if ($st["status"]==0 && !in_array($st["required"], $emailList)) {
				$emailList[] = $st["required"];
			}
		}
		if (strlen($stList)>0) {
			$queryStr = "DELETE FROM `tblDocumentApproveLog` WHERE `tblDocumentApproveLog`.`approveID` IN (".$stList.")";
			if (!$db->getResult($queryStr))
				return false;
		}
		$queryStr = "DELETE FROM `tblDocumentApprovers` WHERE `documentID` = '". $this->_documentID ."' AND `version` = '" . $this->_version."'";
		if (!$db->getResult($queryStr))
			return false;

		// Notify affected users.
		if ($send_email){
		
			if (!isset($this->_document)) {
				$this->_document = getDocument($this->_documentID);
			}	
		
			$recipients = array();
			foreach ($emailList as $eID) {
				$eU = getUser($eID);
				$recipients[] = $eU;
			}
			$subject = $settings->_siteName.": ".$this->_document->getName().", v.".$this->_version." - ".getMLText("version_deleted_email");
			$message = getMLText("version_deleted_email")."\r\n";
			$message .= 
				getMLText("document").": ".$this->_document->getName()."\r\n".
				getMLText("version").": ".$this->_version."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				getMLText("user").": ".$user->getFullName()." <". $user->getEmail() ."> ";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			Email::toList($user, $recipients, $subject, $message);
			
			// Send notification to subscribers.
			$nl=$this->_document->getNotifyList();
			Email::toList($user, $nl["users"], $subject, $message);
			foreach ($nl["groups"] as $grp) {
				Email::toGroup($user, $grp, $subject, $message);
			}
		}

		return true;
	}

	function getStatus($forceTemporaryTable=false) {
		GLOBAL $db;

		// Retrieve the current overall status of the content represented by
		// this object.
		if (!isset($this->_status)) {
			if (!$db->createTemporaryTable("ttstatid", $forceTemporaryTable)) {
				return false;
			}
			$queryStr="SELECT `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
				"`tblDocumentStatusLog`.`comment`, `tblDocumentStatusLog`.`date`, ".
				"`tblDocumentStatusLog`.`userID` ".
				"FROM `tblDocumentStatus` ".
				"LEFT JOIN `tblDocumentStatusLog` USING (`statusID`) ".
				"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
				"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
				"AND `tblDocumentStatus`.`documentID` = '". $this->_documentID ."' ".
				"AND `tblDocumentStatus`.`version` = '". $this->_version ."' ";
			$res = $db->getResultArray($queryStr);
			if (is_bool($res) && !$res)
				return false;
			if (count($res)!=1)
				return false;
			$this->_status = $res[0];
		}
		return $this->_status;
	}

	function setStatus($status, $comment, $updateUser = null) {
		
		GLOBAL $db, $user, $settings;

		// If the supplied value lies outside of the accepted range, return an
		// error.
		if ($status < -3 || $status > 2) {
			return false;
		}

		// Retrieve the current overall status of the content represented by
		// this object, if it hasn't been done already.
		if (!isset($this->_status)) {
			$this->getStatus();
		}
		if ($this->_status["status"]==$status) {
			return false;
		}
		$queryStr = "INSERT INTO `tblDocumentStatusLog` (`statusID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $this->_status["statusID"] ."', '". $status ."', '". $comment ."', NOW(), '". (is_null($updateUser) ? $settings->_adminID : $updateUser->getID()) ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return false;

		// Send notification to subscribers.
		if (!isset($this->_document)) {
			$this->_document = getDocument($this->_documentID);
		}
		$nl=$this->_document->getNotifyList();
		$subject = $settings->_siteName.": ".$this->_document->_name." - ".getMLText("document_status_changed_email");
		$message = getMLText("document_status_changed_email")."\r\n";
		$message .= 
			getMLText("document").": ".$this->_document->_name."\r\n".
			getMLText("status").": ".getOverallStatusText($status)."\r\n".
			getMLText("folder").": ".getFolderPathPlain($this->_document->getFolder())."\r\n".
			getMLText("comment").": ".$this->_document->getComment()."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_documentID."&version=".$this->_version."\r\n";

		$uu = (is_null($updateUser) ? getUser($settings->_adminID) : $updateUser);

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toList($uu, $nl["users"], $subject, $message);
		foreach ($nl["groups"] as $grp) {
			Email::toGroup($uu, $grp, $subject, $message);
		}
		
		// TODO: if user os not owner send notification to owner

		return true;
	}

	function getReviewStatus($forceTemporaryTable=false) {
		GLOBAL $db;

		// Retrieve the current status of each assigned reviewer for the content
		// represented by this object.
		if (!isset($this->_reviewStatus)) {
			if (!$db->createTemporaryTable("ttreviewid", $forceTemporaryTable)) {
				return false;
			}
			$queryStr="SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
				"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
				"`tblDocumentReviewLog`.`userID`, `tblUsers`.`fullName`, `tblGroups`.`name` AS `groupName` ".
				"FROM `tblDocumentReviewers` ".
				"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
				"LEFT JOIN `ttreviewid` on `ttreviewid`.`maxLogID` = `tblDocumentReviewLog`.`reviewLogID` ".
				"LEFT JOIN `tblUsers` on `tblUsers`.`id` = `tblDocumentReviewers`.`required`".
				"LEFT JOIN `tblGroups` on `tblGroups`.`id` = `tblDocumentReviewers`.`required`".
				"WHERE `ttreviewid`.`maxLogID`=`tblDocumentReviewLog`.`reviewLogID` ".
				"AND `tblDocumentReviewers`.`documentID` = '". $this->_documentID ."' ".
				"AND `tblDocumentReviewers`.`version` = '". $this->_version ."' ";
			$res = $db->getResultArray($queryStr);
			if (is_bool($res) && !$res)
				return false;
			// Is this cheating? Certainly much quicker than copying the result set
			// into a separate object.
			$this->_reviewStatus = $res;
		}
		return $this->_reviewStatus;
	}

	function getApprovalStatus($forceTemporaryTable=false) {
		GLOBAL $db;

		// Retrieve the current status of each assigned approver for the content
		// represented by this object.
		if (!isset($this->_approvalStatus)) {
			if (!$db->createTemporaryTable("ttapproveid", $forceTemporaryTable)) {
				return false;
			}
			$queryStr="SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
				"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
				"`tblDocumentApproveLog`.`userID`, `tblUsers`.`fullName`, `tblGroups`.`name` AS `groupName` ".
				"FROM `tblDocumentApprovers` ".
				"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
				"LEFT JOIN `ttapproveid` on `ttapproveid`.`maxLogID` = `tblDocumentApproveLog`.`approveLogID` ".
				"LEFT JOIN `tblUsers` on `tblUsers`.`id` = `tblDocumentApprovers`.`required`".
				"LEFT JOIN `tblGroups` on `tblGroups`.`id` = `tblDocumentApprovers`.`required`".
				"WHERE `ttapproveid`.`maxLogID`=`tblDocumentApproveLog`.`approveLogID` ".
				"AND `tblDocumentApprovers`.`documentID` = '". $this->_documentID ."' ".
				"AND `tblDocumentApprovers`.`version` = '". $this->_version ."'";
			$res = $db->getResultArray($queryStr);
			if (is_bool($res) && !$res)
				return false;
			$this->_approvalStatus = $res;
		}
		return $this->_approvalStatus;
	}

	function addIndReviewer($user, $requestUser, $sendEmail=false) {

		GLOBAL $db, $settings;

		$userID = $user->getID();
		if (!isset($this->_document)) {
			$this->_document = getDocument($this->_documentID);
		}

		// Get the list of users and groups with write access to this document.
		if (!isset($this->_approversList)) {
			$this->_approversList = $this->_document->getApproversList();
		}
		$approved = false;
		foreach ($this->_approversList["users"] as $appUser) {
			if ($userID == $appUser->getID()) {
				$approved = true;
				break;
			}
		}
		if (!$approved) {
			return -2;
		}

		// Check to see if the user has already been added to the review list.
		$reviewStatus = $user->getReviewStatus($this->_documentID, $this->_version);
		if (is_bool($reviewStatus) && !$reviewStatus) {
			return -1;
		}
		if (count($reviewStatus["indstatus"]) > 0 && $reviewStatus["indstatus"][0]["status"]!=-2) {
			// User is already on the list of reviewers; return an error.
			return -3;
		}

		// Add the user into the review database.
		if (! isset($reviewStatus["indstatus"][0]["status"])|| (isset($reviewStatus["indstatus"][0]["status"]) && $reviewStatus["indstatus"][0]["status"]!=-2)) {
			$queryStr = "INSERT INTO `tblDocumentReviewers` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_documentID ."', '". $this->_version ."', '0', '". $userID ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return -1;
			}
			$reviewID = $db->getInsertID();
		}
		else {
			$reviewID = isset($reviewStatus["indstatus"][0]["reviewID"])?$reviewStatus["indstatus"][0]["reviewID"]:NULL;
		}
		
		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $reviewID ."', '0', '', NOW(), '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		// Add reviewer to event notification table.
		//$this->_document->addNotify($userID, true);

		// Send an email notification to the new reviewer.
		if ($sendEmail) {
			$subject = $settings->_siteName.": ".$this->_document->getName().", v.".$this->_version." - ".getMLText("review_request_email");
			$message = getMLText("review_request_email")."\r\n";
			$message .= 
				getMLText("document").": ".$this->_document->getName()."\r\n".
				getMLText("version").": ".$this->_version."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				getMLText("user").": ".$requestUser->getFullName()." <". $requestUser->getEmail() .">\r\n".
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_documentID."&version=".$this->_version."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			return (Email::toIndividual($requestUser, $user, $subject, $message) < 0 ? -4 : 0);
		}
		return 0;
	}

	function addGrpReviewer($group, $requestUser, $sendEmail=false) {
		GLOBAL $db, $settings;

		$groupID = $group->getID();
		if (!isset($this->_document)) {
			$this->_document = getDocument($this->_documentID);
		}

		// Get the list of users and groups with write access to this document.
		if (!isset($this->_approversList)) {
			// TODO: error checking.
			$this->_approversList = $this->_document->getApproversList();
		}
		$approved = false;
		foreach ($this->_approversList["groups"] as $appGroup) {
			if ($groupID == $appGroup->getID()) {
				$approved = true;
				break;
			}
		}
		if (!$approved) {
			return -2;
		}

		// Check to see if the group has already been added to the review list.
		$reviewStatus = $group->getReviewStatus($this->_documentID, $this->_version);
		if (is_bool($reviewStatus) && !$reviewStatus) {
			return -1;
		}
		if (count($reviewStatus) > 0 && $reviewStatus[0]["status"]!=-2) {
			// Group is already on the list of reviewers; return an error.
			return -3;
		}

		// Add the group into the review database.
		if (!isset($reviewStatus[0]["status"]) || (isset($reviewStatus[0]["status"]) && $reviewStatus[0]["status"]!=-2)) {
			$queryStr = "INSERT INTO `tblDocumentReviewers` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_documentID ."', '". $this->_version ."', '1', '". $groupID ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return -1;
			}
			$reviewID = $db->getInsertID();
		}
		else {
			$reviewID = isset($reviewStatus[0]["reviewID"])?$reviewStatus[0]["reviewID"]:NULL;
		}
		
		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $reviewID ."', '0', '', NOW(), '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		// Add reviewer to event notification table.
		//$this->_document->addNotify($groupID, false);

		// Send an email notification to the new reviewer.
		if ($sendEmail) {
			$subject = $settings->_siteName.": ".$this->_document->getName().", v.".$this->_version." - ".getMLText("review_request_email");
			$message = getMLText("review_request_email")."\r\n";
			$message .=
				getMLText("document").": ".$this->_document->getName()."\r\n".
				getMLText("version").": ".$this->_version."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				getMLText("user").": ".$requestUser->getFullName()." <". $requestUser->getEmail() .">\r\n".
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_documentID."&version=".$this->_version."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			return (Email::toGroup($requestUser, $group, $subject, $message) < 0 ? -4 : 0);
		}
		return 0;
	}

	function addIndApprover($user, $requestUser, $sendEmail=false) {
		GLOBAL $db, $settings;

		$userID = $user->getID();
		if (!isset($this->_document)) {
			$this->_document = getDocument($this->_documentID);
		}

		// Get the list of users and groups with write access to this document.
		if (!isset($this->_approversList)) {
			// TODO: error checking.
			$this->_approversList = $this->_document->getApproversList();
		}
		$approved = false;
		foreach ($this->_approversList["users"] as $appUser) {
			if ($userID == $appUser->getID()) {
				$approved = true;
				break;
			}
		}
		if (!$approved) {
			return -2;
		}

		// Check to see if the user has already been added to the approvers list.
		$approvalStatus = $user->getApprovalStatus($this->_documentID, $this->_version);
		if (is_bool($approvalStatus) && !$approvalStatus) {
			return -1;
		}
		if (count($approvalStatus["indstatus"]) > 0 && $approvalStatus["indstatus"][0]["status"]!=-2) {
			// User is already on the list of approvers; return an error.
			return -3;
		}

		if ( !isset($approvalStatus["indstatus"][0]["status"]) || (isset($approvalStatus["indstatus"][0]["status"]) && $approvalStatus["indstatus"][0]["status"]!=-2)) {
			// Add the user into the approvers database.
			$queryStr = "INSERT INTO `tblDocumentApprovers` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_documentID ."', '". $this->_version ."', '0', '". $userID ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return -1;
			}
			$approveID = $db->getInsertID();
		}
		else {
			$approveID = isset($approvalStatus["indstatus"][0]["approveID"]) ? $approvalStatus["indstatus"][0]["approveID"] : NULL;
		}

		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $approveID ."', '0', '', NOW(), '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		// Send an email notification to the new approver.
		if ($sendEmail) {
			$subject = $settings->_siteName.": ".$this->_document->getName().", v.".$this->_version." - ".getMLText("approval_request_email");
			$message = getMLText("approval_request_email")."\r\n";
			$message .= 
				getMLText("document").": ".$this->_document->getName()."\r\n".
				getMLText("version").": ".$this->_version."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				getMLText("user").": ".$requestUser->getFullName()." <". $requestUser->getEmail() .">\r\n".			
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_documentID."&version=".$this->_version."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			return (Email::toIndividual($requestUser, $user, $subject, $message) < 0 ? -4 : 0);
		}
		return 0;
	}

	function addGrpApprover($group, $requestUser, $sendEmail=false) {
		GLOBAL $db, $settings;

		$groupID = $group->getID();
		if (!isset($this->_document)) {
			$this->_document = getDocument($this->_documentID);
		}

		// Get the list of users and groups with write access to this document.
		if (!isset($this->_approversList)) {
			// TODO: error checking.
			$this->_approversList = $this->_document->getApproversList();
		}
		$approved = false;
		foreach ($this->_approversList["groups"] as $appGroup) {
			if ($groupID == $appGroup->getID()) {
				$approved = true;
				break;
			}
		}
		if (!$approved) {
			return -2;
		}

		// Check to see if the group has already been added to the approver list.
		$approvalStatus = $group->getApprovalStatus($this->_documentID, $this->_version);
		if (is_bool($approvalStatus) && !$approvalStatus) {
			return -1;
		}
		if (count($approvalStatus) > 0 && $approvalStatus[0]["status"]!=-2) {
			// Group is already on the list of approvers; return an error.
			return -3;
		}

		// Add the group into the approver database.
		if (!isset($approvalStatus[0]["status"]) || (isset($approvalStatus[0]["status"]) && $approvalStatus[0]["status"]!=-2)) {
			$queryStr = "INSERT INTO `tblDocumentApprovers` (`documentID`, `version`, `type`, `required`) ".
				"VALUES ('". $this->_documentID ."', '". $this->_version ."', '1', '". $groupID ."')";
			$res = $db->getResult($queryStr);
			if (is_bool($res) && !$res) {
				return -1;
			}
			$approveID = $db->getInsertID();
		}
		else {
			$approveID = isset($approvalStatus[0]["approveID"])?$approvalStatus[0]["approveID"]:NULL;
		}
		
		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $approveID ."', '0', '', NOW(), '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		// Add approver to event notification table.
		//$this->_document->addNotify($groupID, false);

		// Send an email notification to the new approver.
		if ($sendEmail) {
			$subject = $settings->_siteName.": ".$this->_document->getName().", v.".$this->_version." - ".getMLText("approval_request_email");
			$message = getMLText("approval_request_email")."\r\n";
			$message .=
				getMLText("document").": ".$this->_document->getName()."\r\n".
				getMLText("version").": ".$this->_version."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				getMLText("user").": ".$requestUser->getFullName()." <". $requestUser->getEmail() .">\r\n".			
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_documentID."&version=".$this->_version."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			return (Email::toGroup($requestUser, $group, $subject, $message) < 0 ? -4 : 0);
		}
		return 0;
	}

	function delIndReviewer($user, $requestUser, $sendEmail=false) {
		GLOBAL $db, $settings;

		$userID = $user->getID();

		// Check to see if the user can be removed from the review list.
		$reviewStatus = $user->getReviewStatus($this->_documentID, $this->_version);
		if (is_bool($reviewStatus) && !$reviewStatus) {
			return -1;
		}
		if (count($reviewStatus["indstatus"])==0) {
			// User is not assigned to review this document. No action required.
			// Return an error.
			return -3;
		}
		if ($reviewStatus["indstatus"][0]["status"]!=0) {
			// User has already submitted a review or has already been deleted;
			// return an error.
			return -3;
		}
		
		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $reviewStatus["indstatus"][0]["reviewID"] ."', '-2', '', NOW(), '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		// Send an email notification to the reviewer.
		if ($sendEmail) {
		
			if (!isset($this->_document)) {
				$this->_document = getDocument($this->_documentID);
			}
		
			$subject = $settings->_siteName.": ".$this->_document->getName().", v.".$this->_version." - ".getMLText("review_deletion_email");
			$message = getMLText("review_deletion_email")."\r\n";
			$message .= 
				getMLText("document").": ".$this->_document->getName()."\r\n".
				getMLText("version").": ".$this->_version."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				getMLText("user").": ".$requestUser->getFullName()." <". $requestUser->getEmail() .">\r\n".			
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_documentID."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			return (Email::toIndividual($requestUser, $user, $subject, $message) < 0 ? -4 : 0);
		}
		return 0;
	}

	function delGrpReviewer($group, $requestUser, $sendEmail=false) {
		GLOBAL $db, $settings;

		$groupID = $group->getID();

		// Check to see if the user can be removed from the review list.
		$reviewStatus = $group->getReviewStatus($this->_documentID, $this->_version);
		if (is_bool($reviewStatus) && !$reviewStatus) {
			return -1;
		}
		if (count($reviewStatus)==0) {
			// User is not assigned to review this document. No action required.
			// Return an error.
			return -3;
		}
		if ($reviewStatus[0]["status"]!=0) {
			// User has already submitted a review or has already been deleted;
			// return an error.
			return -3;
		}
		
		$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $reviewStatus[0]["reviewID"] ."', '-2', '', NOW(), '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		// Send an email notification to the review group.
		if ($sendEmail) {
		
			if (!isset($this->_document)) {
				$this->_document = getDocument($this->_documentID);
			}
			
			$subject = $settings->_siteName.": ".$this->_document->getName().", v.".$this->_version." - ".getMLText("review_deletion_email");
			$message = getMLText("review_deletion_email")."\r\n";
			$message .= 
				getMLText("document").": ".$this->_document->getName()."\r\n".
				getMLText("version").": ".$this->_version."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				getMLText("user").": ".$requestUser->getFullName()." <". $requestUser->getEmail() .">\r\n".			
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_documentID."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			return (Email::toGroup($requestUser, $group, $subject, $message) < 0 ? -4 : 0);
		}
		return 0;
	}

	function delIndApprover($user, $requestUser, $sendEmail=false) {
		GLOBAL $db, $settings;

		$userID = $user->getID();

		// Check to see if the user can be removed from the approval list.
		$approvalStatus = $user->getApprovalStatus($this->_documentID, $this->_version);
		if (is_bool($approvalStatus) && !$approvalStatus) {
			return -1;
		}
		if (count($approvalStatus["indstatus"])==0) {
			// User is not assigned to approve this document. No action required.
			// Return an error.
			return -3;
		}
		if ($approvalStatus["indstatus"][0]["status"]!=0) {
			// User has already submitted an approval or has already been deleted;
			// return an error.
			return -3;
		}
		
		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $approvalStatus["indstatus"][0]["approveID"] ."', '-2', '', NOW(), '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		// Send an email notification to the approver.
		if ($sendEmail) {
		
			if (!isset($this->_document)) {
				$this->_document = getDocument($this->_documentID);
			}
			
			$subject = $settings->_siteName.": ".$this->_document->getName().", v.".$this->_version." - ".getMLText("approval_deletion_email");
			$message = getMLText("approval_deletion_email")."\r\n";
			$message .= 
				getMLText("document").": ".$this->_document->getName()."\r\n".
				getMLText("version").": ".$this->_version."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				getMLText("user").": ".$requestUser->getFullName()." <". $requestUser->getEmail() .">\r\n".			
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_documentID."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			return (Email::toIndividual($requestUser, $user, $subject, $message) < 0 ? -4 : 0);
		}
		return 0;
	}

	function delGrpApprover($group, $requestUser, $sendEmail=false) {
		GLOBAL $db, $settings;

		$groupID = $group->getID();

		// Check to see if the user can be removed from the approver list.
		$approvalStatus = $group->getApprovalStatus($this->_documentID, $this->_version);
		if (is_bool($approvalStatus) && !$approvalStatus) {
			return -1;
		}
		if (count($approvalStatus)==0) {
			// User is not assigned to approve this document. No action required.
			// Return an error.
			return -3;
		}
		if ($approvalStatus[0]["status"]!=0) {
			// User has already submitted an approval or has already been deleted;
			// return an error.
			return -3;
		}
		
		$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
			"VALUES ('". $approvalStatus[0]["approveID"] ."', '-2', '', NOW(), '". $requestUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res) {
			return -1;
		}

		// Send an email notification to the approval group.
		if ($sendEmail) {
		
			if (!isset($this->_document)) {
				$this->_document = getDocument($this->_documentID);
			}
			
			$subject = $settings->_siteName.": ".$this->_document->getName().", v.".$this->_version." - ".getMLText("approval_deletion_email");
			$message = getMLText("approval_deletion_email")."\r\n";
			$message .= 
				getMLText("document").": ".$this->_document->getName()."\r\n".
				getMLText("version").": ".$this->_version."\r\n".
				getMLText("comment").": ".$this->getComment()."\r\n".
				getMLText("user").": ".$requestUser->getFullName()." <". $requestUser->getEmail() .">\r\n".			
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$this->_documentID."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			return (Email::toGroup($requestUser, $group, $subject, $message) < 0 ? -4 : 0);
		}
		return 0;
	}
}


 /* ---------------------------------------------------------------------------------------------------- */
 
function getDocumentLink($linkID) {

	GLOBAL $db;
	
	if (!is_numeric($linkID)) return false;

	$queryStr = "SELECT * FROM tblDocumentLinks WHERE id = " . $linkID;
	$resArr = $db->getResultArray($queryStr);
	if ((is_bool($resArr) && !$resArr) || count($resArr)==0)
		return false;

	$resArr = $resArr[0];
	return new DocumentLink($resArr["id"], $resArr["document"], $resArr["target"], $resArr["userID"], $resArr["public"]);
}

function filterDocumentLinks($user, $links)
{
	GLOBAL $settings;
	
	$tmp = array();
	foreach ($links as $link)
		if ($link->isPublic() || ($link->_userID == $user->getID()) || ($user->getID() == $settings->_adminID) )
			array_push($tmp, $link);
	return $tmp;
}

class DocumentLink
{
	var $_id;
	var $_documentID;
	var $_targetID;
	var $_userID;
	var $_public;

	function DocumentLink($id, $documentID, $targetID, $userID, $public)
	{
		$this->_id = $id;
		$this->_documentID = $documentID;
		$this->_targetID = $targetID;
		$this->_userID = $userID;
		$this->_public = $public;
	}

	function getID() { return $this->_id; }

	function getDocument()
	{
		if (!isset($this->_document))
			$this->_document = getDocument($this->_documentID);
		return $this->_document;
	}

	function getTarget()
	{
		if (!isset($this->_target))
			$this->_target = getDocument($this->_targetID);
		return $this->_target;
	}

	function getUser()
	{
		if (!isset($this->_user))
			$this->_user = getUser($this->_userID);
		return $this->_user;
	}

	function isPublic() { return $this->_public; }

	function remove()
	{
		GLOBAL $db;
		
		$queryStr = "DELETE FROM tblDocumentLinks WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		return true;
	}
}

 /* ---------------------------------------------------------------------------------------------------- */
 
function getDocumentFile($ID) {

	GLOBAL $db;
	
	if (!is_numeric($ID)) return false;

	$queryStr = "SELECT * FROM tblDocumentFiles WHERE id = " . $ID;
	$resArr = $db->getResultArray($queryStr);
	if ((is_bool($resArr) && !$resArr) || count($resArr)==0) return false;

	$resArr = $resArr[0];
	return new DocumentFile($resArr["id"], $resArr["document"], $resArr["userID"], $resArr["comment"], $resArr["date"], $resArr["dir"], $resArr["fileType"], $resArr["mimeType"], $resArr["orgFileName"], $resArr["name"]);
}

class DocumentFile
{
	var $_id;
	var $_documentID;
	var $_userID;
	var $_comment;
	var $_date;
	var $_dir;
	var $_fileType;
	var $_mimeType;
	var $_orgFileName;
	var $_name;

	function DocumentFile($id, $documentID, $userID, $comment, $date, $dir, $fileType, $mimeType, $orgFileName,$name)
	{
		$this->_id = $id;
		$this->_documentID = $documentID;
		$this->_userID = $userID;
		$this->_comment = $comment;
		$this->_date = $date;
		$this->_dir = $dir;
		$this->_fileType = $fileType;
		$this->_mimeType = $mimeType;
		$this->_orgFileName = $orgFileName;
		$this->_name = $name;
	}

	function getID() { return $this->_id; }
	function getDocumentID() { return $this->_documentID; }
	function getUserID() { return $this->_userID; }
	function getComment() { return $this->_comment; }
	function getDate() { return $this->_date; }
	function getDir() { return $this->_dir; }
	function getFileType() { return $this->_fileType; }
	function getMimeType() { return $this->_mimeType; }
	function getOriginalFileName() { return $this->_orgFileName; }
	function getName() { return $this->_name; }
	
	function getUser()
	{
		if (!isset($this->_user))
			$this->_user = getUser($this->_userID);
		return $this->_user;
	}
	
	function getPath()
	{
		return $this->_dir . "f" .$this->_id . $this->_fileType;
	}

	function remove()
	{
		GLOBAL $db,$settings;
		
		if (file_exists( $settings->_contentDir.$this->getPath() ))
			if (!removeFile( $settings->_contentDir.$this->getPath() ))
				return false;
	
		
		$queryStr = "DELETE FROM tblDocumentFiles WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		return true;
	}
}

//
// Perhaps not the cleanest object ever devised, it exists to encapsulate all
// of the data generated during the addition of new content to the database.
// The object stores a copy of the new DocumentContent object, the newly assigned
// reviewers and approvers and the status.
//
class AddContentResultSet {
	
	var $_indReviewers;
	var $_grpReviewers;
	var $_indApprovers;
	var $_grpApprovers;
	var $_content;
	var $_status;

	function AddContentResultSet($content) {

		$this->_content = $content;
		$this->_indReviewers = null;
		$this->_grpReviewers = null;
		$this->_indApprovers = null;
		$this->_grpApprovers = null;
		$this->_status = null;
	}

	function addReviewer($reviewer, $type, $status) {
		
		if (!is_object($reviewer) || (strcasecmp($type, "i") && strcasecmp($type, "g")) && !is_integer($status)){
			return false;
		}
		if (!strcasecmp($type, "i")) {
			if (strcasecmp(get_class($reviewer), "User")) {
				return false;
			}
			if ($this->_indReviewers == null) {
				$this->_indReviewers = array();
			}
			$this->_indReviewers[$status][] = $reviewer;
		}
		if (!strcasecmp($type, "g")) {
			if (strcasecmp(get_class($reviewer), "Group")) {
				return false;
			}
			if ($this->_grpReviewers == null) {
				$this->_grpReviewers = array();
			}
			$this->_grpReviewers[$status][] = $reviewer;
		}
		return true;
	}

	function addApprover($approver, $type, $status) {
		
		if (!is_object($approver) || (strcasecmp($type, "i") && strcasecmp($type, "g")) && !is_integer($status)){
			return false;
		}
		if (!strcasecmp($type, "i")) {
			if (strcasecmp(get_class($approver), "User")) {
				return false;
			}
			if ($this->_indApprovers == null) {
				$this->_indApprovers = array();
			}
			$this->_indApprovers[$status][] = $approver;
		}
		if (!strcasecmp($type, "g")) {
			if (strcasecmp(get_class($approver), "Group")) {
				return false;
			}
			if ($this->_grpApprovers == null) {
				$this->_grpApprovers = array();
			}
			$this->_grpApprovers[$status][] = $approver;
		}
		return true;
	}

	function setStatus($status) {
		if (!is_integer($status)) {
			return false;
		}
		if ($status<-3 || $status>2) {
			return false;
		}
		$this->_status = $status;
		return true;
	}

	function getStatus() {
		return $this->_status;
	}

	function getReviewers($type) {
		if (strcasecmp($type, "i") && strcasecmp($type, "g")) {
			return false;
		}
		if (!strcasecmp($type, "i")) {
			return ($this->_indReviewers == null ? array() : $this->_indReviewers);
		}
		else {
			return ($this->_grpReviewers == null ? array() : $this->_grpReviewers);
		}
	}

	function getApprovers($type) {
		if (strcasecmp($type, "i") && strcasecmp($type, "g")) {
			return false;
		}
		if (!strcasecmp($type, "i")) {
			return ($this->_indApprovers == null ? array() : $this->_indApprovers);
		}
		else {
			return ($this->_grpApprovers == null ? array() : $this->_grpApprovers);
		}
	}
}
