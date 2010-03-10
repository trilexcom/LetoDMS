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
include("../inc/inc.Authentication.php");

$fullname = sanitizeString($_POST["fullname"]);
$email    = sanitizeString($_POST["email"]);
$comment  = sanitizeString($_POST["comment"]);

if (isset($_POST["pwd"]) && ($_POST["pwd"] != ""))
	$user->setPwd(md5($_POST["pwd"]));

if ($user->getFullName() != $fullname)
	$user->setFullName($fullname);

if ($user->getEmail() != $email)
	$user->setEmail($email);

if ($user->getComment() != $comment)
	$user->setComment($comment);

if (is_uploaded_file($_FILES["userfile"]["tmp_name"]) && $_FILES["userfile"]["size"] > 0 && $_FILES['userfile']['error']==0)
{
	$lastDotIndex = strrpos(basename($_FILES["userfile"]["name"]), ".");
	$fileType = substr($_FILES["userfile"]["name"], $lastDotIndex);
	if ($fileType != ".jpg" && $filetype != ".jpeg") {
		UI::exitError(getMLText("user_info"),getMLText("only_jpg_user_images"));
	}
	//verkleinern des Bildes, so dass es 150 Pixel hoch ist
	// Originalbild einlesen
	$origImg = imagecreatefromjpeg($_FILES["userfile"]["tmp_name"]);
	$width = imagesx($origImg);
	$height = imagesy($origImg);
	// Thumbnail im Speicher erzeugen
	$newHeight = 150;
	$newWidth = ($width/$height) * $newHeight;
	$newImg = imagecreatetruecolor($newWidth, $newHeight);
	// Verkleinern
	imagecopyresized($newImg, $origImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
	// In File speichern 
	imagejpeg($newImg, $_FILES["userfile"]["tmp_name"]);
	// Aufräumen
	imagedestroy($origImg);
	imagedestroy($newImg);
	$user->setImage($_FILES["userfile"]["tmp_name"], $_FILES["userfile"]["type"]);
}

header("Location:../out/out.MyAccount.php");

?>
