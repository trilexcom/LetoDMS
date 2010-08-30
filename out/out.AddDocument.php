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


function addFiles()
{
	document.getElementById("files").innerHTML += '<br><input type="File" name="userfile[]" size="60">'; 
	document.form1.name.disabled=true;
}

</script>

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
	<td><?php printMLText("version");?>:</td>
	<td><input name="reqversion" value="1"></td>
</tr>
<tr>
	<td><?php printMLText("local_file");?>:</td>
	<td>
	<a href="javascript:addFiles()"><?php printMLtext("add_multiple_files") ?></a>
	<div id="files">
	<input type="File" name="userfile[]" size="60">
	</div>
	</td>
</tr>
<tr>
	<td><?php printMLText("name");?>:</td>
	<td><input name="name" size="60"></td>
</tr>
<tr>
	<td><?php printMLText("comment");?>:</td>
	<td><textarea name="comment" rows="4" cols="80"></textarea></td>
</tr>
<tr>
	<td><?php printMLText("keywords");?>:</td>
	<td>
	<textarea name="keywords" rows="2" cols="80"></textarea><br>
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

<?php UI::contentSubHeading(getMLText("assign_reviewers")); ?>

	<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
	<div class="cbSelectContainer">
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
	<div class="cbSelectContainer">
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

	<p><?php printMLText("add_doc_reviewer_approver_warning")?></p>
	<p><input type="Submit" value="<?php printMLText("add_document");?>"></p>
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
