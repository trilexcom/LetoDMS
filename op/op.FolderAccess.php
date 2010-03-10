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
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderid = $_GET["folderid"];
$folder = getFolder($folderid);

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
		if (!is_object(getUser($_GET["userid"]))) {
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
		if (!is_object(getGroup($_GET["groupid"]))) {
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
	$newOwner = getUser($_GET["ownerid"]);
	if (!is_object($newOwner)) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
	}
	$folder->setOwner($newOwner);
}

//Änderung auf nicht erben ------------------------------------------------------------------------
else if ($action == "notinherit") {

	$defAccess = $folder->getDefaultAccess();
	$folder->setInheritAccess(false);
	$folder->setDefaultAccess($defAccess);

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
	$folder->setInheritAccess(true);
}

//Standardberechtigung setzen----------------------------------------------------------------------
else if ($action == "setdefault") {
	$folder->setDefaultAccess($mode);
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

header("Location:../out/out.FolderAccess.php?folderid=".$folderid);

?>
