<?php
/**
 * Implementation of the user object in the document management system
 *
 * @category   DMS
 * @package    LetoDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a user in the document management system
 *
 * @category   DMS
 * @package    LetoDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_User {
	var $_id;
	var $_login;
	var $_pwd;
	var $_fullName;
	var $_email;
	var $_language;
	var $_theme;
	var $_comment;
	var $_isAdmin;
	var $_isGuest;
	var $_isHidden;
	var $_dms;

	function LetoDMS_User($id, $login, $pwd, $fullName, $email, $language, $theme, $comment, $isAdmin, $isGuest, $isHidden=0) {
		$this->_id = $id;
		$this->_login = $login;
		$this->_pwd = $pwd;
		$this->_fullName = $fullName;
		$this->_email = $email;
		$this->_language = $language;
		$this->_theme = $theme;
		$this->_comment = $comment;
		$this->_isAdmin = $isAdmin;
		$this->_isGuest = $isGuest;
		$this->_isHidden = $isHidden;
		$this->_dms = null;
	}

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getLogin() { return $this->_login; }

	function setLogin($newLogin) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET login ='" . $newLogin . "' WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_login = $newLogin;
		return true;
	} /* }}} */

	function getFullName() { return $this->_fullName; }

	function setFullName($newFullName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET fullname = '" . $newFullName . "' WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_fullName = $newFullName;
		return true;
	} /* }}} */

	function getPwd() { return $this->_pwd; }

	function setPwd($newPwd) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET pwd ='" . $newPwd . "' WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_pwd = $newPwd;
		return true;
	} /* }}} */

	function getEmail() { return $this->_email; }

	function setEmail($newEmail) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET email ='" . $newEmail . "' WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_email = $newEmail;
		return true;
	} /* }}} */

	function getLanguage() { return $this->_language; }

	function setLanguage($newLanguage) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET language ='" . $newLanguage . "' WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_language = $newLanguage;
		return true;
	} /* }}} */

	function getTheme() { return $this->_theme; }

	function setTheme($newTheme) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET theme ='" . $newTheme . "' WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_theme = $newTheme;
		return true;
	} /* }}} */

	function getComment() { return $this->_comment; }

	function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET comment ='" . $newComment . "' WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	function isAdmin() { return $this->_isAdmin; }

	function setAdmin($isAdmin) { /* {{{ */
		$db = $this->_dms->getDB();

		$isAdmin = ($isAdmin) ? "1" : "0";
		$queryStr = "UPDATE tblUsers SET isAdmin = " . $isAdmin . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_isAdmin = $isAdmin;
		return true;
	} /* }}} */

	function isGuest() { return $this->_isGuest; }

	function setGuest($isGuest) { /* {{{ */
		$db = $this->_dms->getDB();

		$isGuest = ($isGuest) ? "1" : "0";
		$queryStr = "UPDATE tblUsers SET isGuest = " . $isGuest . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_isGuest = $isGuest;
		return true;
	} /* }}} */

	function isHidden() { return $this->_isHidden; }

	function setHidden($isHidden) { /* {{{ */
		$db = $this->_dms->getDB();

		$isHidden = ($isHidden) ? "1" : "0";
		$queryStr = "UPDATE tblUsers SET hidden = " . $isHidden . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_isHidden = $isHidden;
		return true;
	}	 /* }}} */

	/**
	 * Remove the user and also remove all its keywords, notifies, etc.
	 * Do not remove folders and documents of the user, but assign them
	 * to a different user.
	 *
	 * @param object $assignToUser the user who is new owner of folders and
	 *        documents which previously were owned by the delete user.
	 * @return boolean true on success or false in case of an error
	 */
	function remove( $assignToUser=null ) { /* {{{ */
		$db = $this->_dms->getDB();
		$user = $this->_dms->user;

		/* Records like folders and documents that formely have belonged to
		 * the user will assign to another user. If no such user is set,
		 * the function now returns false and will not use the admin user
		 * anymore.
		 */
		if(!$assignToUser)
			return;
		$assignTo = $assignToUser->getID();

		// delete private keyword lists
		$queryStr = "SELECT tblKeywords.id FROM tblKeywords, tblKeywordCategories WHERE tblKeywords.category = tblKeywordCategories.id AND tblKeywordCategories.owner = " . $this->_id;
		$resultArr = $db->getResultArray($queryStr);
		if (count($resultArr) > 0) {
			$queryStr = "DELETE FROM tblKeywords WHERE ";
			for ($i = 0; $i < count($resultArr); $i++) {
				$queryStr .= "id = " . $resultArr[$i]["id"];
				if ($i + 1 < count($resultArr))
					$queryStr .= " OR ";
			}
			if (!$db->getResult($queryStr))	return false;
		}

		$queryStr = "DELETE FROM tblKeywordCategories WHERE owner = " . $this->_id;
		if (!$db->getResult($queryStr))	return false;

		//Benachrichtigungen entfernen
		$queryStr = "DELETE FROM tblNotify WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		//Der Besitz von Dokumenten oder Ordnern, deren bisheriger Besitzer der zu löschende war, geht an den Admin über
		$queryStr = "UPDATE tblFolders SET owner = " . $assignTo . " WHERE owner = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		$queryStr = "UPDATE tblDocuments SET owner = " . $assignTo . " WHERE owner = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		$queryStr = "UPDATE tblDocumentContent SET createdBy = " . $assignTo . " WHERE createdBy = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		//Verweise auf Dokumente: Private löschen...
		$queryStr = "DELETE FROM tblDocumentLinks WHERE userID = " . $this->_id . " AND public = 0";
		if (!$db->getResult($queryStr)) return false;

		//... und öffentliche an Admin übergeben
		$queryStr = "UPDATE tblDocumentLinks SET userID = " . $assignTo . " WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		// set administrator for deleted user's attachments
		$queryStr = "UPDATE tblDocumentFiles SET userID = " . $assignTo . " WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		//Evtl. von diesem Benutzer gelockte Dokumente werden freigegeben
		$queryStr = "DELETE FROM tblDocumentLocks WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		//User aus allen Gruppen löschen
		$queryStr = "DELETE FROM tblGroupMembers WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		//User aus allen ACLs streichen
		$queryStr = "DELETE FROM tblACLs WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		//Eintrag aus tblUsers löschen
		$queryStr = "DELETE FROM tblUserImages WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		//Eintrag aus tblUsers löschen
		$queryStr = "DELETE FROM tblUsers WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		// mandatory review/approve
		$queryStr = "DELETE FROM tblMandatoryReviewers WHERE reviewerUserID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		$queryStr = "DELETE FROM tblMandatoryApprovers WHERE approverUserID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		$queryStr = "DELETE FROM tblMandatoryReviewers WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		$queryStr = "DELETE FROM tblMandatoryApprovers WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		// set administrator for deleted user's events
		$queryStr = "UPDATE tblEvents SET userID = " . $assignTo . " WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;


		// TODO : update document status if reviewer/approver has been deleted
		// "DELETE FROM tblDocumentApproveLog WHERE userID = " . $this->_id;
		// "DELETE FROM tblDocumentReviewLog WHERE userID = " . $this->_id;


		$reviewStatus = $this->getReviewStatus();
		foreach ($reviewStatus["indstatus"] as $ri) {
			$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $ri["reviewID"] ."', '-2', 'Reviewer removed from process', NOW(), '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
		}

		$approvalStatus = $this->getApprovalStatus();
		foreach ($approvalStatus["indstatus"] as $ai) {
			$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $ai["approveID"] ."', '-2', 'Approver removed from process', NOW(), '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
		}

//		unset($this);
		return true;
	} /* }}} */

	function joinGroup($group) { /* {{{ */
		if ($group->isMember($this))
			return false;

		if (!$group->addUser($this))
			return false;

		unset($this->_groups);
		return true;
	} /* }}} */

	function leaveGroup($group) { /* {{{ */
		if (!$group->isMember($this))
			return false;

		if (!$group->removeUser($this))
			return false;

		unset($this->_groups);
		return true;
	} /* }}} */

	function getGroups() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_groups))
		{
			$queryStr = "SELECT `tblGroups`.*, `tblGroupMembers`.`userID` FROM `tblGroups` ".
				"LEFT JOIN `tblGroupMembers` ON `tblGroups`.`id` = `tblGroupMembers`.`groupID` ".
				"WHERE `tblGroupMembers`.`userID`='". $this->_id ."'";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_groups = array();
			foreach ($resArr as $row) {
				$group = new LetoDMS_Group($row["id"], $row["name"], $row["comment"]);
				array_push($this->_groups, $group);
			}
		}
		return $this->_groups;
	} /* }}} */

	/**
	 * Checks if user is member of a given group
	 *
	 * @param object $group
	 * @return boolean true if user is member of the given group otherwise false
	 */
	function isMemberOfGroup($group) { /* {{{ */
		return $group->isMember($this);
	} /* }}} */

	/**
	 * Check if user has an image in its profile
	 *
	 * @return boolean true if user has a picture of itself
	 */
	function hasImage() { /* {{{ */
		if (!isset($this->_hasImage)) {
			$db = $this->_dms->getDB();

			$queryStr = "SELECT COUNT(*) AS num FROM tblUserImages WHERE userID = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			if ($resArr[0]["num"] == 0)	$this->_hasImage = false;
			else $this->_hasImage = true;
		}

		return $this->_hasImage;
	} /* }}} */

	function setImage($tmpfile, $mimeType) { /* {{{ */
		$db = $this->_dms->getDB();

		$fp = fopen($tmpfile, "rb");
		if (!$fp) return false;
		$content = fread($fp, filesize($tmpfile));
		fclose($fp);

		if ($this->hasImage())
			$queryStr = "UPDATE tblUserImages SET image = '".base64_encode($content)."', mimeType = '". $mimeType."' WHERE userID = " . $this->_id;
		else
			$queryStr = "INSERT INTO tblUserImages (userID, image, mimeType) VALUES (" . $this->_id . ", '".base64_encode($content)."', '".$mimeType."')";
		if (!$db->getResult($queryStr))
			return false;

		$this->_hasImage = true;
		return true;
	} /* }}} */

	function getReviewStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!$db->createTemporaryTable("ttreviewid")) {
			return false;
		}

		$status = array("indstatus"=>array(), "grpstatus"=>array());

		// See if the user is assigned as an individual reviewer.
		$queryStr = "SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
			"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
			"`tblDocumentReviewLog`.`userID` ".
			"FROM `tblDocumentReviewers` ".
			"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
			"LEFT JOIN `ttreviewid` on `ttreviewid`.`maxLogID` = `tblDocumentReviewLog`.`reviewLogID` ".
			"WHERE `ttreviewid`.`maxLogID`=`tblDocumentReviewLog`.`reviewLogID` ".
			($documentID==null ? "" : "AND `tblDocumentReviewers`.`documentID` = '". $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentReviewers`.`version` = '". $version ."' ").
			"AND `tblDocumentReviewers`.`type`='0' ".
			"AND `tblDocumentReviewers`.`required`='". $this->_id ."' ";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res)
				$status["indstatus"][] = $res;
		}

		// See if the user is the member of a group that has been assigned to
		// review the document version.
		$queryStr = "SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
			"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
			"`tblDocumentReviewLog`.`userID` ".
			"FROM `tblDocumentReviewers` ".
			"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`groupID` = `tblDocumentReviewers`.`required` ".
			"LEFT JOIN `ttreviewid` on `ttreviewid`.`maxLogID` = `tblDocumentReviewLog`.`reviewLogID` ".
			"WHERE `ttreviewid`.`maxLogID`=`tblDocumentReviewLog`.`reviewLogID` ".
			($documentID==null ? "" : "AND `tblDocumentReviewers`.`documentID` = '". $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentReviewers`.`version` = '". $version ."' ").
			"AND `tblDocumentReviewers`.`type`='1' ".
			"AND `tblGroupMembers`.`userID`='". $this->_id ."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res)
				$status["grpstatus"][] = $res;
		}
		return $status;
	} /* }}} */

	function getApprovalStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!$db->createTemporaryTable("ttapproveid")) {
			return false;
		}

		$status = array("indstatus"=>array(), "grpstatus"=>array());

		// See if the user is assigned as an individual approver.
		$queryStr = "SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
			"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
			"`tblDocumentApproveLog`.`userID` ".
			"FROM `tblDocumentApprovers` ".
			"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
			"LEFT JOIN `ttapproveid` on `ttapproveid`.`maxLogID` = `tblDocumentApproveLog`.`approveLogID` ".
			"WHERE `ttapproveid`.`maxLogID`=`tblDocumentApproveLog`.`approveLogID` ".
			($documentID==null ? "" : "AND `tblDocumentApprovers`.`documentID` = '". $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentApprovers`.`version` = '". $version ."' ").
			"AND `tblDocumentApprovers`.`type`='0' ".
			"AND `tblDocumentApprovers`.`required`='". $this->_id ."' ";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res)
				$status["indstatus"][] = $res;
		}

		// See if the user is the member of a group that has been assigned to
		// approve the document version.
		$queryStr = "SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
			"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
			"`tblDocumentApproveLog`.`userID` ".
			"FROM `tblDocumentApprovers` ".
			"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`groupID` = `tblDocumentApprovers`.`required` ".
			"LEFT JOIN `ttapproveid` on `ttapproveid`.`maxLogID` = `tblDocumentApproveLog`.`approveLogID` ".
			"WHERE `ttapproveid`.`maxLogID`=`tblDocumentApproveLog`.`approveLogID` ".
			($documentID==null ? "" : "AND `tblDocumentApprovers`.`documentID` = '". $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentApprovers`.`version` = '". $version ."' ").
			"AND `tblDocumentApprovers`.`type`='1' ".
			"AND `tblGroupMembers`.`userID`='". $this->_id ."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res)
				$status["grpstatus"][] = $res;
		}
		return $status;
	} /* }}} */

	function getMandatoryReviewers() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblMandatoryReviewers WHERE userID = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);

		return $resArr;
	} /* }}} */

	function getMandatoryApprovers() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblMandatoryApprovers WHERE userID = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);

		return $resArr;
	} /* }}} */

	function setMandatoryReviewer($id, $isgroup=false) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($isgroup){

			$queryStr = "SELECT * FROM tblMandatoryReviewers WHERE userID = " . $this->_id . " AND reviewerGroupID = " . $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return;

			$queryStr = "INSERT INTO tblMandatoryReviewers (userID, reviewerGroupID) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

		}else{

			$queryStr = "SELECT * FROM tblMandatoryReviewers WHERE userID = " . $this->_id . " AND reviewerUserID = " . $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return;

			$queryStr = "INSERT INTO tblMandatoryReviewers (userID, reviewerUserID) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;
		}

	} /* }}} */

	function setMandatoryApprover($id, $isgroup=false) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($isgroup){

			$queryStr = "SELECT * FROM tblMandatoryApprovers WHERE userID = " . $this->_id . " AND approverGroupID = " . $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return;

			$queryStr = "INSERT INTO tblMandatoryApprovers (userID, approverGroupID) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

		}else{

			$queryStr = "SELECT * FROM tblMandatoryApprovers WHERE userID = " . $this->_id . " AND approverUserID = " . $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return;

			$queryStr = "INSERT INTO tblMandatoryApprovers (userID, approverUserID) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;
		}
	} /* }}} */

	function delMandatoryReviewers() { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "DELETE FROM tblMandatoryReviewers WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;
	} /* }}} */

	function delMandatoryApprovers() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM tblMandatoryApprovers WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;
	} /* }}} */
}
?>
