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
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}
$folderid = $_GET["folderid"];
$folder = getFolder($folderid);
if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

if ($folder->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("folder_title", array("foldername" => $folder->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($folderPathHTML, "view_folder", $folder);

?>
<script language="JavaScript">
function checkForm()
{
	msg = "";
	if (document.form1.userfile.value == "") msg += "<?php printMLText("js_no_file");?>\n";
	if (document.form1.name.value == "") msg += "<?php printMLText("js_no_name");?>\n";
<?php
	if (isset($settings->_strictFormCheck) && $settings->_strictFormCheck) {
	?>
	if (document.form1.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
	if (document.form1.keywords.value == "") msg += "<?php printMLText("js_no_keywords");?>\n";
<?php
	}
?>
	if (msg != ""){
		alert(msg);
		return false;
	}
	else return true;
}
</script>
<script language="JavaScript" src="../js/displayFunctions.js"></script>
<?php
UI::contentHeading(getMLText("add_document"));
UI::contentContainerStart();

// Retrieve a list of all users and groups that have review / approve
// privileges.
$docAccess = $folder->getApproversList();
?>
<form action="../op/op.AddDocument.php" enctype="multipart/form-data" method="post" name="form1" onsubmit="return checkForm();">
<input type="Hidden" name="folderid" value="<?php print $folderid; ?>">
<table>
<tr>
	<td><?php printMLText("sequence");?>:</td>
	<td><?php UI::printSequenceChooser($folder->getDocuments());?></td>
</tr>
<tr>
	<td><?php printMLText("local_file");?>:</td>
	<td><input type="File" name="userfile"></td>
</tr>
<tr>
	<td><?php printMLText("name");?>:</td>
	<td><input name="name"></td>
</tr>
<tr>
	<td><?php printMLText("comment");?>:</td>
	<td><textarea name="comment" rows="4" cols="30"></textarea></td>
</tr>
<tr>
	<td><?php printMLText("keywords");?>:</td>
	<td>
	<textarea name="keywords" rows="4" cols="30"></textarea><br>
	<a href="javascript:chooseKeywords();"><?php printMLText("use_default_keywords");?></a>
	<script language="JavaScript">
	var openDlg;

	function chooseKeywords() {
		openDlg = open("out.KeywordChooser.php", "openDlg", "width=500,height=400,scrollbars=yes,resizable=yes");
	}
	</script>
	</td>
</tr>
<tr>
	<td><?php printMLText("expires");?>:</td>
	<td>
	<input type="radio" name="expires" value="false" checked><?php printMLText("does_not_expire");?><br>
	<input type="radio" name="expires" value="true"><?php UI::printDateChooser(-1, "exp");?>
	</td>
</tr>
</table>
<dl>
	<dt><label for="assignDocReviewers"><input onChange="showBlock('docReviewers')" id="assignDocReviewers" type="checkbox" name="assignDocReviewers" value="1"><?php printMLText("assign_reviewers");?></label></dt>
	<dd id="docReviewers">
	<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
	<div class="cbSelectContainer">
	<ul class="cbSelectList">
<?php
	foreach ($docAccess["groups"] as $group) {
?>
		<li class="cbSelectItem"><?php echo "<label for='revGrp".$group->getID()."'><input id='revGrp".$group->getID()."' type='checkbox' name='grpReviewers[]' value='". $group->getID() ."'>".$group->getName()."</label>"; ?></li>
<?php
	}
?>
	</ul>
	</div>
	<div class="cbSelectTitle cbSelectMargin"><?php printMLText("individuals");?>:</div>
	<div class="cbSelectContainer cbSelectMargin">
	<ul class="cbSelectList">
<?php
	foreach ($docAccess["users"] as $user) {
?>
		<li class="cbSelectItem"><?php echo "<label for='revInd".$user->getID()."'><input id='revInd".$user->getID()."' type='checkbox' name='indReviewers[]' value='". $user->getID() ."'>". $user->getFullName()." &lt;".$user->getEmail().">"; ?></li>
<?php
	}
?>
	</ul>
	</div>
	<script language="JavaScript">if (!document.getElementById('assignDocReviewers').checked) hideBlock('docReviewers');</script>
	</dd>

	<dt><label for="assignDocApprovers"><input onChange="showBlock('docApprovers')" id="assignDocApprovers" type="checkbox" name="assignDocApprovers" value="1"><?php printMLText("assign_approvers");?></label></dt>
	<dd id="docApprovers">
	<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
	<div class="cbSelectContainer">
	<ul class="cbSelectList">
<?php
	foreach ($docAccess["groups"] as $group) {
?>
		<li class="cbSelectItem"><?php echo "<label for='appGrp".$group->getID()."'><input id='appGrp".$group->getID()."' type='checkbox' name='grpApprovers[]' value='". $group->getID() ."'>".$group->getName()."</label>"; ?></li>
<?php
	}
?>
	</ul>
	</div>
	<div class="cbSelectTitle cbSelectMargin"><?php printMLText("individuals");?>:</div>
	<div class="cbSelectContainer cbSelectMargin">
	<ul class="cbSelectList">
<?php
	foreach ($docAccess["users"] as $user) {
?>
		<li class="cbSelectItem"><?php echo "<label for='appInd".$user->getID()."'><input id='appInd".$user->getID()."' type='checkbox' name='indApprovers[]' value='". $user->getID() ."'>". $user->getFullName()." &lt;".$user->getEmail().">"; ?></li>
<?php
	}
?>
	</ul>
	</div>
	<script language="JavaScript">if (!document.getElementById('assignDocApprovers').checked) hideBlock('docApprovers');</script>
	</dd>
	</dl>
	<p><?php printMLText("add_doc_reviewer_approver_warning")?></p>
	<p><input type="Submit" value="<?php printMLText("add_document");?>"></p>
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
