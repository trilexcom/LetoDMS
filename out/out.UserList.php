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

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("admin_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");
UI::contentHeading(getMLText("user_list"));
UI::contentContainerStart();

$users = getAllUsers();
for ($i = 0; $i < count($users); $i++) {
	$currUser = $users[$i];
	if ($currUser->getID() == $settings->_guestID)
		continue;

	UI::contentSubHeading(getMLText("user") . ": \"" . $currUser->getFullName() . "\"");
?>
	<table border="0">
		<tr>
			<td><?php printMLText("user_login");?>:</td>
			<td><?php print $currUser->getLogin();?></td>
		</tr>
	<tr>
			<td><?php printMLText("user_name");?>:</td>
			<td><?php print $currUser->getFullName();?></td>
		</tr>
		<tr>
			<td><?php printMLText("email");?>:</td>
			<td><a href="mailto:<?php print $currUser->getEmail();?>"><?php print $currUser->getEmail();?></a></td>
		</tr>
		<tr>
			<td><?php printMLText("comment");?>:</td>
			<td><?php print $currUser->getComment();?></td>
		</tr>
		<tr>
			<td><?php printMLText("groups");?>:</td>
			<td>
				<?php
					$groups = $currUser->getGroups();
					if (count($groups) == 0) {
						printMLText("no_groups");
					}
					else {
						for ($j = 0; $j < count($groups); $j++)	{
							print $groups[$j]->getName();
							if ($j +1 < count($groups))
								print ", ";
						}
					}
				?>
			</td>
		</tr>
		<tr>
			<td><?php printMLText("user_image");?>:</td>
			<td>
				<?php
					if ($currUser->hasImage())
						print "<img src=\"".$currUser->getImageURL()."\">";
					else
						printMLText("no_user_image");
				?>
			</td>
		</tr>
	</table>
<?php
}

UI::contentContainerEnd();
UI::htmlEndPage();
?>
