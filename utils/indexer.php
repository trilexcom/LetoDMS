<?php
ini_set('include_path', '.:/usr/share/php:/usr/share/letodms/www');

require_once("inc/inc.ClassSettings.php");
require_once("LetoDMS/Core.php");
require_once("LetoDMS/Lucene.php");

function usage() { /* {{{ */
	echo "Usage:\n";
	echo "  letodms-indexer [-h] [-v] [--config <file>]\n";
	echo "\n";
	echo "Description:\n";
	echo "  This program recreates the full text index of LetoDMS.\n";
	echo "\n";
	echo "Options:\n";
	echo "  -h, --help: print usage information and exit.\n";
	echo "  -v, --version: print version and exit.\n";
	echo "  --config: set alternative config file.\n";
} /* }}} */

$version = "0.0.1";
$shortoptions = "hv";
$longoptions = array('help', 'version', 'config:');
if(false === ($options = getopt($shortoptions, $longoptions))) {
	usage();
	exit(0);
}

/* Print help and exit */
if(isset($options['h']) || isset($options['help'])) {
	usage();
	exit(0);
}

/* Print version and exit */
if(isset($options['v']) || isset($options['verѕion'])) {
	echo $version."\n";
	exit(0);
}

/* Set alternative config file */
if(isset($options['config'])) {
	$settings = new Settings($options['config']);
} else {
	$settings = new Settings();
}

function tree($folder, $indent='') {
	global $index, $dms;
	echo $indent."D ".$folder->getName()."\n";
	$subfolders = $folder->getSubFolders();
	foreach($subfolders as $subfolder) {
		tree($subfolder, $indent.'  ');
	}
	$documents = $folder->getDocuments();
	foreach($documents as $document) {
		echo $indent."  ".$document->getId().":".$document->getName()."\n";
		if(!($hits = $index->find('document_id:'.$document->getId()))) {
			$index->addDocument(new LetoDMS_Lucene_IndexedDocument($dms, $document));
		} else {
			$hit = $hits[0];
			$created = (int) $hit->getDocument()->getFieldValue('created');
			if($created >= $document->getDate()) {
				echo $indent."    Document unchanged\n";
			} else {
				if($index->delete($hit->id)) {
					$index->addDocument(new LetoDMS_Lucene_IndexedDocument($dms, $document));
				}
			}
		}
	}
}

$db = new LetoDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");

$dms = new LetoDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);
$dms->setRootFolderID($settings->_rootFolderID);

$index = Zend_Search_Lucene::create($settings->_luceneDir);

$folder = $dms->getFolder($settings->_rootFolderID);
tree($folder);

$index->commit();
?>
