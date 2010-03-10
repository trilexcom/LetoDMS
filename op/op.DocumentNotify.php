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

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_GET["documentid"];
$document = getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if (!isset($_GET["action"]) || (strcasecmp($_GET["action"], "delnotify") && strcasecmp($_GET["action"],"addnotify"))) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_action"));
}

$action = $_GET["action"];

if (isset($_GET["userid"]) && (!is_numeric($_GET["userid"]) || $_GET["userid"]<-1)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_user"));
}

$userid = $_GET["userid"];

if (isset($_GET["groupid"]) && (!is_numeric($_GET["groupid"]) || $_GET["groupid"]<-1)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_group"));
}

$groupid = $_GET["groupid"];

$folder = $document->getFolder();
$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

if ($document->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

//Benachrichtigung löschen ------------------------------------------------------------------------
if ($action == "delnotify"){
	if (isset($userid)) {
		$res = $document->removeNotify($userid, true);
	}
	else if (isset($groupid)) {
		$res = $document->removeNotify($groupid, false);
	}
	switch ($res) {
		case -1:
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),isset($userid) ? getMLText("unknown_user") : getMLText("unknown_group"));
			break;
		case -2:
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
			break;
		case -3:
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("already_subscribed"));
			break;
		case -4:
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("internal_error"));
			break;
		case 0:
			break;
	}
}
	
	//Benachrichtigung hinzufügen ---------------------------------------------------------------------
else if ($action == "addnotify") {

	if ($userid != -1) {
		$res = $document->addNotify($userid, true);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_user"));
				break;
			case -2:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("internal_error"));
				break;
			case 0:
				break;
		}
	}
	if ($groupid != -1) {
		$res = $document->addNotify($groupid, false);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_group"));
				break;
			case -2:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("internal_error"));
				break;
			case 0:
				break;
		}
	}

}

header("Location:../out/out.DocumentNotify.php?documentid=".$documentid);

?>
