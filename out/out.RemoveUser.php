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

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

if (!isset($_GET["userid"]) || !is_numeric($_GET["userid"]) || intval($_GET["userid"])<1) {
	UI::exitError(getMLText("rm_user"),getMLText("invalid_user_id"));
}

$userid = $_GET["userid"];


if (($userid==$settings->_adminID)||($userid==$settings->_guestID)) {
	UI::exitError(getMLText("rm_user"),getMLText("access_denied"));
}

$currUser = getUser($userid);

if (!is_object($currUser)) {
	UI::exitError(getMLText("rm_user"),getMLText("invalid_user_id"));
}

UI::htmlStartPage(getMLText("admin_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");
UI::contentHeading(getMLText("rm_user"));
UI::contentContainerStart();

?>
<form action="../op/op.UsrMgr.php" name="form1" method="POST">
<input type="Hidden" name="userid" value="<?php print $userid;?>">
<input type="Hidden" name="action" value="removeuser">
<p>
<?php printMLText("confirm_rm_user", array ("username" => $currUser->getFullName()));?>
</p>

<p>
<?php printMLText("assign_user_property_to"); ?> :
<select name="assignTo">
<option value="<?php print $settings->_adminID; ?>"><?php echo getMLText("admin")?>

<?php
	$users = getAllUsers();
	foreach ($users as $currUser) {
		if (($currUser->getID() == $settings->_adminID) || ($currUser->getID() == $settings->_guestID) || ($currUser->getID() == $userid) )
			continue;
			
		if (isset($_GET["userid"]) && $currUser->getID()==$_GET["userid"]) $selected=$count;
		print "<option value=\"".$currUser->getID()."\">" . $currUser->getLogin();
	}
?>
</select>
</p>

<p><input type="Submit" value="<?php printMLText("rm_user");?>"></p>

</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
