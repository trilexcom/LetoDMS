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
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

//Neuen Benutzer anlegen --------------------------------------------------------------------------
if ($_POST["action"] == "adduser") {
	
	$login   = sanitizeString($_POST["login"]);
	$name    = sanitizeString($_POST["name"]);
	$email   = sanitizeString($_POST["email"]);
	$comment = sanitizeString($_POST["comment"]);
	$isAdmin = (isset($_POST["isadmin"]) && $_POST["isadmin"]==1 ? 1 : 0);

	if (is_object(getUserByLogin($login))) {
		UI::exitError(getMLText("admin_tools"),getMLText("user_exists"));
	}

	$newUser = addUser($login, md5($_POST["pwd"]), $name, $email, $settings->_language, $settings->_theme, $comment, $isAdmin);
	if ($newUser) {

		if (is_uploaded_file($_FILES["userfile"]["tmp_name"]) && $_FILES["userfile"]["size"] > 0 && $_FILES['userfile']['error']==0)
		{
			$userfiletype = sanitizeString($_FILES["userfile"]["type"]);
			$userfilename = sanitizeString($_FILES["userfile"]["name"]);
			$lastDotIndex = strrpos(basename($userfilename), ".");
			$fileType = substr($userfilename, $lastDotIndex);
			if ($fileType != ".jpg" && $filetype != ".jpeg")
			{
				UI::exitError(getMLText("admin_tools"),getMLText("only_jpg_user_images"));
			}
			else
			{
				resizeImage($_FILES["userfile"]["tmp_name"]);
				$newUser->setImage($_FILES["userfile"]["tmp_name"], $userfiletype);
			}
		}
	}
	else UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

//Benutzer löschen --------------------------------------------------------------------------------
else if (($_POST["action"] == "removeuser") || ($_GET["action"] == "removeuser")) {

	if (isset($_POST["userid"])) {
		$userid = $_POST["userid"];
	}
	else if (isset($_GET["userid"])) {
		$userid = $_GET["userid"];
	}

	if ($userid==$settings->_adminID) {
		UI::exitError(getMLText("admin_tools"),getMLText("cannot_delete_admin"));
	}
	if (!isset($userid) || !is_numeric($userid) || intval($userid)<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}

	$userToRemove = getUser($userid);
	if (!is_object($userToRemove)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}

	if (!$userToRemove->remove()) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
}

//Benutzer bearbeiten -----------------------------------------------------------------------------
else if ($_POST["action"] == "edituser") {

	if (!isset($_POST["userid"]) || !is_numeric($_POST["userid"]) || intval($_POST["userid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}
	$editedUser = getUser($_POST["userid"]);
	if (!is_object($editedUser)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}
	
	$login   = sanitizeString($_POST["login"]);
	$pwd     = $_POST["pwd"];
	$name    = sanitizeString($_POST["name"]);
	$email   = sanitizeString($_POST["email"]);
	$comment = sanitizeString($_POST["comment"]);
	$isAdmin = (isset($_POST["isadmin"]) && $_POST["isadmin"]==1 ? 1 : 0);
	
	if ($editedUser->getLogin() != $login)
		$editedUser->setLogin($login);
	if (isset($pwd) && ($pwd != ""))
		$editedUser->setPwd(md5($pwd));
	if ($editedUser->getFullName() != $name)
		$editedUser->setFullName($name);
	if ($editedUser->getEmail() != $email)
		$editedUser->setEmail($email);
	if ($editedUser->getComment() != $comment)
		$editedUser->setComment($comment);
	if ($editedUser->isAdmin() != $isAdmin && $editedUser->getID()!=$settings->_adminID)
		$editedUser->setAdmin($isAdmin);

	if (is_uploaded_file($_FILES["userfile"]["tmp_name"]) && $_FILES["userfile"]["size"] > 0 && $_FILES['userfile']['error']==0)
	{
		$userfiletype = sanitizeString($_FILES["userfile"]["type"]);
		$userfilename = sanitizeString($_FILES["userfile"]["name"]);
		$lastDotIndex = strrpos(basename($userfilename), ".");
		$fileType = substr($userfilename, $lastDotIndex);
		if ($fileType != ".jpg" && $filetype != ".jpeg") {
			UI::exitError(getMLText("admin_tools"),getMLText("only_jpg_user_images"));
		}
		else {
			resizeImage($_FILES["userfile"]["tmp_name"]);
			$editedUser->setImage($_FILES["userfile"]["tmp_name"], $userfiletype);
		}
	}
}
else UI::exitError(getMLText("admin_tools"),getMLText("unknown_command"));


function resizeImage($imageFile) {
	// Not perfect. Creates a new image even if the old one is acceptable,
	// and the output quality is low. Now uses the function imagecreatetruecolor(),
	// though, so at least the pictures are in colour.
	
	// Originalbild einlesen
	$origImg = imagecreatefromjpeg($imageFile);
	$width = imagesx($origImg);
	$height = imagesy($origImg);
	// Thumbnail im Speicher erzeugen
	$newHeight = 150;
	$newWidth = ($width/$height) * $newHeight;
	$newImg = imagecreatetruecolor($newWidth, $newHeight);
	// Verkleinern
	imagecopyresized($newImg, $origImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
	// In File speichern 
	imagejpeg($newImg, $imageFile);
	// Aufräumen
	imagedestroy($origImg);
	imagedestroy($newImg);
	
	return true;
}

header("Location:../out/out.UsrMgr.php");

?>
