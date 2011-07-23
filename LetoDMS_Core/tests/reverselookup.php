<?php
include("config.php");
include("LetoDMS/LetoDMS_Core.php");

$db = new LetoDMS_Core_DatabaseAccess($g_config['type'], $g_config['hostname'], $g_config['user'], $g_config['passwd'], $g_config['name']);
$db->connect() or die ("Could not connect to db-server \"" . $g_config['hostname'] . "\"");

$dms = new LetoDMS_Core_DMS($db, $g_config['contentDir'], $g_config['contentOffsetDir']);

$path = '/Test 1/';
echo "Searching for folder or document with path '".$path."'\n";

$root = $dms->getRootFolder();
if($path[0] == '/') {
	$path = substr($path, 1);
}
$patharr = explode('/', $path);
/* The last entry is always the document, though if the path ends in '/' the
 * document name will be empty.
 */
$docname = array_pop($patharr);
$parentfolder = $root;

foreach($patharr as $pathseg) {
	if($folder = $dms->getFolderByName($pathseg, $parentfolder)) {
		$parentfolder = $folder;
	}
}
if($folder) {
	if($docname) {
		if($document = $dms->getDocumentByName($docname, $folder)) {
			echo "Given path is document '".$document->getName()."'\n";
		} else {
			echo "No object found\n";
		}
	} else {
		echo "Given path is a folder '".$folder->getName()."'\n";
	}
} else {
	echo "No object found\n";
}

?>

