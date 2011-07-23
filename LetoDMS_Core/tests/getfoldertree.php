<?php
include("config.php");
include("LetoDMS/LetoDMS_Core.php");

$db = new LetoDMS_Core_DatabaseAccess($g_config['type'], $g_config['hostname'], $g_config['user'], $g_config['passwd'], $g_config['name']);
$db->connect() or die ("Could not connect to db-server \"" . $g_config['hostname'] . "\"");

$dms = new LetoDMS_Core_DMS($db, $g_config['contentDir'], $g_config['contentOffsetDir']);

function tree($folder, $indent='') {
	echo $indent."D ".$folder->getName()."\n";
	$subfolders = $folder->getSubFolders();
	foreach($subfolders as $subfolder) {
		tree($subfolder, $indent.'  ');
	}
	$documents = $folder->getDocuments();
	foreach($documents as $document) {
		echo $indent."  ".$document->getName()."\n";
	}
}

$folder = $dms->getFolder(1);
tree($folder);

?>
