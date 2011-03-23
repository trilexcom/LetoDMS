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
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderid = $_GET["folderid"];
$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

if ($folder->getAccessMode($user) < M_ALL) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
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
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_access_mode"));
		}
		$mode = $_GET["mode"];
		break;
	case "notinherit":
		$action = $_GET["action"];
		if (strcasecmp($_GET["mode"], "copy") && strcasecmp($_GET["mode"], "empty")) {
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_access_mode"));
		}
		$mode = $_GET["mode"];
		break;
	default:
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_action"));
		break;
}

if (isset($_GET["userid"])) {
	if (!is_numeric($_GET["userid"])) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
	}
	if (!strcasecmp($action, "addaccess") && $_GET["userid"]==-1) {
		$userid = -1;
	}
	else {
		if (!is_object($dms->getUser($_GET["userid"]))) {
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
		}
		$userid = $_GET["userid"];
	}
}

if (isset($_GET["groupid"])) {
	if (!is_numeric($_GET["groupid"])) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_group"));
	}
	if (!strcasecmp($action, "addaccess") && $_GET["groupid"]==-1) {
		$groupid = -1;
	}
	else {
		if (!is_object($dms->getGroup($_GET["groupid"]))) {
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_group"));
		}
		$groupid = $_GET["groupid"];
	}
}

//Ändern des Besitzers ----------------------------------------------------------------------------
if ($action == "setowner") {

	if (!$user->isAdmin()) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
	}
	if (!isset($_GET["ownerid"]) || !is_numeric($_GET["ownerid"]) || $_GET["ownerid"]<1) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
	}
	$newOwner = $dms->getUser($_GET["ownerid"]);
	if (!is_object($newOwner)) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
	}
	$oldOwner = $folder->getOwner();
	if($folder->setOwner($newOwner)) {
		if($notifier) {
			// Send notification to subscribers.
			$folder->getNotifyList();
			$subject = "###SITENAME###: ".$folder->_name." - ".getMLText("ownership_changed_email");
			$message = getMLText("ownership_changed_email")."\r\n";
			$message .= 
				getMLText("name").": ".$folder->_name."\r\n".
				getMLText("old").": ".$oldOwner->getFullName()."\r\n".
				getMLText("new").": ".$newOwner->getFullName()."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				getMLText("comment").": ".$folder->_comment."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $folder->_notifyList["users"], $subject, $message);
			foreach ($folder->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	} else {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("set_owner_error"));
	}
}

//Änderung auf nicht erben ------------------------------------------------------------------------
else if ($action == "notinherit") {

	$defAccess = $folder->getDefaultAccess();
	if($folder->setInheritAccess(false)) {
		if($notifier) {
			// Send notification to subscribers.
			$folder->getNotifyList();
			$subject = "###SITENAME###: ".$folder->_name." - ".getMLText("access_permission_changed_email");
			$message = getMLText("access_permission_changed_email")."\r\n";
			$message .= 
				getMLText("name").": ".$folder->_name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $folder->_notifyList["users"], $subject, $message);
			foreach ($folder->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	}
	if($folder->setDefaultAccess($defAccess)) {
		if($notifier) {
			// Send notification to subscribers.
			$folder->getNotifyList();
			$subject = "###SITENAME###: ".$folder->_name." - ".getMLText("access_permission_changed_email");
			$message = getMLText("access_permission_changed_email")."\r\n";
			$message .= 
				getMLText("name").": ".$folder->_name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $folder->_notifyList["users"], $subject, $message);
			foreach ($folder->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	}
	if ($mode == "copy") {
		$parent = $folder->getParent();
		$accessList = $parent->getAccessList();
		foreach ($accessList["users"] as $userAccess)
			$folder->addAccess($userAccess->getMode(), $userAccess->getUserID(), true);
		foreach ($accessList["groups"] as $groupAccess)
			$folder->addAccess($groupAccess->getMode(), $groupAccess->getGroupID(), false);
	}
}

//Änderung auf erben ------------------------------------------------------------------------------
else if ($action == "inherit") {

	if ($folderid == $settings->_rootFolderID || !$folder->getParent()) return;

	$folder->clearAccessList();
	if($folder->setInheritAccess(true)) {
		if($notifier) {
			// Send notification to subscribers.
			$folder->getNotifyList();
			$subject = "###SITENAME###: ".$folder->_name." - ".getMLText("access_permission_changed_email");
			$message = getMLText("access_permission_changed_email")."\r\n";
			$message .= 
				getMLText("name").": ".$folder->_name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $folder->_notifyList["users"], $subject, $message);
			foreach ($folder->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	}
}

//Standardberechtigung setzen----------------------------------------------------------------------
else if ($action == "setdefault") {
	if($folder->setDefaultAccess($mode)) {
		if($notifier) {
			// Send notification to subscribers.
			$folder->getNotifyList();
			$subject = "###SITENAME###: ".$folder->_name." - ".getMLText("access_permission_changed_email");
			$message = getMLText("access_permission_changed_email")."\r\n";
			$message .= 
				getMLText("name").": ".$folder->_name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $folder->_notifyList["users"], $subject, $message);
			foreach ($folder->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	}
}

//Bestehende Berechtigung änndern -----------------------------------------------------------------
else if ($action == "editaccess") {
	if (isset($userid)) {
		$folder->changeAccess($mode, $userid, true);
	}
	else if (isset($groupid)) {
		$folder->changeAccess($mode, $groupid, false);
	}
}

//Berechtigung löschen ----------------------------------------------------------------------------
else if ($action == "delaccess") {

	if (isset($userid)) {
		$folder->removeAccess($userid, true);
	}
	else if (isset($groupid)) {
		$folder->removeAccess($groupid, false);
	}
}

//Neue Berechtigung hinzufügen --------------------------------------------------------------------
else if ($action == "addaccess") {

	if (isset($userid) && $userid != -1) {
		$folder->addAccess($mode, $userid, true);
	}
	if (isset($groupid) && $groupid != -1) {
		$folder->addAccess($mode, $groupid, false);
	}
}

add_log_line();

header("Location:../out/out.FolderAccess.php?folderid=".$folderid);

?>
