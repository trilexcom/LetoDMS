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

$refer=urlencode($_SERVER["REQUEST_URI"]);
if (!strncmp("/op", $refer, 3)) {
	$refer="";
}
if (!isset($_COOKIE["mydms_session"]))
{
	header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
	exit;
}

include_once("inc.Utils.php");

$dms_session = sanitizeString($_COOKIE["mydms_session"]);

$queryStr = "SELECT * FROM tblSessions WHERE id = '".$dms_session."'";
$resArr = $db->getResultArray($queryStr);
if (is_bool($resArr) && $resArr == false)
	die ("Error while reading from tblSessions: " . $db->getErrorMsg());

if (count($resArr) == 0)
{
	setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot); //delete cookie
	header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
	exit;
}

$resArr = $resArr[0];

$queryStr = "UPDATE tblSessions SET lastAccess = " . mktime() . " WHERE id = '" . $resArr["id"] . "'";
if (!$db->getResult($queryStr))
	die ("Error while updating tblSessions: " . $db->getErrorMsg());

$user = getUser($resArr["userID"]);
if (!is_object($user)) {
	setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot); //delete cookie
	header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
	exit;
}

$theme = $resArr["theme"];
include $settings->_rootDir . "languages/" . $resArr["language"] . "/lang.inc";

?>
