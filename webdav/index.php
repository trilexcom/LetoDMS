<?php
ini_set('include_path', '.:/etc/letodms-webdav:/usr/share/php');

include("config.php");
include("Log.php");
include("letodms_webdav.php");

$db = new LetoDMS_Core_DatabaseAccess($g_config['type'], $g_config['hostname'], $g_config['user'], $g_config['passwd'], $g_config['name']);
$db->connect() or die ("Could not connect to db-server \"" . $g_config['hostname'] . "\"");
$db->_conn->Execute("set names 'utf8'");

$dms = new LetoDMS_Core_DMS($db, $g_config['contentDir'].$g_config['contentOffsetDir']);

$log = Log::factory('file', $g_config['logfile']);

$server = new HTTP_WebDAV_Server_LetoDMS();
$server->ServeRequest($dms, $log);
//$files = array();
//$options = array('path'=>'/Test1/subdir', 'depth'=>1);
//echo $server->MKCOL(&$options);

?>
