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
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

$users = $dms->getAllUsers();

if (is_bool($users)) {
	UI::exitError(getMLText("admin_tools"),getMLText("internal_error"));
}

$groups = $dms->getAllGroups();

if (is_bool($groups)) {
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
	if ((formObj.pwd.value != formObj.pwdconf.value)&&(formObj.pwd.value != "" )&&(formObj.pwd.value != "" )) msg += "<?php printMLText("js_pwd_not_conf");?>\n";
	if (formObj.name.value == "") msg += "<?php printMLText("js_no_name");?>\n";
	//if (formObj.email.value == "") msg += "<?php printMLText("js_no_email");?>\n";
	if (formObj.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
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

UI::contentHeading(getMLText("user_management"));
UI::contentContainerStart();
?>
<table><tr>

<td><?php echo getMLText("selection")?>:
<select onchange="showUser(this)" id="selector">
<option value="-1"><?php echo getMLText("choose_user")?>
<option value="0"><?php echo getMLText("add_user")?>

<?php
	$selected=0;
	$count=2;
	foreach ($users as $currUser) {
		if (isset($_GET["userid"]) && $currUser->getID()==$_GET["userid"]) $selected=$count;
		print "<option value=\"".$currUser->getID()."\">" . $currUser->getLogin();
		$count++;
}
?>
</select>
&nbsp;&nbsp;
</td>

<td id="keywords0" style="display : none;">

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
			<td><textarea name="comment" rows="4" cols="50"></textarea></td>
		</tr>
		<tr>
			<td><?php printMLText("role");?>:</td>
			<td><select name="role"><option value="<?php echo LetoDMS_Core_User::role_user; ?>"><?php printMLText("role_user"); ?></option><option value="<?php echo LetoDMS_Core_User::role_admin; ?>"><?php printMLText("role_admin"); ?></option><option value="<?php echo LetoDMS_Core_User::role_guest; ?>"><?php printMLText("role_guest"); ?></option></select></td>
		</tr>
		<tr>
			<td><?php printMLText("is_hidden");?>:</td>
			<td><input type="checkbox" name="ishidden" value="1"></td>
		</tr>

		<?php if ($settings->_enableUserImage){ ?>

			<tr>
				<td><?php printMLText("user_image");?>:</td>
				<td><input type="File" name="userfile"></td>
			</tr>

		<?php } ?>

		<tr>
			<td><?php printMLText("reviewers");?>:</td>
			<td>
				<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
				<div class="cbSelectContainer">
				<ul class="cbSelectList"><?php
				foreach ($users as $usr) {

					if ($usr->isGuest()) continue;

					print "<li class=\"cbSelectItem\"><input id='revUsr".$usr->getID()."' type='checkbox' name='usrReviewers[]' value='". $usr->getID() ."'>".$usr->getLogin();
				}
?>
				</ul>
				</div>
				<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
				<div class="cbSelectContainer">
				<ul class="cbSelectList">
<?php
				foreach ($groups as $grp) {

					print "<li class=\"cbSelectItem\"><input id='revGrp".$grp->getID()."' type='checkbox' name='grpReviewers[]' value='". $grp->getID() ."'>".$grp->getName();
				}
?>
				</ul>
				</div>
			</td>
		</tr>

		<tr>
			<td><?php printMLText("approvers");?>:</td>
			<td>
				<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
				<div class="cbSelectContainer">
				<ul class="cbSelectList">
<?php
				foreach ($users as $usr) {

					if ($usr->isGuest()) continue;

					print "<li class=\"cbSelectItem\"><input id='appUsr".$usr->getID()."' type='checkbox' name='usrApprovers[]' value='". $usr->getID() ."'>".$usr->getLogin();
				}
?>
				</ul>
				</div>
				<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
				<div class="cbSelectContainer">
				<ul class="cbSelectList">
<?php
				foreach ($groups as $grp) {

					print "<li class=\"cbSelectItem\"><input id='revGrp".$grp->getID()."' type='checkbox' name='grpApprovers[]' value='". $grp->getID() ."'>".$grp->getName();
				}
?>
				</ul>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2"><input type="Submit" value="<?php printMLText("add_user");?>"></td>
		</tr>
	</table>
	</form>

</td>


	<?php
	foreach ($users as $currUser) {

		print "<td id=\"keywords".$currUser->getID()."\" style=\"display : none;\">";

		UI::contentSubHeading(getMLText("user")." : ".$currUser->getLogin());
	?>

	<a class="standardText" href="../out/out.RemoveUser.php?userid=<?php print $currUser->getID();?>"><img src="images/del.gif" width="15" height="15" border="0" align="absmiddle" alt=""> <?php printMLText("rm_user");?></a>

	<?php	UI::contentSubHeading(getMLText("edit_user"));?>

	<form action="../op/op.UsrMgr.php" method="post" enctype="multipart/form-data" name="form<?php print $currUser->getID();?>" onsubmit="return checkForm('<?php print $currUser->getID();?>');">
	<input type="Hidden" name="userid" value="<?php print $currUser->getID();?>">
	<input type="Hidden" name="action" value="edituser">
	<table>
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
			<td><textarea name="comment" rows="4" cols="50"><?php print $currUser->getComment();?></textarea></td>
		</tr>
		<tr>
			<td><?php printMLText("role");?>:</td>
			<td><select name="role"><option value="<?php echo LetoDMS_Core_User::role_user; ?>"><?php printMLText("role_user"); ?></option><option value="<?php echo LetoDMS_Core_User::role_admin; ?>" <?php if($currUser->getRole() == LetoDMS_Core_User::role_admin) echo "selected"; ?>><?php printMLText("role_admin"); ?></option><option value="<?php echo LetoDMS_Core_User::role_guest; ?>" <?php if($currUser->getRole() == LetoDMS_Core_User::role_guest) echo "selected"; ?>><?php printMLText("role_guest"); ?></option></select></td>
		</tr>
		<tr>
			<td><?php printMLText("is_hidden");?>:</td>
			<td><input type="checkbox" name="ishidden" value="1"<?php print ($currUser->isHidden() ? " checked='checked'" : "");?>></td>
		</tr>

		<?php if ($settings->_enableUserImage){ ?>

			<tr>
				<td><?php printMLText("user_image");?>:</td>
				<td>
					<?php
						if ($currUser->hasImage())
							print "<img src=\"".$settings->_httpRoot . "out/out.UserImage.php?userid=".$currUser->getId()."\">";
						else
							printMLText("no_user_image");
					?>
				</td>
			</tr>
			<tr>
				<td><?php printMLText("new_user_image");?>:</td>
				<td><input type="file" name="userfile" accept="image/jpeg"></td>
			</tr>

		<?php } ?>


		<tr>
			<td><?php printMLText("reviewers");?>:</td>
			<td>
				<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
				<div class="cbSelectContainer">
				<ul class="cbSelectList">
				<?php

				$res=$currUser->getMandatoryReviewers();

				foreach ($users as $usr) {

					if ($usr->isGuest() || ($usr->getID() == $currUser->getID()))
						continue;

					$checked=false;
					foreach ($res as $r) if ($r['reviewerUserID']==$usr->getID()) $checked=true;

					print "<li class=\"cbSelectItem\"><input id='revUsr".$usr->getID()."' type='checkbox' ".($checked?"checked='checked' ":"")."name='usrReviewers[]' value='". $usr->getID() ."'>".$usr->getLogin()."</li>\n";
				}
				?>
				</ul>
				</div>
				<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
				<div class="cbSelectContainer">
				<ul class="cbSelectList">
				<?php
				foreach ($groups as $grp) {

					$checked=false;
					foreach ($res as $r) if ($r['reviewerGroupID']==$grp->getID()) $checked=true;

					print "<li class=\"cbSelectItem\"><input id='revGrp".$grp->getID()."' type='checkbox' ".($checked?"checked='checked' ":"")."name='grpReviewers[]' value='". $grp->getID() ."'>".$grp->getName()."</li>\n";
				}
				?>
				</ul>
				</div>
			</td>
		</tr>

		<tr>
			<td><?php printMLText("approvers");?>:</td>
			<td>
				<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
				<div class="cbSelectContainer">
				<ul class="cbSelectList">
				<?php

				$res=$currUser->getMandatoryApprovers();

				foreach ($users as $usr) {

					if ($usr->isGuest() || ($usr->getID() == $currUser->getID()))
						continue;

					$checked=false;
					foreach ($res as $r) if ($r['approverUserID']==$usr->getID()) $checked=true;

					print "<li class=\"cbSelectItem\"><input id='appUsr".$usr->getID()."' type='checkbox' ".($checked?"checked='checked' ":"")."name='usrApprovers[]' value='". $usr->getID() ."'>".$usr->getLogin()."</li>\n";
				}
				?>
				</ul>
				</div>
				<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
				<div class="cbSelectContainer">
				<ul class="cbSelectList">
				<?php
				foreach ($groups as $grp) {

					$checked=false;
					foreach ($res as $r) if ($r['approverGroupID']==$grp->getID()) $checked=true;

					print "<li class=\"cbSelectItem\"><input id='revGrp".$grp->getID()."' type='checkbox' ".($checked?"checked='checked' ":"")."name='grpApprovers[]' value='". $grp->getID() ."'>".$grp->getName()."</li>\n";
				}
				?>
				</ul>
				</div>
			</td>
		</tr>

		<tr>
			<td colspan="2"><input type="Submit" value="<?php printMLText("save");?>"></td>
		</tr>
	</table>
	</form>
</td>
<?php  } ?>
</tr></table>

<script language="JavaScript">

sel = document.getElementById("selector");
sel.selectedIndex=<?php print $selected ?>;
showUser(sel);

</script>


<?php
UI::contentContainerEnd();

UI::htmlEndPage();
?>
