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
include("../inc/inc.Utils.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}
$documentid = $_GET["documentid"];
$document = $dms->getDocument($documentid);

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
	if (document.form1.name.value == "") msg += "<?php printMLText("js_no_name");?>\n";
<?php
	if (isset($settings->_strictFormCheck) && $settings->_strictFormCheck) {
	?>
	if (document.form1.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
	if (document.form1.keywords.value == "") msg += "<?php printMLText("js_no_keywords");?>\n";
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
UI::contentHeading(getMLText("edit_document_props") . ": " . $document->getName());
UI::contentContainerStart();
?>
<form action="../op/op.EditDocument.php" name="form1" onsubmit="return checkForm();" method="POST">
	<input type="hidden" name="documentid" value="<?= $documentid ?>">
	<table cellpadding="3">
		<tr>
			<td class="inputDescription"><?php printMLText("name");?>:</td>
			<td><input name="name" value="<?php print $document->getName();?>" size="60"></td>
		</tr>
		<tr>
			<td valign="top" class="inputDescription"><?php printMLText("comment");?>:</td>
			<td><textarea name="comment" rows="4" cols="80"><?php print $document->getComment();?></textarea></td>
		</tr>
		<tr>
			<td valign="top" class="inputDescription"><?php printMLText("keywords");?>:</td>
			<td class="standardText">
				<textarea name="keywords" rows="2" cols="80"><?php print $document->getKeywords();?></textarea><br>
				<a href="javascript:chooseKeywords('form1.keywords');"><?php printMLText("use_default_keywords");?></a>
				<script language="JavaScript">
					var openDlg;
					
					function chooseKeywords(target) {
						openDlg = open("out.KeywordChooser.php?target="+target, "openDlg", "width=500,height=400,scrollbars=yes,resizable=yes");
					}
				</script>
			</td>
		</tr>
		<tr>
			<td><?php printMLText("categories")?>:</td>
			<td><?php UI::printCategoryChooser("form1", $document->getCategories());?></td>
		</tr>
		<?php
			if ($folder->getAccessMode($user) > M_READ)
			{
				print "<tr>";
				print "<td class=\"inputDescription\">" . getMLText("sequence") . ":</td>";
				print "<td>";
				UI::printSequenceChooser($folder->getDocuments(), $document->getID());
				print "</td></tr>";
			}
		?>
		<tr>
			<td colspan="2"><br><input type="Submit" value="<?php printMLText("save") ?>"></td>
		</tr>
	</table>
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
