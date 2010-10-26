<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_GET["documentid"];
$document = getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$folder = $document->getFolder();
$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

if ($document->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if (!isset($_GET["version"]) || !is_numeric($_GET["version"]) || intval($_GET["version"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$version = $_GET["version"];
$version = $document->getContentByVersion($version);

if (!is_object($version)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

// if version is last got out.ViewDocument
$latestContent = $document->getLatestContent();
if ($latestContent->getVersion()==$version->getVersion()) {
	header("Location:../out/out.ViewDocument.php?documentid=".$documentid);
}

$status = $version->getStatus();
$reviewStatus = $version->getReviewStatus();
$approvalStatus = $version->getApprovalStatus();

UI::htmlStartPage(getMLText("document_title", array("documentname" => $document->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($docPathHTML, "view_document");
UI::contentHeading(getMLText("document_infos"));
UI::contentContainerStart();

?>
<table>
<tr>
<td><?php printMLText("owner");?>:</td>
<td>
<?php
$owner = $document->getOwner();
print "<a class=\"infos\" href=\"mailto:".$owner->getEmail()."\">".$owner->getFullName()."</a>";
?>
</td>
</tr>
<tr>
<td><?php printMLText("comment");?>:</td>
<td><?php print $document->getComment();?></td>
</tr>
<tr>
<td><?php printMLText("creation_date");?>:</td>
<td><?php print getLongReadableDate($document->getDate()); ?></td>
</tr>
<tr>
<td><?php printMLText("keywords");?>:</td>
<td><?php print $document->getKeywords();?></td>
</tr>
<?php
if ($document->isLocked()) {
	$lockingUser = $document->getLockingUser();
?>
<tr>
	<td><?php printMLText("lock_status");?>:</td>
	<td><?php printMLText("lock_message", array("email" => $lockingUser->getEmail(), "username" => $lockingUser->getFullName()));?></td>
</tr>
<?php
}
?>
</tr>
</table>
<?php
UI::contentContainerEnd();

// verify if file exists
$file_exists=file_exists($settings->_contentDir . $version->getPath());

UI::contentHeading(getMLText("details_version", array ("version" => $version->getVersion())));
UI::contentContainerStart();
print "<table class=\"folderView\">";
print "<thead>\n<tr>\n";
print "<th width='10%'></th>\n";
print "<th width='10%'>".getMLText("version")."</th>\n";
print "<th width='20%'>".getMLText("file")."</th>\n";
print "<th width='25%'>".getMLText("comment")."</th>\n";
print "<th width='15%'>".getMLText("status")."</th>\n";
print "<th width='20%'></th>\n";
print "</tr>\n</thead>\n<tbody>\n";
print "<tr>\n";
print "<td><ul class=\"actions\">";

if ($file_exists){
	print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&version=".$version->getVersion()."\"><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($version->getFileType())."\" title=\"".$version->getMimeType()."\"> ".getMLText("download")."</a>";
	if ($version->viewOnline())
		print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&version=".$version->getVersion()."\"><img src=\"images/view.gif\" class=\"mimeicon\">" . getMLText("view_online") . "</a>";
}else print "<li><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($version->getFileType())."\" title=\"".$version->getMimeType()."\"> ";

print "</ul></td>\n";
print "<td class=\"center\">".$version->getVersion()."</td>\n";

print "<td><ul class=\"documentDetail\">\n";
print "<li>".$version->getOriginalFileName()."</li>\n";

if ($file_exists) print "<li>". formatted_size(filesize($settings->_contentDir . $version->getPath())) ." ".$version->getMimeType()."</li>";
else print "<li><span class=\"warning\">".getMLText("document_deleted")."</span></li>";

$updatingUser = $version->getUser();
print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".$updatingUser->getEmail()."\">".$updatingUser->getFullName()."</a></li>";
print "<li>".getLongReadableDate($version->getDate())."</li>";
print "</ul></td>\n";

print "<td>".$version->getComment()."</td>";
print "<td>".getOverallStatusText($status["status"])."</td>";
print "<td>";

if (($document->getAccessMode($user) >= M_READWRITE)) {
	print "<ul class=\"actions\">";
	print "<li><a href=\"out.RemoveVersion.php?documentid=".$documentid."&version=".$version->getVersion()."\">".getMLText("rm_version")."</a></li>";
	if ($document->getAccessMode($user) == M_ALL) {
		if ( $status["status"]==S_RELEASED || $status["status"]==S_OBSOLETE ){
			print "<li><a href='../out/out.OverrideContentStatus.php?documentid=".$documentid."&version=".$version->getVersion()."'>".getMLText("change_status")."</a></li>";
		}
	}
	print "<li><a href=\"out.EditComment.php?documentid=".$documentid."&version=".$version->getVersion()."\">".getMLText("edit_comment")."</a></li>";
	print "</ul>";
}
else {
	print "&nbsp;";
}

echo "</td>";
print "</tr></tbody>\n</table>\n";


print "<table class=\"folderView\">\n";

if (is_array($reviewStatus) && count($reviewStatus)>0) {

	print "<tr><td colspan=4>\n";
	UI::contentSubHeading(getMLText("reviewers"));
	print "</td></tr>\n";
	
	print "<tr>\n";
	print "<td width='20%'><b>".getMLText("name")."</b></td>\n";
	print "<td width='20%'><b>".getMLText("last_update")."</b></td>\n";
	print "<td width='25%'><b>".getMLText("comment")."</b></td>";
	print "<td width='35%'><b>".getMLText("status")."</b></td>\n";
	print "</tr>\n";

	foreach ($reviewStatus as $r) {
		$required = null;
		switch ($r["type"]) {
			case 0: // Reviewer is an individual.
				$required = getUser($r["required"]);
				if (!is_object($required)) {
					$reqName = getMLText("unknown_user")." '".$r["required"]."'";
				}
				else {
					$reqName = $required->getFullName();
				}
				break;
			case 1: // Reviewer is a group.
				$required = getGroup($r["required"]);
				if (!is_object($required)) {
					$reqName = getMLText("unknown_group")." '".$r["required"]."'";
				}
				else {
					$reqName = $required->getName();
				}
				break;
		}
		print "<tr>\n";
		print "<td>".$reqName."</td>\n";
		print "<td><ul class=\"documentDetail\"><li>".$r["date"]."</li>";
		$updateUser = getUser($r["userID"]);
		print "<li>".(is_object($updateUser) ? $updateUser->getFullName() : "unknown user id '".$r["userID"]."'")."</li></ul></td>";
		print "<td>".$r["comment"]."</td>\n";
		print "<td>".getReviewStatusText($r["status"])."</td>\n";
		print "</tr>\n";
	}
}

if (is_array($approvalStatus) && count($approvalStatus)>0) {

	print "<tr><td colspan=4>\n";
	UI::contentSubHeading(getMLText("approvers"));
	print "</td></tr>\n";
		
	print "<tr>\n";
	print "<td width='20%'><b>".getMLText("name")."</b></td>\n";
	print "<td width='20%'><b>".getMLText("last_update")."</b></td>\n";
	print "<td width='25%'><b>".getMLText("comment")."</b></td>";
	print "<td width='35%'><b>".getMLText("status")."</b></td>\n";
	print "</tr>\n";

	foreach ($approvalStatus as $a) {
		$required = null;
		switch ($a["type"]) {
			case 0: // Approver is an individual.
				$required = getUser($a["required"]);
				if (!is_object($required)) {
					$reqName = getMLText("unknown_user")." '".$r["required"]."'";
				}
				else {
					$reqName = $required->getFullName();
				}
				break;
			case 1: // Approver is a group.
				$required = getGroup($a["required"]);
				if (!is_object($required)) {
					$reqName = getMLText("unknown_group")." '".$r["required"]."'";
				}
				else {
					$reqName = $required->getName();
				}
				break;
		}
		print "<tr>\n";
		print "<td>".$reqName."</td>\n";
		print "<td><ul class=\"documentDetail\"><li>".$a["date"]."</li>";
		$updateUser = getUser($a["userID"]);
		print "<li>".(is_object($updateUser) ? $updateUser->getFullName() : "unknown user id '".$a["userID"]."'")."</li></ul></td>";
		print "<td>".$a["comment"]."</td>\n";
		print "<td>".getApprovalStatusText($a["status"])."</td>\n";
		print "</tr>\n";
	}
}

print "</table>\n";

UI::contentContainerEnd();
UI::htmlEndPage();
?>
