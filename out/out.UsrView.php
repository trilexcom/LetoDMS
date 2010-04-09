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

if (!$settings->_enableUsersView) {
	UI::exitError(getMLText("my_account"),getMLText("access_denied"));
}

$users = getAllUsers();

if (is_bool($users)) {
	UI::exitError(getMLText("my_account"),getMLText("internal_error"));
}

UI::htmlStartPage(getMLText("my_account"));
UI::globalNavigation();
UI::pageNavigation(getMLText("my_account"), "my_account");

UI::contentHeading(getMLText("users"));
UI::contentContainerStart();

echo "<table class=\"userView\">\n";
echo "<thead>\n<tr>\n";
echo "<th>".getMLText("name")."</th>\n";
echo "<th>".getMLText("email")."</th>\n";
echo "<th>".getMLText("comment")."</th>\n";
echo "<th>".getMLText("user_image")."</th>\n";
echo "</tr>\n</thead>\n";

foreach ($users as $currUser) {

	if (($currUser->getID() == $settings->_adminID) || ($currUser->getID() == $settings->_guestID))
		continue;
		
	echo "<tr>\n";
	
	print "<td>".$currUser->getFullName()."</td>";
	
	print "<td><a href=\"mailto:".$currUser->getEmail()."\">".$currUser->getEmail()."</a></td>";
	print "<td>".$currUser->getComment()."</td>";
	print "<td>";
	if ($currUser->hasImage()) print "<img src=\"".$currUser->getImageURL()."\">";
	else printMLText("no_user_image");
	print "</td>";	
	
	echo "</tr>\n";
}

echo "</table>\n";

UI::contentContainerEnd();
UI::htmlEndPage();
?>
