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
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

// Redirect to the search page if the navigation search button has been
// selected without supplying any search terms.
if (isset($_GET["navBar"]) && strlen($_GET["query"])==0) {
	if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
		$folderid=$settings->_rootFolderID;
	}
	else {
		$folderid = $_GET["folderid"];
	}
	header("Location: ../out/out.SearchForm.php?folderid=".$folderid);
}

//
// Supporting functions.
//

function makeTimeStamp($hour, $min, $sec, $year, $month, $day) {
	$thirtyone = array (1, 3, 5, 7, 8, 10, 12);
	$thirty = array (4, 6, 9, 11);

	// Very basic check that the terms are valid. Does not fail for illegal
	// dates such as 31 Feb.
	if (!is_numeric($hour) || !is_numeric($min) || !is_numeric($sec) || !is_numeric($year) || !is_numeric($month) || !is_numeric($day) || $month<1 || $month>12 || $day<1 || $day>31 || $hour<0 || $hour>23 || $min<0 || $min>59 || $sec<0 || $sec>59) {
		return false;
	}
	$year = (int) $year;
	$month = (int) $month;
	$day = (int) $day;

	if (array_search($month, $thirtyone)) {
		$max=31;
	}
	else if (array_search($month, $thirty)) {
		$max=30;
	}
	else {
		$max=(($year % 4 == 0) && ($year % 100 != 0 || $year % 400 == 0)) ? 29 : 28;
	}

	// If the date falls out of bounds, set it to the maximum for the given
	// month. Makes assumption about the user's intention, rather than failing
	// for absolutely everything.
	if ($day>$max) {
		$day=$max;
	}

	return mktime($hour, $min, $sec, $month, $day, $year);
}

function getTime() {
	if (function_exists('microtime')) {
		$tm = microtime();
		$tm = explode(' ', $tm);
		return (float) sprintf('%f', $tm[1] + $tm[0]);
	}
	return time();
}

function markQuery($str, $tag = "b") {

	GLOBAL $query;
	$querywords = split(" ", $query);
	
	foreach ($querywords as $queryword)
		$str = eregi_replace("($queryword)", "<" . $tag . ">\\1</" . $tag . ">", $str);
	
	return $str;
}


//
// Parse all of the parameters for the search
//

// Create the keyword search string. This search spans up to three columns
// in the database: keywords, name and comment.

if (isset($_GET["query"]) && is_string($_GET["query"])) {
	$query = sanitizeString($_GET["query"]);
}
else {
	$query = "";
}
// Split the search string into constituent keywords.
$tkeys=array();
if (strlen($query)>0) {
	$tkeys = split("[\t\r\n ,]+", $query);
}

$mode = "AND";
if (isset($_GET["mode"]) && is_numeric($_GET["mode"]) && $_GET["mode"]==0) {
		$mode = "OR";
}

$searchin = array();
if (is_array($_GET["searchin"])) {
	foreach ($_GET["searchin"] as $si) {
		if (isset($si) && is_numeric($si)) {
			switch ($si) {
				case 1: // keywords
				case 2: // name
				case 3: // comment
					$searchin[$si] = $si;
					break;
			}
		}
	}
}

// if none is checkd search all
if (count($searchin)==0) $searchin=array( 0, 1, 2, 3);

$searchKey = "";
// Assemble the arguments for the concatenation function. This allows the
// search to be carried across all the relevant fields.
$concatFunction = "";
if (in_array(1, $searchin)) {
	$concatFunction = "`tblDocuments`.`keywords`";
}
if (in_array(2, $searchin)) {
	$concatFunction = (strlen($concatFunction) == 0 ? "" : $concatFunction.", ")."`tblDocuments`.`name`";
}
if (in_array(3, $searchin)) {
	$concatFunction = (strlen($concatFunction) == 0 ? "" : $concatFunction.", ")."`tblDocuments`.`comment`";
}

if (strlen($concatFunction)>0 && count($tkeys)>0) {
	$concatFunction = "CONCAT_WS(' ', ".$concatFunction.")";
	foreach ($tkeys as $key) {
		$key = trim($key);
		if (strlen($key)>0) {
			$searchKey = (strlen($searchKey)==0 ? "" : $searchKey." ".$mode." ").$concatFunction." LIKE '%".$key."%'";
		}
	}
}

// Check to see if the search has been restricted to a particular sub-tree in
// the folder hierarchy.
$searchFolder = "";
if (isset($_GET["targetidform1"]) && is_numeric($_GET["targetidform1"]) && $_GET["targetidform1"]>0) {
	$targetid = $_GET["targetidform1"];
	$startFolder = getFolder($targetid);
}
else {
	$targetid = $settings->_rootFolderID;
	$startFolder = getFolder($targetid);
}
if (!is_object($startFolder)) {
	UI::exitError(getMLText("search_results"),getMLText("invalid_folder_id"));
}

if ($targetid != $settings->_rootFolderID) {
	$searchFolder = "`tblDocuments`.`folderList` LIKE '%:".$targetid.":%'";
}

// Now that the target folder has been identified, it is possible to create
// the full navigation bar.
$folderPathHTML = getFolderPathHTML($startFolder, true);
UI::htmlStartPage(getMLText("search_results"));
UI::globalNavigation($startFolder);
UI::pageNavigation($folderPathHTML, "", $startFolder);
UI::contentHeading(getMLText("search_results"));

// Check to see if the search has been restricted to a particular
// document owner.
$searchOwner = "";
if (isset($_GET["ownerid"]) && is_numeric($_GET["ownerid"]) && $_GET["ownerid"]!=-1) {
	if (!is_object(getUser($_GET["ownerid"]))) {
		UI::contentContainer(getMLText("unknown_owner"));
		UI::htmlEndPage();
		exit;
	}
	$ownerid = $_GET["ownerid"];
}
else {
	$ownerid = -1;
}
if ($ownerid != -1) {
	$searchOwner = "`tblDocuments`.`owner` = '".$ownerid."'";
}

// Is the search restricted to documents created between two specific dates?
$searchCreateDate = "";
if (isset($_GET["creationdate"]) && $_GET["creationdate"]!=null) {
	$startdate = makeTimeStamp(0, 0, 0, $_GET["createstartyear"], $_GET["createstartmonth"], $_GET["createstartday"]);
	if (is_bool($startdate)) {
		UI::contentContainer(getMLText("invalid_create_date_start"));
		UI::htmlEndPage();
		exit;
	}
	$stopdate = makeTimeStamp(23, 59, 59, $_GET["createendyear"], $_GET["createendmonth"], $_GET["createendday"]);
	if (is_bool($stopdate)) {
		UI::contentContainer(getMLText("invalid_create_date_end"));
		UI::htmlEndPage();
		exit;
	}
	$searchCreateDate = "`tblDocuments`.`date` >= ".$startdate. " AND `tblDocuments`.`date` <= ".$stopdate;
}

// Is the search restricted to documents last updated between two specific
// dates? Not currently used as a more sophisticated method for reporting
// updates is required.
/*
$searchLastUpdate = "";
if (isset($_GET["lastupdate"]) && $_GET["lastupdate"]!=null) {
	$lastupdate = true;

	$startdate = makeTimeStamp(0, 0, 0, $_GET["updatestartyear"], $_GET["updatestartmonth"], $_GET["updatestartday"]);
	if (is_bool($startdate)) {
		die ("invalid start date for last update date range");
	}
	$stopdate  = mktime(23,59,59, $updateendmonth, $updateendday, $updateendyear);
	$stopdate = makeTimeStamp(23, 59, 59, $_GET["updateendyear"], $_GET["updateendmonth"], $_GET["updateendday"]);
	if (is_bool($stopdate)) {
		die ("invalid end date for last update date range");
	}
	$searchLastUpdate = "`contentDate` >= ".$startdate. " AND `contentDate` <= ".$stopdate;
}
*/

//
// Get the page number to display. If the result set contains more than
// 25 entries, it is displayed across multiple pages.
//
// This requires that a page number variable be used to track which page the
// user is interested in, and an extra clause on the select statement.
//
// Default page to display is always one.
$pageNumber=1;
if (isset($_GET["pg"])) {
	if (is_numeric($_GET["pg"]) && $_GET["pg"]>0) {
		$pageNumber = (integer)$_GET["pg"];
	}
	else if (!strcasecmp($_GET["pg"], "all")) {
		$pageNumber = "all";
	}
}


// ------------------------------------- Suche starten --------------------------------------------
$startTime = getTime();

//
// Construct the SQL query that will be used to search the database.
//

if (!$db->createTemporaryTable("ttcontentid") || !$db->createTemporaryTable("ttstatid")) {
	UI::contentContainer(getMLText("internal_error"));
	UI::htmlEndPage();
	exit;
}

//$searchQuery = "SELECT `tblDocuments`.*, ".
//	"`tblDocumentContent`.`version`, ".
// "`tblDocumentStatusLog`.`status`, `tblDocumentLocks`.`userID` as `lockUser` ".

$searchQuery = "FROM `tblDocumentContent` ".
	"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
	"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
	"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusID` = `tblDocumentStatus`.`statusID` ".
	"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
	"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
	"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
	"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
	"AND `ttcontentid`.`maxVersion` = `tblDocumentContent`.`version`";

if (strlen($searchKey)>0) {
	$searchQuery .= " AND (".$searchKey.")";
}
if (strlen($searchFolder)>0) {
	$searchQuery .= " AND ".$searchFolder;
}
if (strlen($searchOwner)>0) {
	$searchQuery .= " AND (".$searchOwner.")";
}
if (strlen($searchCreateDate)>0) {
	$searchQuery .= " AND (".$searchCreateDate.")";
}

// status
$stlist = "(";
if (isset($_GET["pendingReview"])){
	if ($stlist != "(") $stlist .= ",";
	$stlist .= S_DRAFT_REV;
}
if (isset($_GET["pendingApproval"])){
	if ($stlist != "(") $stlist .= ",";
	$stlist .= S_DRAFT_APP;
}
if (isset($_GET["released"])){
	if ($stlist != "(") $stlist .= ",";
	$stlist .= S_RELEASED;
}
if (isset($_GET["rejected"])){
	if ($stlist != "(") $stlist .= ",";
	$stlist .= S_REJECTED;
}
if (isset($_GET["obsolete"])){
	if ($stlist != "(") $stlist .= ",";
	$stlist .= S_OBSOLETE;
}
if (isset($_GET["expired"])){
	if ($stlist != "(") $stlist .= ",";
	$stlist .= S_EXPIRED;
}
if ($stlist != "("){
	$stlist .= ")";
	$searchQuery .= " AND `tblDocumentStatusLog`.`status` IN ".$stlist;
}

// Count the number of rows that the search will produce.
$resArr = $db->getResultArray("SELECT COUNT(*) AS num ".$searchQuery);
$totalDocs = 0;
if (is_numeric($resArr[0]["num"]) && $resArr[0]["num"]>0) {
	$totalDocs = (integer)$resArr[0]["num"];
}
$totalPages = (integer)($totalDocs/25);
if (($totalDocs%25) > 0) {
	$totalPages++;
}
if (is_numeric($pageNumber) && $pageNumber>$totalPages) {
	$pageNumber = $totalPages;
}

// If there are no results from the count query, then there is no real need
// to run the full query. TODO: re-structure code to by-pass additional
// queries when no initial results are found. In the meantime, make sure that
// the page number is at least 1.
if (is_numeric($pageNumber) && $pageNumber==0) {
	$pageNumber = 1;
}
// Prepare the complete search query, including the LIMIT clause.
$searchQuery = "SELECT `tblDocuments`.*, ".
	"`tblDocumentContent`.`version`, ".
	"`tblDocumentStatusLog`.`status`, `tblDocumentLocks`.`userID` as `lockUser` ".$searchQuery;

if (is_numeric($pageNumber)) {
	$searchQuery .= " LIMIT ".(($pageNumber-1)*25).", 25";
}

// Send the complete search query to the database.
$resArr = $db->getResultArray($searchQuery);

$searchTime = getTime() - $startTime;
$searchTime = round($searchTime, 2);
// ---------------------------------- Ausgabe der Ergebnisse --------------------------------------

UI::contentContainerStart();
?>
<table width="100%" style="border-collapse: collapse;">
<tr>
<td align="left" style="padding:0; margin:0;">
<?php
$numResults = count($resArr);
if ($numResults == 0) {
	printMLText("search_no_results");
}
else {
	printMLText("search_report", array("count" => $totalDocs));
}
?>
</td>
<td align="right"><?php printMLText("search_time", array("time" => $searchTime));?></td>
</tr>
</table>

<?php
if ($numResults == 0) {
	UI::contentContainerEnd();
	UI::htmlEndPage();
	exit;
}

UI::pageList($pageNumber, $totalPages, "../op/op.Search.php", $_GET);

print "<table class=\"folderView\">";
print "<thead>\n<tr>\n";
//print "<th></th>\n";
print "<th>".getMLText("name")."</th>\n";
print "<th>".getMLText("owner")."</th>\n";
print "<th>".getMLText("status")."</th>\n";
print "<th>".getMLText("version")."</th>\n";
print "<th>".getMLText("comment")."</th>\n";
//print "<th>".getMLText("reviewers")."</th>\n";
//print "<th>".getMLText("approvers")."</th>\n";
print "</tr>\n</thead>\n<tbody>\n";

$resultsFilteredByAccess = false;
foreach ($resArr as $docArr) {

	$document = new Document(
		$docArr["id"], $docArr["name"],
		$docArr["comment"], $docArr["date"],
		$docArr["expires"], $docArr["owner"],
		$docArr["folder"], $docArr["inheritAccess"],
		$docArr["defaultAccess"], $docArr["lockUser"],
		$docArr["keywords"], $docArr["sequence"]);
		
	if ($document->getAccessMode($user) < M_READ) {
		$resultsFilteredByAccess = true;
	}
	else {
		print "<tr>";
		//print "<td><img src=\"../out/images/file.gif\" class=\"mimeicon\"></td>";
		if (in_array(2, $searchin)) {
			$docName = markQuery($docArr["name"], "i");
		}
		else {
			$docName = $docArr["name"];
		}
		print "<td><a class=\"standardText\" href=\"../out/out.ViewDocument.php?documentid=".$docArr["id"]."\">/";
		$folder = getFolder($docArr["folder"]);
		$path = $folder->getPath();
		for ($i = 1; $i  < count($path); $i++) {
			print $path[$i]->getName()."/";
		}
		print $docName;
		print "</a></td>";
		
		$owner = $document->getOwner();
		print "<td>".$owner->getFullName()."</td>";
		print "<td>".getOverallStatusText($docArr["status"]). "</td>";

		print "<td class=\"center\">".$docArr["version"]."</td>";
		
		if (in_array(3, $searchin)) $comment = markQuery($docArr["comment"]);
		else $comment = $docArr["comment"];
		if (strlen($comment) > 50) $comment = substr($comment, 0, 47) . "...";
		print "<td>".$comment."</td>";
		
		/*print "<td>";
		if (!$db->createTemporaryTable("ttreviewid")) {
			print "-";
		}
		else {
			$queryStr="SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
				"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
				"`tblDocumentReviewLog`.`userID`, `tblUsers`.`fullName`, `tblGroups`.`name` AS `groupName` ".
				"FROM `tblDocumentReviewers` ".
				"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
				"LEFT JOIN `ttreviewid` on `ttreviewid`.`maxLogID` = `tblDocumentReviewLog`.`reviewLogID` ".
				"LEFT JOIN `tblUsers` on `tblUsers`.`id` = `tblDocumentReviewers`.`required`".
				"LEFT JOIN `tblGroups` on `tblGroups`.`id` = `tblDocumentReviewers`.`required`".
				"WHERE `ttreviewid`.`maxLogID`=`tblDocumentReviewLog`.`reviewLogID` ".
				"AND `tblDocumentReviewers`.`documentID` = '". $docArr["id"] ."' ".
				"AND `tblDocumentReviewers`.`version` = '". $docArr["version"] ."' ";
			$rstat = $db->getResultArray($queryStr);
			if (!is_bool($rstat) && count($rstat)>0) {
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
		}
		print "</td>\n<td>";
		if (!$db->createTemporaryTable("ttapproveid", $forceTemporaryTable)) {
			print "-";
		}
		else {
			$queryStr="SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
				"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
				"`tblDocumentApproveLog`.`userID`, `tblUsers`.`fullName`, `tblGroups`.`name` AS `groupName` ".
				"FROM `tblDocumentApprovers` ".
				"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
				"LEFT JOIN `ttapproveid` on `ttapproveid`.`maxLogID` = `tblDocumentApproveLog`.`approveLogID` ".
				"LEFT JOIN `tblUsers` on `tblUsers`.`id` = `tblDocumentApprovers`.`required`".
				"LEFT JOIN `tblGroups` on `tblGroups`.`id` = `tblDocumentApprovers`.`required`".
				"WHERE `ttapproveid`.`maxLogID`=`tblDocumentApproveLog`.`approveLogID` ".
				"AND `tblDocumentApprovers`.`documentID` = '". $docArr["id"] ."' ".
				"AND `tblDocumentApprovers`.`version` = '". $docArr["version"] ."'";
			$astat = $db->getResultArray($queryStr);
			if (!is_bool($astat) && count($astat)>0) {
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
		}
		print "</td>";
		*/
		print "</tr>\n";
	}
}
if ($resultsFilteredByAccess) {
	print "<tr><td colspan=\"7\">". getMLText("search_results_access_filtered") . "</td></tr>";
}
print "</tbody></table>\n";

UI::pageList($pageNumber, $totalPages, "../op/op.Search.php", $_GET);

UI::contentContainerEnd();
UI::htmlEndPage();
?>
