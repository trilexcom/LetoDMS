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

function removeFolderFiles($folder)
{
	global $settings;

	$documents = $folder->getDocuments();
	foreach ($documents as $document){
	
		// TODO :controllare $document->getDir()
		
		if (!removeDir($settings->_contentDir . $document->getDir()))
			return false;
	}

	$subFolders = $folder->getSubFolders();
	
	foreach ($subFolders as $folder)
		if (!removeFolderFiles($folder))
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

if (!removeFolderFiles($folder)) {
	UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
}

header("Location:../out/out.BackupTools.php");

?>
