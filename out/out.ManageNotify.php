<?php
//    MyDMS. Document Management System
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

if ($user->getID($user) == $settings->_guestID) {
	UI::exitError(getMLText("my_account"),getMLText("access_denied"));
}

// Get list of subscriptions for documents or folders for user or groups
function getNotificationList($as_group,$folders)
{
	global $user,$db;

	// First, get the list of groups of which the user is a member.
	if ($as_group){
	
		$groups = $user->getGroups();
		
		if (count($groups)==0) return NULL;
		
		$grpList = "";
		foreach ($groups as $group) {
			$grpList .= (strlen($grpList)==0 ? "" : ", ") . $group->getID();
		}
		
		$queryStr = "SELECT `tblNotify`.* FROM `tblNotify` ".
		 "WHERE `tblNotify`.`groupID` IN (". $grpList .")";
		 		
	}else{
		$queryStr = "SELECT `tblNotify`.* FROM `tblNotify` ".
			"WHERE `tblNotify`.`userID` = '". $user->getID()."'" ;
	}
	
	$resArr = $db->getResultArray($queryStr);
	
	$ret=array();
		
	foreach ($resArr as $res){
		
		if (($res["targetType"] == T_DOCUMENT)&&(!$folders)) $ret[]=$res["target"];
		if (($res["targetType"] == T_FOLDER)&&($folders)) $ret[]=$res["target"];
	}
	
	return $ret;
}

function printFolderNotificationList($ret,$deleteaction=true)
{
	if (count($ret)==0) {
		printMLText("empty_notify_list");
	}
	else {

		print "<table class=\"folderView\">";
		print "<thead><tr>\n";
		print "<th></th>\n";
		print "<th>".getMLText("name")."</th>\n";
		print "<th>".getMLText("owner")."</th>\n";
		print "<th>".getMLText("actions")."</th>\n";
		print "</tr></thead>\n<tbody>\n";
		foreach($ret as $ID) {
			$fld = getFolder($ID);
			if (is_object($fld)) {
				$owner = $fld->getOwner();
				print "<tr class=\"folder\">";
				print "<td><img src=\"images/folder_closed.gif\" width=18 height=18 border=0></td>";
				print "<td><a href=\"../out/out.ViewFolder.php?folderid=".$ID."\">" . $fld->getName() . "</a></td>\n";
				print "<td>".$owner->getFullName()."</td>";
				print "<td><ul class=\"actions\">";
				if ($deleteaction) print "<li><a href='../op/op.ManageNotify.php?id=".$ID."&type=folder&action=del'>".getMLText("delete")."</a>";
				else print "<li><a href='../out/out.FolderNotify.php?folderid=".$ID."'>".getMLText("edit")."</a>";
				print "</ul></td></tr>";
			}
		}
		print "</tbody></table>";
	}
}

function printDocumentNotificationList($ret,$deleteaction=true)
{
	if (count($ret)==0) {
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
		print "</tr></thead>\n<tbody>\n";
		foreach ($ret as $ID) {
			$doc = getDocument($ID);
			if (is_object($doc)) {
				$owner = $doc->getOwner();
				$latest = $doc->getLatestContent();
				$status = $latest->getStatus();
				print "<tr>\n";
				print "<td><img src=\"images/file.gif\" width=18 height=18 border=0></td>";
				print "<td><a href=\"../out/out.ViewDocument.php?documentid=".$ID."\">" . $doc->getName() . "</a></td>\n";
				print "<td>".$owner->getFullName()."</td>";
				print "<td>".getOverallStatusText($status["status"])."</td>";
				print "<td class=\"center\">".$latest->getVersion()."</td>";
				print "<td><ul class=\"actions\">";
				if ($deleteaction) print "<li><a href='../op/op.ManageNotify.php?id=".$ID."&type=document&action=del'>".getMLText("delete")."</a>";
				else print "<li><a href='../out/out.DocumentNotify.php?documentid=".$ID."'>".getMLText("edit")."</a>";
				print "</ul></td></tr>\n";
			}
		}
		print "</tbody></table>";
	}
}

UI::htmlStartPage(getMLText("my_account"));
UI::globalNavigation();
UI::pageNavigation(getMLText("my_account"), "my_account");

UI::contentHeading(getMLText("edit_existing_notify"));
UI::contentContainerStart();

print "<form method=POST action=\"../op/op.ManageNotify.php?type=folder&action=add\" name=\"form1\">";
UI::contentSubHeading(getMLText("choose_target_folder"));
UI::printFolderChooser("form1",M_READ);
print "<input type=\"checkbox\" name=\"recursefolder\" value=\"1\">";
print getMLText("include_subdirectories");
print "<input type=\"checkbox\" name=\"recursedoc\" value=\"1\">";
print getMLText("include_documents");
print "&nbsp;&nbsp;<input type='submit' name='' value='".getMLText("add")."'/>";
print "</form>";

print "<form method=POST action=\"../op/op.ManageNotify.php?type=document&action=add\" name=\"form2\">";
UI::contentSubHeading(getMLText("choose_target_document"));
UI::printDocumentChooser("form2");
print "&nbsp;&nbsp;<input type=\"Submit\" value=\"".getMLText("add")."\">";
print "</form>";

UI::contentContainerEnd();


//
// Display the results.
//
UI::contentHeading(getMLText("edit_folder_notify"));
UI::contentContainerStart();
UI::contentSubHeading(getMLText("user"));
$ret=getNotificationList(false,true);
printFolderNotificationList($ret);
UI::contentSubHeading(getMLText("group"));
$ret=getNotificationList(true,true);
printFolderNotificationList($ret,false);
UI::contentContainerEnd();

UI::contentHeading(getMLText("edit_document_notify"));
UI::contentContainerStart();
UI::contentSubHeading(getMLText("user"));
$ret=getNotificationList(false,false);
printDocumentNotificationList($ret);
UI::contentSubHeading(getMLText("group"));
$ret=getNotificationList(true,false);
printDocumentNotificationList($ret,false);
UI::contentContainerEnd();

UI::htmlEndPage();
?>
