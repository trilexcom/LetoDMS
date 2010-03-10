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

include("../inc/inc.Settings.php");
include("../inc/inc.AccessUtils.php");
include("../inc/inc.ClassAccess.php");
include("../inc/inc.ClassDocument.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}
$documentid = $_POST["documentid"];
$document = getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if ($document->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if (!isset($_POST["version"]) || !is_numeric($_POST["version"]) || intval($_POST["version"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$version = $_POST["version"];
$content = $document->getContentByVersion($version);

if (!is_object($content)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

// verify document is waiting for review
$document->verifyLastestContentExpriry();
$status = $content->getStatus();
if ($status["status"]!=S_DRAFT_REV) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if (!isset($_POST["reviewStatus"]) || !is_numeric($_POST["reviewStatus"]) ||
		(intval($_POST["reviewStatus"])!=1 && intval($_POST["reviewStatus"])!=-1)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_review_status"));
}

// retrieve the review status for the current user.
$reviewStatus = $user->getReviewStatus($documentid, $version);
if (count($reviewStatus["indstatus"]) == 0 && count($reviewStatus["grpstatus"]) == 0) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if ($_POST["reviewType"] == "ind") {
	$indReviewer = true;
	if (count($reviewStatus["indstatus"])==0) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}
	if ($reviewStatus["indstatus"][0]["status"]==-2) {		
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}
	if ($reviewStatus["indstatus"][0]["status"]!=0) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}

	// User is eligible to make this update.

	$comment = sanitizeString($_POST["comment"]);
	$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
		"VALUES ('". $reviewStatus["indstatus"][0]["reviewID"] ."', '".
		$_POST["reviewStatus"] ."', '". $comment ."', NOW(), '". $user->getID() ."')";
	$res=$db->getResult($queryStr);
	if (is_bool($res) && !res) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("review_update_failed"));
	}
	else {
		// Send an email notification to the document owner.
		$subject = $setting->_siteName.": ".$document->getName().", v.".$version;
		$message = getMLText("review_submit_email");
		$message = wordwrap ($message, 72, "\r\n");
		$message .= 
			getMLText("name").": ".$document->getName()."\r\n".
			getMLText("version").": ".$version."\r\n".
			getMLText("user").": ".$user->getFullName()." <". $user->getEmail() ."> ".
			getMLText("status").": ".getReviewStatusText($_POST["reviewStatus"])."\r\n".
			getMLText("comment").": ".$comment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$documentid."\r\n";

		Email::toIndividual($user, $document->getOwner(), $subject, $message);
		
		// Send notification to subscribers.
		$nl=$document->getNotifyList();
		$subject = "[Document Notification]: ".$document->getName().", v. ".$version;
		Email::toList($user, $nl["users"], $subject, $message);
		foreach ($nl["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}
	}
}
else if ($_POST["reviewType"] == "grp") {
	$grpReviewer=false;
	foreach ($reviewStatus["grpstatus"] as $gs) {
		if ($_POST["reviewGroup"] == $gs["required"]) {
			if ($gs["status"]==-2) {
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
			}
			if ($gs["status"]!=0) {
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
				
			}
			$grpStatus=$gs;
			$grpReviewer=true;
			break;
		}
	}
	if (!$grpReviewer) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}

	// User is eligible to make this update.

	$comment = sanitizeString($_POST["comment"]);
	$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
		"VALUES ('". $grpStatus["reviewID"] ."', '".
		$_POST["reviewStatus"] ."', '". $comment ."', NOW(), '". $user->getID() ."')";
	$res=$db->getResult($queryStr);
	if (is_bool($res) && !res) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("review_update_failed"));
	}
	else {
		// Send an email notification to the document owner.
		$grp = getGroup($grpStatus["required"]);
		
		$subject = $setting->_siteName.": ".$document->getName().", v.".$version;
		$message = getMLText("review_submit_email");
		$message = wordwrap ($message, 72, "\r\n");
		$message .= 
			getMLText("name").": ".$document->getName()."\r\n".
			getMLText("user").": ".$user->getFullName()." <". $user->getEmail() ."> ".
			getMLText("version").": ".$version."\r\n".
			getMLText("status").": ".getReviewStatusText($_POST["reviewStatus"])."\r\n".
			getMLText("comment").": ".$comment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$documentid."\r\n";

		Email::toIndividual($user, $document->getOwner(), $subject, $message);
		
		// Send notification to subscribers.
		$nl=$document->getNotifyList();
		$subject = "[Document Notification]: ".$document->getName().", v. ".$version;
		Email::toList($user, $nl["users"], $subject, $message);
		foreach ($nl["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}
	}
}

//
// Check to see if the overall status for the document version needs to be
// updated.
//

if ($_POST["reviewStatus"]==-1){

	$content->setStatus(S_REJECTED,$comment,$user);

}else{

	$docReviewStatus = $content->getReviewStatus(true);
	if (is_bool($docReviewStatus) && !$docReviewStatus) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("cannot_retrieve_review_snapshot"));
	}
	$reviewCT = 0;
	$reviewTotal = 0;
	foreach ($docReviewStatus as $drstat) {
		if ($drstat["status"] == 1) {
			$reviewCT++;
		}
		if ($drstat["status"] != -2) {
			$reviewTotal++;
		}
	}
	// If all reviews have been received and there are no rejections, retrieve a
	// count of the approvals required for this document.
	if ($reviewCT == $reviewTotal) {
		$docApprovalStatus = $content->getApprovalStatus(true);
		if (is_bool($docApprovalStatus) && !$docApprovalStatus) {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("cannot_retrieve_approval_snapshot"));
		}
		$approvalCT = 0;
		$approvalTotal = 0;
		foreach ($docApprovalStatus as $dastat) {
			if ($dastat["status"] == 1) {
				$approvalCT++;
			}
			if ($dastat["status"] != -2) {
				$approvalTotal++;
			}
		}
		// If the approvals received is less than the approvals total, then
		// change status to pending approval.
		if ($approvalCT<$approvalTotal) {
			$newStatus=1;
		}
		else {
			// Otherwise, change the status to released.
			$newStatus=2;
		}
		if ($content->setStatus($newStatus, getMLText("automatic_status_update"), $user)) {
		
			//Send email notification to document owner reporting the change in status.
			$subject = $setting->_siteName.": ".$document->getName().", v.".$version;
			$message = getMLText("automatic_status_update");
			$message = wordwrap ($message, 72, "\r\n");
			$message .= 
				getMLText("name").": ".$document->getName()."\r\n".
				getMLText("version").": ".$version."\r\n".
				getMLText("status").": ".getOverallStatusText($newStatus)."\r\n".
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$documentid."\r\n";

			Email::toIndividual($user, $document->getOwner(), $subject, $message);

			// Notify approvers, if necessary.
			if ($newStatus == S_DRAFT_APP) {
				$requestUser = $document->getOwner();
				
				$subject = $setting->_siteName.": ".$document->getName().", v.".$version;
				$message = getMLText("approval_request_email");
				$message = wordwrap ($message, 72, "\r\n");
				$message .= 
					getMLText("name").": ".$content->getOriginalFileName()."\r\n".
					getMLText("version").": ".$version."\r\n".
					getMLText("comment").": ".$content->getComment()."\r\n".
					"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ApproveDocument.php?documentid=".$documentid."&version=".$version."\r\n";

				foreach ($docApprovalStatus as $dastat) {
					if ($dastat["status"] == 0) {
						if ($dastat["type"] == 0) {
							$imessage = $requestUser->getFullName()." <". $requestUser->getEmail() ."> ".
								"has requested that you approve the following document:";
							$imessage = wordwrap ($imessage, 72, "\r\n");
							$approver = getUser($dastat["required"]);
							
							Email::toIndividual($document->getOwner(), $approver, $subject, $imessage.$message);
						}
						else if ($dastat["type"] == 1) {
							$group = getGroup($dastat["required"]);
							$gmessage = $requestUser->getFullName()." <". $requestUser->getEmail() ."> ".
								"has requested that a member of the group '". $group->getName() ."' approve the following document:";
							$gmessage = wordwrap ($gmessage, 72, "\r\n");
							
							Email::toGroup($document->getOwner(), $group, $subject, $gmessage.$message);
						}
					}
				}
			}
		}
	}
}

header("Location:../out/out.ViewDocument.php?documentid=".$documentid);

?>
