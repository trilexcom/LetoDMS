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
include("../inc/inc.ClassAccess.php");
include("../inc/inc.ClassDMS.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_GET["documentid"];
$document = $dms->getDocument($documentid);

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

if (isset($_GET["groupid"])&&$_GET["groupid"]!=-1){
	$group=$dms->getGroup($groupid);
	if (!$group->isMember($user,true) && !$user->isAdmin())
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

$folder = $document->getFolder();
$docPathHTML = $folder->getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

if ($document->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

//Benachrichtigung löschen ------------------------------------------------------------------------
if ($action == "delnotify"){
	if (isset($userid)) {
		if($res = $document->removeNotify($userid, true)) {
			$obj = $dms->getUser($userid);
		}
	}
	else if (isset($groupid)) {
		if($res = $document->removeNotify($groupid, false)) {
			$obj = $dms->getGroup($groupid);
		}
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
			// Email user / group, informing them of subscription change.
			if($notifier) {
				$path="";
				$folder = $document->getFolder();
				$folderPath = $folder->getPath();
				for ($i = 0; $i  < count($folderPath); $i++) {
					$path .= $folderPath[$i]->getName();
					if ($i +1 < count($folderPath))
						$path .= " / ";
				}
				$subject = "###SITENAME###: ".$document->getName()." - ".getMLText("notify_deleted_email");
				$message = getMLText("notify_deleted_email")."\r\n";
				$message .= 
					getMLText("document").": ".$document->getName()."\r\n".
					getMLText("folder").": ".$path."\r\n".
					getMLText("comment").": ".$document->getComment()."\r\n".
					"URL: ###URL_PREFIX###out/out.ViewDocument.php?documentid=".$document->_id."\r\n";

				$subject=mydmsDecodeString($subject);
				$message=mydmsDecodeString($message);
		
				if ($isUser) {
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
				// Email user / group, informing them of subscription.
				if ($notifier){
					$path="";
					$folder = $document->getFolder();
					$folderPath = $folder->getPath();
					for ($i = 0; $i  < count($folderPath); $i++) {
						$path .= $folderPath[$i]->getName();
						if ($i +1 < count($folderPath))
							$path .= " / ";
					}
					$obj = $dms->getUser($userid);
					$subject = "###SITENAME###: ".$document->getName()." - ".getMLText("notify_added_email");
					$message = getMLText("notify_added_email")."\r\n";
					$message .= 
						getMLText("document").": ".$document->getName()."\r\n".
						getMLText("folder").": ".$path."\r\n".
						getMLText("comment").": ".$document->getComment()."\r\n".
						"URL: ###URL_PREFIX###out/out.ViewDocument.php?documentid=".$document->_id."\r\n";

					$subject=mydmsDecodeString($subject);
					$message=mydmsDecodeString($message);
					
					$notifier->toIndividual($user, $obj, $subject, $message);
				}

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
				if ($notifier){
					$path="";
					$folder = $document->getFolder();
					$folderPath = $folder->getPath();
					for ($i = 0; $i  < count($folderPath); $i++) {
						$path .= $folderPath[$i]->getName();
						if ($i +1 < count($folderPath))
							$path .= " / ";
					}
					$obj = $dms->getGroup($groupid);
					$subject = "###SITENAME###: ".$document->getName()." - ".getMLText("notify_added_email");
					$message = getMLText("notify_added_email")."\r\n";
					$message .= 
						getMLText("document").": ".$document->getName()."\r\n".
						getMLText("folder").": ".$path."\r\n".
						getMLText("comment").": ".$document->getComment()."\r\n".
						"URL: ###URL_PREFIX###out/out.ViewDocument.php?documentid=".$document->_id."\r\n";

					$subject=mydmsDecodeString($subject);
					$message=mydmsDecodeString($message);
					
					$notifier->toGroup($user, $obj, $subject, $message);
				}
				break;
		}
	}

}

header("Location:../out/out.DocumentNotify.php?documentid=".$documentid);

?>
