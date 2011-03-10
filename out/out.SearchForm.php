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
include("../inc/inc.Utils.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
	$folderid=$settings->_rootFolderID;
	$folder = $dms->getFolder($folderid);
}
else {
	$folderid = $_GET["folderid"];
	$folder = $dms->getFolder($folderid);
}
if (!is_object($folder)) {
	UI::exitError(getMLText("search"),getMLText("invalid_folder_id"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

UI::htmlStartPage(getMLText("search"));
UI::globalNavigation($folder);
UI::pageNavigation($folderPathHTML, "", $folder);

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
<div style="width: 35%; float: left;">
<h2><?= getMLText('databasesearch') ?></h2>
<form action="../op/op.Search.php" name="form1" onsubmit="return checkForm();">
<table class="searchform">
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
<td><?php printMLText("search_in");?>:</td>
<td><ul class="actions">
<li class="first"><input type="Checkbox" id="keywords" name="searchin[]" value="1"><label for="keywords"><?php printMLText("keywords");?></label></li>
<li><input type="Checkbox" name="searchin[]" id="searchName" value="2"><label for="searchName"><?php printMLText("name");?></label></li>
<li><input type="Checkbox" name="searchin[]" id="comment" value="3"><label for="comment"><?php printMLText("comment");?></label></li>
</ul>
</td>
</tr>
<tr>
<td><?php printMLText("category");?>:</td>
<td>
<select name="categoryids[]" multiple>
<option value="-1"><?php printMLText("all_categories");?>
<?php
$allCats = $dms->getDocumentCategories();
foreach ($allCats as $catObj) {
	print "<option value=\"".$catObj->getID()."\">" . $catObj->getName() . "\n";
}
?>
</select>
</td>
</tr>
<tr>
<td><?php printMLText("status");?>:</td>
<td>
<ul class="actions">
<li class="first"><input type="checkbox" id="pendingReview" name="pendingReview" value="1"><label for='pendingReview'><?php printOverallStatusText(S_DRAFT_REV);?></label></li>
<li><input type="checkbox" id="pendingApproval" name="pendingApproval" value="1"><label for='pendingApproval'><?php printOverallStatusText(S_DRAFT_APP);?></label></li>
<li><input type="checkbox" id="released" name="released" value="1"><label for='released'><?php printOverallStatusText(S_RELEASED);?></label></li>
<li><input type="checkbox" id="rejected" name="rejected" value="1"><label for='rejected'><?php printOverallStatusText(S_REJECTED);?></label></li>
<li><input type="checkbox" id="obsolete" name="obsolete" value="1"><label for='obsolete'><?php printOverallStatusText(S_OBSOLETE);?></label></li>
<li><input type="checkbox" id="expired" name="expired" value="1"><label for='expired'><?php printOverallStatusText(S_EXPIRED);?></label></li>
</ul>
</td>
</tr>
<tr>
<td><?php printMLText("owner");?>:</td>
<td>
<select name="ownerid">
<option value="-1"><?php printMLText("all_users");?>
<?php
$allUsers = $dms->getAllUsers();
foreach ($allUsers as $userObj)
{
	if ($userObj->isGuest())
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
</div>
<?php
	if($settings->_enableFullSearch) {
?>
<div style="width: 35%; float: left; margin-left: 20px;">
<form action="../op/op.SearchFulltext.php" name="form2" onsubmit="return checkForm();">
<table class="searchform">
<h2><?= getMLText('fullsearch') ?></h2>
<tr>
<td><?php printMLText("search_query");?>:</td>
<td>
<input name="query">
<!--
<select name="mode">
<option value="1" selected><?php printMLText("search_mode_and");?><br>
<option value="0"><?php printMLText("search_mode_or");?>
</select>
-->
</td>
</tr>
<tr>
<td><?php printMLText("category_filter");?>:</td>
<td>
<select name="categoryids[]" multiple>
<!--
<option value="-1"><?php printMLText("all_categories");?>
-->
<?php
$allCats = $dms->getDocumentCategories();
foreach ($allCats as $catObj) {
	print "<option value=\"".$catObj->getID()."\">" . $catObj->getName() . "\n";
}
?>
</select>
</td>
</tr>
<tr>
<td><?php printMLText("owner");?>:</td>
<td>
<select name="ownerid">
<option value="-1"><?php printMLText("all_users");?>
<?php
$allUsers = $dms->getAllUsers();
foreach ($allUsers as $userObj)
{
	if ($userObj->isGuest())
		continue;
	print "<option value=\"".$userObj->getID()."\">" . $userObj->getFullName() . "\n";
}
?>
</select>
</td>
</tr>
<tr>
<td colspan="2"><input type="Submit" value="<?php printMLText("search"); ?>"></td>
</tr>
</table>

</form>
</div>
<div style="clear: both"></div>
<?php
	}
?>

<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
