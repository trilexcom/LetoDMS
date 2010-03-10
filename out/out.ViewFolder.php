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

if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
	$folderid = $settings->_rootFolderID;
}
else {
	$folderid = $_GET["folderid"];
}
$folder = getFolder($folderid);
if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderPathHTML = getFolderPathHTML($folder);

if ($folder->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("folder_title", array("foldername" => $folder->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($folderPathHTML, "view_folder", $folder);
UI::contentHeading(getMLText("folder_infos"));

$owner = $folder->getOwner();
UI::contentContainer("<table>\n<tr>\n".
			"<td>".getMLText("owner").":</td>\n".
			"<td><a class=\"infos\" href=\"mailto:".$owner->getEmail()."\">".$owner->getFullName()."</a>".
			"</td>\n</tr>\n<tr>\n".
			"<td>".getMLText("comment").":</td>\n".
			"<td>".$folder->getComment()."</td>\n</tr>\n</table>\n");

UI::contentHeading(getMLText("folder_contents"));
UI::contentContainerStart();

$subFolders = $folder->getSubFolders();
$subFolders = filterAccess($subFolders, $user, M_READ);
$documents = array();
$documentVersion = array();
$documentStatus = array();
$documentFileType = array();
$documentMimeType = array();

if ($db->createTemporaryTable("ttcontentid") && $db->createTemporaryTable("ttstatid")) {
	$queryStr = "SELECT `tblDocuments`.*, `tblDocumentContent`.`version`, `tblDocumentContent`.`fileType`, `tblDocumentContent`.`mimeType`, `tblDocumentStatusLog`.`status`, `tblDocumentLocks`.`userID` as `lockUser` ".
		"FROM `tblDocumentContent` ".
		"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
		"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
		"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusID` = `tblDocumentStatus`.`statusID` ".
		"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
		"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
		"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
		"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
		"AND `ttcontentid`.`maxVersion` = `tblDocumentContent`.`version` ".
		"AND `tblDocuments`.`folder` = '".$folderid."' ".
		"ORDER BY `tblDocuments`.`sequence`";
	$resArr = $db->getResultArray($queryStr);	
	if (!is_bool($resArr)) {
		foreach ($resArr as $res) {
		
			// verify expiry
			if ( $res["expires"] && time()>$res["expires"]+24*60*60 ){
				if  ( $res["status"]==S_DRAFT_APP || $res["status"]==S_DRAFT_REV ){
					$res["status"]=S_EXPIRED;
				}
			}
		
			$documents[] = new Document($res["id"], $res["name"], $res["comment"], $res["date"], $res["expires"], $res["owner"], $res["folder"], $res["inheritAccess"], $res["defaultAccess"], $res["lockUser"], $res["keywords"], $res["sequence"]);
			$documentVersion[$res["id"]] = $res["version"];
			$documentStatus[$res["id"]] = $res["status"];
			$documentFileType[$res["id"]] = $res["fileType"];
			$documentMimeType[$res["id"]] = $res["mimeType"];
		}
	}
	//$documents = $folder->getDocuments();
	$documents = filterAccess($documents, $user, M_READ);
}

if ((count($subFolders) > 0)||(count($documents) > 0)){
	print "<table class=\"folderView\">";
	print "<thead>\n<tr>\n";
	print "<th></th>\n";
	print "<th>".getMLText("name")."</th>\n";
	print "<th>".getMLText("owner")."</th>\n";
	print "<th>".getMLText("status")."</th>\n";
	print "<th>".getMLText("version")."</th>\n";
	print "<th>".getMLText("reviewers")."</th>\n";
	print "<th>".getMLText("approvers")."</th>\n";
	print "</tr>\n</thead>\n<tbody>\n";
}
else printMLText("empty_notify_list");

if (count($subFolders) > 0) {
	foreach($subFolders as $subFolder) {
		$owner = $subFolder->getOwner();
		$comment = $subFolder->getComment();
		if (strlen($comment) > 25) $comment = substr($comment, 0, 22) . "...";
		print "<tr class=\"folder\">";
		print "<td><img src=\"images/folder_closed.gif\" width=18 height=18 border=0></td>";
		print "<td><a href=\"out.ViewFolder.php?folderid=".$subFolder->getID()."\">" . $subFolder->getName() . "</a></td>\n";
		print "<td>".$owner->getFullName()."</td>";
		print "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
		print "</tr>";
	}
}

if (count($documents) > 0) {

	foreach($documents as $document) {
	
		$owner = $document->getOwner();
		$comment = $document->getComment();
		$docID = $document->getID();
		$version = $documentVersion[$docID];
		// Retrieve the content object. Only need enough of the
		// DocumentContent object to be able to retrieve the list of
		// reviewers and approvers.
		$content = new DocumentContent($docID, $version, "", "", "", "", "", "", "");
		$rstat = $content->getReviewStatus();
		$astat = $content->getApprovalStatus();
		if (strlen($comment) > 25) $comment = substr($comment, 0, 22) . "...";
		print "<tr>";
		print "<td><a href=\"../op/op.Download.php?documentid=".$docID."&version=".$version."\"><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($documentFileType[$docID])."\" title=\"".$documentMimeType[$docID]."\"></a></td>";
		print "<td><a href=\"out.ViewDocument.php?documentid=".$docID."\">" . $document->getName() . "</a></td>\n";
		print "<td>".$owner->getFullName()."</td>";
		print "<td>".getOverallStatusText($documentStatus[$docID])."</td>";
		print "<td>".$version."</td>";
		print "<td>";
		if (count($rstat)>0) {
			print "<ul class=\"reviewer\">";
			$liFlag=false;
			foreach ($rstat as $r) {
				if ($r["status"]!=-2) {
					print "<li".(!$liFlag ? " class=\"first\"" : "").">";
					$required = null;
					switch ($r["type"]) {
						case 0: // Reviewer is an individual.
							if (strlen($r["fullName"])==0) {
								$reqName = getMLText("unknown_user").$r["required"];
							}
							else {
								$reqName = $r["fullName"];
							}
							break;
						case 1: // Reviewer is a group.
							if (strlen($r["groupName"])==0) {
								$reqName = getMLText("unknown_group").$r["required"];
							}
							else {
								$reqName = "<i>".$r["groupName"]."</i>";
							}
							break;
					}
					print "<b>".$reqName."</b>: ";
					printReviewStatusText($r["status"], $r["date"]);
					print "</li>";
					$liFlag=true;
				}
			}
			print "</ul>";
		}
		else {
			print "-";
		}
		print "</td><td>";
		if (count($astat)>0) {
			print "<ul class=\"reviewer\">";
			$liFlag=false;
			foreach ($astat as $a) {
				if ($a["status"]!=-2) {
					print "<li".(!$liFlag ? " class=\"first\"" : "").">";
					$required = null;
					switch ($a["type"]) {
						case 0: // Approver is an individual.
							if (strlen($a["fullName"])==0) {
								$reqName = getMLText("unknown_user").$a["required"];
							}
							else {
								$reqName = $a["fullName"];
							}
							break;
						case 1: // Approver is a group.
							if (strlen($a["groupName"])==0) {
								$reqName = getMLText("unknown_group").$a["required"];
							}
							else {
								$reqName = "<i>".$a["groupName"]."</i>";
							}
							break;
					}
					print "<b>".$reqName."</b>: ";
					printApprovalStatusText($a["status"], $a["date"]);
					print "</li>";
					$liFlag=true;
				}
			}
			print "</ul>";
		}
		else {
			print "-";
		}
		print "</td></tr>";
	}
}

?>
<?php

if ((count($subFolders) > 0)||(count($documents) > 0)) echo "</tbody>\n</table>\n";

UI::contentContainerEnd();
UI::htmlEndPage();
?>
