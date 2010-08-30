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

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"]<1)) {
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
	if (document.form1.name.value == "") msg += "<?php printMLText("js_no_name");?>\n";
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
UI::contentHeading(getMLText("linked_files"));
UI::contentContainerStart();

?>
<form action="../op/op.AddFile.php" enctype="multipart/form-data" method="post" name="form1" onsubmit="return checkForm();">
<input type="Hidden" name="documentid" value="<?php print $documentid; ?>">
<table>
<tr>
	<td><?php printMLText("local_file");?>:</td>
	<td><input type="File" name="userfile" size="60"></td>
</tr>
<tr>
	<td><?php printMLText("name");?>:</td>
	<td><input name="name" size="60"></td>
</tr>
<tr>
	<td><?php printMLText("comment");?>:</td>
	<td><textarea name="comment" rows="4" cols="80"></textarea></td>
</tr>
</table>

	<p><input type="Submit" value="<?php printMLText("add");?>"></p>
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
