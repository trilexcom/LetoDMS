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

if ($document->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("document_title", array("documentname" => $document->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($docPathHTML, "view_document");

?>

<script language="JavaScript">
function checkForm()
{
	msg = "";
	if (document.form1.userfile.value == "") msg += "<?php printMLText("js_no_file");?>\n";
<?php
	if (isset($settings->_strictFormCheck) && $settings->_strictFormCheck) {
	?>
	if (document.form1.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
<?php
	}
?>
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
UI::contentHeading(getMLText("update_document") . ": " . $document->getName());
UI::contentContainerStart();

if ($document->isLocked()) {

	$lockingUser = $document->getLockingUser();
	print "<span class=\"warning\">";
	
	printMLText("update_locked_msg", array("username" => $lockingUser->getFullName(), "email" => $lockingUser->getEmail()));
	
	if ($lockingUser->getID() == $user->getID())
		printMLText("unlock_cause_locking_user");
	else if ($document->getAccessMode($user) == M_ALL)
		printMLText("unlock_cause_access_mode_all");
	else
	{
		printMLText("no_update_cause_locked");
		print "</span>";
		UI::contentContainerEnd();
		UI::htmlEndPage();
		exit;
	}
	print "</span>";
}

// Retrieve a list of all users and groups that have review / approve
// privileges.
$docAccess = $document->getApproversList();
?>

<form action="../op/op.UpdateDocument.php" enctype="multipart/form-data" method="post" name="form1" onsubmit="return checkForm();">
	<input type="Hidden" name="documentid" value="<?php print $documentid; ?>">
	<table>
		<tr>
			<td><?php printMLText("local_file");?>:</td>
			<td><input type="File" name="userfile" size="60"></td>
		</tr>
		<tr>
			<td><?php printMLText("comment");?>:</td>
			<td class="standardText">
				<textarea name="comment" rows="4" cols="80"></textarea>
			</td>
		</tr>
		<tr>
			<td><?php printMLText("expires");?>:</td>
			<td class="standardText">
				<input type="Radio" name="expires" value="false"<?php if (!$document->expires()) print " checked";?>><?php printMLText("does_not_expire");?><br>
				<input type="radio" name="expires" value="true"<?php if ($document->expires()) print " checked";?>><?php UI::printDateChooser(-1, "exp");?>
			</td>
		</tr>
		<tr>
			<td colspan=2>
			
				<?php UI::contentSubHeading(getMLText("assign_reviewers")); ?>

				<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
				<div class="cbSelectContainer cbSelectMargin">
				<ul class="cbSelectList">
<?php
				$res=$user->getMandatoryReviewers();

				foreach ($docAccess["users"] as $usr) {

					if ($usr->getID()==$user->getID()) continue; 

					$mandatory=false;
					foreach ($res as $r) if ($r['reviewerUserID']==$usr->getID()) $mandatory=true;

					if ($mandatory) print "<li class=\"cbSelectItem\"><input type='checkbox' checked='checked' disabled='disabled'>". $usr->getFullName();
					else print "<li class=\"cbSelectItem\"><input id='revInd".$usr->getID()."' type='checkbox' name='indReviewers[]' value='". $usr->getID() ."'>". $usr->getFullName();
				}
?>
				</ul>
				</div>
				<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
				<div class="cbSelectContainer">
				<ul class="cbSelectList">
<?php
				foreach ($docAccess["groups"] as $grp) {
				
					$mandatory=false;
					foreach ($res as $r) if ($r['reviewerGroupID']==$grp->getID()) $mandatory=true;	

					if ($mandatory) print "<li class=\"cbSelectItem\"><input type='checkbox' checked='checked' disabled='disabled'>".$grp->getName();
					else print "<li class=\"cbSelectItem\"><input id='revGrp".$grp->getID()."' type='checkbox' name='grpReviewers[]' value='". $grp->getID() ."'>".$grp->getName();
				}
?>
				</ul>
				</div>
					
				<?php UI::contentSubHeading(getMLText("assign_approvers")); ?>	
				
				<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
				<div class="cbSelectContainer cbSelectMargin">
				<ul class="cbSelectList">
<?php
				$res=$user->getMandatoryApprovers();

				foreach ($docAccess["users"] as $usr) {
				
					if ($usr->getID()==$user->getID()) continue; 

					$mandatory=false;
					foreach ($res as $r) if ($r['approverUserID']==$usr->getID()) $mandatory=true;
					
					if ($mandatory) print "<li class=\"cbSelectItem\"><input type='checkbox' checked='checked' disabled='disabled'>". $usr->getFullName();
					else print "<li class=\"cbSelectItem\"><input id='appInd".$usr->getID()."' type='checkbox' name='indApprovers[]' value='". $usr->getID() ."'>". $usr->getFullName();
				}
?>
				</ul>
				</div>
				<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
				<div class="cbSelectContainer">
				<ul class="cbSelectList">
<?php
				foreach ($docAccess["groups"] as $grp) {
				
					$mandatory=false;
					foreach ($res as $r) if ($r['approverGroupID']==$grp->getID()) $mandatory=true;	

					if ($mandatory) print "<li class=\"cbSelectItem\"><input type='checkbox' checked='checked' disabled='disabled'>".$grp->getName();
					else print "<li class=\"cbSelectItem\"><input id='appGrp".$grp->getID()."' type='checkbox' name='grpApprovers[]' value='". $grp->getID() ."'>".$grp->getName();

				}
?>
				</ul>
				</div>
			</td>
			</tr>
		<tr>
			<td colspan="2"><?php printMLText("add_doc_reviewer_approver_warning")?></td>
		</tr>
		<tr>
			<td colspan="2"><input type="Submit" value="<?php printMLText("update_document")?>"></td>
		</tr>
	</table>
</form>

<?php

UI::contentContainerEnd();
UI::htmlEndPage();
?>
