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
include("../inc/inc.ClassDMS.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.Authentication.php");

if (!isset($_POST["folderid"]) || !is_numeric($_POST["folderid"]) || intval($_POST["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderid = $_POST["folderid"];
$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

if ($folder->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));	
}

$name    = sanitizeString($_POST["name"]);
$comment = sanitizeString($_POST["comment"]);
$sequence = $_POST["sequence"];
if (!is_numeric($sequence)) {
	$sequence = "keep";
}

$wasupdated = false;
if(($oldname = $folder->getName()) != $name) {
	if($folder->setName($name)) {
		// Send notification to subscribers.
		if($notifier) {
			$folder->getNotifyList();
			$subject = "###SITENAME###: ".$folder->_name." - ".getMLText("folder_renamed_email");
			$message = getMLText("folder_renamed_email")."\r\n";
			$message .= 
				getMLText("old").": ".$oldname."\r\n".
				getMLText("new").": ".$folder->_name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				getMLText("comment").": ".$comment."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $folder->_notifyList["users"], $subject, $message);
			foreach ($folder->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	} else {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_occured"));	
	}
}
if(($oldcomment = $folder->getComment()) != $comment) {
	if($folder->setComment($comment)) {
		// Send notification to subscribers.
		if($notifier) {
			$folder->getNotifyList();
			$subject = "###SITENAME###: ".$folder->_name." - ".getMLText("comment_changed_email");
			$message = getMLText("comment_changed_email")."\r\n";
			$message .= 
				getMLText("name").": ".$folder->_name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				getMLText("comment").": ".$comment."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $folder->_notifyList["users"], $subject, $message);
			foreach ($folder->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	} else {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_occured"));	
	}
}
if(strcasecmp($sequence, "keep")) {
	if($folder->setSequence($sequence)) {
	} else {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_occured"));	
	}
}

add_log_line("?folderid=".$folderid);

header("Location:../out/out.ViewFolder.php?folderid=".$folderid."&showtree=".$_POST["showtree"]);

?>
