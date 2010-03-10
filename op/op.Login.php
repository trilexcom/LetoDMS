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
include("../inc/inc.Language.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassUser.php");

function _printMessage($heading, $message) {

	UI::htmlStartPage($heading, "login");
	UI::globalBanner();
	UI::pageNavigation($heading);
	UI::contentContainer($message);
	UI::htmlEndPage();
	return;
}

if (isset($_POST["login"])) {
	$login = sanitizeString($_POST["login"]);
	$login = str_replace("*", "", $login);
}
else if (isset($_GET["login"])) {
	$login = sanitizeString($_GET["login"]);
	$login = str_replace("*", "", $login);
}

if (!isset($login) || strlen($login)==0) {
	_printMessage(getMLText("login_error_title"),	"<p>".getMLText("login_not_given")."</p>\n".
		"<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
	exit;
}

$pwd = (string) $_POST["pwd"];
if (get_magic_quotes_gpc()) {
	$pwd = stripslashes($pwd);
}

$guestUser = getUser($settings->_guestID);
if ((!isset($pwd) || strlen($pwd)==0) && ($login != $guestUser->getLogin()))  {
	_printMessage(getMLText("login_error_title"),	"<p>".getMLText("login_error_text")."</p>\n".
		"<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
	exit;
}

//
// LDAP Sign In
//

$user = false;
if (isset($settings->_ldapHost) && strlen($settings->_ldapHost)>0) {
	if (isset($settings->_ldapPort) && is_int($settings->_ldapPort)) {
		$ds = ldap_connect($settings->_ldapHost, $settings->_ldapPort);
	}
	else {
		$ds = ldap_connect($settings->_ldapHost);
	}
	if (!is_bool($ds)) {
		// Ensure that the LDAP connection is set to use version 3 protocol.
		// Required for most authentication methods, including SASL.
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

		// try an anonymous bind first. If it succeeds, get the DN for the user.
		$bind = @ldap_bind($ds);
		$dn = false;
		if ($bind) {
			$search = ldap_search($ds, $settings->_ldapBaseDN, "uid=".$login);
			if (!is_bool($search)) {
				$info = ldap_get_entries($ds, $search);
				if (!is_bool($info) && $info["count"]>0) {
					$dn = $info[0]['dn'];
				}
			}
		}
		if (is_bool($dn)) {
			// This is the fallback position, in case the anonymous bind does not
			// succeed.
			$dn = "uid=".$login.",".$settings->_ldapBaseDN;
		}
		$bind = @ldap_bind($ds, $dn, $pwd);
		if ($bind) {
			// Successfully authenticated. Now check to see if the user exists within
			// the database. If not, add them in, but do not add their password.
			$user = getUserByLogin($login);
			if (is_bool($user) && !$settings->_restricted) {
				// Retrieve the user's LDAP information.
				$search = ldap_search($ds, $dn, "uid=".$login);
				if (!is_bool($search)) {
					$info = ldap_get_entries($ds, $search);
					if (!is_bool($info) && $info["count"]==1 && $info[0]["count"]>0) {
						$user = addUser($login, null, $info[0]['cn'][0], $info[0]['mail'][0], $settings->_language, $settings->_theme, "");
					}
				}
			}
			if (!is_bool($user)) {
				$userid = $user->getID();
			}
		}
		ldap_close($ds);
	}
}

if (is_bool($user)) {
	//
	// LDAP Authentication did not succeed or is not configured. Try internal
	// authentication system.
	//

	//Retrieve user information from the database.
	$queryStr = "SELECT * FROM tblUsers WHERE login = '".$login."'";
	$resArr = $db->getResultArray($queryStr);
	if (is_bool($resArr) && $resArr == false) {
		_printMessage(getMLText("login_error_title"),	"<p>".getMLText("internal_error")." - database: " . $db->getErrorMsg().
									"</p>\n<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
		exit;
	}

	if (count($resArr) == 0) {
		_printMessage(getMLText("login_error_title"),	"<p>".getMLText("login_error_text")."</p>\n".
									"<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
		exit;
	}

	$resArr = $resArr[0];

	if (($resArr["id"] == $settings->_guestID) && (!$settings->_enableGuestLogin)) {
		_printMessage(getMLText("login_error_title"),	"<p>".getMLText("guest_login_disabled").
									"</p>\n<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
		exit;
	}

	//Vergleichen des Passwortes (falls kein guest-login)
	// Assume that the password has been sent via HTTP POST. It would be careless
	// (and dangerous) for passwords to be sent via GET.
	if (($resArr["id"] != $settings->_guestID) && (md5($pwd) != $resArr["pwd"])) {
		_printMessage(getMLText("login_error_title"),	"<p>".getMLText("login_error_text").
									"</p>\n<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
		exit;
	}
	$userid = $resArr["id"];
	$user = getUser($userid);
}


// L�schen von Sitzungen, die �lter als 24h sind
// Delete any sessions that are more than 24 hours old. Probably not the most
// reliable place to put this check -- move to inc.Authentication.php?
$queryStr = "DELETE FROM tblSessions WHERE " . mktime() . " - lastAccess > 86400";
if (!$db->getResult($queryStr)) {
	_printMessage(getMLText("login_error_title"), "<p>".getMLText("error_occured").": ".$db->getErrorMsg()."</p>");
	exit;
}

//Erstellen einer Sitzungs-ID
$id = "" . rand() . mktime() . rand() . "";
$id = md5($id);

// Capture the user's language and theme settings.
if (isset($_POST["lang"]) && strlen($_POST["lang"])>0 && is_numeric(array_search($_POST["lang"],getLanguages())) ) {
	$lang = sanitizeString($_POST["lang"]);
	$user->setLanguage($lang);
}
else if (isset($_GET["lang"]) && strlen($_GET["lang"])>0 && is_numeric(array_search($_GET["lang"],getLanguages())) ) {
	$lang = sanitizeString($_GET["lang"]);
	$user->setLanguage($lang);
}
else {
	$lang = $user->getLanguage();
	if (strlen($lang)==0) {
		$lang = $settings->_language;
		$user->setLanguage($lang);
	}
}
if (isset($_POST["sesstheme"]) && strlen($_POST["sesstheme"])>0 && is_numeric(array_search($_POST["sesstheme"],UI::getStyles())) ) {
	$sesstheme = sanitizeString($_POST["sesstheme"]);
	$user->setTheme($sesstheme);
}
else if (isset($_GET["sesstheme"]) && strlen($_GET["sesstheme"])>0 && is_numeric(array_search($_GET["sesstheme"],UI::getStyles())) ) {
	$sesstheme = sanitizeString($_GET["sesstheme"]);
	$user->setTheme($sesstheme);
}
else {
	$sesstheme = $user->getTheme();
	if (strlen($sesstheme)==0) {
		$sesstheme = $settings->_theme;
		$user->setTheme($sesstheme);
	}
}

//Einf�gen eines neuen Datensatzes in tblSessions
$queryStr = "INSERT INTO tblSessions (id, userID, lastAccess, theme, language) ".
	"VALUES ('".$id."', ".$userid.", ".mktime().", '".$sesstheme."', '".$lang."')";
if (!$db->getResult($queryStr)) {
	_printMessage(getMLText("login_error_title"), "<p>".getMLText("error_occured").": ".$db->getErrorMsg()."</p>");
	exit;
}
//Setzen des Sitzungs-Cookies
// Set the session cookie.
setcookie("mydms_session", $id, 0, $settings->_httpRoot);

// TODO: by the PHP manual: The superglobals $_GET and $_REQUEST  are already decoded. 
// Using urldecode() on an element in $_GET or $_REQUEST could have unexpected and dangerous results.

if (isset($_POST["referuri"]) && strlen($_POST["referuri"])>0) {
	$referuri = urldecode($_POST["referuri"]);
}
else if (isset($_GET["referuri"]) && strlen($_GET["referuri"])>0) {
	$referuri = urldecode($_GET["referuri"]);
}
if (isset($referuri) && strlen($referuri)>0) {
	header("Location: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'] . $referuri);
}
else {
	header("Location: ../".(isset($settings->_siteDefaultPage) && strlen($settings->_siteDefaultPage)>0 ? $settings->_siteDefaultPage : "out/out.ViewFolder.php?folderid=1"));
}

_printMessage(getMLText("login_ok"),
	"<p><a href='".$settings->_httpRoot.(isset($settings->_siteDefaultPage) && strlen($settings->_siteDefaultPage)>0 ? $settings->_siteDefaultPage : "out/out.ViewFolder.php")."'>".getMLText("continue")."</a></p>");

?>
