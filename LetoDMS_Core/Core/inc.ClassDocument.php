<?php
/**
 * Implementation of a document in the document management system
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
 * The different states a document can be in
 */
define("S_DRAFT_REV", 0);
define("S_DRAFT_APP", 1);
define("S_RELEASED",  2);
define("S_REJECTED", -1);
define("S_OBSOLETE", -2);
define("S_EXPIRED",  -3);

/**
 * Class to represent a document in the document management system
 *
 * A document in LetoDMS is similar to files in a regular file system.
 * Documents may have any number of content elements
 * ({@link LetoDMS_Core_DocumentContent}). These content elements are often
 * called versions ordered in a timely manner. The most recent content element
 * is the current version.
 *
 * Documents can be linked to other documents and can have attached files.
 * The document content can be anything that can be stored in a regular
 * file.
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_Document { /* {{{ */
	/**
	 * @var integer unique id of document
	 */
	var $_id;

	/**
	 * @var string name of document
	 */
	var $_name;

	/**
	 * @var string comment of document
	 */
	var $_comment;

	/**
	 * @var integer unix timestamp of creation date
	 */
	var $_date;

	/**
	 * @var integer id of user who is the owner
	 */
	var $_ownerID;

	/**
	 * @var integer id of folder this document belongs to
	 */
	var $_folderID;

	/**
	 * @var integer timestamp of expiration date
	 */
	var $_expires;

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
	 * @var boolean true if document is locked, otherwise false
	 */
	var $_locked;

	/**
	 * @var string list of keywords
	 */
	var $_keywords;

	/**
	 * @var integer position of document within the parent folder
	 */
	var $_sequence;

	/**
	 * @var object back reference to document management system
	 */
	var $_dms;

	function LetoDMS_Core_Document($id, $name, $comment, $date, $expires, $ownerID, $folderID, $inheritAccess, $defaultAccess, $locked, $keywords, $sequence) { /* {{{ */
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
		$this->_notifyList = array();
		$this->_dms = null;
	} /* }}} */

	/*
	 * Set dms this document belongs to.
	 *
	 * Each document needs a reference to the dms it belongs to. It will be
	 * set when the folder is created by LetoDMS::getDocument() or
	 * LetoDMS::search(). The dms has a
	 * references to the currently logged in user and the database connection.
	 *
	 * @param object $dms reference to dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/*
	 * Return the directory of the document in the file system relativ
	 * to the contentDir
	 *
	 * @return string directory of document
	 */
	function getDir() { /* {{{ */
		return $this->_id."/";
	} /* }}} */

	/*
	 * Return the internal id of the document
	 *
	 * @return integer id of document
	 */
	function getID() { return $this->_id; }

	/*
	 * Return the name of the document
	 *
	 * @return string name of document
	 */
	function getName() { return $this->_name; }

	/*
	 * Set the name of the document
	 *
	 * @param $newName string new name of document
	 */
	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblDocuments SET name = '" . $newName . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	/*
	 * Return the comment of the document
	 *
	 * @return string comment of document
	 */
	function getComment() { return $this->_comment; }

	/*
	 * Set the comment of the document
	 *
	 * @param $newComment string new comment of document
	 */
	function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblDocuments SET comment = '" . $newComment . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	function getKeywords() { return $this->_keywords; }

	function setKeywords($newKeywords) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblDocuments SET keywords = '" . $newKeywords . "' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_keywords = $newKeywords;
		return true;
	} /* }}} */

	/**
	 * Return creation date of document
	 *
	 * @return integer unix timestamp of creation date
	 */
	function getDate() { /* {{{ */
		return $this->_date;
	} /* }}} */

	function getFolder() { /* {{{ */
		if (!isset($this->_folder))
			$this->_folder = $this->_dms->getFolder($this->_folderID);
		return $this->_folder;
	} /* }}} */

	/**
	 * Set folder of a document
	 *
	 * This function basically moves a document from a folder to another
	 * folder.
	 *
	 * @param object $newFolder
	 * @return boolean false in case of an error, otherwise true
	 */
	function setFolder($newFolder) { /* {{{ */
		$db = $this->_dms->getDB();

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

		return true;
	} /* }}} */

	/**
	 * Return owner of document
	 *
	 * @return object owner of document as an instance of {@link LetoDMS_Core_User}
	 */
	function getOwner() { /* {{{ */
		if (!isset($this->_owner))
			$this->_owner = $this->_dms->getUser($this->_ownerID);
		return $this->_owner;
	} /* }}} */

	/**
	 * Set owner of a document
	 *
	 * @param object $newOwner new owner
	 * @return boolean true if successful otherwise false
	 */
	function setOwner($newOwner) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblDocuments set owner = " . $newOwner->getID() . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_ownerID = $newOwner->getID();
		$this->_owner = $newOwner;
		return true;
	} /* }}} */

	function getDefaultAccess() { /* {{{ */
		if ($this->inheritsAccess()) {
			$res = $this->getFolder();
			if (!$res) return false;
			return $this->_folder->getDefaultAccess();
		}
		return $this->_defaultAccess;
	} /* }}} */

	function setDefaultAccess($mode) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblDocuments set defaultAccess = " . $mode . " WHERE id = " . $this->_id;
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

	/**
	 * Set inherited access mode
	 * Setting inherited access mode will set or unset the internal flag which
	 * controls if the access mode is inherited from the parent folder or not.
	 * It will not modify the
	 * access control list for the current object. It will remove all
	 * notifications of users which do not even have read access anymore
	 * after setting or unsetting inherited access.
	 *
	 * @param boolean $inheritAccess set to true for setting and false for
	 *        unsetting inherited access mode
	 * @return boolean true if operation was successful otherwise false
	 */
	function setInheritAccess($inheritAccess) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblDocuments SET inheritAccess = " . ($inheritAccess ? "1" : "0") . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_inheritAccess = ($inheritAccess ? "1" : "0");

		// If any of the notification subscribers no longer have read access,
		// remove their subscription.
		if(isset($this->_notifyList["users"])) {
			foreach ($this->_notifyList["users"] as $u) {
				if ($this->getAccessMode($u) < M_READ) {
					$this->removeNotify($u->getID(), true);
				}
			}
		}
		if(isset($this->_notifyList["groups"])) {
			foreach ($this->_notifyList["groups"] as $g) {
				if ($this->getGroupAccessMode($g) < M_READ) {
					$this->removeNotify($g->getID(), false);
				}
			}
		}

		return true;
	} /* }}} */

	function expires() { /* {{{ */
		if (intval($this->_expires) == 0)
			return false;
		else
			return true;
	} /* }}} */

	function getExpires() { /* {{{ */
		if (intval($this->_expires) == 0)
			return false;
		else
			return $this->_expires;
	} /* }}} */

	function setExpires($expires) { /* {{{ */
		$db = $this->_dms->getDB();

		$expires = (!$expires) ? 0 : $expires;

		if ($expires == $this->_expires) {
			// No change is necessary.
			return true;
		}

		$queryStr = "UPDATE tblDocuments SET expires = " . $expires . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_expires = $expires;
		return true;
	} /* }}} */

	/**
	 * Check if the document has expired
	 *
	 * @return boolean true if document has expired otherwise false
	 */
	function hasExpired() { /* {{{ */
		if (intval($this->_expires) == 0) return false;
		if (time()>$this->_expires+24*60*60) return true;
		return false;
	} /* }}} */

	// return true if status has changed (to reload page)
	function verifyLastestContentExpriry(){ /* {{{ */
		$lc=$this->getLatestContent();
		$st=$lc->getStatus();

		if (($st["status"]==S_DRAFT_REV || $st["status"]==S_DRAFT_APP) && $this->hasExpired()){
			$lc->setStatus(S_EXPIRED,"", $this->getOwner());
			return true;
		}
		else if ($st["status"]==S_EXPIRED && !$this->hasExpired() ){
			$lc->verifyStatus(true, $this->getOwner());
			return true;
		}
		return false;
	} /* }}} */

	/**
	 * Check if document is locked
	 *
	 * @return boolean true if locked otherwise false
	 */
	function isLocked() { return $this->_locked != -1; }

	/**
	 * Lock or unlock document
	 *
	 * @param $falseOrUser user object for locking or false for unlocking
	 * @return boolean true if operation was successful otherwise false
	 */
	function setLocked($falseOrUser) { /* {{{ */
		$db = $this->_dms->getDB();

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
	} /* }}} */

	/**
	 * Get the user currently locking the document
	 *
	 * @return object user have a lock
	 */
	function getLockingUser() { /* {{{ */
		if (!$this->isLocked())
			return false;

		if (!isset($this->_lockingUser))
			$this->_lockingUser = $this->_dms->getUser($this->_locked);
		return $this->_lockingUser;
	} /* }}} */

	function getSequence() { return $this->_sequence; }

	function setSequence($seq) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblDocuments SET sequence = " . $seq . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_sequence = $seq;
		return true;
	} /* }}} */

	/**
	 * Delete all entries for this folder from the access control list
	 *
	 * @return boolean true if operation was successful otherwise false
	 */
	function clearAccessList() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM tblACLs WHERE targetType = " . T_DOCUMENT . " AND target = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);
		return true;
	} /* }}} */

	/**
	 * Returns a list of access privileges
	 *
	 */
	function getAccessList($mode = M_ANY, $op = O_EQ) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->inheritsAccess()) {
			$res = $this->getFolder();
			if (!$res) return false;
			return $this->_folder->getAccessList($mode, $op);
		}

		if (!isset($this->_accessList[$mode])) {
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
			foreach ($resArr as $row) {
				if ($row["userID"] != -1)
					array_push($this->_accessList[$mode]["users"], new LetoDMS_Core_UserAccess($this->_dms->getUser($row["userID"]), $row["mode"]));
				else //if ($row["groupID"] != -1)
					array_push($this->_accessList[$mode]["groups"], new LetoDMS_Core_GroupAccess($this->_dms->getGroup($row["groupID"]), $row["mode"]));
			}
		}

		return $this->_accessList[$mode];
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
					(".$this->_id.", ".T_DOCUMENT.", " . $userOrGroupID . ", " .$mode. ")";
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
	 * Change access right of document
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

		$queryStr = "UPDATE tblACLs SET mode = " . $newMode . " WHERE targetType = ".T_DOCUMENT." AND target = " . $this->_id . " AND " . $userOrGroup . " = " . $userOrGroupID;
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

		$queryStr = "DELETE FROM tblACLs WHERE targetType = ".T_DOCUMENT." AND target = ".$this->_id." AND ".$userOrGroup." = " . $userOrGroupID;
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
	 * Returns the greatest access privilege for a given user
	 *
	 * This function searches the access control list for entries of
	 * user $user. If it finds more than one entry it will return the
	 * one allowing the greatest privileges. If there is no entry in the
	 * access control list, it will return the default access mode.
	 * The function takes inherited access rights into account.
	 * For a list of possible access rights see @file inc.AccessUtils.php
	 *
	 * @param $user object instance of class LetoDMS_Core_User
	 * @return integer access mode
	 */
	function getAccessMode($user) { /* {{{ */
		/* Administrators have unrestricted access */
		if ($user->isAdmin()) return M_ALL;

		/* The owner of the document has unrestricted access */
		if ($user->getID() == $this->_ownerID) return M_ALL;

		/* The guest users do not have more than read access */
		if ($user->isGuest()) {
			$mode = $this->getDefaultAccess();
			if ($mode >= M_READ) return M_READ;
			else return M_NONE;
		}

		/* Check ACLs */
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
	 * Returns the greatest access privilege for a given group
	 *
	 * This function searches the access control list for entries of
	 * group $group. If it finds more than one entry it will return the
	 * one allowing the greatest privileges. If there is no entry in the
	 * access control list, it will return the default access mode.
	 * The function takes inherited access rights into account.
	 * For a list of possible access rights see @file inc.AccessUtils.php
	 *
	 * @param $group object instance of class LetoDMS_Core_Group
	 * @return integer access mode
	 */
	function getGroupAccessMode($group) { /* {{{ */
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
	} /* }}} */

	/**
	 * Returns a list of all notifications
	 *
	 * The returned list has two elements called 'users' and 'groups'. Each one
	 * is an array itself countaining objects of class LetoDMS_Core_User and
	 * LetoDMS_Core_Group.
	 *
	 * @return array list of notifications
	 */
	function getNotifyList() { /* {{{ */
		if (empty($this->_notifyList)) {
			$db = $this->_dms->getDB();

			$queryStr ="SELECT * FROM tblNotify WHERE targetType = " . T_DOCUMENT . " AND target = " . $this->_id;
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

	/**
	 * Add a user/group to the notification list
	 * This function does not check if the currently logged in user
	 * is allowed to add a notification. This must be checked by the calling
	 * application.
	 *
	 * @param $userOrGroupID integer id of user or group to add
	 * @param $isUser integer 1 if $userOrGroupID is a user,
	 *                0 if $userOrGroupID is a group
	 * @return integer  0: Update successful.
	 *                 -1: Invalid User/Group ID.
	 *                 -2: Target User / Group does not have read access.
	 *                 -3: User is already subscribed.
	 *                 -4: Database / internal error.
	 */
	function addNotify($userOrGroupID, $isUser,$send_email=TRUE) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser ? "userID" : "groupID");

		/* Verify that user / group exists. */
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

		/* Verify that target user / group has read access to the document. */
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
		/* Check to see if user/group is already on the list. */
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

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/**
	 * Remove a user or group from the notification list
	 * This function does not check if the currently logged in user
	 * is allowed to remove a notification. This must be checked by the calling
	 * application.
	 *
	 * @param $userOrGroupID id of user or group
	 * @param $isUser boolean true if a user is passed in $userOrGroupID, false
	 *        if a group is passed in $userOrGroupID
	 * @return integer 0 if operation was succesful
	 *                 -1 if the userid/groupid is invalid
	 *                 -3 if the user/group is already subscribed
	 *                 -4 in case of an internal database error
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

		/* Check to see if the target is in the database. */
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

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/**
	 * Add content to a document
	 *
	 * Each document may have any number of content elements attached to it.
	 * Each content element has a version number. Newer versions (greater
	 * version number) replace older versions.
	 *
	 * @param string $comment comment
	 * @param object $user user who shall be the owner of this content
	 * @param string $tmpFile file containing the actuall content
	 * @param string $orgFileName original file name
	 * @param string $mimeType MimeType of the content
	 * @param array $reviewers list of reviewers
	 * @param array $approvers list of approvers
	 * @param integer $version version number of content or 0 if next higher version shall be used.
	 * @return bool/array false in case of an error or a result set
	 */
	function addContent($comment, $user, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers=array(), $approvers=array(), $version=0) { /* {{{ */
		$db = $this->_dms->getDB();

		// the doc path is id/version.filetype
		$dir = $this->getDir();

		$date = mktime();

		/* The version field in table tblDocumentContent used to be auto
		 * increment but that requires the field to be primary as well if
		 * innodb is used. That's why the version is now determined here.
		 */
		if ((int)$version<1) {
			$queryStr = "SELECT MAX(version) as m from tblDocumentContent where document = ".$this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$res)
				return false;

			$version = $resArr[0]['m']+1;
		}

		$queryStr = "INSERT INTO tblDocumentContent (document, version, comment, date, createdBy, dir, orgFileName, fileType, mimeType) VALUES ".
						"(".$this->_id.", ".(int)$version.",'".$comment."', ".$date.", ".$user->getID().", '".$dir."', '".$orgFileName."', '".$fileType."', '" . $mimeType . "')";
		if (!$db->getResult($queryStr)) return false;

		// copy file
		if (!LetoDMS_Core_File::makeDir($this->_dms->contentDir . $dir)) return false;
		if (!LetoDMS_Core_File::copyFile($tmpFile, $this->_dms->contentDir . $dir . $version . $fileType)) return false;

		unset($this->_content);
		unset($this->_latestContent);
		$docResultSet = new LetoDMS_Core_AddContentResultSet(new LetoDMS_Core_DocumentContent($this, $version, $comment, $date, $user->getID(), $dir, $orgFileName, $fileType, $mimeType));

		// TODO - verify
		if ($this->_dms->enableConverting && in_array($docResultSet->_content->getFileType(), array_keys($this->_dms->convertFileTypes)))
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
					$reviewer=($i=="i" ?$this->_dms->getUser($reviewerID) : $this->_dms->getGroup($reviewerID));
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
					$approver=($i=="i" ? $this->_dms->getUser($approverID) : $this->_dms->getGroup($approverID));
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

		return $docResultSet;
	} /* }}} */

	/**
	 * Return all content elements of a document
	 *
	 * This functions returns an array of content elements ordered by version
	 *
	 * @return array list of objects of class LetoDMS_Core_DocumentContent
	 */
	function getContent() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_content)) {
			$queryStr = "SELECT * FROM tblDocumentContent WHERE document = ".$this->_id." ORDER BY version";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$res)
				return false;

			$this->_content = array();
			foreach ($resArr as $row)
				array_push($this->_content, new LetoDMS_Core_DocumentContent($this, $row["version"], $row["comment"], $row["date"], $row["createdBy"], $row["dir"], $row["orgFileName"], $row["fileType"], $row["mimeType"]));
		}

		return $this->_content;
	} /* }}} */

	/**
	 * Return the content element of a document with a given version number
	 *
	 * @param integer $version version number of content element
	 * @return object object of class LetoDMS_Core_DocumentContent
	 */
	function getContentByVersion($version) { /* {{{ */
		if (!is_numeric($version)) return false;

		if (isset($this->_content)) {
			foreach ($this->_content as $revision) {
				if ($revision->getVersion() == $version)
					return $revision;
			}
			return false;
		}

		$db = $this->_dms->getDB();
		$queryStr = "SELECT * FROM tblDocumentContent WHERE document = ".$this->_id." AND version = " . $version;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$res)
			return false;
		if (count($resArr) != 1)
			return false;

		$resArr = $resArr[0];
		return new LetoDMS_Core_DocumentContent($this, $resArr["version"], $resArr["comment"], $resArr["date"], $resArr["createdBy"], $resArr["dir"], $resArr["orgFileName"], $resArr["fileType"], $resArr["mimeType"]);
	} /* }}} */

	function getLatestContent() { /* {{{ */
		if (!isset($this->_latestContent)) {
			$db = $this->_dms->getDB();
			$queryStr = "SELECT * FROM tblDocumentContent WHERE document = ".$this->_id." ORDER BY version DESC LIMIT 0,1";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;
			if (count($resArr) != 1)
				return false;

			$resArr = $resArr[0];
			$this->_latestContent = new LetoDMS_Core_DocumentContent($this, $resArr["version"], $resArr["comment"], $resArr["date"], $resArr["createdBy"], $resArr["dir"], $resArr["orgFileName"], $resArr["fileType"], $resArr["mimeType"]);
		}
		return $this->_latestContent;
	} /* }}} */

	function removeContent($version) { /* {{{ */
		$db = $this->_dms->getDB();

		$emailList = array();
		$emailList[] = $version->_userID;

		if (file_exists( $this->_dms->contentDir.$version->getPath() ))
			if (!LetoDMS_Core_File::removeFile( $this->_dms->contentDir.$version->getPath() ))
				return false;

		$status = $version->getStatus();
		$stID = $status["statusID"];

		$queryStr = "DELETE FROM tblDocumentContent WHERE `document` = " . $this->getID() .	" AND `version` = " . $version->_version;
		if (!$db->getResult($queryStr))
			return false;

		$queryStr = "DELETE FROM `tblDocumentStatusLog` WHERE `statusID` = '".$stID."'";
		if (!$db->getResult($queryStr))
			return false;

		$queryStr = "DELETE FROM `tblDocumentStatus` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->_version."'";
		if (!$db->getResult($queryStr))
			return false;

		$status = $version->getReviewStatus();
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
		$queryStr = "DELETE FROM `tblDocumentReviewers` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->_version."'";
		if (!$db->getResult($queryStr))
			return false;
		$status = $version->getApprovalStatus();
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
		$queryStr = "DELETE FROM `tblDocumentApprovers` WHERE `documentID` = '". $this->getID() ."' AND `version` = '" . $version->_version."'";
		if (!$db->getResult($queryStr))
			return false;

		return true;
	} /* }}} */

	function getDocumentLink($linkID) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!is_numeric($linkID)) return false;

		$queryStr = "SELECT * FROM tblDocumentLinks WHERE document = " . $this->_id ." AND id = " . $linkID;
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || count($resArr)==0)
			return false;

		$resArr = $resArr[0];
		$document = $this->_dms->getDocument($resArr["document"]);
		$target = $this->_dms->getDocument($resArr["target"]);
		return new LetoDMS_Core_DocumentLink($resArr["id"], $document, $target, $resArr["userID"], $resArr["public"]);
	} /* }}} */

	function getDocumentLinks() { /* {{{ */
		if (!isset($this->_documentLinks)) {
			$db = $this->_dms->getDB();

			$queryStr = "SELECT * FROM tblDocumentLinks WHERE document = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;
			$this->_documentLinks = array();

			foreach ($resArr as $row) {
				$target = $this->_dms->getDocument($row["target"]);
				array_push($this->_documentLinks, new LetoDMS_Core_DocumentLink($row["id"], $this, $target, $row["userID"], $row["public"]));
			}
		}
		return $this->_documentLinks;
	} /* }}} */

	function addDocumentLink($targetID, $userID, $public) { /* {{{ */
		$db = $this->_dms->getDB();

		$public = ($public) ? "1" : "0";

		$queryStr = "INSERT INTO tblDocumentLinks(document, target, userID, public) VALUES (".$this->_id.", ".$targetID.", ".$userID.", " . $public.")";
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_documentLinks);
		return true;
	} /* }}} */

	function removeDocumentLink($linkID) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM tblDocumentLinks WHERE document = " . $this->_id ." AND id = " . $linkID;
		if (!$db->getResult($queryStr)) return false;
		unset ($this->_documentLinks);
		return true;
	} /* }}} */

	function getDocumentFile($ID) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!is_numeric($ID)) return false;

		$queryStr = "SELECT * FROM tblDocumentFiles WHERE document = " . $this->_id ." AND id = " . $ID;
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || count($resArr)==0) return false;

		$resArr = $resArr[0];
		return new LetoDMS_Core_DocumentFile($resArr["id"], $this, $resArr["userID"], $resArr["comment"], $resArr["date"], $resArr["dir"], $resArr["fileType"], $resArr["mimeType"], $resArr["orgFileName"], $resArr["name"]);
	} /* }}} */

	function getDocumentFiles() { /* {{{ */
		if (!isset($this->_documentFiles)) {
			$db = $this->_dms->getDB();

			$queryStr = "SELECT * FROM tblDocumentFiles WHERE document = " . $this->_id." ORDER BY `date` DESC";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

			$this->_documentFiles = array();

			foreach ($resArr as $row) {
				array_push($this->_documentFiles, new LetoDMS_Core_DocumentFile($row["id"], $this, $row["userID"], $row["comment"], $row["date"], $row["dir"], $row["fileType"], $row["mimeType"], $row["orgFileName"], $row["name"]));
			}
		}
		return $this->_documentFiles;
	} /* }}} */

	function addDocumentFile($name, $comment, $user, $tmpFile, $orgFileName,$fileType, $mimeType ) { /* {{{ */
		$db = $this->_dms->getDB();

		$dir = $this->getDir();

		$queryStr = "INSERT INTO tblDocumentFiles (comment, date, dir, document, fileType, mimeType, orgFileName, userID, name) VALUES ".
			"('".$comment."', '".mktime()."', '" . $dir ."', " . $this->_id.", '".$fileType."', '".$mimeType."', '".$orgFileName."',".$user->getID().",'".$name."')";
		if (!$db->getResult($queryStr)) return false;

		$id = $db->getInsertID();

		$file = $this->getDocumentFile($id);
		if (is_bool($file) && !$file) return false;

		// copy file
		if (!LetoDMS_Core_File::makeDir($this->_dms->contentDir . $dir)) return false;
		if (!LetoDMS_Core_File::copyFile($tmpFile, $this->_dms->contentDir . $file->getPath() )) return false;

		return true;
	} /* }}} */

	function removeDocumentFile($ID) { /* {{{ */
		$db = $this->_dms->getDB();

		$file = $this->getDocumentFile($ID);
		if (is_bool($file) && !$file) return false;

		if (file_exists( $this->_dms->contentDir . $file->getPath() )){
			if (!LetoDMS_Core_File::removeFile( $this->_dms->contentDir . $file->getPath() ))
				return false;
		}

		$name=$file->getName();
		$comment=$file->getcomment();

		$queryStr = "DELETE FROM tblDocumentFiles WHERE document = " . $this->getID() . " AND id = " . $ID;
		if (!$db->getResult($queryStr))
			return false;

		unset ($this->_documentFiles);

		return true;
	} /* }}} */

	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		$res = $this->getContent();
		if (is_bool($res) && !$res) return false;

		// FIXME: call a new function removeContent instead
		foreach ($this->_content as $version)
			if (!$this->removeContent($version))
				return false;

		// remove document file
		$res = $this->getDocumentFiles();
		if (is_bool($res) && !$res) return false;

		foreach ($res as $documentfile)
			if(!$this->removeDocumentFile($documentfile->getId()))
				return false;

		// TODO: versioning file?

		if (file_exists( $this->_dms->contentDir . $this->getDir() ))
			if (!LetoDMS_Core_File::removeDir( $this->_dms->contentDir . $this->getDir() ))
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

		// Delete the notification list.
		$queryStr = "DELETE FROM tblNotify WHERE target = " . $this->_id . " AND targetType = " . T_DOCUMENT;
		if (!$db->getResult($queryStr))
			return false;

		return true;
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

				if (!$this->_dms->enableAdminRevApp && $c_user->isAdmin()) continue;
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
						"AND `tblUsers`.`role` != ".LetoDMS_Core_User::role_guest .
						(strlen($userIDs) == 0 ? ")" : " AND (`tblUsers`.`id` NOT IN (". $userIDs .")))");
				}
				$queryStr .= (strlen($queryStr)==0 ? "" : " UNION ").
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`role` = ".LetoDMS_Core_User::role_admin."))".
					"UNION ".
					"(SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE `tblUsers`.`role` != ".LetoDMS_Core_User::role_guest .
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
						$group = $this->_dms->getGroup($row["id"]);
						$this->_approversList["groups"][] = $group;
					}
				}
			}
		}
		return $this->_approversList;
	} /* }}} */
} /* }}} */


/**
 * Class to represent content of a document
 *
 * Each document has content attached to it, often called a 'version' of the
 * document. The document content represents a file on the disk with some
 * meta data stored in the database. A document content has a version number
 * which is incremented with each replacement of the old content. Old versions
 * are kept unless they are explicitly deleted by
 * {@link LetoDMS_Core_Document::removeContent()}.
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_DocumentContent { /* {{{ */

	// if status is released and there are reviewers set status draft_rev
	// if status is released or draft_rev and there are approves set status draft_app
	// if status is draft and there are no approver and no reviewers set status to release
	function verifyStatus($ignorecurrentstatus=false,$user=null) { /* {{{ */

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
	} /* }}} */

	function LetoDMS_Core_DocumentContent($document, $version, $comment, $date, $userID, $dir, $orgFileName, $fileType, $mimeType) { /* {{{ */
		$this->_document = $document;
		$this->_version = $version;
		$this->_comment = $comment;
		$this->_date = $date;
		$this->_userID = $userID;
		$this->_dir = $dir;
		$this->_orgFileName = $orgFileName;
		$this->_fileType = $fileType;
		$this->_mimeType = $mimeType;
	} /* }}} */

	function getVersion() { return $this->_version; }
	function getComment() { return $this->_comment; }
	function getDate() { return $this->_date; }
	function getOriginalFileName() { return $this->_orgFileName; }
	function getFileType() { return $this->_fileType; }
	function getFileName(){ return "data" . $this->_fileType; }
	function getDir() { return $this->_dir; }
	function getMimeType() { return $this->_mimeType; }
	function getUser() { /* {{{ */
		if (!isset($this->_user))
			$this->_user = $this->_document->_dms->getUser($this->_userID);
		return $this->_user;
	} /* }}} */
//	function getPath() { return $this->_dir . $this->_version . $this->_fileType; }
	function getPath() { return $this->_document->getID() .'/'. $this->_version . $this->_fileType; }

	function setComment($newComment) { /* {{{ */
		$db = $this->_document->_dms->getDB();

		$queryStr = "UPDATE tblDocumentContent SET comment = '" . $newComment . "' WHERE `document` = " . $this->_document->getID() .	" AND `version` = " . $this->_version;
		if (!$db->getResult($queryStr))
			return false;

		$this->_comment = $newComment;

		return true;
	} /* }}} */

	function convert() { /* {{{ */
		if (file_exists($this->_document->_dms->contentDir . $this->_document->getID() .'/' . "index.html"))
			return true;

		if (!in_array($this->_fileType, array_keys($this->_document->_dms->convertFileTypes)))
			return false;

		$source = $this->_document->_dms->contentDir . $this->_document->getID() .'/' . $this->getFileName();
		$target = $this->_document->_dms->contentDir . $this->_document->getID() .'/' . "index.html";
	//	$source = str_replace("/", "\\", $source);
	//	$target = str_replace("/", "\\", $target);

		$command = $this->_document->_dms->convertFileTypes[$this->_fileType];
		$command = str_replace("{SOURCE}", "\"$source\"", $command);
		$command = str_replace("{TARGET}", "\"$target\"", $command);

		$output = array();
		$res = 0;
		exec($command, $output, $res);

		if ($res != 0) {
			print (implode("\n", $output));
			return false;
		}
		return true;
	} /* }}} */

	/* FIXME: this function should not be part of the DMS. It lies in the duty
	 * of the application whether a file can viewed online or not.
	 */
	function viewOnline() { /* {{{ */
		if (!isset($this->_document->_dms->_viewOnlineFileTypes) || !is_array($this->_document->_dms->_viewOnlineFileTypes)) {
			return false;
		}

		if (in_array(strtolower($this->_fileType), $this->_document->_dms->_viewOnlineFileTypes))
			return true;

		if ($this->_document->_dms->enableConverting && in_array($this->_fileType, array_keys($this->_document->_dms->convertFileTypes)))
			if ($this->wasConverted()) return true;

		return false;
	} /* }}} */

	function wasConverted() { /* {{{ */
		return file_exists($this->_document->_dms->contentDir . $this->_document->getID() .'/' . "index.html");
	} /* }}} */

	function getURL() { /* {{{ */
		if (!$this->viewOnline())return false;

		if (in_array(strtolower($this->_fileType), $this->_document->_dms->_viewOnlineFileTypes))
			return "/" . $this->_document->getID() . "/" . $this->_version . "/" . $this->getOriginalFileName();
		else
			return "/" . $this->_document->getID() . "/" . $this->_version . "/index.html";
	} /* }}} */

	function getStatus($forceTemporaryTable=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

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
				"AND `tblDocumentStatus`.`documentID` = '". $this->_document->getID() ."' ".
				"AND `tblDocumentStatus`.`version` = '". $this->_version ."' ";
			$res = $db->getResultArray($queryStr);
			if (is_bool($res) && !$res)
				return false;
			if (count($res)!=1)
				return false;
			$this->_status = $res[0];
		}
		return $this->_status;
	} /* }}} */

	function setStatus($status, $comment, $updateUser) { /* {{{ */
		$db = $this->_document->_dms->getDB();

		/* return an error if $updateuser is not set */
		if(!$updateUser)
			return false;

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
			"VALUES ('". $this->_status["statusID"] ."', '". $status ."', '". $comment ."', NOW(), '". $updateUser->getID() ."')";
		$res = $db->getResult($queryStr);
		if (is_bool($res) && !$res)
			return false;

		return true;
	} /* }}} */

	function getReviewStatus($forceTemporaryTable=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

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
				"AND `tblDocumentReviewers`.`documentID` = '". $this->_document->getID() ."' ".
				"AND `tblDocumentReviewers`.`version` = '". $this->_version ."' ";
			$res = $db->getResultArray($queryStr);
			if (is_bool($res) && !$res)
				return false;
			// Is this cheating? Certainly much quicker than copying the result set
			// into a separate object.
			$this->_reviewStatus = $res;
		}
		return $this->_reviewStatus;
	} /* }}} */

	function getApprovalStatus($forceTemporaryTable=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

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
				"AND `tblDocumentApprovers`.`documentID` = '". $this->_document->getID() ."' ".
				"AND `tblDocumentApprovers`.`version` = '". $this->_version ."'";
			$res = $db->getResultArray($queryStr);
			if (is_bool($res) && !$res)
				return false;
			$this->_approvalStatus = $res;
		}
		return $this->_approvalStatus;
	} /* }}} */

	function addIndReviewer($user, $requestUser, $sendEmail=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

		$userID = $user->getID();

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
		$reviewStatus = $user->getReviewStatus($this->_document->getID(), $this->_version);
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
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '0', '". $userID ."')";
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

		return 0;
	} /* }}} */

	function addGrpReviewer($group, $requestUser, $sendEmail=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

		$groupID = $group->getID();

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
		$reviewStatus = $group->getReviewStatus($this->_document->getID(), $this->_version);
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
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '1', '". $groupID ."')";
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

		return 0;
	} /* }}} */

	function addIndApprover($user, $requestUser, $sendEmail=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

		$userID = $user->getID();

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
		$approvalStatus = $user->getApprovalStatus($this->_document->getID(), $this->_version);
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
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '0', '". $userID ."')";
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

		return 0;
	} /* }}} */

	function addGrpApprover($group, $requestUser, $sendEmail=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

		$groupID = $group->getID();

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
		$approvalStatus = $group->getApprovalStatus($this->_document->getID(), $this->_version);
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
				"VALUES ('". $this->_document->getID() ."', '". $this->_version ."', '1', '". $groupID ."')";
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

		return 0;
	} /* }}} */

	function delIndReviewer($user, $requestUser, $sendEmail=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

		$userID = $user->getID();

		// Check to see if the user can be removed from the review list.
		$reviewStatus = $user->getReviewStatus($this->_document->getID(), $this->_version);
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

		return 0;
	} /* }}} */

	function delGrpReviewer($group, $requestUser, $sendEmail=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

		$groupID = $group->getID();

		// Check to see if the user can be removed from the review list.
		$reviewStatus = $group->getReviewStatus($this->_document->getID(), $this->_version);
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

		return 0;
	} /* }}} */

	function delIndApprover($user, $requestUser, $sendEmail=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

		$userID = $user->getID();

		// Check to see if the user can be removed from the approval list.
		$approvalStatus = $user->getApprovalStatus($this->_document->getID(), $this->_version);
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

		return 0;
	} /* }}} */

	function delGrpApprover($group, $requestUser, $sendEmail=false) { /* {{{ */
		$db = $this->_document->_dms->getDB();

		$groupID = $group->getID();

		// Check to see if the user can be removed from the approver list.
		$approvalStatus = $group->getApprovalStatus($this->_document->getID(), $this->_version);
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

		return 0;
	} /* }}} */
} /* }}} */


/**
 * Class to represent a link between two document
 *
 * Document links are to establish a reference from one document to
 * another document. The owner of the document link may not be the same
 * as the owner of one of the documents.
 * Use {@link LetoDMS_Core_Document::addDocumentLink()} to add a reference
 * to another document.
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_DocumentLink { /* {{{ */
	var $_id;
	var $_document;
	var $_target;
	var $_userID;
	var $_public;

	function LetoDMS_Core_DocumentLink($id, $document, $target, $userID, $public) {
		$this->_id = $id;
		$this->_document = $document;
		$this->_target = $target;
		$this->_userID = $userID;
		$this->_public = $public;
	}

	function getID() { return $this->_id; }

	function getDocument() {
		return $this->_document;
	}

	function getTarget() {
		return $this->_target;
	}

	function getUser() {
		if (!isset($this->_user))
			$this->_user = $this->_document->_dms->getUser($this->_userID);
		return $this->_user;
	}

	function isPublic() { return $this->_public; }

} /* }}} */

/**
 * Class to represent a file attached to a document
 *
 * Beside the regular document content arbitrary files can be attached
 * to a document. This is a similar concept as attaching files to emails.
 * The owner of the attached file and the document may not be the same.
 * Use {@link LetoDMS_Core_Document::addDocumentFile()} to attach a file.
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_DocumentFile { /* {{{ */
	var $_id;
	var $_document;
	var $_userID;
	var $_comment;
	var $_date;
	var $_dir;
	var $_fileType;
	var $_mimeType;
	var $_orgFileName;
	var $_name;

	function LetoDMS_Core_DocumentFile($id, $document, $userID, $comment, $date, $dir, $fileType, $mimeType, $orgFileName,$name) {
		$this->_id = $id;
		$this->_document = $document;
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
	function getDocument() { return $this->_document; }
	function getUserID() { return $this->_userID; }
	function getComment() { return $this->_comment; }
	function getDate() { return $this->_date; }
	function getDir() { return $this->_dir; }
	function getFileType() { return $this->_fileType; }
	function getMimeType() { return $this->_mimeType; }
	function getOriginalFileName() { return $this->_orgFileName; }
	function getName() { return $this->_name; }

	function getUser() {
		if (!isset($this->_user))
			$this->_user = $this->_document->_dms->getUser($this->_userID);
		return $this->_user;
	}

	function getPath() {
		return $this->_document->getID() . "/f" .$this->_id . $this->_fileType;
	}

} /* }}} */

//
// Perhaps not the cleanest object ever devised, it exists to encapsulate all
// of the data generated during the addition of new content to the database.
// The object stores a copy of the new DocumentContent object, the newly assigned
// reviewers and approvers and the status.
//
/**
 * Class to represent a list of document contents
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_AddContentResultSet { /* {{{ */

	var $_indReviewers;
	var $_grpReviewers;
	var $_indApprovers;
	var $_grpApprovers;
	var $_content;
	var $_status;

	function LetoDMS_Core_AddContentResultSet($content) {

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
			if (strcasecmp(get_class($reviewer), "LetoDMS_Core_User")) {
				return false;
			}
			if ($this->_indReviewers == null) {
				$this->_indReviewers = array();
			}
			$this->_indReviewers[$status][] = $reviewer;
		}
		if (!strcasecmp($type, "g")) {
			if (strcasecmp(get_class($reviewer), "LetoDMS_Core_Group")) {
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
			if (strcasecmp(get_class($approver), "LetoDMS_Core_User")) {
				return false;
			}
			if ($this->_indApprovers == null) {
				$this->_indApprovers = array();
			}
			$this->_indApprovers[$status][] = $approver;
		}
		if (!strcasecmp($type, "g")) {
			if (strcasecmp(get_class($approver), "LetoDMS_Core_Group")) {
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
} /* }}} */
?>
