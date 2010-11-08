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

// Check to see if the user wants to see only those documents that are still
// in the review / approve stages.
$showInProcess = false;
if (isset($_GET["inProcess"]) && strlen($_GET["inProcess"])>0 && $_GET["inProcess"]!=0) {
	$showInProcess = true;
}

$orderby='n';
if (isset($_GET["orderby"]) && strlen($_GET["orderby"])==1 ) {
	$orderby=$_GET["orderby"];
}

UI::htmlStartPage(getMLText("my_documents"));
UI::globalNavigation();
UI::pageNavigation(getMLText("my_documents"), "my_documents");

if ($showInProcess){

	if (!$db->createTemporaryTable("ttstatid") || !$db->createTemporaryTable("ttcontentid")) {
		UI::contentHeading(getMLText("warning"));
		UI::contentContainer(getMLText("internal_error_exit"));
		UI::htmlEndPage();
		exit;
	}

	// Get document list for the current user.
	$reviewStatus = $user->getReviewStatus();
	$approvalStatus = $user->getApprovalStatus();
	
	// Create a comma separated list of all the documentIDs whose information is
	// required.
	$dList = array();
	foreach ($reviewStatus["indstatus"] as $st) {
		if (!in_array($st["documentID"], $dList)) {
			$dList[] = $st["documentID"];
		}
	}
	foreach ($reviewStatus["grpstatus"] as $st) {
		if (!in_array($st["documentID"], $dList)) {
			$dList[] = $st["documentID"];
		}
	}
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
		// Get the document information.
		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser`, ".
			"`tblDocumentContent`.`version`, `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
			"`tblDocumentStatusLog`.`comment` AS `statusComment`, `tblDocumentStatusLog`.`date` as `statusDate`, ".
			"`tblDocumentStatusLog`.`userID`, `oTbl`.`fullName` AS `ownerName`, `sTbl`.`fullName` AS `statusName` ".
			"FROM `tblDocumentContent` ".
			"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
			"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
			"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusID` = `tblDocumentStatus`.`statusID` ".
			"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"LEFT JOIN `tblUsers` AS `oTbl` on `oTbl`.`id` = `tblDocuments`.`owner` ".
			"LEFT JOIN `tblUsers` AS `sTbl` on `sTbl`.`id` = `tblDocumentStatusLog`.`userID` ".
			"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
			"AND `ttcontentid`.`maxVersion` = `tblDocumentContent`.`version` ".
			"AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_REV.", ".S_DRAFT_APP.", ".S_EXPIRED.") ".
			"AND `tblDocuments`.`id` IN (" . $docCSV . ") ".
			"ORDER BY `statusDate` DESC";

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr) {
			UI::contentHeading(getMLText("warning"));
			UI::contentContainer(getMLText("internal_error_exit"));
			UI::htmlEndPage();
			exit;
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

		// List the documents where a review has been requested.
		UI::contentHeading(getMLText("documents_to_review"));
		UI::contentContainerStart();
		$printheader=true;
		$iRev = array();
		foreach ($reviewStatus["indstatus"] as $st) {
		
			if ( $st["status"]==0 && isset($docIdx[$st["documentID"]][$st["version"]]) ) {
			
				if ($printheader){
					print "<table class=\"folderView\">";
					print "<thead>\n<tr>\n";
					print "<th>".getMLText("name")."</th>\n";
					print "<th>".getMLText("owner")."</th>\n";
					print "<th>".getMLText("version")."</th>\n";
					print "<th>".getMLText("last_update")."</th>\n";
					print "<th>".getMLText("expires")."</th>\n";
					print "</tr>\n</thead>\n<tbody>\n";
					$printheader=false;
				}
			
				print "<tr>\n";
				print "<td><a href=\"out.ViewDocument.php?documentid=".$st["documentID"]."\">".$docIdx[$st["documentID"]][$st["version"]]["name"]."</a></td>";
				print "<td>".$docIdx[$st["documentID"]][$st["version"]]["ownerName"]."</td>";
				print "<td>".$st["version"]."</td>";
				print "<td>".$st["date"]." ". $docIdx[$st["documentID"]][$st["version"]]["statusName"] ."</td>";
				print "<td".($docIdx[$st["documentID"]][$st["version"]]['status']!=S_EXPIRED?"":" class=\"warning\"").">".(!$docIdx[$st["documentID"]][$st["version"]]["expires"] ? "-":getReadableDate($docIdx[$st["documentID"]][$st["version"]]["expires"]))."</td>";				
				print "</tr>\n";
			}
		}
		foreach ($reviewStatus["grpstatus"] as $st) {
		
			if (!in_array($st["documentID"], $iRev) && $st["status"]==0 && isset($docIdx[$st["documentID"]][$st["version"]])) {

				if ($printheader){
					print "<table class=\"folderView\">";
					print "<thead>\n<tr>\n";
					print "<th>".getMLText("name")."</th>\n";
					print "<th>".getMLText("owner")."</th>\n";
					print "<th>".getMLText("version")."</th>\n";
					print "<th>".getMLText("last_update")."</th>\n";
					print "<th>".getMLText("expires")."</th>\n";
					print "</tr>\n</thead>\n<tbody>\n";
					$printheader=false;
				}

				print "<tr>\n";
				print "<td><a href=\"out.ViewDocument.php?documentid=".$st["documentID"]."\">".$docIdx[$st["documentID"]][$st["version"]]["name"]."</a></td>";
				print "<td>".$docIdx[$st["documentID"]][$st["version"]]["ownerName"]."</td>";
				print "<td>".$st["version"]."</td>";
				print "<td>".$st["date"]." ". $docIdx[$st["documentID"]][$st["version"]]["statusName"]."</td>";
				print "<td".($docIdx[$st["documentID"]][$st["version"]]['status']!=S_EXPIRED?"":" class=\"warning\"").">".(!$docIdx[$st["documentID"]][$st["version"]]["expires"] ? "-":getReadableDate($docIdx[$st["documentID"]][$st["version"]]["expires"]))."</td>";				
				print "</tr>\n";
			}
		}
		if (!$printheader){
			echo "</tbody>\n</table>";
		}else{
			printMLText("empty_notify_list");
		}
		UI::contentContainerEnd();

		// List the documents where an approval has been requested.
		UI::contentHeading(getMLText("documents_to_approve"));
		UI::contentContainerStart();
		$printheader=true;
		
		foreach ($approvalStatus["indstatus"] as $st) {
		
			if ( $st["status"]==0 && isset($docIdx[$st["documentID"]][$st["version"]])) {
			
				if ($printheader){
					print "<table class=\"folderView\">";
					print "<thead>\n<tr>\n";
					print "<th>".getMLText("name")."</th>\n";
					print "<th>".getMLText("owner")."</th>\n";
					print "<th>".getMLText("version")."</th>\n";
					print "<th>".getMLText("last_update")."</th>\n";
					print "<th>".getMLText("expires")."</th>\n";
					print "</tr>\n</thead>\n<tbody>\n";
					$printheader=false;
				}
				print "<tr>\n";
				print "<td><a href=\"out.ViewDocument.php?documentid=".$st["documentID"]."\">".$docIdx[$st["documentID"]][$st["version"]]["name"]."</a></td>";
				print "<td>".$docIdx[$st["documentID"]][$st["version"]]["ownerName"]."</td>";
				print "<td>".$st["version"]."</td>";
				print "<td>".$st["date"]." ". $docIdx[$st["documentID"]][$st["version"]]["statusName"]."</td>";
				print "<td".($docIdx[$st["documentID"]][$st["version"]]['status']!=S_EXPIRED?"":" class=\"warning\"").">".(!$docIdx[$st["documentID"]][$st["version"]]["expires"] ? "-":getReadableDate($docIdx[$st["documentID"]][$st["version"]]["expires"]))."</td>";					
				print "</tr>\n";
			}
		}
		foreach ($approvalStatus["grpstatus"] as $st) {
		
			if (!in_array($st["documentID"], $iRev) && $st["status"]==0 && isset($docIdx[$st["documentID"]][$st["version"]])) {
				if ($printheader){
					print "<table class=\"folderView\">";
					print "<thead>\n<tr>\n";
					print "<th>".getMLText("name")."</th>\n";
					print "<th>".getMLText("owner")."</th>\n";
					print "<th>".getMLText("version")."</th>\n";
					print "<th>".getMLText("last_update")."</th>\n";
					print "<th>".getMLText("expires")."</th>\n";
					print "</tr>\n</thead>\n<tbody>\n";
					$printheader=false;
				}
				print "<tr>\n";
				print "<td><a href=\"out.ViewDocument.php?documentid=".$st["documentID"]."\">".$docIdx[$st["documentID"]][$st["version"]]["name"]."</a></td>";
				print "<td>".$docIdx[$st["documentID"]][$st["version"]]["ownerName"]."</td>";
				print "<td>".$st["version"]."</td>";				
				print "<td>".$st["date"]." ". $docIdx[$st["documentID"]][$st["version"]]["statusName"]."</td>";
				print "<td".($docIdx[$st["documentID"]][$st["version"]]['status']!=S_EXPIRED?"":" class=\"warning\"").">".(!$docIdx[$st["documentID"]][$st["version"]]["expires"] ? "-":getReadableDate($docIdx[$st["documentID"]][$st["version"]]["expires"]))."</td>";				
				print "</tr>\n";
			}
		}
		if (!$printheader){
			echo "</tbody>\n</table>\n";
		 }else{
		 	printMLText("empty_notify_list");
		 }
		UI::contentContainerEnd();
	}
	else {
	
		UI::contentHeading(getMLText("documents_to_review"));
		UI::contentContainerStart();
		printMLText("empty_notify_list");
		UI::contentContainerEnd();
		UI::contentHeading(getMLText("documents_to_approve"));
		UI::contentContainerStart();
		printMLText("empty_notify_list");
		UI::contentContainerEnd();
	}

	// Get list of documents owned by current user that are pending review or
	// pending approval.
	$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser`, ".
		"`tblDocumentContent`.`version`, `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
		"`tblDocumentStatusLog`.`comment` AS `statusComment`, `tblDocumentStatusLog`.`date` as `statusDate`, ".
		"`tblDocumentStatusLog`.`userID`, `oTbl`.`fullName` AS `ownerName`, `sTbl`.`fullName` AS `statusName` ".
		"FROM `tblDocumentContent` ".
		"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
		"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
		"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusID` = `tblDocumentStatus`.`statusID` ".
		"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
		"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
		"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
		"LEFT JOIN `tblUsers` AS `oTbl` on `oTbl`.`id` = `tblDocuments`.`owner` ".
		"LEFT JOIN `tblUsers` AS `sTbl` on `sTbl`.`id` = `tblDocumentStatusLog`.`userID` ".
		"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
		"AND `ttcontentid`.`maxVersion` = `tblDocumentContent`.`version` ".
		"AND `tblDocuments`.`owner` = '".$user->getID()."' ".
		"AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_REV.", ".S_DRAFT_APP.") ".
		"ORDER BY `statusDate` DESC";

	$resArr = $db->getResultArray($queryStr);
	if (is_bool($resArr) && !$resArr) {
		UI::contentHeading(getMLText("warning"));
		UI::contentContainer("Internal error. Unable to complete request. Exiting.");
		UI::htmlEndPage();
		exit;
	}

	UI::contentHeading(getMLText("documents_user_requiring_attention"));
	UI::contentContainerStart();
	if (count($resArr)>0) {

		print "<table class=\"folderView\">";
		print "<thead>\n<tr>\n";
		print "<th>".getMLText("name")."</th>\n";
		print "<th>".getMLText("status")."</th>\n";
		print "<th>".getMLText("version")."</th>\n";
		print "<th>".getMLText("last_update")."</th>\n";
		print "<th>".getMLText("expires")."</th>\n";
		print "</tr>\n</thead>\n<tbody>\n";

		foreach ($resArr as $res) {
		
			// verify expiry
			if ( $res["expires"] && time()>$res["expires"]+24*60*60 ){
				if  ( $res["status"]==S_DRAFT_APP || $res["status"]==S_DRAFT_REV ){
					$res["status"]=S_EXPIRED;
				}
			}
		
			print "<tr>\n";
			print "<td><a href=\"out.ViewDocument.php?documentid=".$res["documentID"]."\">" . $res["name"] . "</a></td>\n";
			print "<td>".getOverallStatusText($res["status"])."</td>";
			print "<td>".$res["version"]."</td>";
			print "<td>".$res["statusDate"]." ".$res["statusName"]."</td>";
			print "<td>".(!$res["expires"] ? "-":getReadableDate($res["expires"]))."</td>";				
			print "</tr>\n";
		}		
		print "</tbody></table>";	
		
	}
	else printMLText("empty_notify_list");
	
	UI::contentContainerEnd();
	
	
	// Get list of documents locked by current user 
	$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser`, ".
		"`tblDocumentContent`.`version`, `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
		"`tblDocumentStatusLog`.`comment` AS `statusComment`, `tblDocumentStatusLog`.`date` as `statusDate`, ".
		"`tblDocumentStatusLog`.`userID`, `oTbl`.`fullName` AS `ownerName`, `sTbl`.`fullName` AS `statusName` ".
		"FROM `tblDocumentContent` ".
		"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
		"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
		"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusID` = `tblDocumentStatus`.`statusID` ".
		"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
		"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
		"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
		"LEFT JOIN `tblUsers` AS `oTbl` on `oTbl`.`id` = `tblDocuments`.`owner` ".
		"LEFT JOIN `tblUsers` AS `sTbl` on `sTbl`.`id` = `tblDocumentStatusLog`.`userID` ".
		"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
		"AND `ttcontentid`.`maxVersion` = `tblDocumentContent`.`version` ".
		"AND `tblDocumentLocks`.`userID` = '".$user->getID()."' ".
		"ORDER BY `statusDate` DESC";

	$resArr = $db->getResultArray($queryStr);
	if (is_bool($resArr) && !$resArr) {
		UI::contentHeading(getMLText("warning"));
		UI::contentContainer("Internal error. Unable to complete request. Exiting.");
		UI::htmlEndPage();
		exit;
	}

	UI::contentHeading(getMLText("documents_locked_by_you"));
	UI::contentContainerStart();
	if (count($resArr)>0) {

		print "<table class=\"folderView\">";
		print "<thead>\n<tr>\n";
		print "<th>".getMLText("name")."</th>\n";
		print "<th>".getMLText("status")."</th>\n";
		print "<th>".getMLText("version")."</th>\n";
		print "<th>".getMLText("last_update")."</th>\n";
		print "<th>".getMLText("expires")."</th>\n";
		print "</tr>\n</thead>\n<tbody>\n";

		foreach ($resArr as $res) {
		
			// verify expiry
			if ( $res["expires"] && time()>$res["expires"]+24*60*60 ){
				if  ( $res["status"]==S_DRAFT_APP || $res["status"]==S_DRAFT_REV ){
					$res["status"]=S_EXPIRED;
				}
			}
		
			print "<tr>\n";
			print "<td><a href=\"out.ViewDocument.php?documentid=".$res["documentID"]."\">" . $res["name"] . "</a></td>\n";
			print "<td>".getOverallStatusText($res["status"])."</td>";
			print "<td>".$res["version"]."</td>";
			print "<td>".$res["statusDate"]." ".$res["statusName"]."</td>";
			print "<td>".(!$res["expires"] ? "-":getReadableDate($res["expires"]))."</td>";				
			print "</tr>\n";
		}		
		print "</tbody></table>";	
		
	}
	else printMLText("empty_notify_list");
	
	UI::contentContainerEnd();
	
}
else {

	// Get list of documents owned by current user
	if (!$db->createTemporaryTable("ttstatid")) {
		UI::contentHeading(getMLText("warning"));
		UI::contentContainer(getMLText("internal_error_exit"));
		UI::htmlEndPage();
		exit;
	}

	if (!$db->createTemporaryTable("ttcontentid")) {
		UI::contentHeading(getMLText("warning"));
		UI::contentContainer(getMLText("internal_error_exit"));
		UI::htmlEndPage();
		exit;
	}
	$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser`, ".
		"`tblDocumentContent`.`version`, `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
		"`tblDocumentStatusLog`.`comment` AS `statusComment`, `tblDocumentStatusLog`.`date` as `statusDate`, ".
		"`tblDocumentStatusLog`.`userID`, `oTbl`.`fullName` AS `ownerName`, `sTbl`.`fullName` AS `statusName` ".
		"FROM `tblDocumentContent` ".
		"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
		"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
		"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusID` = `tblDocumentStatus`.`statusID` ".
		"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
		"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
		"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
		"LEFT JOIN `tblUsers` AS `oTbl` on `oTbl`.`id` = `tblDocuments`.`owner` ".
		"LEFT JOIN `tblUsers` AS `sTbl` on `sTbl`.`id` = `tblDocumentStatusLog`.`userID` ".
		"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
		"AND `ttcontentid`.`maxVersion` = `tblDocumentContent`.`version` ".
		"AND `tblDocuments`.`owner` = '".$user->getID()."' ";
		
	if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
	else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
	else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
	else $queryStr .= "ORDER BY `name`";

	$resArr = $db->getResultArray($queryStr);
	if (is_bool($resArr) && !$resArr) {
		UI::contentHeading(getMLText("warning"));
		UI::contentContainer(getMLText("internal_error_exit"));
		UI::htmlEndPage();
		exit;
	}
	
	UI::contentHeading(getMLText("all_documents"));
	UI::contentContainerStart();

	if (count($resArr)>0) {

		print "<table class=\"folderView\">";
		print "<thead>\n<tr>\n";
		print "<th><a href=\"../out/out.MyDocuments.php?orderby=n\">".getMLText("name")."</a></th>\n";
		print "<th><a href=\"../out/out.MyDocuments.php?orderby=s\">".getMLText("status")."</a></th>\n";
		print "<th>".getMLText("version")."</th>\n";
		print "<th><a href=\"../out/out.MyDocuments.php?orderby=u\">".getMLText("last_update")."</a></th>\n";
		print "<th><a href=\"../out/out.MyDocuments.php?orderby=e\">".getMLText("expires")."</a></th>\n";
		print "</tr>\n</thead>\n<tbody>\n";

		foreach ($resArr as $res) {
		
			// verify expiry
			if ( $res["expires"] && time()>$res["expires"]+24*60*60 ){
				if  ( $res["status"]==S_DRAFT_APP || $res["status"]==S_DRAFT_REV ){
					$res["status"]=S_EXPIRED;
				}
			}
		
			print "<tr>\n";
			print "<td><a href=\"out.ViewDocument.php?documentid=".$res["documentID"]."\">" . $res["name"] . "</a></td>\n";
			print "<td>".getOverallStatusText($res["status"])."</td>";
			print "<td>".$res["version"]."</td>";
			print "<td>".$res["statusDate"]." ". $res["statusName"]."</td>";
			//print "<td>".(!$res["expires"] ? getMLText("does_not_expire"):getReadableDate($res["expires"]))."</td>";				
			print "<td>".(!$res["expires"] ? "-":getReadableDate($res["expires"]))."</td>";				
			print "</tr>\n";
		}
		print "</tbody></table>";
	}
	else printMLText("empty_notify_list");
	
	UI::contentContainerEnd();
}

UI::htmlEndPage();
?>
