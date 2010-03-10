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

if (!isset($_GET["action"]) || (strcasecmp($_GET["action"], "delnotify") && strcasecmp($_GET["action"], "addnotify"))) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_action"));
}
$action = $_GET["action"];

if (isset($_GET["userid"]) && (!is_numeric($_GET["userid"]) || $_GET["userid"]<-1)) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
}
$userid = $_GET["userid"];

if (isset($_GET["groupid"]) && (!is_numeric($_GET["groupid"]) || $_GET["groupid"]<-1)) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_group"));
}
$groupid = $_GET["groupid"];

$folderPathHTML = getFolderPathHTML($folder, true);

if ($folder->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

//Benachrichtigung löschen ------------------------------------------------------------------------
if ($action == "delnotify") {

	if (isset($userid)) {
		$res = $folder->removeNotify($userid, true);
	}
	else if (isset($groupid)) {
		$res = $folder->removeNotify($groupid, false);
	}
	switch ($res) {
		case -1:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),isset($userid) ? getMLText("unknown_user") : getMLText("unknown_group"));
			break;
		case -2:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
			break;
		case -3:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("already_subscribed"));
			break;
		case -4:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("internal_error"));
			break;
		case 0:
			break;
	}
}

//Benachrichtigung hinzufügen ---------------------------------------------------------------------
else if ($action == "addnotify") {

	if ($userid != -1) {
		$res = $folder->addNotify($userid, true);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
				break;
			case -2:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("internal_error"));
				break;
			case 0:
				break;
		}
	}
	if ($groupid != -1) {
		$res = $folder->addNotify($groupid, false);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_group"));
				break;
			case -2:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("internal_error"));
				break;
			case 0:
				break;
		}
	}
}
	
header("Location:../out/out.FolderNotify.php?folderid=".$folderid);

?>
