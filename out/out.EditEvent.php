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
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Calendar.php");
include("../inc/inc.Authentication.php");

if ($user->isGuest()) {
	UI::exitError(getMLText("edit_event"),getMLText("access_denied"));
}

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
<script language="JavaScript">
function checkForm()
{
	msg = "";
	if (document.form1.name.value == "") msg += "<?php printMLText("js_no_name");?>\n";
<?php
	if (isset($settings->_strictFormCheck) && $settings->_strictFormCheck) {
	?>
	if (document.form1.comment.value == "") msg += "<?php printMLText("js_no_comment");?>\n";
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
</script>

<form action="../op/op.EditEvent.php" name="form1" onsubmit="return checkForm();" method="POST">

	<input type="Hidden" name="eventid" value="<?php echo $_GET["id"]; ?>">

	<table>
		<tr>
			<td><?php printMLText("from");?>:</td>
			<td><?php UI::printDateChooser($event["start"], "from");?></td>
		</tr>
		<tr>
			<td><?php printMLText("to");?>:</td>
			<td><?php UI::printDateChooser($event["stop"], "to");?></td>
		</tr>
		<tr>
			<td class="inputDescription"><?php printMLText("name");?>:</td>
			<td><input name="name" value="<?php echo $event["name"];?>" size="60"></td>
		</tr>
		<tr>
			<td valign="top" class="inputDescription"><?php printMLText("comment");?>:</td>
			<td><textarea name="comment" rows="4" cols="80"><?php echo $event["comment"]?></textarea></td>
		</tr>
		<tr>
			<td colspan="2"><br><input type="Submit" value="<?php printMLText("edit_event");?>"></td>
		</tr>
	</table>
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
