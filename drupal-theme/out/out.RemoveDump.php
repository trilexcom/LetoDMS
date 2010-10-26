<?php
//    MyDMS. Document Management System
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

if (!isset($_GET["dumpname"]) || !file_exists($settings->_contentDir.$_GET["dumpname"]) ) {
	UI::exitError(getMLText("admin_tools"),getMLText("unknown_id"));
}

$dumpname = $_GET["dumpname"];

UI::htmlStartPage(getMLText("backup_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");
UI::contentHeading(getMLText("dump_remove"));
UI::contentContainerStart();

?>
<form action="../op/op.RemoveDump.php" name="form1" method="POST">
	<input type="Hidden" name="dumpname" value="<?php echo $dumpname?>">
	<p><?php printMLText("confirm_rm_dump", array ("dumpname" => $dumpname));?></p>
	<input type="Submit" value="<?php printMLText("dump_remove");?>">
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
