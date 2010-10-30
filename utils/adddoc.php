<?php
ini_set('include_path', '.:/usr/share/php:/usr/share/letodms/www');

include("inc/inc.Settings.php");
include("inc/inc.Utils.php");
include("inc/inc.AccessUtils.php");
include("inc/inc.ClassAccess.php");
include("inc/inc.ClassDocument.php");
include("inc/inc.ClassFolder.php");
include("inc/inc.ClassGroup.php");
include("inc/inc.ClassUser.php");
include("inc/inc.ClassEmail.php");
include("inc/inc.DBAccess.php");
include("inc/inc.DBInit.php");
include("inc/inc.FileUtils.php");
include("inc/inc.Language.php");
include("inc/inc.ClassUIConsole.php");

#$db->_conn->debug = 1;

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

if(isset($options['c'])) {
	$comment = $options['c'];
}

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

/* Create a global user object */
$user = LetoDMS_User::getUser(1);

/* Fake some server variables used in add_log_line() */
$_SERVER['REMOTE_ADDR'] = 'console';
$_SERVER["REQUEST_URI"] = __FILE__;
$_SERVER['HTTP_HOST'] = 'localhost';

if(is_readable($filename)) {
	if(filesize($filename)) {
		$finfo = new finfo(FILEINFO_MIME);
		if(!$filetype) {
			$filetype = $finfo->file($filename);
		}
	} else {
		UIConsole::exitError("","File has zero size");
	}
} else {
	UIConsole::exitError("","File is not readable");
}

$folder = LetoDMS_Folder::getFolder($folderid);

if (!is_object($folder)) {
	UIConsole::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

if ($folder->getAccessMode($user) < M_READWRITE) {
	UIConsole::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

if (!is_numeric($sequence)) {
	UIConsole::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_sequence"));
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
	UIConsole::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_occured"));
}
?>
