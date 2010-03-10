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

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

$action = $_GET["action"];
//Neue Gruppe anlegen -----------------------------------------------------------------------------
if ($action == "addgroup") {

	$name = sanitizeString($_GET["name"]);
	$comment = sanitizeString($_GET["comment"]);

	if (is_object(getGroupByName($name))) {
		UI::exitError(getMLText("admin_tools"),getMLText("group_exists"));
	}

	$newGroup = addGroup($name, $comment);
	if (!$newGroup) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
}

//Gruppe löschen ----------------------------------------------------------------------------------
else if ($action == "removegroup") {
	
	if (!isset($_GET["groupid"]) || !is_numeric($_GET["groupid"]) || intval($_GET["groupid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$group = getGroup($_GET["groupid"]);
	if (!is_object($group)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}

	if (!$group->remove()) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
}

//Gruppe bearbeiten -------------------------------------------------------------------------------
else if ($action == "editgroup") {

	if (!isset($_GET["groupid"]) || !is_numeric($_GET["groupid"]) || intval($_GET["groupid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$group = getGroup($_GET["groupid"]);
	if (!is_object($group)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$name = sanitizeString($_GET["name"]);
	$comment = sanitizeString($_GET["comment"]);

	if ($group->getName() != $name)
		$group->setName($name);
	if ($group->getComment() != $comment)
		$group->setComment($comment);
}

//Benutzer zu Gruppe hinzufügen -------------------------------------------------------------------
else if ($action == "addmember") {

	if (!isset($_GET["groupid"]) || !is_numeric($_GET["groupid"]) || intval($_GET["groupid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$group = getGroup($_GET["groupid"]);
	if (!is_object($group)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}

	if (!isset($_GET["userid"]) || !is_numeric($_GET["userid"]) || intval($_GET["userid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}
	
	$newMember = getUser($_GET["userid"]);
	if (!is_object($newMember)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}

	$group->addUser($newMember);
}

//Benutzer aus Gruppe entfernen -------------------------------------------------------------------
else if ($action == "rmmember") {

	if (!isset($_GET["groupid"]) || !is_numeric($_GET["groupid"]) || intval($_GET["groupid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$group = getGroup($_GET["groupid"]);
	if (!is_object($group)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}

	if (!isset($_GET["userid"]) || !is_numeric($_GET["userid"]) || intval($_GET["userid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}
	
	$oldMember = getUser($_GET["userid"]);
	if (!is_object($oldMember)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}

	$group->removeUser($oldMember);
}

header("Location:../out/out.GroupMgr.php");

?>
