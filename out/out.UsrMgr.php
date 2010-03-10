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


if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

$users = getAllUsers();
if (is_bool($users)) {
	UI::exitError(getMLText("admin_tools"),getMLText("internal_error"));
}

UI::htmlStartPage(getMLText("admin_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");

?>
<script language="JavaScript">

function checkForm(num)
{
	msg = "";
	eval("var formObj = document.form" + num + ";");
	
	if (formObj.login.value == "") msg += "<?php printMLText("js_no_login");?>\n";
	if ((num == '0') && (formObj.pwd.value == "")) msg += "<?php printMLText("js_no_pwd");?>\n";
	if (formObj.pwd.value != formObj.pwdconf.value) msg += "<?php printMLText("js_pwd_not_conf");?>\n";
	if (formObj.name.value == "") msg += "<?php printMLText("js_no_name");?>\n";
	if (formObj.email.value == "") msg += "<?php printMLText("js_no_email");?>\n";
	// if (formObj.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
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

UI::contentHeading(getMLText("edit_user"));
UI::contentContainerStart();
?>
<table>
	<tr>
		<td><?php echo getMLText("user_name")?>:</td>
		<td>
			<select onchange="showUser(this)">
				<option value="-1"><?php echo getMLText("choose_user")?>
				<?php
				foreach ($users as $currUser) {
					if (($currUser->getID() == $settings->_adminID) || ($currUser->getID() == $settings->_guestID))
						continue;
					
					print "<option value=\"".$currUser->getID()."\">" . $currUser->getFullName();
				}
				?>
			</select>
		</td>
	</tr>
	<?php
	foreach ($users as $currUser) {
		if (($currUser->getID() == $settings->_adminID) || ($currUser->getID() == $settings->_guestID))
			continue;
	?>
	<tr id="keywords<?php echo $currUser->getID()?>" style="display : none;">
	<td colspan=2>
	<form action="../op/op.UsrMgr.php" method="post" enctype="multipart/form-data" name="form<?php print $currUser->getID();?>" onsubmit="return checkForm('<?php print $currUser->getID();?>');">
	<input type="Hidden" name="userid" value="<?php print $currUser->getID();?>">
	<input type="Hidden" name="action" value="edituser">
	<table border="0" style="border-top: 1px solid #707277;">
		<tr>
			<td><?php printMLText("user_login");?>:</td>
			<td><input name="login" value="<?php print $currUser->getLogin();?>"></td>
		</tr>
		<tr>
			<td><?php printMLText("password");?>:</td>
			<td><input type="Password" name="pwd"></td>
		</tr>
		<tr>
			<td><?php printMLText("confirm_pwd");?>:</td>
			<td><input type="Password" name="pwdconf"></td>
		</tr>
		<tr>
			<td><?php printMLText("user_name");?>:</td>
			<td><input name="name" value="<?php print $currUser->getFullName();?>"></td>
		</tr>
		<tr>
			<td><?php printMLText("email");?>:</td>
			<td><input name="email" value="<?php print $currUser->getEmail();?>"></td>
		</tr>
		<tr>
			<td><?php printMLText("comment");?>:</td>
			<td><textarea name="comment" rows="4" cols="30"><?php print $currUser->getComment();?></textarea></td>
		</tr>
		<tr>
			<td><?php printMLText("is_admin");?>:</td>
		<td><input type="checkbox" name="isadmin" value="1"<?php print ($currUser->isAdmin() ? " checked='checked'" : "");?>></td>
		</tr>
		<tr>
			<td><?php printMLText("user_image");?>:</td>
			<td>
				<?php
					if ($currUser->hasImage())
						print "<img src=\"".$currUser->getImageURL()."\">";
					else
						printMLText("no_user_image");
				?>
			</td>
		</tr>
		<tr>
			<td><?php printMLText("new_user_image");?>:</td>
			<td><input type="file" name="userfile" accept="image/jpeg"></td>
		</tr>
		<tr>
			<td colspan="2"><input type="Submit" value="<?php printMLText("edit_user");?>"></td>
		</tr>
	</table>
	</form>
	<hr size="1" noshade style="border-top: 1px solid #707277;"/>
	<a class="standardText" href="../op/op.UsrMgr.php?userid=<?php print $currUser->getID();?>&action=removeuser"><img src="images/del.gif" width="15" height="15" border="0" align="absmiddle" alt=""> <?php printMLText("rm_user");?></a>
	</td>
	</tr>
<?php  } ?>
</table>

<?php
UI::contentContainerEnd();

UI::contentHeading(getMLText("add_user"));
UI::contentContainerStart();
?>

<form action="../op/op.UsrMgr.php" method="post" enctype="multipart/form-data" name="form0" onsubmit="return checkForm('0');">
	<input type="Hidden" name="action" value="adduser">
	<table>
		<tr>
			<td><?php printMLText("user_login");?>:</td>
			<td><input name="login"></td>
		</tr>
		<tr>
			<td><?php printMLText("password");?>:</td>
			<td><input name="pwd" type="Password"></td>
		</tr>
		<tr>
			<td><?php printMLText("confirm_pwd");?>:</td>
			<td><input type="Password" name="pwdconf"></td>
		</tr>
		<tr>
			<td><?php printMLText("user_name");?>:</td>
			<td><input name="name"></td>
		</tr>
		<tr>
			<td><?php printMLText("email");?>:</td>
			<td><input name="email"></td>
		</tr>
		<tr>
			<td><?php printMLText("comment");?>:</td>
			<td><textarea name="comment" rows="4" cols="30"></textarea></td>
		</tr>
		<tr>
			<td><?php printMLText("is_admin");?>:</td>
			<td><input type="checkbox" name="isadmin" value="1"></td>
		</tr>
		<tr>
			<td><?php printMLText("user_image");?>:</td>
			<td><input type="File" name="userfile"></td>
		</tr>
		<tr>
			<td colspan="2"><input type="Submit" value="<?php printMLText("add_user");?>"></td>
		</tr>
	</table>
</form>

<?php
UI::contentContainerEnd();

UI::htmlEndPage();
?>
