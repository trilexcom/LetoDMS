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

if (!isset($_GET["groupid"]) || !is_numeric($_GET["groupid"]) || intval($_GET["groupid"])<1) {
	UI::exitError(getMLText("rm_group"),getMLText("invalid_user_id"));
}
$groupid = $_GET["groupid"];
$currGroup = getGroup($groupid);

if (!is_object($currGroup)) {
	UI::exitError(getMLText("rm_group"),getMLText("invalid_group_id"));
}

UI::htmlStartPage(getMLText("admin_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");
UI::contentHeading(getMLText("rm_group"));
UI::contentContainerStart();

?>
<form action="../op/op.GroupMgr.php" name="form1" method="POST">
<input type="Hidden" name="groupid" value="<?php print $groupid;?>">
<input type="Hidden" name="action" value="removegroup">
<p>
<?php printMLText("confirm_rm_group", array ("groupname" => $currGroup->getName()));?>
</p>
<p><input type="Submit" value="<?php printMLText("rm_group");?>"></p>
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
