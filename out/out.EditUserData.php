<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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

if ($user->getID($user) == $settings->_guestID) {
	UI::exitError(getMLText("edit_user_details"),getMLText("access_denied"));
}

if (($user->getID($user) != $settings->_adminID) && ($settings->_disableSelfEdit)) {
	UI::exitError(getMLText("edit_user_details"),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("edit_user_details"));
UI::globalNavigation();
UI::pageNavigation(getMLText("my_account"), "my_account");

?>

<script language="JavaScript">

function checkForm()
{
	msg = "";
	if (document.form1.pwd.value != document.form1.pwdconf.value) msg += "<?php printMLText("js_pwd_not_conf");?>\n";
	if (document.form1.fullname.value == "") msg += "<?php printMLText("js_no_name");?>\n";
	// if (document.form1.email.value == "") msg += "<?php printMLText("js_no_email");?>\n";
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
UI::contentHeading(getMLText("edit_user_details"));
UI::contentContainerStart();
?>

<form action="../op/op.EditUserData.php" enctype="multipart/form-data" method="post" name="form1" onsubmit="return checkForm();">
<table>
	<tr>
		<td><?php printMLText("password");?>:</td>
		<td><input type="Password" name="pwd" size="30"></td>
	</tr>
	<tr>
		<td><?php printMLText("confirm_pwd");?>:</td>
		<td><input type="Password" name="pwdconf" size="30"></td>
	</tr>
	<tr>
		<td><?php printMLText("name");?>:</td>
		<td><input name="fullname" value="<?php print $user->getFullName();?>" size="30"></td>
	</tr>
	<tr>
		<td><?php printMLText("email");?>:</td>
		<td><input name="email" value="<?php print $user->getEmail();?>" size="30"></td>
	</tr>
	<tr>
		<td><?php printMLText("comment");?>:</td>
		<td><textarea name="comment" rows="4" cols="80"><?php print $user->getComment();?></textarea></td>
	</tr>

<?php	
if ($settings->_enableUserImage){	
?>	
	<tr>
		<td><?php printMLText("user_image");?>:</td>
		<td>
	<?php
	if ($user->hasImage())
		print "<img src=\"".$user->getImageURL()."\">";
	else printMLText("no_user_image");
	?>
		</td>
	</tr>
	<tr>
		<td><?php printMLText("new_user_image");?>:</td>
		<td><input type="file" name="userfile" accept="image/jpeg" size="30"></td>
	</tr>
<?php	} ?>

	<tr>
		<td colspan="2"><input type="Submit" value="<?php printMLText("update_info") ?>"></td>
	</tr>
</table>
</form>

<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
