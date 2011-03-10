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

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$folder = $document->getFolder();
$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

if ($document->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

$name =     sanitizeString($_POST["name"]);
$comment =  sanitizeString($_POST["comment"]);
$keywords = sanitizeString($_POST["keywords"]);
$categories = sanitizeString($_POST["categoryidform1"]);
$sequence = $_POST["sequence"];
if (!is_numeric($sequence)) {
	$sequence="keep";
}

if (($oldname = $document->getName()) != $name) {
	if($document->setName($name)) {
		// Send notification to subscribers.
		$document->getNotifyList();
		if($notifier) {
			$folder = $document->getFolder();
			$subject = "###SITENAME###: ".$document->_name." - ".getMLText("document_renamed_email");
			$message = getMLText("document_renamed_email")."\r\n";
			$message .= 
				getMLText("old").": ".$oldname."\r\n".
				getMLText("new").": ".$name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				getMLText("comment").": ".$document->getComment()."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewDocument.php?documentid=".$document->getID()."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);

			$notifier->toList($user, $document->_notifyList["users"], $subject, $message);
			foreach ($document->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
			
			// if user is not owner send notification to owner
			if ($user->getID()!= $document->_ownerID) 
				$notifier->toIndividual($user, $document->getOwner(), $subject, $message);		
		}

	}
	else {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
	}
}

if (($oldcomment = $document->getComment()) != $comment) {
	if($document->setComment($comment)) {
		// Send notification to subscribers.
		$document->getNotifyList();
		if($notifier) {
			$folder = $document->getFolder();
			$subject = "###SITENAME###: ".$document->getName()." - ".getMLText("comment_changed_email");
			$message = getMLText("comment_changed_email")."\r\n";
			$message .= 
				getMLText("document").": ".$document->getName()."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				getMLText("comment").": ".$comment."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewDocument.php?documentid=".$document->getID()."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $document->_notifyList["users"], $subject, $message);
			foreach ($document->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}

			// if user is not owner send notification to owner
			if ($user->getID() != $document->getOwner()) 
				$notifier->toIndividual($user, $document->getOwner(), $subject, $message);		
		}
	}
	else {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
	}
}

if (($oldkeywords = $document->getKeywords()) != $keywords) {
	if($document->setKeywords($keywords)) {
	}
	else {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
	}
}

if($categories) {
	$categoriesarr = array();
	foreach(explode(',', $categories) as $catid) {
		if($cat = $dms->getDocumentCategory($catid)) {
			$categoriesarr[] = $cat;
		}
		
	}
	$oldcategories = $document->getCategories();
	$oldcatsids = array();
	foreach($oldcategories as $oldcategory)
		$oldcatsids[] = $oldcategory->getID();

	if (count($categoriesarr) != count($oldcategories) ||
			array_diff(explode(',', $categories), $oldcatsids)) {
		if($document->setCategories($categoriesarr)) {
		} else {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
		}
	}
}

if($sequence != "keep") {
 	if($document->setSequence($sequence)) {
	}
	else {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
	}
}

add_log_line("?documentid=".$documentid);

header("Location:../out/out.ViewDocument.php?documentid=".$documentid);

?>
