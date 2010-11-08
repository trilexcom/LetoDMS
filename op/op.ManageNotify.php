<?php
//    MyDMS. Document Management System
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
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.Authentication.php");

if ($user->getID() == $settings->_guestID) {
	UI::exitError(getMLText("my_account"),getMLText("access_denied"));
}

function add_folder_notify($folder,$userid,$recursefolder,$recursedoc)
{
	$folder->addNotify($userid, true);
	
	if ($recursedoc){
	
		// include all folder's document
		
		$documents = $folder->getDocuments();
		$documents = filterAccess($documents, getUser($userid), M_READ);

		foreach($documents as $document)
			$document->addNotify($userid, true);
	}
	
	if ($recursefolder){
	
		// recurse all folder's folders
		
		$subFolders = $folder->getSubFolders();
		$subFolders = filterAccess($subFolders, getUser($userid), M_READ);

		foreach($subFolders as $subFolder)
			add_folder_notify($subFolder,$userid,$recursefolder,$recursedoc);
	}
}

if (!isset($_GET["type"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
if (!isset($_GET["action"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));

if ($_GET["type"]=="document"){

	if ($_GET["action"]=="add"){
		if (!isset($_POST["docidform2"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$documentid = $_POST["docidform2"];
	}else if ($_GET["action"]=="del"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$documentid = $_GET["id"];
	
	}else UI::exitError(getMLText("my_account"),getMLText("error_occured"));
	
	$document = getDocument($documentid);
	
	$userid=$user->getID();
	
	if ($document->getAccessMode($user) < M_READ) 
		UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if ($_GET["action"]=="add") $document->addNotify($userid, true);
	else if ($_GET["action"]=="del") $document->removeNotify($userid, true);
	
}else if ($_GET["type"]=="folder"){

	if ($_GET["action"]=="add"){
		if (!isset($_POST["targetidform1"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$folderid = $_POST["targetidform1"];
	}else if ($_GET["action"]=="del"){
		if (!isset($_GET["id"])) UI::exitError(getMLText("my_account"),getMLText("error_occured"));
		$folderid = $_GET["id"];
	
	}else UI::exitError(getMLText("my_account"),getMLText("error_occured"));
	
	$folder = getFolder($folderid);
	
	$userid=$user->getID();
	
	if ($folder->getAccessMode($user) < M_READ) 
		UI::exitError(getMLText("my_account"),getMLText("error_occured"));

	if ($_GET["action"]=="add"){
	
		$recursefolder = isset($_POST["recursefolder"]);
		$recursedoc = isset($_POST["recursedoc"]);
	
		add_folder_notify($folder,$userid,$recursefolder,$recursedoc);
		
	}else if ($_GET["action"]=="del") $folder->removeNotify($userid, true);
}
	
header("Location:../out/out.ManageNotify.php");

?>
