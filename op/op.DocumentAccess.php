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
include("../inc/inc.Utils.php");
include("../LetoDMS_Core.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}
$documentid = $_GET["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$folder = $document->getFolder();
$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

if ($document->getAccessMode($user) < M_ALL) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

switch ($_GET["action"]) {
	case "setowner":
	case "delaccess":
	case "inherit":
		$action = $_GET["action"];
		break;
	case "setdefault":
	case "editaccess":
	case "addaccess":
		$action = $_GET["action"];
		if (!isset($_GET["mode"]) || !is_numeric($_GET["mode"]) || $_GET["mode"]<M_ANY || $_GET["mode"]>M_ALL) {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_access_mode"));
		}
		$mode = $_GET["mode"];
		break;
	case "notinherit":
		$action = $_GET["action"];
		if (strcasecmp($_GET["mode"], "copy") && strcasecmp($_GET["mode"], "empty")) {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_access_mode"));
		}
		$mode = $_GET["mode"];
		break;
	default:
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_action"));
		break;
}

if (isset($_GET["userid"])) {
	if (!is_numeric($_GET["userid"])) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_user"));
	}
	
	if (!strcasecmp($action, "addaccess") && $_GET["userid"]==-1) {
		$userid = -1;
	}
	else {
		if (!is_object($dms->getUser($_GET["userid"]))) {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_user"));
		}
		$userid = $_GET["userid"];
	}
}

if (isset($_GET["groupid"])) {
	if (!is_numeric($_GET["groupid"])) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_group"));
	}
	if (!strcasecmp($action, "addaccess") && $_GET["groupid"]==-1) {
		$groupid = -1;
	}
	else {
		if (!is_object($dms->getGroup($_GET["groupid"]))) {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_group"));
		}
		$groupid = $_GET["groupid"];
	}
}

//Ändern des Besitzers ----------------------------------------------------------------------------
if ($action == "setowner") {
	if (!$user->isAdmin()) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}
	if (!isset($_GET["ownerid"]) || !is_numeric($_GET["ownerid"]) || $_GET["ownerid"]<1) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_user"));	
	}
	
	$newOwner = $dms->getUser($_GET["ownerid"]);
	
	if (!is_object($newOwner)) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_user"));	
	}
	$oldOwner = $document->getOwner();
	if($document->setOwner($newOwner)) {
		$document->getNotifyList();
		// Send notification to subscribers.
		if($notifier) {
			$folder = $document->getFolder();
			$subject = "###SITENAME###: ".$document->_name." - ".getMLText("ownership_changed_email");
			$message = getMLText("ownership_changed_email")."\r\n";
			$message .= 
				getMLText("document").": ".$document->_name."\r\n".
				getMLText("old").": ".$oldOwner->getFullName()."\r\n".
				getMLText("new").": ".$newOwner->getFullName()."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				getMLText("comment").": ".$document->_comment."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewDocument.php?documentid=".$document->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $document->_notifyList["users"], $subject, $message);
			foreach ($document->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
			// Send notification to previous owner.
			$notifier->toIndividual($user, $oldOwner, $subject, $message);
		}
	}
}

//Änderung auf nicht erben ------------------------------------------------------------------------
else if ($action == "notinherit") {

	$defAccess = $document->getDefaultAccess();
	if($document->setInheritAccess(false)) {
		$document->getNotifyList();
		if($notifier) {
			$folder = $document->getFolder();
			// Send notification to subscribers.
			$subject = "###SITENAME###: ".$document->_name." - ".getMLText("access_permission_changed_email");
			$message = getMLText("access_permission_changed_email")."\r\n";
			$message .= 
				getMLText("document").": ".$document->_name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewDocument.php?documentid=".$document->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $document->_notifyList["users"], $subject, $message);
			foreach ($document->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	}
	if($document->setDefaultAccess($defAccess)) {
		$document->getNotifyList();
		if($notifier) {
			$folder = $document->getFolder();
			// Send notification to subscribers.
			$subject = "###SITENAME###: ".$document->_name." - ".getMLText("access_permission_changed_email");
			$message = getMLText("access_permission_changed_email")."\r\n";
			$message .= 
				getMLText("document").": ".$document->_name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewDocument.php?documentid=".$document->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $document->_notifyList["users"], $subject, $message);
			foreach ($document->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	}

	//copy ACL of parent folder
	if ($mode == "copy") {
		$accessList = $folder->getAccessList();
		foreach ($accessList["users"] as $userAccess)
			$document->addAccess($userAccess->getMode(), $userAccess->getUserID(), true);
		foreach ($accessList["groups"] as $groupAccess)
			$document->addAccess($groupAccess->getMode(), $groupAccess->getGroupID(), false);
	}
}

//Änderung auf erben ------------------------------------------------------------------------------
else if ($action == "inherit") {
	$document->clearAccessList();
	$document->setInheritAccess(true);
}

//Standardberechtigung setzen----------------------------------------------------------------------
else if ($action == "setdefault") {
	if($document->setDefaultAccess($mode)) {
		$document->getNotifyList();
		if($notifier) {
			$folder = $document->getFolder();
			// Send notification to subscribers.
			$subject = "###SITENAME###: ".$document->_name." - ".getMLText("access_permission_changed_email");
			$message = getMLText("access_permission_changed_email")."\r\n";
			$message .= 
				getMLText("document").": ".$document->_name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewDocument.php?documentid=".$document->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $document->_notifyList["users"], $subject, $message);
			foreach ($document->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	}
}

// Bestehende Berechtigung ändern --------------------------------------------
else if ($action == "editaccess") {
	if (isset($userid)) {
		$document->changeAccess($mode, $userid, true);
	}
	else if (isset($groupid)) {
		$document->changeAccess($mode, $groupid, false);
	}
}

//Berechtigung löschen ----------------------------------------------------------------------------
else if ($action == "delaccess") {
	if (isset($userid)) {
		$document->removeAccess($userid, true);
	}
	else if (isset($groupid)) {
		$document->removeAccess($groupid, false);
	}
}

	//Neue Berechtigung hinzufügen --------------------------------------------------------------------
else if ($action == "addaccess") {
	if (isset($userid) && $userid != -1) {
		$document->addAccess($mode, $userid, true);
	}
	if (isset($groupid) && $groupid != -1) {
		$document->addAccess($mode, $groupid, false);
	}
}

add_log_line("");

header("Location:../out/out.DocumentAccess.php?documentid=".$documentid);

?>
