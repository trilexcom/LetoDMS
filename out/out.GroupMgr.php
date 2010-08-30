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

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

$allUsers = getAllUsers();

if (is_bool($allUsers)) {
	UI::exitError(getMLText("admin_tools"),getMLText("internal_error"));
}

$groups = getAllGroups();

if (is_bool($groups)) {
	UI::exitError(getMLText("admin_tools"),getMLText("internal_error"));
}

UI::htmlStartPage(getMLText("admin_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");

?>
<script language="JavaScript">

function checkForm1(num) {
	msg = "";
	eval("var formObj = document.form" + num + "_1;");
	
	if (formObj.name.value == "") msg += "<?php printMLText("js_no_name");?>\n";
<?php
	if (isset($settings->_strictFormCheck) && $settings->_strictFormCheck) {
	?>
	if (formObj.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
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

function checkForm2(num) {
	msg = "";
	eval("var formObj = document.form" + num + "_2;");
	
	if (formObj.userid.options[formObj.userid.selectedIndex].value == -1) msg += "<?php printMLText("js_select_user");?>\n";

	if (msg != "")
	{
		alert(msg);
		return false;
	}
	else
		return true;
}

obj = -1;
function showUser(selectObj) {
	if (obj != -1)
		obj.style.display = "none";
	
	id = selectObj.options[selectObj.selectedIndex].value;
	if (id == -1)
		return;
	
	obj = document.getElementById("keywords" + id);
	obj.style.display = "";
}

</script>
<?php
UI::contentHeading(getMLText("group_management"));
UI::contentContainerStart();
?>

<table>
<tr>
<td>

<?php echo getMLText("selection")?>:<select onchange="showUser(this)" id="selector">
<option value="-1"><?php echo getMLText("choose_group")?>
<option value="0"><?php echo getMLText("add_group")?>
<?php
	$selected=0;
	$count=2;
	foreach ($groups as $group) {
		
		if (isset($_GET["groupid"]) && $group->getID()==$_GET["groupid"]) $selected=$count;
		print "<option value=\"".$group->getID()."\">" . $group->getName();
		$count++;
	}
?>
</select>
&nbsp;&nbsp;
</td>

	<td id="keywords0" style="display : none;">
	
	<form action="../op/op.GroupMgr.php" name="form0_1" onsubmit="return checkForm1('0');">
	<input type="Hidden" name="action" value="addgroup">
	<table>
		<tr>
			<td><?php printMLText("name");?>:</td>
			<td><input name="name"></td>
		</tr>
		<tr>
			<td><?php printMLText("comment");?>:</td>
			<td><textarea name="comment" rows="4" cols="50"></textarea></td>
		</tr>
		<tr>
			<td colspan="2"><input type="Submit" value="<?php printMLText("add_group");?>"></td>
		</tr>
	</table>
	</form>	
	
	</td>
	
	<?php	

	foreach ($groups as $group) {
	
		print "<td id=\"keywords".$group->getID()."\" style=\"display : none;\">";
		
		UI::contentSubHeading(getMLText("group")." : ".$group->getName());
		
	?>
	
	<a href="../out/out.RemoveGroup.php?groupid=<?php print $group->getID();?>"><img src="images/del.gif" width="15" height="15" border="0" align="absmiddle" alt=""> <?php printMLText("rm_group");?></a>


	<?php	UI::contentSubHeading(getMLText("edit_group"));?>
		
	
	<form action="../op/op.GroupMgr.php" name="form<?php print $group->getID();?>_1" onsubmit="return checkForm1('<?php print $group->getID();?>');">
	<input type="Hidden" name="groupid" value="<?php print $group->getID();?>">
	<input type="Hidden" name="action" value="editgroup">
	<table>
		<tr>
			<td><?php printMLText("name");?>:</td>
			<td><input name="name" value="<?php print $group->getName();?>"></td>
		</tr>
		<tr>
			<td><?php printMLText("comment");?>:</td>
			<td><textarea name="comment" rows="4" cols="50"><?php print $group->getComment();?></textarea></td>
		</tr>
		<tr>
			<td colspan="2"><input type="Submit" value="<?php printMLText("save");?>"></td>
		</tr>
	</table>
	</form>
	<?php
		UI::contentSubHeading(getMLText("group_members"));
		?>
		<table class="folderView">
		<?php
			$members = $group->getUsers();
			if (count($members) == 0)
				print "<tr><td>".getMLText("no_group_members")."</td></tr>";
			else {
			
				foreach ($members as $member) {
				
					print "<tr>";
					print "<td><img src=\"images/usericon.gif\" width=16 height=16></td>";
					print "<td>" . $member->getFullName() . "</td>";
					print "<td>" . ($group->isMember($member,true)?getMLText("manager"):"&nbsp;") . "</td>";
					print "<td align=\"right\"><ul class=\"actions\">";
					print "<li><a href=\"../op/op.GroupMgr.php?groupid=". $group->getID() . "&userid=".$member->getID()."&action=rmmember\">".getMLText("delete")."</a>";
					print "<li><a href=\"../op/op.GroupMgr.php?groupid=". $group->getID() . "&userid=".$member->getID()."&action=tmanager\">".getMLText("toggle_manager")."</a>";
					print "</td></tr>";
				}
			}
		?>
		</table>
		
		
		<?php
		
		UI::contentSubHeading(getMLText("add_member"));
		
		?>
		
		<form action="../op/op.GroupMgr.php" method=POST name="form<?php print $group->getID();?>_2" onsubmit="return checkForm2('<?php print $group->getID();?>');">
		<input type="Hidden" name="action" value="addmember">
		<input type="Hidden" name="groupid" value="<?php print $group->getID();?>">
		<table width="100%">
			<tr>
				<td>
					<select name="userid">
						<option value="-1"><?php printMLText("select_one");?>
						<?php
							foreach ($allUsers as $currUser)
								if (!$group->isMember($currUser))
									print "<option value=\"".$currUser->getID()."\">" . $currUser->getFullName() . "\n";
						?>
					</select>
				</td>
				<td>
					<input type="checkbox" name="manager" value="1"><?php printMLText("manager");?>
				</td>
				<td align="right">
					<input type="Submit" value="<?php printMLText("add");?>">
				</td>
			</tr>
		</table>
		</form>
	</td>
<?php  } ?>

</tr>
</table>

<script language="JavaScript">

sel = document.getElementById("selector");
sel.selectedIndex=<?php print $selected ?>;
showUser(sel);

</script>

<?php
UI::contentContainerEnd();

UI::htmlEndPage();
?>
