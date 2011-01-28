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
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderid = $_GET["folderid"];
$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

if (!isset($_GET["action"]) || (strcasecmp($_GET["action"], "delnotify") && strcasecmp($_GET["action"], "addnotify"))) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_action"));
}
$action = $_GET["action"];

if (isset($_GET["userid"]) && (!is_numeric($_GET["userid"]) || $_GET["userid"]<-1)) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
}
$userid = $_GET["userid"];

if (isset($_GET["groupid"]) && (!is_numeric($_GET["groupid"]) || $_GET["groupid"]<-1)) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_group"));
}
$groupid = $_GET["groupid"];

if (isset($_GET["groupid"])&&$_GET["groupid"]!=-1){
	$group=$dms->getGroup($groupid);
	if (!$group->isMember($user,true) && !$user->isAdmin())
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

if ($folder->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

//Benachrichtigung löschen ------------------------------------------------------------------------
if ($action == "delnotify") {

	if ($userid > 0) {
		$res = $folder->removeNotify($userid, true);
		$obj = $dms->getUser($userid);
	}
	elseif ($groupid > 0) {
		$res = $folder->removeNotify($groupid, false);
		$obj = $dms->getGroup($groupid);
	}
	switch ($res) {
		case -1:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),isset($userid) ? getMLText("unknown_user") : getMLText("unknown_group"));
			break;
		case -2:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
			break;
		case -3:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("already_subscribed"));
			break;
		case -4:
			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("internal_error"));
			break;
		case 0:
			if($notifier) {
				// Email user / group, informing them of subscription.
				$path="";
				$folderPath = $folder->getPath();
				for ($i = 0; $i  < count($folderPath); $i++) {
					$path .= $folderPath[$i]->getName();
					if ($i +1 < count($folderPath))
						$path .= " / ";
				}

				$subject = "###SITENAME###: ".$folder->getName()." - ".getMLText("notify_deleted_email");
				$message = getMLText("notify_deleted_email")."\r\n";
				$message .= 
					getMLText("name").": ".$folder->getName()."\r\n".
					getMLText("folder").": ".$path."\r\n".
					getMLText("comment").": ".$folder->getComment()."\r\n".
					"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

				$subject=mydmsDecodeString($subject);
				$message=mydmsDecodeString($message);
				
				if ($userid > 0) {
					$notifier->toIndividual($user, $obj, $subject, $message);
				}
				else {
					$notifier->toGroup($user, $obj, $subject, $message);
				}
			}
			break;
	}
}

//Benachrichtigung hinzufügen ---------------------------------------------------------------------
else if ($action == "addnotify") {

	if ($userid != -1) {
		$res = $folder->addNotify($userid, true);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_user"));
				break;
			case -2:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("internal_error"));
				break;
			case 0:
				$obj = $dms->getUser($userid);
				// Email user / group, informing them of subscription.
				$path="";
				$folderPath = $folder->getPath();
				for ($i = 0; $i  < count($folderPath); $i++) {
					$path .= $folderPath[$i]->getName();
					if ($i +1 < count($folderPath))
						$path .= " / ";
				}
				if($notifier) {
					$subject = "###SITENAME###: ".$folder->getName()." - ".getMLText("notify_added_email");
					$message = getMLText("notify_added_email")."\r\n";
					$message .= 
						getMLText("name").": ".$folder->getName()."\r\n".
						getMLText("folder").": ".$path."\r\n".
						getMLText("comment").": ".$folder->getComment()."\r\n".
						"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

					$subject=mydmsDecodeString($subject);
					$message=mydmsDecodeString($message);
					
					$notifier->toIndividual($user, $obj, $subject, $message);
				}

				break;
		}
	}
	if ($groupid != -1) {
		$res = $folder->addNotify($groupid, false);
		switch ($res) {
			case -1:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("unknown_group"));
				break;
			case -2:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
				break;
			case -3:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("already_subscribed"));
				break;
			case -4:
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("internal_error"));
				break;
			case 0:
				$obj = $dms->getGroup($groupid);
				// Email user / group, informing them of subscription.
				$path="";
				$folderPath = $folder->getPath();
				for ($i = 0; $i  < count($folderPath); $i++) {
					$path .= $folderPath[$i]->getName();
					if ($i +1 < count($folderPath))
						$path .= " / ";
				}
				if($notifier) {
					$subject = "###SITENAME###: ".$folder->getName()." - ".getMLText("notify_added_email");
					$message = getMLText("notify_added_email")."\r\n";
					$message .= 
						getMLText("name").": ".$folder->getName()."\r\n".
						getMLText("folder").": ".$path."\r\n".
						getMLText("comment").": ".$folder->getComment()."\r\n".
						"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

					$subject=mydmsDecodeString($subject);
					$message=mydmsDecodeString($message);
					
					$notifier->toGroup($user, $obj, $subject, $message);
				}
				break;
		}
	}
}
	
header("Location:../out/out.FolderNotify.php?folderid=".$folderid);

?>
