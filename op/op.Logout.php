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
include("../inc/inc.Utils.php");
include("../inc/inc.DBAccess.php");

//Code when running PHP as Module -----------------------------------------------------------------

/*
setcookie("mydms_logged_out", "true", 0, $settings->_httpRoot);
header("Location: ../out/out.ViewFolder.php");
print "Logout successful";
*/

//Code when running PHP in CGI-Mode ---------------------------------------------------------------

//Delete from tblSessions

$dms_session = $_COOKIE["mydms_session"];
$dms_session = sanitizeString($dms_session);

$queryStr = "DELETE FROM tblSessions WHERE id = '$dms_session'";
if (!$db->getResult($queryStr))
	UI::exitError(getMLText("logout"),$db->getErrorMsg());

//Delete Cookie
setcookie("mydms_session", $_COOKIE["mydms_session"], time()-3600, $settings->_httpRoot);

//Forward to Login-page
header("Location: ../out/out.Login.php");



?>
