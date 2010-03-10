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
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

UI::htmlStartPage(getMLText("my_account"));
UI::globalNavigation();
UI::pageNavigation(getMLText("my_account"), "my_account");

UI::contentHeading(getMLText("user_info"));
UI::contentContainer("<table>\n<tr>\n".
			"<td rowspan=5 id=\"userImage\">".($user->hasImage() ? "<img class=\"userImage\" src=\"".$user->getImageURL()."\">" : getMLText("no_user_image"))."</td>\n".
			"</tr>\n<tr>\n".
			"<td>".getMLText("name").":</td>\n".
			"<td>".$user->getFullName().($user->isAdmin() ? " (".getMLText("admin").")" : "")."</td>\n".
			"</tr>\n<tr>\n".
			"<td>".getMLText("user_login").":</td>\n".
			"<td>".$user->getLogin()."</td>\n".
			"</tr>\n<tr>\n".
			"<td>".getMLText("email").":</td>\n".
			"<td>".$user->getEmail()."</td>\n".
			"</tr>\n<tr>\n".
			"<td>".getMLText("comment").":</td>\n".
			"<td>".$user->getComment()."</td>\n".
			"</tr>\n</table>\n");


UI::htmlEndPage();
?>
