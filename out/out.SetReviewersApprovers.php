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

if ($document->getAccessMode($user) < M_ALL) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if (!isset($_GET["version"]) || !is_numeric($_GET["version"]) || intval($_GET["version"]<1)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$version = $_GET["version"];
$content = $document->getContentByVersion($version);
$overallStatus = $content->getStatus();

if (!is_object($content)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

// control for document state
if ($overallStatus["status"]==S_REJECTED || $overallStatus["status"]==S_OBSOLETE ) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("cannot_assign_invalid_state"));
}

UI::htmlStartPage(getMLText("document_title", array("documentname" => $document->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($docPathHTML, "view_document");
UI::contentHeading(getMLText("change_assignments"));

// Retrieve a list of all users and groups that have review / approve privileges.
$docAccess = $document->getApproversList();

// Retrieve list of currently assigned reviewers and approvers, along with
// their latest status.
$reviewStatus = $content->getReviewStatus();
$approvalStatus = $content->getApprovalStatus();

// Index the review results for easy cross-reference with the Approvers List.
$reviewIndex = array("i"=>array(), "g"=>array());
foreach ($reviewStatus as $i=>$rs) {
	if ($rs["type"]==0) {
		$reviewIndex["i"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
	}
	else if ($rs["type"]==1) {
		$reviewIndex["g"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
	}
}

// Index the approval results for easy cross-reference with the Approvers List.
$approvalIndex = array("i"=>array(), "g"=>array());
foreach ($approvalStatus as $i=>$rs) {
	if ($rs["type"]==0) {
		$approvalIndex["i"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
	}
	else if ($rs["type"]==1) {
		$approvalIndex["g"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
	}
}
?>

<?php UI::contentContainerStart(); ?>

<form action="../op/op.SetReviewersApprovers.php" method="post" name="form1">

<?php UI::contentSubHeading(getMLText("update_reviewers"));?>

<div class="cbSelectTitle cbSelectMargin"><?php printMLText("individuals")?>:</div>
<div class="cbSelectContainer cbSelectMargin">
<ul class="cbSelectList">
<?php

$res=$user->getMandatoryReviewers();

foreach ($docAccess["users"] as $usr) {

	$mandatory=false;
	foreach ($res as $r) if ($r['reviewerUserID']==$usr->getID()) $mandatory=true;
	
	if ($mandatory){

		print "<li class=\"cbSelectItem\"><input type='checkbox' checked='checked' disabled='disabled'>". $usr->getFullName()." &lt;".$usr->getEmail()."&gt;";
		print "<input id='revInd".$usr->getID()."' type='hidden' name='indReviewers[]' value='". $usr->getID() ."'>";

	}else if (isset($reviewIndex["i"][$usr->getID()])) {

		switch ($reviewIndex["i"][$usr->getID()]["status"]) {
			case 0:
				print "<li class=\"cbSelectItem\"><input id='revInd".$usr->getID()."' type='checkbox' name='indReviewers[]' value='". $usr->getID() ."' checked='checked'>".$usr->getFullName();
				break;
			case -2:
				print "<li class=\"cbSelectItem\"><input id='revInd".$usr->getID()."' type='checkbox' name='indReviewers[]' value='". $usr->getID() ."'>".$usr->getFullName();
				break;
			default:
				print "<li class=\"cbSelectItem\"><input id='revInd".$usr->getID()."' type='checkbox' name='indReviewers[]' value='". $usr->getID() ."' disabled='disabled'>".$usr->getFullName();
				break;
		}
	}
	else {
		print "<li class=\"cbSelectItem\"><input id='revInd".$usr->getID()."' type='checkbox' name='indReviewers[]' value='". $usr->getID() ."'>". $usr->getFullName();
	}
}
?>
</ul>
</div>
<div class="cbSelectTitle"><?php printMLText("groups")?>:</div>
<div class="cbSelectContainer">
<ul class="cbSelectList">
<?php
foreach ($docAccess["groups"] as $group) {

	$mandatory=false;
	foreach ($res as $r) if ($r['reviewerGroupID']==$group->getID()) $mandatory=true;
	
	if ($mandatory){

		print "<li class=\"cbSelectItem\"><input type='checkbox' checked='checked' disabled='disabled'>".$group->getName();
		print "<input id='revGrp".$group->getID()."' type='hidden' name='grpReviewers[]' value='". $group->getID() ."'>";

	}else if (isset($reviewIndex["g"][$group->getID()])) {

		switch ($reviewIndex["g"][$group->getID()]["status"]) {
			case 0:
				print "<li class=\"cbSelectItem\"><input id='revGrp".$group->getID()."' type='checkbox' name='grpReviewers[]' value='". $group->getID() ."' checked='checked'>".$group->getName();
				break;
			case -2:
				print "<li class=\"cbSelectItem\"><input id='revGrp".$group->getID()."' type='checkbox' name='grpReviewers[]' value='". $group->getID() ."'>".$group->getName();
				break;
			default:
				print "<li class=\"cbSelectItem\"><input id='revGrp".$group->getID()."' type='checkbox' name='grpReviewers[]' value='". $group->getID() ."' disabled='disabled'>".$group->getName();
				break;
		}
	}
	else {
		print "<li class=\"cbSelectItem\"><input id='revGrp".$group->getID()."' type='checkbox' name='grpReviewers[]' value='". $group->getID() ."'>".$group->getName();
	}
}
?>
</ul>
</div>

<?php UI::contentSubHeading(getMLText("update_approvers"));?>

<div class="cbSelectTitle cbSelectMargin"><?php printMLText("individuals")?>:</div>
<div class="cbSelectContainer cbSelectMargin">
<ul class="cbSelectList">
<?php

$res=$user->getMandatoryApprovers();

foreach ($docAccess["users"] as $usr) {

	$mandatory=false;
	foreach ($res as $r) if ($r['approverUserID']==$usr->getID()) $mandatory=true;

	if ($mandatory){
	
		print "<li class=\"cbSelectItem\"><input type='checkbox' checked='checked' disabled='disabled'>". $usr->getFullName()." &lt;".$usr->getEmail()."&gt;";
		print "<input id='appInd".$usr->getID()."' type='hidden' name='indApprovers[]' value='". $usr->getID() ."'>";

	}else if (isset($approvalIndex["i"][$usr->getID()])) {
	
		switch ($approvalIndex["i"][$usr->getID()]["status"]) {
			case 0:
				print "<li class=\"cbSelectItem\"><input id='appInd".$usr->getID()."' type='checkbox' name='indApprovers[]' value='". $usr->getID() ."' checked='checked'>".$usr->getFullName();
				break;
			case -2:
				print "<li class=\"cbSelectItem\"><input id='appInd".$usr->getID()."' type='checkbox' name='indApprovers[]' value='". $usr->getID() ."'>".$usr->getFullName();
				break;
			default:
				print "<li class=\"cbSelectItem\"><input id='appInd".$usr->getID()."' type='checkbox' name='indApprovers[]' value='". $usr->getID() ."' disabled='disabled'>".$usr->getFullName();
				break;
		}
	}
	else {
		print "<li class=\"cbSelectItem\"><input id='appInd".$usr->getID()."' type='checkbox' name='indApprovers[]' value='". $usr->getID() ."'>". $usr->getFullName();
	}
}
?>
</ul>
</div>
<div class="cbSelectTitle"><?php printMLText("groups")?>:</div>
<div class="cbSelectContainer">
<ul class="cbSelectList">
<?php
foreach ($docAccess["groups"] as $group) {

	$mandatory=false;
	foreach ($res as $r) if ($r['approverGroupID']==$group->getID()) $mandatory=true;

	if ($mandatory){

		print "<li class=\"cbSelectItem\"><input type='checkbox' checked='checked' disabled='disabled'>".$group->getName();
		print "<input id='appGrp".$group->getID()."' type='hidden' name='grpApprovers[]' value='". $group->getID() ."'>";

	}else if (isset($approvalIndex["g"][$group->getID()])) {

		switch ($approvalIndex["g"][$group->getID()]["status"]) {
			case 0:
				print "<li class=\"cbSelectItem\"><input id='appGrp".$group->getID()."' type='checkbox' name='grpApprovers[]' value='". $group->getID() ."' checked='checked'>".$group->getName();
				break;
			case -2:
				print "<li class=\"cbSelectItem\"><input id='appGrp".$group->getID()."' type='checkbox' name='grpApprovers[]' value='". $group->getID() ."'>".$group->getName();
				break;
			default:
				print "<li class=\"cbSelectItem\"><input id='appGrp".$group->getID()."' type='checkbox' name='grpApprovers[]' value='". $group->getID() ."' disabled='disabled'>".$group->getName();
				break;
		}
	}
	else {
		print "<li class=\"cbSelectItem\"><input id='appGrp".$group->getID()."' type='checkbox' name='grpApprovers[]' value='". $group->getID() ."'>".$group->getName();
	}
}
?>
</ul>
</div>

<p>
<input type='hidden' name='documentid' value='<?php echo $documentid ?>'/>
<input type='hidden' name='version' value='<?php echo $version ?>'/>
<input type="Submit" value="<?php printMLText("update");?>">
</p>
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
