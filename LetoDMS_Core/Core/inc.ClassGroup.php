<?php
/**
 * Implementation of the group object in the document management system
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a user group in the document management system
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_Group {
	/**
	 * The id of the user group
	 *
	 * @var integer
	 */
	var $_id;

	/**
	 * The name of the user group
	 *
	 * @var string
	 */
	var $_name;

	/**
	 * Back reference to DMS this user group belongs to
	 *
	 * @var object
	 */
	var $_dms;

	function LetoDMS_Core_Group($id, $name, $comment) { /* {{{ */
		$this->_id = $id;
		$this->_name = $name;
		$this->_comment = $comment;
		$this->_dms = null;
	} /* }}} */

	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblGroups SET name = '" . $newName . "' WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	function getComment() { return $this->_comment; }

	function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblGroups SET comment = '" . $newComment . "' WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	function getUsers() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_users)) {
			$queryStr = "SELECT `tblUsers`.* FROM `tblUsers` ".
				"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
				"WHERE `tblGroupMembers`.`groupID` = '". $this->_id ."'";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_users = array();

			foreach ($resArr as $row) {
				$user = new LetoDMS_Core_User($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $row["role"], $row['hidden']);
				array_push($this->_users, $user);
			}
		}
		return $this->_users;
	} /* }}} */

	function addUser($user,$asManager=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "INSERT INTO tblGroupMembers (groupID, userID, manager) VALUES (".$this->_id.", ".$user->getID(). ", " . ($asManager?"1":"0") ." )";
		$res = $db->getResult($queryStr);

		if ($res) return false;

		unset($this->_users);
		return true;
	} /* }}} */

	function removeUser($user) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM tblGroupMembers WHERE groupID = ".$this->_id." AND userID = ".$user->getID();
		$res = $db->getResult($queryStr);

		if ($res) return false;
		unset($this->_users);
		return true;
	} /* }}} */

	// $asManager=false: verify if user is in group
	// $asManager=true : verify if user is in group as manager
	function isMember($user,$asManager=false) { /* {{{ */
		if (isset($this->_users)&&!$asManager) {
			foreach ($this->_users as $usr)
				if ($usr->getID() == $user->getID())
					return true;
			return false;
		}

		$db = $this->_dms->getDB();
		if ($asManager) $queryStr = "SELECT * FROM tblGroupMembers WHERE groupID = " . $this->_id . " AND userID = " . $user->getID() . " AND manager = 1";
		else $queryStr = "SELECT * FROM tblGroupMembers WHERE groupID = " . $this->_id . " AND userID = " . $user->getID();

		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		return true;
	} /* }}} */

	function toggleManager($user) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!$this->isMember($user)) return false;

		if ($this->isMember($user,true)) $queryStr = "UPDATE tblGroupMembers SET manager = 0 WHERE groupID = ".$this->_id." AND userID = ".$user->getID();
		else $queryStr = "UPDATE tblGroupMembers SET manager = 1 WHERE groupID = ".$this->_id." AND userID = ".$user->getID();

		if (!$db->getResult($queryStr)) return false;
		return true;
	} /* }}} */

	/**
	 * Delete user group
	 * This function deletes the user group and all it references, like access
	 * control lists, notifications, as a child of other groups, etc.
	 *
	 * @param object $user the user doing the removal (needed for entry in
	 *        review log.
	 * @return boolean true on success or false in case of an error
	 */
	function remove($user) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM tblGroups WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblGroupMembers WHERE groupID = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblACLs WHERE groupID = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblNotify WHERE groupID = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblMandatoryReviewers WHERE reviewerGroupID = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblMandatoryApprovers WHERE approverGroupID = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		// TODO : update document status if reviewer/approver has been deleted


		$reviewStatus = $this->getReviewStatus();
		foreach ($reviewStatus as $r) {
			$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $r["reviewID"] ."', '-2', 'Review group removed from process', NOW(), '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
		}

		$approvalStatus = $this->getApprovalStatus();
		foreach ($approvalStatus as $a) {
			$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $a["approveID"] ."', '-2', 'Approval group removed from process', NOW(), '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
		}

		return true;
	} /* }}} */

	function getReviewStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!$db->createTemporaryTable("ttreviewid")) {
			return false;
		}

		$status = array();

		// See if the group is assigned as a reviewer.
		$queryStr = "SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
			"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
			"`tblDocumentReviewLog`.`userID` ".
			"FROM `tblDocumentReviewers` ".
			"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
			"LEFT JOIN `ttreviewid` on `ttreviewid`.`maxLogID` = `tblDocumentReviewLog`.`reviewLogID` ".
			"WHERE `ttreviewid`.`maxLogID`=`tblDocumentReviewLog`.`reviewLogID` ".
			($documentID==null ? "" : "AND `tblDocumentReviewers`.`documentID` = '". $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentReviewers`.`version` = '". $version ."' ").
			"AND `tblDocumentReviewers`.`type`='1' ".
			"AND `tblDocumentReviewers`.`required`='". $this->_id ."' ";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res)
				$status[] = $res;
		}
		return $status;
	} /* }}} */

	function getApprovalStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!$db->createTemporaryTable("ttapproveid")) {
			return false;
		}

		$status = array();

		// See if the group is assigned as an approver.
		$queryStr = "SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
			"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
			"`tblDocumentApproveLog`.`userID` ".
			"FROM `tblDocumentApprovers` ".
			"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
			"LEFT JOIN `ttapproveid` on `ttapproveid`.`maxLogID` = `tblDocumentApproveLog`.`approveLogID` ".
			"WHERE `ttapproveid`.`maxLogID`=`tblDocumentApproveLog`.`approveLogID` ".
			($documentID==null ? "" : "AND `tblDocumentApprovers`.`documentID` = '". $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentApprovers`.`version` = '". $version ."' ").
			"AND `tblDocumentApprovers`.`type`='1' ".
			"AND `tblDocumentApprovers`.`required`='". $this->_id ."' ";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res)
				$status[] = $res;
		}

		return $status;
	} /* }}} */
}
?>
