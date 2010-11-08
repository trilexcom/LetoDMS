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
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if ($user->getID() == $settings->_guestID) {
	UI::exitError(getMLText("my_documents"),getMLText("access_denied"));
}

if (!$db->createTemporaryTable("ttstatid")) {
	UI::exitError(getMLText("approval_summary"),getMLText("internal_error_exit"));
}

UI::htmlStartPage(getMLText("approval_summary"));
UI::globalNavigation();
UI::pageNavigation(getMLText("my_documents"), "my_documents");
UI::contentHeading(getMLText("approval_summary"));
UI::contentContainerStart();

// TODO: verificare scadenza

// Get document list for the current user.
$approvalStatus = $user->getApprovalStatus();

// reverse order
$approvalStatus["indstatus"]=array_reverse($approvalStatus["indstatus"],true);
$approvalStatus["grpstatus"]=array_reverse($approvalStatus["grpstatus"],true);

// Create a comma separated list of all the documentIDs whose information is
// required.
$dList = array();
foreach ($approvalStatus["indstatus"] as $st) {
	if (!in_array($st["documentID"], $dList)) {
		$dList[] = $st["documentID"];
	}
}
foreach ($approvalStatus["grpstatus"] as $st) {
	if (!in_array($st["documentID"], $dList)) {
		$dList[] = $st["documentID"];
	}
}
$docCSV = "";
foreach ($dList as $d) {
	$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
}

if (strlen($docCSV)>0) {

	$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser`, ".
		"`tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
		"`tblDocumentStatusLog`.`comment` AS `statusComment`, `tblDocumentStatusLog`.`date` as `statusDate`, ".
		"`tblDocumentStatusLog`.`userID`, `oTbl`.`fullName` AS `ownerName`, `sTbl`.`fullName` AS `statusName` ".
		"FROM `tblDocumentStatus` ".
		"LEFT JOIN `tblDocumentStatusLog` USING (`statusID`) ".
		"LEFT JOIN `ttstatid` on `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
		"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentStatus`.`documentID` ".
		"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
		"LEFT JOIN `tblUsers` AS `oTbl` on `oTbl`.`id` = `tblDocuments`.`owner` ".
		"LEFT JOIN `tblUsers` AS `sTbl` on `sTbl`.`id` = `tblDocumentStatusLog`.`userID` ".
		"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
		"AND `tblDocuments`.`id` IN (" . $docCSV . ") ".
		"ORDER BY `statusDate` DESC";

	$resArr = $db->getResultArray($queryStr);
	
	if (is_bool($resArr) && !$resArr) {
		UI::exitError(getMLText("approval_summary"),getMLText("internal_error_exit"));
	}
	// Create an array to hold all of these results, and index the array by
	// document id. This makes it easier to retrieve document ID information
	// later on and saves us having to repeatedly poll the database every time
	// new document information is required.
	$docIdx = array();
	foreach ($resArr as $res) {
	
		// verify expiry
		if ( $res["expires"] && time()>$res["expires"]+24*60*60 ){
			if  ( $res["status"]==S_DRAFT_APP || $res["status"]==S_DRAFT_REV ){
				$res["status"]=S_EXPIRED;
			}
		}

		$docIdx[$res["id"]][$res["version"]] = $res;
	}
}

$iRev = array();	
$printheader = true;
foreach ($approvalStatus["indstatus"] as $st) {

	if (isset($docIdx[$st["documentID"]][$st["version"]])) {
	
		if ($printheader){
			print "<table class=\"folderView\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("owner")."</th>\n";
			print "<th>".getMLText("status")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("last_update")."</th>\n";
			print "<th>".getMLText("expires")."</th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			$printheader = false;
		}
	
		print "<tr>\n";
		print "<td><a href=\"out.DocumentVersionDetail.php?documentid=".$st["documentID"]."&version=".$st["version"]."\">".$docIdx[$st["documentID"]][$st["version"]]["name"]."</a></td>";
		print "<td>".$docIdx[$st["documentID"]][$st["version"]]["ownerName"]."</td>";
		print "<td>".getOverallStatusText($docIdx[$st["documentID"]][$st["version"]]["status"])."</td>";
		print "<td>".$st["version"]."</td>";
		print "<td>".$st["date"]." ". $docIdx[$st["documentID"]][$st["version"]]["statusName"] ."</td>";
		print "<td>".(!$docIdx[$st["documentID"]][$st["version"]]["expires"] ? "-":getReadableDate($docIdx[$st["documentID"]][$st["version"]]["expires"]))."</td>";				
		print "</tr>\n";
	}
	if ($st["status"]!=-2) {
		$iRev[] = $st["documentID"];
	}
}
if (!$printheader) {
	echo "</tbody>\n</table>\n";
}else{
	printMLText("empty_notify_list");
}

UI::contentContainerEnd();
UI::contentHeading(getMLText("group_approval_summary"));
UI::contentContainerStart();

$printheader = true;
foreach ($approvalStatus["grpstatus"] as $st) {

	if (!in_array($st["documentID"], $iRev) && isset($docIdx[$st["documentID"]][$st["version"]])) {
	
		if ($printheader){
			print "<table class=\"folderView\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("owner")."</th>\n";
			print "<th>".getMLText("status")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("last_update")."</th>\n";
			print "<th>".getMLText("expires")."</th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			$printheader = false;
		}	
	
		print "<tr>\n";
		print "<td><a href=\"out.DocumentVersionDetail.php?documentid=".$st["documentID"]."&version=".$st["version"]."\">".$docIdx[$st["documentID"]][$st["version"]]["name"]."</a></td>";
		print "<td>".$docIdx[$st["documentID"]][$st["version"]]["ownerName"]."</td>";
		print "<td>".getOverallStatusText($docIdx[$st["documentID"]][$st["version"]]["status"])."</td>";
		print "<td>".$st["version"]."</td>";
		print "<td>".$st["date"]." ". $docIdx[$st["documentID"]][$st["version"]]["statusName"] ."</td>";
		print "<td>".(!$docIdx[$st["documentID"]][$st["version"]]["expires"] ? "-":getReadableDate($docIdx[$st["documentID"]][$st["version"]]["expires"]))."</td>";				
		print "</tr>\n";
	}
}
if (!$printheader) {
	echo "</tbody>\n</table>\n";
}else{
	printMLText("empty_notify_list");
}

UI::contentContainerEnd();
UI::htmlEndPage();

?>
