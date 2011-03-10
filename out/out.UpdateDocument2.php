<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2011 Uwe Steinmann
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

if ($document->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("document_title", array("documentname" => $document->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($docPathHTML, "view_document");

UI::contentHeading(getMLText("update_document") . ": " . $document->getName());
UI::contentContainerStart();

if ($document->isLocked()) {

	$lockingUser = $document->getLockingUser();
	
	print "<table><tr><td class=\"warning\">";
	
	printMLText("update_locked_msg", array("username" => $lockingUser->getFullName(), "email" => $lockingUser->getEmail()));
	
	if ($lockingUser->getID() == $user->getID())
		printMLText("unlock_cause_locking_user");
	else if ($document->getAccessMode($user) == M_ALL)
		printMLText("unlock_cause_access_mode_all");
	else
	{
		printMLText("no_update_cause_locked");
		print "</td></tr></table>";
		UI::contentContainerEnd();
		UI::htmlEndPage();
		exit;
	}

	print "</td></tr></table><br>";
}

// Retrieve a list of all users and groups that have review / approve
// privileges.
$docAccess = $document->getApproversList();

UI::printUploadApplet('../op/op.UpdateDocument2.php', array('folderid'=>$folder->getId(), 'documentid'=>$document->getId()), 1, array('version_comment'=>1));

UI::contentContainerEnd();
UI::htmlEndPage();
?>
