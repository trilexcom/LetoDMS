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
$content = $document->getContentByVersion($version);
if (!is_object($content)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

// verify document is waiting for approval
$document->verifyLastestContentExpriry();
$status = $content->getStatus();
if ($status["status"]!=S_DRAFT_APP) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("document_title", array("documentname" => $document->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($docPathHTML, "view_document");
UI::contentHeading(getMLText("submit_approval"));

?>
<script language="JavaScript">
function checkIndForm()
{
	msg = "";
	if (document.form1.approvalStatus.value == "") msg += "<?php printMLText("js_no_approval_status");?>\n";
	if (document.form1.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
	if (msg != "")
	{
		alert(msg);
		return false;
	}
	else
		return true;
}
function checkGrpForm()
{
	msg = "";
	if (document.form1.approvalGroup.value == "") msg += "<?php printMLText("js_no_approval_group");?>\n";
	if (document.form1.approvalStatus.value == "") msg += "<?php printMLText("js_no_approval_status");?>\n";
	if (document.form1.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
	if (msg != "")
	{
		alert(msg);
		return false;
	}
	else
		return true;
}
</script>

<?php

UI::contentContainerStart();

// retrieve the approval status for the current user.
$approvalStatus = $user->getApprovalStatus($documentid, $version);
if (count($approvalStatus["indstatus"]) == 0 && count($approvalStatus["grpstatus"]) == 0) {
	UI::contentSubHeading(getMLText("warning"));
	printMLText("not_approver");
	UI::contentContainerEnd();
	UI::htmlEndPage();
	exit;
}

$indApprover = true;
if (count($approvalStatus["indstatus"])==0){
	$indApprover = false;
}
else if ($approvalStatus["indstatus"][0]["status"]==-2) {
	UI::contentSubHeading(getMLText("warning"));
	print "<dl><dt>".getMLText("user_removed_approver")."</dt>";
	$indUser=getUser($approvalStatus["indstatus"][0]["userID"]);
	print "<dd><ul class=\"actions\"><li class=\"first\">".getMLText("status_changed_by")." ".$indUser->getFullName()." <".$indUser->getEmail().">, ". $approvalStatus["indstatus"][0]["date"] .".</li>";
	print "<li>".getMLText("comment").": ". $approvalStatus["indstatus"][0]["comment"] ."</li></ul></dd></dl>";
	$indApprover = false;
}

$grpApprover=false;
foreach ($approvalStatus["grpstatus"] as $grpStatus) {
	if ($grpStatus["status"]==-2) {
		UI::contentSubHeading(getMLText("warning"));
		$g=getGroup($grpStatus["required"]);
		print "<dl><dt>".getMLText("group")." ". $g->getName() ." ".getMLText("removed_approver").".</dt>";
		$u=getUser($grpStatus["userID"]);
		print "<dd><ul class=\"actions\"><li class=\"first\">".getMLText("status_changed_by")." ".$u->getFullName()." <".$u->getEmail().">, ". $grpStatus["date"] .".</li>";
		print "<li>".getMLText("comment").": ". $grpStatus["comment"] ."</li></ul></dd></dl>";
	}
	else {
		if (isset($grpStatus["status"])) {
			$grpApprover=true;
		}
	}
}

if (!$indApprover && !$grpApprover) {
	UI::contentSubHeading(getMLText("warning"));
	printMLText("not_approver");
	UI::contentContainerEnd();
	UI::htmlEndPage();
	exit;
}

// Display the Approval form.
if ($indApprover) {
	if($approvalStatus["indstatus"][0]["status"]!=0) {
		print getMLText("user_already_approved").":";
		print "<table><thead><tr>";
		print "<th>".getMLText("status")."</th>";
		print "<th>".getMLText("comment")."</th>";
		print "<th>".getMLText("last_update")."</th>";
		print "</tr></thead><tbody><tr>";
		print "<td>";
		printApprovalStatusText($approvalStatus["indstatus"][0]["status"]);
		print "</td>";
		print "<td>".$approvalStatus["indstatus"][0]["comment"]."</td>";
		$indUser = getUser($approvalStatus["indstatus"][0]["userID"]);
		print "<td>".$approvalStatus["indstatus"][0]["date"]." by ". $indUser->getFullname() ."</td>";
		print "</tr></tbody></table>";
	}
	else {
?>
		<form method="POST" action="../op/op.ApproveDocument.php" name="form1" onsubmit="return checkIndForm();">
		<table>
		<tr><td><?php printMLText("comment")?>:</td>
		<td><textarea name="comment" cols="40" rows="4"></textarea>
		</td></tr>
		<tr><td><?php printMLText("approval_status")?>:</td>
		<td><select name="approvalStatus">
		<option value=''></option>
		<option value='1'><?php printMLText("status_approved")?></option>
		<option value='-1'><?php printMLText("rejected")?></option>
		</select>
		</td></tr><tr><td></td><td>
		<input type='hidden' name='approvalType' value='ind'/>
		<input type='hidden' name='documentid' value='<?php echo $documentid ?>'/>
		<input type='hidden' name='version' value='<?php echo $version ?>'/>
		<input type='submit' name='indApproval' value='<?php printMLText("submit_approval")?>'/>
		</td></tr></table>
		</form>
<?php
	}
}
else if ($grpApprover) {
	$grpSelectBox = "";
	foreach ($approvalStatus["grpstatus"] as $grp) {
		if ($grp["status"]!=-2) {
			$g=getGroup($grpStatus["required"]);
			if ($grp["status"] == 0) {
				$grpSelectBox .= (strlen($grpSelectBox)==0 ? "": "<option value=''></option>").
					"<option value='". $grpStatus["required"] ."'>". $g->getName() ."</option>";
			}
		}
	}
	if (strlen($grpSelectBox)>0) {
?>
	<form method="POST" action="../op/op.ApproveDocument.php" name="form1" onsubmit="return checkGrpForm();">
	<table>
	<tr><td><?php printMLText("comment")?>:</td>
	<td><textarea name="comment" cols="40" rows="4"></textarea>
	</td></tr>
	<tr><td><?php printMLText("approval_group")?>:</td>
	<td class='infos' valign='top'><select name="approvalGroup"><?php print $grpSelectBox; ?></select>
	</td></tr>
	<tr><td><?php printMLText("approval_status")?>:</td>
	<td>
	<select name="approvalStatus">
	<option value=''></option>
	<option value='1'><?php printMLText("status_approved")?></option>
	<option value='-1'><?php printMLText("rejected")?></option>
	</select>
	</td></tr>
	<tr><td></td><td>
	<input type='hidden' name='approvalType' value='grp'/>
	<input type='hidden' name='documentid' value='<?php echo $documentid ?>'/>
	<input type='hidden' name='version' value='<?php echo $version ?>'/>
	<input type='submit' name='groupApproval' value='<?php printMLText("submit_approval")?>'/></td></tr>
	</table>
	</form>
<?php
	}
	else {
?>
	<p><?php printMLText("user_approval_not_required")?></p>
<?php
	}
}

UI::contentContainerEnd();


UI::htmlEndPage();
?>
