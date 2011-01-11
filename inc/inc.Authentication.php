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
if (!isset($_COOKIE["mydms_session"])) {
	header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
	exit;
}

require_once("inc.Utils.php");
require_once("inc.ClassEmail.php");
require_once("inc.ClassSession.php");

/* Load session */
$dms_session = sanitizeString($_COOKIE["mydms_session"]);
$session = new LetoDMS_Session($db);
if(!$resArr = $session->load($dms_session)) {
	setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot); //delete cookie
	header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=".$refer);
	exit;
}

/* Load user data */
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
