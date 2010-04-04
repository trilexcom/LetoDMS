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

$folder = $document->getFolder();
$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

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

// verify document is waiting for approval
$document->verifyLastestContentExpriry();
$status = $content->getStatus();
if ($status["status"]!=S_DRAFT_APP) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if (!isset($_POST["approvalStatus"]) || !is_numeric($_POST["approvalStatus"]) ||
		(intval($_POST["approvalStatus"])!=1 && intval($_POST["approvalStatus"])!=-1)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_approval_status"));
}

// retrieve the approve status for the current user.
$approvalStatus = $user->getApprovalStatus($documentid, $version);
if (count($approvalStatus["indstatus"]) == 0 && count($approvalStatus["grpstatus"]) == 0) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if ($_POST["approvalType"] == "ind") {
	$indApprover = true;
	if (count($approvalStatus["indstatus"])==0) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}
	if ($approvalStatus["indstatus"][0]["status"]==-2) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}
	if ($approvalStatus["indstatus"][0]["status"]!=0) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}

	// User is eligible to make this update.

	$comment = sanitizeString($_POST["comment"]);
	$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
		"VALUES ('". $approvalStatus["indstatus"][0]["approveID"] ."', '".
		$_POST["approvalStatus"] ."', '". $comment ."', NOW(), '". $user->getID() ."')";
	$res=$db->getResult($queryStr);
	if (is_bool($res) && !res) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("approval_update_failed"));
	}
	else {
		// Send an email notification to the document owner.
		$subject = $settings->_siteName.": ".$document->getName().", v.".$version." - ".getMLText("approval_submit_email");
		$message = getMLText("approval_submit_email")."\r\n";
		$message .= 
			getMLText("name").": ".$document->getName()."\r\n".
			getMLText("version").": ".$version."\r\n".
			getMLText("user").": ".$user->getFullName()." <". $user->getEmail() ."> ".
			getMLText("status").": ".getApprovalStatusText($_POST["approvalStatus"])."\r\n".
			getMLText("comment").": ".$comment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$documentid."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toIndividual($user, $document->getOwner(), $subject, $message);

		// Send notification to subscribers.
		$nl=$document->getNotifyList();
		Email::toList($user, $nl["users"], $subject, $message);
		foreach ($nl["groups"] as $grp) {
			Email::toGroup($user, $grp, $subject, $message);
		}
	}
}
else if ($_POST["approvalType"] == "grp") {
	$grpApprover=false;
	foreach ($approvalStatus["grpstatus"] as $gs) {
		if ($_POST["approvalGroup"] == $gs["required"]) {
			if ($gs["status"]==-2) {
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
			}
			if ($gs["status"]!=0) {
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
			}
			$grpStatus=$gs;
			$grpApprover=true;
			break;
		}
	}
	if (!$grpApprover) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}

	// User is eligible to make this update.

	$comment = sanitizeString($_POST["comment"]);
	$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
		"VALUES ('". $grpStatus["approveID"] ."', '".
		$_POST["approvalStatus"] ."', '". $comment ."', NOW(), '". $user->getID() ."')";
	$res=$db->getResult($queryStr);
	if (is_bool($res) && !res) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("approval_update_failed"));
	}
	else {
		// Send an email notification to the document owner.
		$grp = getGroup($grpStatus["required"]);
		
		$subject = $settings->_siteName.": ".$document->getName().", v.".$version." - ".getMLText("approval_submit_email");
		$message = getMLText("approval_submit_email")."\r\n";
		$message .= 
			getMLText("name").": ".$document->getName()."\r\n".
			getMLText("version").": ".$version."\r\n".
			getMLText("user").": ".$user->getFullName()." <". $user->getEmail() ."> ".
			getMLText("status").": ".getApprovalStatusText($_POST["approvalStatus"])."\r\n".
			getMLText("comment").": ".$comment."\r\n".
			"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$documentid."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		Email::toIndividual($user, $document->getOwner(), $subject, $message);

		// Send notification to subscribers.
		$nl=$document->getNotifyList();
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

if ($_POST["approvalStatus"]==-1){

	$content->setStatus(S_REJECTED,$comment,$user);

}else{

	$docApprovalStatus = $content->getApprovalStatus(true);
	if (is_bool($docApprovalStatus) && !$docApprovalStatus) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("cannot_retrieve_approval_snapshot"));
	}
	$approvalCT = 0;
	$approvalTotal = 0;
	foreach ($docApprovalStatus as $drstat) {
		if ($drstat["status"] == 1) {
			$approvalCT++;
		}
		if ($drstat["status"] != -2) {
			$approvalTotal++;
		}
	}
	// If all approvals have been received and there are no rejections, retrieve a
	// count of the approvals required for this document.
	if ($approvalCT == $approvalTotal) {
		// Change the status to released.
		$newStatus=2;
		if ($content->setStatus($newStatus, getMLText("automatic_status_update"), $user)) {
		
			//Send email notification to document owner reporting the change in status.
			/*$subject = $settings->_siteName.": ".$document->getName().", v.".$version." - ".getMLText("automatic_status_update");
			$message = getMLText("automatic_status_update")."\r\n";
			$message .= 
				getMLText("name").": ".$document->getName()."\r\n".
				getMLText("version").": ".$version."\r\n".
				getMLText("status").": ".getOverallStatusText($newStatus)."\r\n".
				"URL: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$documentid."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			Email::toIndividual($user, $document->getOwner(), $subject, $message);*/
		}
	}
}

header("Location:../out/out.ViewDocument.php?documentid=".$documentid);

?>
