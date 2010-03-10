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
include("../inc/inc.AccessUtils.php");
include("../inc/inc.ClassAccess.php");
include("../inc/inc.ClassDocument.php");
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_POST["folderid"]) || !is_numeric($_POST["folderid"]) || intval($_POST["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderid = $_POST["folderid"];
$folder = getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

if ($folder->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

if (is_uploaded_file($_FILES["userfile"]["tmp_name"]) && $_FILES["userfile"]["size"] > 0 && $_FILES['userfile']['error']!=0){
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("uploading_failed"));
}

$name     = sanitizeString($_POST["name"]);
$comment  = sanitizeString($_POST["comment"]);
$keywords = sanitizeString($_POST["keywords"]);

$userfiletmp = $_FILES["userfile"]["tmp_name"];
$userfiletype = sanitizeString($_FILES["userfile"]["type"]);
$userfilename = sanitizeString($_FILES["userfile"]["name"]);

$sequence = $_POST["sequence"];

if (!is_numeric($sequence)) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_sequence"));
}
$lastDotIndex = strrpos(basename($userfilename), ".");
if (is_bool($lastDotIndex) && !$lastDotIndex)
	$fileType = ".";
else
	$fileType = substr($userfilename, $lastDotIndex);

$expires = ($_POST["expires"] == "true") ? mktime(0,0,0, sanitizeString($_POST["expmonth"]), sanitizeString($_POST["expday"]), sanitizeString($_POST["expyear"])) : false;

// Get the list of reviewers and approvers for this document.
$reviewers = array();
$approvers = array();
if (isset($_POST["assignDocReviewers"])) {
	// Retrieve the list of individual reviewers from the form.
	$reviewers["i"] = array();
	if (isset($_POST["indReviewers"])) {
		foreach ($_POST["indReviewers"] as $ind) {
			$reviewers["i"][] = $ind;
		}
	}
	// Retrieve the list of reviewer groups from the form.
	$reviewers["g"] = array();
	if (isset($_POST["grpReviewers"])) {
		foreach ($_POST["grpReviewers"] as $grp) {
			$reviewers["g"][] = $grp;
		}
	}
}
if (isset($_POST["assignDocApprovers"])) {
	// Retrieve the list of individual approvers from the form.
	$approvers["i"] = array();
	if (isset($_POST["indApprovers"])) {
		foreach ($_POST["indApprovers"] as $ind) {
			$approvers["i"][] = $ind;
		}
	}
	// Retrieve the list of approver groups from the form.
	$approvers["g"] = array();
	if (isset($_POST["grpApprovers"])) {
		foreach ($_POST["grpApprovers"] as $grp) {
			$approvers["g"][] = $grp;
		}
	}
}

$res = $folder->addDocument($name, $comment, $expires, $user, $keywords,
                                $userfiletmp, basename($userfilename),
                                $fileType, $userfiletype, $sequence,
                                $reviewers, $approvers);
                                
if (is_bool($res) && !$res) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_occured"));
}

header("Location:../out/out.ViewFolder.php?folderid=".$folderid);


?>
