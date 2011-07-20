<?php
ini_set('include_path', '.:/etc/letodms-webdav:/usr/share/php');

include("config.php");
include("Log.php");
include("letodms_webdav.php");
include("../inc/inc.Settings.php");

$db = new LetoDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");
$db->_conn->Execute("set names 'utf8'");

$dms = new LetoDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);

$log = Log::factory('file', $g_config['logfile']);

$server = new HTTP_WebDAV_Server_LetoDMS();
$server->ServeRequest($dms, $log);
//$files = array();
//$options = array('path'=>'/Test1/subdir', 'depth'=>1);
//echo $server->MKCOL(&$options);

?>
