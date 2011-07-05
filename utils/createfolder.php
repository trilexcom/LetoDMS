<?php
#ini_set('include_path', '.:/usr/share/php:/usr/share/letodms/www');

include("/etc/letodms/conf.Settings.php");
include("LetoDMS/Core.php");

function usage() { /* {{{ */
	echo "Usage:\n";
	echo "  letocreatefolder [-c <comment>] [-n <name>] [-s <sequence>] [-h] [-v] -F <parent id>\n";
	echo "\n";
	echo "Description:\n";
	echo "  This program creates a new folder in LetoDMS.\n";
	echo "\n";
	echo "Options:\n";
	echo "  -h: print usage information and exit.\n";
	echo "  -v: print version and exit.\n";
	echo "  -F <parent id>: id of parent folder\n";
	echo "  -c <comment>: set comment for file\n";
	echo "  -n <name>: set name of the folder\n";
	echo "  -s <sequence>: set sequence of folder\n";
} /* }}} */

$shortoptions = "F:c:s:n:hv";
if(false === ($options = getopt($shortoptions))) {
	usage();
	exit(0);
}

/* Print help and exit */
if(isset($options['h'])) {
	usage();
	exit(0);
}

/* Print version and exit */
if(isset($options['v'])) {
	echo $version."\n";
	exit(0);
}

if(isset($options['F'])) {
	$folderid = (int) $options['F'];
} else {
	echo "Missing parent folder ID\n";
	usage();
	exit(1);
}

$comment = '';
if(isset($options['c'])) {
	$comment = $options['c'];
}

$sequence = 0;
if(isset($options['s'])) {
	$sequence = $options['s'];
}

$name = '';
if(isset($options['n'])) {
	$name = $options['n'];
}

$db = new LetoDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");
$db->_conn->debug = 1;


$dms = new LetoDMS_Core_DMS($db, $settings->_contentDir, $settings->_contentOffsetDir);
$dms->setRootFolderID($settings->_rootFolderID);
$dms->setGuestID($settings->_guestID);
$dms->setEnableGuestLogin($settings->_enableGuestLogin);
$dms->setEnableAdminRevApp($settings->_enableAdminRevApp);
$dms->setEnableConverting($settings->_enableConverting);
$dms->setViewOnlineFileTypes($settings->_viewOnlineFileTypes);

/* Create a global user object */
$user = $dms->getUser(1);

$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	echo "Could not find specified folder\n";
	exit(1);
}

if ($folder->getAccessMode($user) < M_READWRITE) {
	echo "Not sufficient access rights\n";
	exit(1);
}

if (!is_numeric($sequence)) {
	echo "Sequence must be numeric\n";
	exit(1);
}

$res = $folder->addSubFolder($name, $comment, $user, $sequence);

if (is_bool($res) && !$res) {
	echo "Could not add folder\n";
	exit(1);
}
?>

