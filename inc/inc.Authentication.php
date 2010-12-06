<?php
/**
 * Do authentication of users and session management
 *
 * @category   DMS
 * @package    LetoDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

$refer=urlencode($_SERVER["REQUEST_URI"]);
if (!strncmp("/op", $refer, 3)) {
	$refer="";
}
if (!isset($_COOKIE["mydms_session"]))
{
	header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
	exit;
}

require_once("inc.Utils.php");
require_once("inc.ClassEmail.php");

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

$user = $dms->getUser($resArr["userID"]);
if (!is_object($user)) {
	setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot); //delete cookie
	header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
	exit;
}

$dms->setUser($user);
$notifier = new LetoDMS_Email();
$notifier->setSender($user);

$theme = $resArr["theme"];
include $settings->_rootDir . "languages/" . $resArr["language"] . "/lang.inc";

?>
