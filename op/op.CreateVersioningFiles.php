<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

function createVersionigFile($document)
{
	global $settings;
	
	// if directory has been removed recreate it
	if (!file_exists($settings->_contentDir . $document->getDir())) 
		if (!makeDir($settings->_contentDir . $document->getDir())) return false;
	
	$handle = fopen($settings->_contentDir . $document->getDir() .$settings-> _versioningFileName , "wb");
	
	if (is_bool($handle)&&!$handle) return false;
	
	$tmp = mydmsDecodeString($document->getName())." (ID ".$document->getID().")\n\n";
	fwrite($handle, $tmp);

	$owner = $document->getOwner();
	$tmp = getMLText("owner")." = ".$owner->getFullName()." <".$owner->getEmail().">\n";
	fwrite($handle, $tmp);
	
	$tmp = getMLText("creation_date")." = ".getLongReadableDate($document->getDate())."\n";
	fwrite($handle, $tmp);
	
	$latestContent = $document->getLatestContent();
	$tmp = "\n### ".getMLText("current_version")." ###\n\n";
	fwrite($handle, $tmp);
	
	$tmp = getMLText("version")." = ".$latestContent->getVersion()."\n";
	fwrite($handle, $tmp);	
	
	$tmp = getMLText("file")." = ".$latestContent->getOriginalFileName()." (".$latestContent->getMimeType().")\n";
	fwrite($handle, $tmp);
	
	$tmp = getMLText("comment")." = ". mydmsDecodeString($latestContent->getComment())."\n";
	fwrite($handle, $tmp);
	
	$status = $latestContent->getStatus();
	$tmp = getMLText("status")." = ".getOverallStatusText($status["status"])."\n";
	fwrite($handle, $tmp);
	
	$reviewStatus = $latestContent->getReviewStatus();
	$tmp = "\n### ".getMLText("reviewers")." ###\n";
	fwrite($handle, $tmp);
	
	foreach ($reviewStatus as $r) {
	
		switch ($r["type"]) {
			case 0: // Reviewer is an individual.
				$required = getUser($r["required"]);
				if (!is_object($required)) $reqName = getMLText("unknown_user")." = ".$r["required"];
				else $reqName =  getMLText("user")." = ".$required->getFullName();
				break;
			case 1: // Reviewer is a group.
				$required = getGroup($r["required"]);
				if (!is_object($required)) $reqName = getMLText("unknown_group")." = ".$r["required"];
				else $reqName = getMLText("group")." = ".$required->getName();
				break;
		}

		$tmp = "\n".$reqName."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("status")." = ".getReviewStatusText($r["status"])."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("comment")." = ". mydmsDecodeString($r["comment"])."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("last_update")." = ".$r["date"]."\n";
		fwrite($handle, $tmp);

	}
	
	
	$approvalStatus = $latestContent->getApprovalStatus();
	$tmp = "\n### ".getMLText("approvers")."###\n";
	fwrite($handle, $tmp);

	foreach ($approvalStatus as $r) {
	
		switch ($r["type"]) {
			case 0: // Reviewer is an individual.
				$required = getUser($r["required"]);
				if (!is_object($required)) $reqName = getMLText("unknown_user")." = ".$r["required"];
				else $reqName =  getMLText("user")." = ".$required->getFullName();
				break;
			case 1: // Reviewer is a group.
				$required = getGroup($r["required"]);
				if (!is_object($required)) $reqName = getMLText("unknown_group")." = ".$r["required"];
				else $reqName = getMLText("group")." = ".$required->getName();
				break;
		}

		$tmp = "\n".$reqName."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("status")." = ".getReviewStatusText($r["status"])."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("comment")." = ". mydmsDecodeString($r["comment"])."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("last_update")." = ".$r["date"]."\n";
		fwrite($handle, $tmp);
	
	}

	$versions = $document->getContent();	
	$tmp = "\n### ".getMLText("previous_versions")."###\n";
	fwrite($handle, $tmp);

	for ($i = count($versions)-2; $i >= 0; $i--){
	
		$version = $versions[$i];
		$status = $version->getStatus();		
		
		$tmp = "\n".getMLText("version")." = ".$version->getVersion()."\n";
		fwrite($handle, $tmp);	
		
		$tmp = getMLText("file")." = ".$version->getOriginalFileName()." (".$version->getMimeType().")\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("comment")." = ". mydmsDecodeString($version->getComment())."\n";
		fwrite($handle, $tmp);
		
		$status = $latestContent->getStatus();
		$tmp = getMLText("status")." = ".getOverallStatusText($status["status"])."\n";
		fwrite($handle, $tmp);
			
	}
	
	fclose($handle);
	return true;
}

function createVersionigFiles($folder)
{
	global $settings;

	//print_r($folder);

	$documents = $folder->getDocuments();
	foreach ($documents as $document)
		if (!createVersionigFile($document))
			return false;

	$subFolders = $folder->getSubFolders();
	
	foreach ($subFolders as $folder)
		if (!createVersionigFiles($folder))
			return false;

	return true;
}

if (!isset($_POST["folderid"]) || !is_numeric($_POST["folderid"]) || intval($_POST["folderid"])<1) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_folder_id"));
}
$folderid = $_POST["folderid"];
$folder = getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_folder_id"));
}

if (!createVersionigFiles($folder)) {
	UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
}

header("Location:../out/out.BackupTools.php");

?>
