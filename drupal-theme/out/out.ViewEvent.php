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

if (!isset($_GET["id"])){
	UI::exitError(getMLText("event_details"),getMLText("error_occured"));
}

$event=getEvent($_GET["id"]);

if (is_bool($event)&&!$event){
	UI::exitError(getMLText("event_details"),getMLText("error_occured"));
}

UI::htmlStartPage(getMLText("calendar"));
UI::globalNavigation();
UI::pageNavigation(getMLText("calendar"), "calendar");

UI::contentHeading(getMLText("event_details"));
UI::contentContainerStart();

$u=getUser($event["userID"]);

echo "<table>";

echo "<tr>";
echo "<td>".getMLText("name").": </td>";
echo "<td>".$event["name"]."</td>";
echo "</tr>";

echo "<tr>";
echo "<td>".getMLText("comment").": </td>";
echo "<td>".$event["comment"]."</td>";
echo "</tr>";

echo "<tr>";
echo "<td>".getMLText("from").": </td>";
echo "<td>".getReadableDate($event["start"])."</td>";
echo "</tr>";

echo "<tr>";
echo "<td>".getMLText("to").": </td>";
echo "<td>".getReadableDate($event["stop"])."</td>";
echo "</tr>";

echo "<tr>";
echo "<td>".getMLText("last_update").": </td>";
echo "<td>".getLongReadableDate($event["date"])."</td>";
echo "</tr>";

echo "<tr>";
echo "<td>".getMLText("user").": </td>";
echo "<td>".(is_object($u)?$u->getFullName():getMLText("unknown_user"))."</td>";
echo "</tr>";

echo "</table>";

UI::contentContainerEnd();

if (($user->getID()==$event["userID"])||($user->isAdmin())){

	UI::contentHeading(getMLText("edit"));
	UI::contentContainerStart();

	print "<ul class=\"actions\">";
	print "<li><a href=\"../out/out.RemoveEvent.php?id=".$event["id"]."\">".getMLText("delete")."</a>";
	print "<li><a href=\"../out/out.EditEvent.php?id=".$event["id"]."\">".getMLText("edit")."</a>";
	print "</ul>";
	
	UI::contentContainerEnd();
}



UI::htmlEndPage();
?>
