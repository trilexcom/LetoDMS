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
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassDocument.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

UI::htmlStartPage(getMLText("my_documents"));
UI::globalNavigation();
UI::pageNavigation(getMLText("my_documents"), "my_documents");

//
// Get list of subscriptions for user.
//

// First, get the list of groups of which the user is a member.
$groups = $user->getGroups();
$grpList = "";
foreach ($groups as $group) {
	$grpList .= (strlen($grpList)==0 ? "" : ", ") . $group->getID();
}

// Now send the main query.
$queryStr = "(SELECT `tblNotify`.* FROM `tblNotify` ".
	"WHERE `tblNotify`.`userID` = '". $user->getID() ."') ".
	(strlen($grpList) == 0 ? "" : "UNION (SELECT `tblNotify`.* FROM `tblNotify` ".
	 "WHERE `tblNotify`.`groupID` IN (". $grpList ."))");
$resArr = $db->getResultArray($queryStr);

// Parse the results, creating arrays to contain the document and folder IDs.
$docArr = array();
$fldArr = array();

foreach ($resArr as $res) {
	if ($res["targetType"] == T_DOCUMENT && !in_array($res["target"], $docArr)) {
		$docArr[] = $res["target"];
	}
	else if ($res["targetType"] == T_FOLDER && !in_array($res["target"], $fldArr)) {
		$fldArr[] = $res["target"];
	}
}

//
// Display the results.
//
UI::contentHeading(getMLText("edit_folder_notify"));
UI::contentContainerStart();
if (count($fldArr)==0) {
	printMLText("empty_notify_list");
}
else {
	print "<table class=\"folderView\">";
	print "<thead>\n<tr>\n";
	print "<th></th>\n";
	print "<th>".getMLText("name")."</th>\n";
	print "<th>".getMLText("owner")."</th>\n";
	print "<th>".getMLText("actions")."</th>\n";
	foreach($fldArr as $fldID) {
		$fld = getFolder($fldID);
		if (is_object($fld)) {
			$owner = $fld->getOwner();
			print "<tr class=\"folder\">";
			print "<td><img src=\"images/folder_closed.gif\" width=18 height=18 border=0></td>";
			print "<td><a href=\"../out/out.ViewFolder.php?folderid=".$fldID."\">" . $fld->getName() . "</a></td>\n";
			print "<td>".$owner->getFullName()."</td>";
			print "<td>";
			print "<a href='../out/out.FolderNotify.php?folderid=".$fldID."'>".getMLText("edit")."</a>";
			print "</td></tr>";
		}
	}
	print "</tbody></table>";
}

UI::contentContainerEnd();

UI::contentHeading(getMLText("edit_document_notify"));
UI::contentContainerStart();

if (count($docArr)==0) {
	printMLText("empty_notify_list");
}
else {
	print "<table class=\"folderView\">";
	print "<thead>\n<tr>\n";
	print "<th></th>\n";
	print "<th>".getMLText("name")."</th>\n";
	print "<th>".getMLText("owner")."</th>\n";
	print "<th>".getMLText("status")."</th>\n";
	print "<th>".getMLText("version")."</th>\n";
	print "<th>".getMLText("actions")."</th>\n";
	print "</tr>\n</thead>\n<tbody>\n";
	foreach ($docArr as $docID) {
		$doc = getDocument($docID);
		if (is_object($doc)) {
			$owner = $doc->getOwner();
			$latest = $doc->getLatestContent();
			$status = $latest->getStatus();
			print "<tr>\n";
			print "<td><img src=\"images/file.gif\" width=18 height=18 border=0></td>";
			print "<td><a href=\"../out/out.ViewDocument.php?documentid=".$docID."\">" . $doc->getName() . "</a></td>\n";
			print "<td>".$owner->getFullName()."</td>";
			print "<td>".getOverallStatusText($status["status"])."</td>";
			print "<td class=\"center\">".$latest->getVersion()."</td>";
			print "<td>";
			print "<a href='../out/out.DocumentNotify.php?documentid=".$docID."'>".getMLText("edit")."</a>";
			print "</td></tr>\n";

		}
	}
	print "</tbody></table>";
}
UI::contentContainerEnd();
UI::htmlEndPage();
?>
