<?php
#ini_set('include_path', '.:/usr/share/php:/usr/share/letodms/www');

include("/etc/letodms/conf.Settings.php");
include("LetoDMS/LetoDMS_Core.php");

function usage() { /* {{{ */
	echo "Usage:\n";
	echo "  letoadddoc [-d <date>] [-c <caller>] [-n] [-a] [-m] [-h] [-v]\n";
	echo "\n";
	echo "Options:\n";
	echo "  -h: print usage information and exit.\n";
	echo "  -v: print version and exit.\n";
	echo "  -F <folder id>: id of folder the file is uploaded\n";
	echo "  -c <comment>: set comment for file\n";
	echo "  -k <keywords>: set keywords for file\n";
	echo "  -s <number>: set sequence for file (used for ordering files within a folder\n";
	echo "  -n <name>: set name of file\n";
	echo "  -V <version>: set version of file (defaults to 1).\n";
	echo "  -f <filename>: upload this file\n";
	echo "  -t <mimetype> set mimetype of file manually. Do not do that unless you know\n";
	echo "      what you do. If not set, the mimetype will be determined automatically.\n";
} /* }}} */

$shortoptions = "F:c:k:s:V:f:t:hv";
if(false === ($options = getopt($shortoptions))) {
	usage();
	exit;
}

/* Print help and exit */
if(isset($options['h'])) {
	usage();
	exit;
}

/* Print version and exit */
if(isset($options['v'])) {
	echo $version."\n";
	exit;
}

if(isset($options['F'])) {
	$folderid = (int) $options['F'];
} else {
	echo "Missing folder ID\n";
	usage();
	exit;
}

$comment = '';
if(isset($options['c'])) {
	$comment = $options['c'];
}

$keywords = '';
if(isset($options['k'])) {
	$keywords = $options['k'];
}

$sequence = 0;
if(isset($options['s'])) {
	$sequence = $options['s'];
}

$name = '';
if(isset($options['n'])) {
	$name = $options['n'];
}

$filename = '';
if(isset($options['f'])) {
	$filename = $options['f'];
} else {
	usage();
	exit;
}

$filetype = '';
if(isset($options['t'])) {
	$filetype = $options['t'];
}

$reqversion = 0;
if(isset($options['V'])) {
	$reqversion = $options['V'];
}
if($reqversion<1)
	$reqversion=1;

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

if(is_readable($filename)) {
	if(filesize($filename)) {
		$finfo = new finfo(FILEINFO_MIME);
		if(!$filetype) {
			$filetype = $finfo->file($filename);
		}
	} else {
		echo "File has zero size\n";
		exit;
	}
} else {
	echo "File is not readable\n";
	exit;
}

$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	echo "Could not find specified folder\n";
	exit;
}

if ($folder->getAccessMode($user) < M_READWRITE) {
	echo "Not sufficient access rights\n";
	exit;
}

if (!is_numeric($sequence)) {
	echo "Sequence must be numeric\n";
	exit;
}

//$expires = ($_POST["expires"] == "true") ? mktime(0,0,0, sanitizeString($_POST["expmonth"]), sanitizeString($_POST["expday"]), sanitizeString($_POST["expyear"])) : false;
$expires = false;

if(!$name)
	$name = basename($filename);
$filetmp = $filename;

$reviewers = array();
$approvers = array();

$res = $folder->addDocument($name, $comment, $expires, $user, $keywords,
                            $filetmp, basename($filename),
                            '', $filetype, $sequence,
                            $reviewers, $approvers, $reqversion);

if (is_bool($res) && !$res) {
	echo "Could not add document to folder\n";
	exit;
}
?>
