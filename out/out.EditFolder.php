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
UI::contentHeading(getMLText("edit_folder_props"));
UI::contentContainerStart();
?>
<form action="../op/op.EditFolder.php" name="form1" onsubmit="return checkForm();" method="POST">
<input type="Hidden" name="folderid" value="<?php print $folderid;?>">
<table>
<tr>
<td><?php printMLText("name");?>:</td>
<td><input name="name" value="<?php print $folder->getName();?>"></td>
</tr>
<tr>
<td><?php printMLText("comment");?>:</td>
<td><textarea name="comment" rows="4" cols="30"><?php print $folder->getComment();?></textarea></td>
</tr>
<?php
$parent = ($folder->getID() == $settings->_rootFolderID) ? false : $folder->getParent();
if ($parent && $parent->getAccessMode($user) > M_READ) {
	print "<tr>";
	print "<td>" . getMLText("sequence") . ":</td>";
	print "<td>";
	UI::printSequenceChooser($parent->getSubFolders(), $folder->getID());
	print "</td></tr>\n";
}
?>
<tr>
<td colspan="2"><input type="Submit" value="<?php printMLText("save"); ?>"></td>
</tr>
</table>
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
