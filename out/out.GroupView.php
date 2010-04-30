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

$allUsers = getAllUsers();

if (is_bool($allUsers)) {
	UI::exitError(getMLText("my_account"),getMLText("internal_error"));
}

$groups = getAllGroups();

if (is_bool($groups)) {
	UI::exitError(getMLText("admin_tools"),getMLText("internal_error"));
}

UI::htmlStartPage(getMLText("my_account"));
UI::globalNavigation();
UI::pageNavigation(getMLText("my_account"), "my_account");

UI::contentHeading(getMLText("groups"));
UI::contentContainerStart();

echo "<ul class=\"groupView\">\n";

foreach ($groups as $group){

	echo "<li>".$group->getName()." : ".$group->getComment()."</li>";
	
	$members = $group->getUsers();
	
	echo "<ul>\n";
	
	foreach ($members as $member) {
	
		echo "<li>".$member->getFullName();
		if ($member->getEmail()!="")
			echo " (<a href=\"mailto:".$member->getEmail()."\">".$member->getEmail()."</a>)</li>";
		else echo "</li>";
	}
	echo "</ul>\n";
}
echo "</ul>\n";

UI::contentContainerEnd();
UI::htmlEndPage();
?>
