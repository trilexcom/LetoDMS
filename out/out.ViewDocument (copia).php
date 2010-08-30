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
include("../inc/inc.FileUtils.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");
include("../inc/inc.ClassEmail.php");

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_GET["documentid"];
$document = getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$folder = $document->getFolder();
$docPathHTML = getFolderPathHTML($folder, true). " / ".$document->getName();

if ($document->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if ($document->verifyLastestContentExpriry()){
	header("Location:../out/out.ViewDocument.php?documentid=".$documentid);
}

$versions = $document->getContent();
$latestContent = $document->getLatestContent();
$status = $latestContent->getStatus();
$reviewStatus = $latestContent->getReviewStatus();
$approvalStatus = $latestContent->getApprovalStatus();

// verify if file exists
$file_exists=file_exists($settings->_contentDir . $latestContent->getPath());

UI::htmlStartPage(getMLText("document_title", array("documentname" => $document->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($docPathHTML, "view_document");
UI::contentHeading(getMLText("document_infos"));
UI::contentContainerStart();

?>
<table>
<?php
if ($document->isLocked()) {
	$lockingUser = $document->getLockingUser();
?>
<tr>
	<td><?php printMLText("lock_status");?>:</td>
	<td class="warning"><?php printMLText("lock_message", array("email" => $lockingUser->getEmail(), "username" => $lockingUser->getFullName()));?></td>
</tr>
<?php
}
?>
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
</table>
<?php
UI::contentContainerEnd();

UI::contentHeading(getMLText("current_version"));
UI::contentContainerStart();
print "<table class=\"folderView\">";
print "<thead>\n<tr>\n";
print "<th></th>\n";
print "<th>".getMLText("version")."</th>\n";
print "<th>".getMLText("file")."</th>\n";
print "<th>".getMLText("comment")."</th>\n";
print "<th>".getMLText("status")."</th>\n";
print "<th></th>\n";
print "</tr></thead><tbody>\n";
print "<tr>\n";
print "<td><ul class=\"actions\">";

if ($file_exists){
	print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\"><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($latestContent->getFileType())."\" title=\"".$latestContent->getMimeType()."\">".getMLText("download")."</a></li>";
	if ($latestContent->viewOnline())
		print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&version=". $latestContent->getVersion()."\"><img src=\"images/view.gif\" class=\"mimeicon\">" . getMLText("view_online") . "</a></li>";
}else print "<li><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($latestContent->getFileType())."\" title=\"".$latestContent->getMimeType()."\"></li>";

print "</ul></td>\n";
print "<td>".$latestContent->getVersion()."</td>\n";

print "<td><ul class=\"documentDetail\">\n";
print "<li>".$latestContent->getOriginalFileName() ."</li>\n";

if ($file_exists)
	print "<li>". formatted_size(filesize($settings->_contentDir . $latestContent->getPath())) ." ".$latestContent->getMimeType()."</li>";
else print "<li>".$latestContent->getMimeType()." - <span class=\"warning\">".getMLText("document_deleted")."</span></li>";

$updatingUser = $latestContent->getUser();
print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".$updatingUser->getEmail()."\">".$updatingUser->getFullName()."</a> - ".getLongReadableDate($latestContent->getDate())."</li>";

print "</ul>\n";
print "<td>".$latestContent->getComment()."</td>";

print "<td><ul class=\"actions\"><li>".getOverallStatusText($status["status"]);
if ( $status["status"]==S_DRAFT_REV || $status["status"]==S_DRAFT_APP || $status["status"]==S_EXPIRED ){
	print "<li".($document->hasExpired()?" class=\"warning\" ":"").">".(!$document->getExpires() ? getMLText("does_not_expire") : getMLText("expires").": ".getReadableDate($document->getExpires()))."</li>";
}
print "</td>";

print "<td>";

print "<ul class=\"actions\">";
if (($document->getAccessMode($user) >= M_READWRITE) && (count($versions) > 1)) {
	print "<li><a href=\"out.RemoveVersion.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\">".getMLText("rm_version")."</a></li>";
}
if ($document->getAccessMode($user) == M_ALL) {
	if ( $status["status"]==S_RELEASED || $status["status"]==S_OBSOLETE ){
		print "<li><a href='../out/out.OverrideContentStatus.php?documentid=".$documentid."&version=".$latestContent->getVersion()."'>".getMLText("change_status")."</a></li>";
	}
	if ( $status["status"]==S_RELEASED || $status["status"]==S_DRAFT_REV || $status["status"]==S_DRAFT_APP ){
		print "<li><a href='../out/out.SetReviewersApprovers.php?documentid=".$documentid."&version=".$latestContent->getVersion()."'>".getMLText("change_assignments")."</a></li>";
	}
	if ( $status["status"]==S_DRAFT_REV || $status["status"]==S_DRAFT_APP || $status["status"]==S_EXPIRED ){
		print "<li><a href='../out/out.SetExpires.php?documentid=".$documentid."'>".getMLText("set_expiry")."</a></li>";
	}
}
if ($document->getAccessMode($user) >= M_READWRITE) {
	print "<li><a href=\"out.EditComment.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\">".getMLText("edit_comment")."</a></li>";
}

print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&vfile=1\">".getMLText("versioning_info")."</a></li>";	

//
// Display a link if the user is a reviewer or approver for this document.
//
$userRStat = $user->getReviewStatus($documentid, $latestContent->getVersion());
$userAStat = $user->getApprovalStatus($documentid, $latestContent->getVersion());
$is_reviewer = false;
$is_approver = false;

if (!is_bool($userRStat)) {
	if (count($userRStat["indstatus"])>0) {
		if ($userRStat["indstatus"][0]["status"]==0) {
			$is_reviewer = true;
		}
	}
	else {
		foreach ($userRStat["grpstatus"] as $grpstatus) {
			if ($grpstatus["status"]==0) {
				$is_reviewer = true;
				break;
			}
		}
	}
}
if (!is_bool($userAStat)) {
	if (count($userAStat["indstatus"])>0) {
		if ($userAStat["indstatus"][0]["status"]==0) {
			$is_approver = true;
		}
	}
	else {
		foreach ($userAStat["grpstatus"] as $grpstatus) {
			if ($grpstatus["status"]==0) {
				$is_approver = true;
				break;
			}
		}
	}
}

if ($is_reviewer && $status["status"]==S_DRAFT_REV) {
	print "<li><a href=\"../out/out.ReviewDocument.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\">".getMLText("submit_review")."</a></li>";
}
else if ($is_approver && $status["status"]==S_DRAFT_APP) {
	print "<li><a href=\"../out/out.ApproveDocument.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\">".getMLText("submit_approval")."</a></li>";
}

print "</ul>";
echo "</td>";
print "</tr></tbody>\n</table>\n";

print "<table class=\"folderView\">\n";

if (is_array($reviewStatus) && count($reviewStatus)>0) {

	UI::contentSubHeading(getMLText("reviewers"));
	
	//print "<table class=\"folderView\">\n";
	print "<thead>\n<tr>\n";
	print "<th>".getMLText("name")."</th>\n";
	print "<th>".getMLText("status")."</th>\n";
	print "<th>".getMLText("comment")."</th>";
	print "<th>".getMLText("last_update")."</th>\n";
	print "<th></th>\n";
	print "</tr>\n</thead>\n<tbody>\n";

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
					$reqName = "<i>".$required->getName()."</i>";
				}
				break;
		}
		print "<tr>\n";
		print "<td>".$reqName."</td>\n";
		print "<td>".getReviewStatusText($r["status"])."</td>\n";
		print "<td>".$r["comment"]."</td>\n";
		print "<td>".$r["date"];
		$updateUser = getUser($r["userID"]);
		print " - ".(is_object($updateUser) ? $updateUser->getFullName() : "unknown user id '".$r["userID"]."'");	
		print "<td><ul class=\"actions\">";
		if (($updateUser==$user)&&(($r["status"]==1)||($r["status"]==-1))&&(!$document->hasExpired()))
			print "<li><a href=\"../out/out.ReviewDocument.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\">".getMLText("edit")."</a></li>";
		print "</ul></td>\n";	
		print "</td>\n</tr>\n";
	}
	print	"</tbody>\n</table>\n";
	print	"</tbody>\n";
}

if (is_array($approvalStatus) && count($approvalStatus)>0) {

	UI::contentSubHeading(getMLText("approvers"));

	//print "<table class=\"folderView\">\n";
	print "<thead>\n<tr>\n";
	print "<th>".getMLText("name")."</th>\n";
	print "<th>".getMLText("status")."</th>\n";
	print "<th>".getMLText("comment")."</th>";
	print "<th>".getMLText("last_update")."</th>\n";	
	print "<th></th>\n";
	print "</tr>\n</thead>\n<tbody>\n";

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
					$reqName = "<i>".$required->getName()."</i>";
				}
				break;
		}
		print "<tr>\n";
		print "<td>".$reqName."</td>\n";
		print "<td>".getApprovalStatusText($a["status"])."</td>\n";
		print "<td>".$a["comment"]."</td>\n";
		print "<td>".$a["date"];
		$updateUser = getUser($a["userID"]);
		print " - ".(is_object($updateUser) ? $updateUser->getFullName() : "unknown user id '".$a["userID"]."'");	
		print "<td><ul class=\"actions\">";
		if (($updateUser==$user)&&(($a["status"]==1)||($a["status"]==-1))&&(!$document->hasExpired()))
			print "<li><a href=\"../out/out.ApproveDocument.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\">".getMLText("edit")."</a></li>";
		print "</ul></td>\n";	
		print "</td>\n</tr>\n";
	}
	//print "</tbody>\n</table>\n";
	print "</tbody>\n";
}

print "</table>\n";

UI::contentContainerEnd();

UI::contentHeading(getMLText("previous_versions"));
UI::contentContainerStart();

if (count($versions)>1) {

	print "<table class=\"folderView\">";
	print "<thead>\n<tr>\n";
	print "<th></th>\n";
	print "<th>".getMLText("version")."</th>\n";
	print "<th>".getMLText("file")."</th>\n";
	print "<th>".getMLText("comment")."</th>\n";
	print "<th>".getMLText("status")."</th>\n";
	print "<th></th>\n";
	print "</tr>\n</thead>\n<tbody>\n";

	for ($i = count($versions)-2; $i >= 0; $i--) {
		$version = $versions[$i];
		$vstat = $version->getStatus();
		$comment = $version->getComment();
		
		// verify if file exists
		$file_exists=file_exists($settings->_contentDir . $version->getPath());
		
		print "<tr>\n";
		print "<td><ul class=\"actions\">";
		if ($file_exists){
			print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&version=".$version->getVersion()."\"><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($version->getFileType())."\" title=\"".$version->getMimeType()."\">".getMLText("download")."</a>";
			if ($version->viewOnline())
				print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&version=".$version->getVersion()."\"><img src=\"images/view.gif\" class=\"mimeicon\">" . getMLText("view_online") . "</a>";
		}else print "<li><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($version->getFileType())."\" title=\"".$version->getMimeType()."\">";
		
		print "</ul></td>\n";
		print "<td>".$version->getVersion()."</td>\n";
		print "<td><ul class=\"documentDetail\">\n";
		print "<li>".$version->getOriginalFileName()."</li>\n";
		if ($file_exists) print "<li>". formatted_size(filesize($settings->_contentDir . $version->getPath())) ." ".$version->getMimeType()."</li>";
		else print "<li>". $version->getMimeType()." - <span class=\"warning\">".getMLText("document_deleted")."</span></li>";
		$updatingUser = $version->getUser();
		print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".$updatingUser->getEmail()."\">".$updatingUser->getFullName()."</a> - ".getLongReadableDate($version->getDate())."</li>";
		print "</ul>\n";
		print "<td>".$version->getComment()."</td>";
		print "<td>".getOverallStatusText($vstat["status"])."</td>";
		print "<td>";
		print "<ul class=\"actions\">";
		if (($document->getAccessMode($user) == M_ALL) && (count($versions) > 1)) {
			print "<li><a href=\"out.RemoveVersion.php?documentid=".$documentid."&version=".$version->getVersion()."\">".getMLText("rm_version")."</a></li>";
		}
		print "<li><a href='../out/out.DocumentVersionDetail.php?documentid=".$documentid."&version=".$version->getVersion()."'>".getMLText("details")."</a></li>";
		print "</ul>";
		print "</td>\n</tr>\n";
	}
	print "</tbody>\n</table>\n";
}
else printMLText("empty_notify_list");

UI::contentContainerEnd();

UI::contentHeading(getMLText("linked_files"));
UI::contentContainerStart();

$files = $document->getDocumentFiles();

if (count($files) > 0) {

	print "<table class=\"folderView\">";
	print "<thead>\n<tr>\n";
	print "<th></th>\n";
	print "<th>".getMLText("name")."</th>\n";
	print "<th>".getMLText("file")."</th>\n";
	print "<th>".getMLText("comment")."</th>\n";
	print "<th></th>\n";
	print "</tr>\n</thead>\n<tbody>\n";

	foreach($files as $file) {
	
		$file_exists=file_exists($settings->_contentDir . $file->getPath());
		
		$responsibleUser = $file->getUser();

		print "<tr>";
		print "<td><ul class=\"actions\">";
		if ($file_exists)
			print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$file->getID()."\"><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($file->getFileType())."\" title=\"".$file->getMimeType()."\">".getMLText("download")."</a>";
		else print "<li><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($file->getFileType())."\" title=\"".$file->getMimeType()."\">";
		print "</ul></td>";

		print "<td>".$file->getName()."</td>";
		
		print "<td><ul class=\"documentDetail\">\n";
		print "<li>".$file->getOriginalFileName() ."</li>\n";
		if ($file_exists)
			print "<li>". filesize($settings->_contentDir . $file->getPath()) ." bytes ".$file->getMimeType()."</li>";
		else print "<li>".$file->getMimeType()." - <span class=\"warning\">".getMLText("document_deleted")."</span></li>";

		print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".$responsibleUser->getEmail()."\">".$responsibleUser->getFullName()."</a> - ".getLongReadableDate($file->getDate())."</li>";

		print "<td>".$file->getComment()."</td>";
	
		print "<td><span class=\"actions\">";
		if (($document->getAccessMode($user) == M_ALL)||($file->getUserID()==$user->getID()))
			print "<a href=\"../out/out.RemoveDocumentFile.php?documentid=".$documentid."&fileid=".$file->getID()."\">".getMLText("delete")."</a>";
		print "</span></td>";		
		
		print "</tr>";
	}
	print "</tbody>\n</table>\n";	

}
else printMLText("empty_notify_list");

if ($document->getAccessMode($user) >= M_READWRITE)
	print "<ul class=\"actions\"><li><a href=\"../out/out.AddFile.php?documentid=".$documentid."\">".getMLText("add")."</a></ul>\n";

UI::contentContainerEnd();


UI::contentHeading(getMLText("linked_documents"));
UI::contentContainerStart();
$links = $document->getDocumentLinks();
$links = filterDocumentLinks($user, $links);

if (count($links) > 0) {

	print "<table class=\"folderView\">";
	print "<thead>\n<tr>\n";
	print "<th>".getMLText("name")."</th>\n";
	print "<th>".getMLText("comment")."</th>\n";
	print "<th>".getMLText("document_link_by")."</th>\n";
	print "<th>".getMLText("document_link_public")."</th>\n";
	print "</tr>\n</thead>\n<tbody>\n";

	foreach($links as $link) {
		$responsibleUser = $link->getUser();
		$targetDoc = $link->getTarget();

		print "<tr>";
		print "<td><a href=\"out.ViewDocument.php?documentid=".$targetDoc->getID()."\" class=\"linklist\">".$targetDoc->getName()."</a></td>";
		print "<td>".$targetDoc->getComment()."</td>";
		print "<td>".$responsibleUser->getFullName()."</td>";
		print "<td>" . (($link->isPublic()) ? getMLText("yes") : getMLText("no")) . "</td>";
		print "<td><span class=\"actions\">";
		if (($user->getID() == $responsibleUser->getID()) || ($document->getAccessMode($user) == M_ALL ))
			print "<a href=\"../op/op.RemoveDocumentLink.php?documentid=".$documentid."&linkid=".$link->getID()."\">".getMLText("delete")."</a>";
		print "</span></td>";
		print "</tr>";
	}
	print "</tbody>\n</table>\n";
}
else printMLText("empty_notify_list");

if ($user->getID() != $settings->_guestID){
?>
	<form action="../op/op.AddDocumentLink.php" name="form1">
	<input type="Hidden" name="documentid" value="<?php print $documentid;?>">
	<table>
	<tr>
	<td><?php printMLText("add_document_link");?>:</td>
	<td><?php UI::printDocumentChooser("form1");?></td>
	</tr>
	<?php
	if ($document->getAccessMode($user) >= M_READWRITE) {
		print "<tr><td>".getMLText("document_link_public")."</td>";
		print "<td><ul class=\"actions\">";
		print "<li><input type=\"Radio\" name=\"public\" value=\"true\" checked>" . getMLText("yes")."</li>";
		print "<li><input type=\"Radio\" name=\"public\" value=\"false\">" . getMLText("no")."</li>";
		print "</ul></td></tr>";
	}
	?>
	<tr>
	<td colspan="2"><input type="Submit" value="<?php printMLText("update");?>"></td>
	</tr>
	</table>
	</form>
<?php
}
UI::contentContainerEnd();

UI::htmlEndPage();
?>
