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

if ($document->getAccessMode($user) < M_ALL) {
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

$overallStatus = $content->getStatus();

// status change control
if ($overallStatus["status"] == S_REJECTED || $overallStatus["status"] == S_EXPIRED || $overallStatus["status"] == S_DRAFT_REV || $overallStatus["status"] == S_DRAFT_APP ) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("cannot_change_final_states"));
}

$reviewStatus = $content->getReviewStatus();
$approvalStatus = $content->getApprovalStatus();

UI::htmlStartPage(getMLText("document_title", array("documentname" => $document->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($docPathHTML, "view_document");

UI::contentHeading(getMLText("change_status"));

?>
<script language="JavaScript">
function checkForm()
{
	msg = "";
	if (document.form1.overrideStatus.value == "") msg += "<?php printMLText("js_no_override_status");?>\n";
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

// Display the Review form.
?>
<form method="POST" action="../op/op.OverrideContentStatus.php" name="form1" onsubmit="return checkForm();">
<table>
<tr><td><?php echo(printMLText("comment")); ?></td>
<td><textarea name="comment" cols="40" rows="4"></textarea>
</td></tr>
<tr><td><?php echo(printMLText("status")); ?></td>
<td><select name="overrideStatus">
<option value=''></option>
<?php

if ($overallStatus["status"] == S_OBSOLETE) echo "<option value='".S_RELEASED."'>".getOverallStatusText(S_RELEASED)."</option>";
if ($overallStatus["status"] == S_RELEASED) echo "<option value='".S_OBSOLETE."'>".getOverallStatusText(S_OBSOLETE)."</option>";

?>
</select>
</td></tr><tr><td></td><td>
<input type='hidden' name='documentid' value='<?php echo $documentid ?>'/>
<input type='hidden' name='version' value='<?php echo $version ?>'/>
<input type='submit' name='overrideContentStatus' value='<?php echo(printMLText("update")); ?>'/>
</td></tr></table>
</form>
<?php

UI::contentContainerEnd();

UI::htmlEndPage();
?>
