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
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
	$folderid=$settings->_rootFolderID;
	$folder = getFolder($folderid);
}
else {
	$folderid = $_GET["folderid"];
	$folder = getFolder($folderid);
}
if (!is_object($folder)) {
	UI::exitError(getMLText("search"),getMLText("invalid_folder_id"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

UI::htmlStartPage(getMLText("search"));
UI::globalNavigation($folder);
UI::pageNavigation($folderPathHTML, "view_folder", $folder);

?>
<script language="JavaScript">
function checkForm()
{
	msg = "";
	if (document.form1.query.value == "")
	{
		if (!document.form1.creationdate.checked && !document.form1.lastupdate.checked &&
				!document.form1.pendingReview.checked && !document.form1.pendingApproval.checked)
			msg += "<?php printMLText("js_no_query");?>\n";
	}
	
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
UI::contentHeading(getMLText("search"));
UI::contentContainerStart();
?>
<form action="../op/op.Search.php" name="form1" onsubmit="return checkForm();">
<table>
<tr>
<td><?php printMLText("search_query");?>:</td>
<td>
<input name="query">
<select name="mode">
<option value="1" selected><?php printMLText("search_mode_and");?><br>
<option value="0"><?php printMLText("search_mode_or");?>
</select>
</td>
</tr>
<tr>
<td></td>
<td>
<ul class="actions">
<li class="first"><input type="checkbox" id="pendingReview" name="pendingReview" value="1"><label for='pendingReview'><?php printMLText("pending_review");?></label></li>
<li><input type="checkbox" id="pendingApproval" name="pendingApproval" value="1"><label for='pendingApproval'><?php printMLText("pending_approval");?></label></li>
</ul>
</td>
</tr>
<tr>
<td><?php printMLText("search_in");?>:</td>
<td><ul class="actions">
<li class="first"><input type="Checkbox" id="keywords" name="searchin[]" value="1" checked><label for="keywords"><?php printMLText("keywords");?></label></li>
<li><input type="Checkbox" name="searchin[]" id="searchName" value="2"><label for="searchName"><?php printMLText("name");?></label></li>
<li><input type="Checkbox" name="searchin[]" id="comment" value="3"><label for="comment"><?php printMLText("comment");?></label></li>
</ul>
</td>
</tr>
<tr>
<td><?php printMLText("owner");?>:</td>
<td>
<select name="ownerid">
<option value="-1"><?php printMLText("all_users");?>
<?php
$allUsers = getAllUsers();
foreach ($allUsers as $userObj)
{
	if ($userObj->getID() == $settings->_guestID)
		continue;
	print "<option value=\"".$userObj->getID()."\">" . $userObj->getFullName() . "\n";
}
?>
</select>
</td>
</tr>
<tr>
<td><?php printMLText("under_folder")?>:</td>
<td><?php UI::printFolderChooser("form1", M_READ, -1, $folder);?></td>
</tr>
<tr>
<td><?php printMLText("creation_date");?>:</td>
<td>
<input type="Checkbox" name="creationdate" value="true">
<?php
printMLText("between");
print "&nbsp;&nbsp;";
UI::printDateChooser(-1, "createstart");
print "&nbsp;&nbsp;";
printMLText("and");
print "&nbsp;&nbsp;";
UI::printDateChooser(-1, "createend");
?>
</td>
</tr>
<?php
/*
echo "<tr>\n<td>".getMLText("last_update").":</td>\n";
echo "<td><input type=\"Checkbox\" name=\"lastupdate\" value=\"true\">";
printMLText("between");
print "&nbsp;&nbsp;";
UI::printDateChooser(-1, "updatestart");
print "&nbsp;&nbsp;";
printMLText("and");
print "&nbsp;&nbsp;";
UI::printDateChooser(-1, "updateend");
echo "</td>\n</tr>\n";
*/
?>
<tr>
<td colspan="2"><input type="Submit" value="<?php printMLText("search"); ?>"></td>
</tr>

</table>

</form>

<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
