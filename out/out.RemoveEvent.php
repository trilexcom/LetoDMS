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
include("../inc/inc.Calendar.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["id"]) || !is_numeric($_GET["id"]) || intval($_GET["id"])<1) {
	UI::exitError(getMLText("edit_event"),getMLText("error_occured"));
}

$event=getEvent($_GET["id"]);

if (is_bool($event)&&!$event){
	UI::exitError(getMLText("edit_event"),getMLText("error_occured"));
}
if (($user->getID()!=$event["userID"])&&(!$user->isAdmin())){
	UI::exitError(getMLText("edit_event"),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("calendar"));
UI::globalNavigation();
UI::pageNavigation(getMLText("calendar"), "calendar");

UI::contentHeading(getMLText("edit_event"));
UI::contentContainerStart();

?>
<form action="../op/op.RemoveEvent.php" name="form1" method="POST">
	<input type="Hidden" name="eventid" value="<?php echo $_GET["id"]; ?>">
	<p><?php printMLText("confirm_rm_event", array ("name" => $event["name"]));?></p>
	<input type="Submit" value="<?php printMLText("delete");?>">
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
