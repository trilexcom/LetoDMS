<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2011 Matteo Lucarelli
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

include("../inc/inc.Version.php");
include("../inc/inc.Settings.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

function tree($folder, $indent='') { /* {{{ */
	global $index, $dms;
	echo $indent."D ".$folder->getName()."\n";
	$subfolders = $folder->getSubFolders();
	foreach($subfolders as $subfolder) {
		tree($subfolder, $indent.'  ');
	}
	$documents = $folder->getDocuments();
	foreach($documents as $document) {
		echo $indent."  ".$document->getId().":".$document->getName()." ";
		/* If the document wasn't indexed before then just add it */
		if(!($hits = $index->find('document_id:'.$document->getId()))) {
			$index->addDocument(new LetoDMS_Lucene_IndexedDocument($dms, $document));
			echo "(document added)";
		} else {
			$hit = $hits[0];
			$created = (int) $hit->getDocument()->getFieldValue('created');
			$content = $document->getLatestContent();
			if($created >= $content->getDate()) {
				echo $indent."(document unchanged)";
			} else {
				if($index->delete($hit->id)) {
					$index->addDocument(new LetoDMS_Lucene_IndexedDocument($dms, $document));
				}
				echo $indent."(document updated)";
			}
		}
		echo "\n";
	}
} /* }}} */

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

$v = new LetoDMS_Version;

UI::htmlStartPage($v->banner());
UI::globalNavigation();
UI::pageNavigation($v->banner());
UI::contentContainerStart();
if($settings->_enableFullSearch) {
	if(!empty($settings->_luceneDir))
		require_once($settings->_luceneDir.'/Lucene.php');
	else
		require_once('LetoDMS/Lucene.php');

	if(isset($_GET['create']) && $_GET['create'] == 1) {
		if(isset($_GET['confirm']) && $_GET['confirm'] == 1) {
			echo "<p>Recreating index</p>";
			$index = Zend_Search_Lucene::create($settings->_indexPath);
		} else {
			echo '<p>'.getMLText('create_fulltext_index_warning').'</p>';
			echo '<a href="out.Indexer.php?create=1&confirm=1">'.getMLText('confirm_create_fulltext_index').'</a>';
			UI::contentContainerEnd();
			UI::htmlEndPage();
			exit;
		}
	} else {
		echo "<p>Updating index</p>";
		$index = Zend_Search_Lucene::open($settings->_indexPath);
	}

	if($settings->_stopWordsFile && file_exists($settings->_stopWordsFile)) {
		$stopWordsFilter = new Zend_Search_Lucene_Analysis_TokenFilter_StopWords();
		$stopWordsFilter->loadFromFile($settings->_stopWordsFile);
	 
		$analyzer = new Zend_Search_Lucene_Analysis_Analyzer_Common_TextNum_CaseInsensitive();
		$analyzer->addFilter($stopWordsFilter);
	 
		Zend_Search_Lucene_Analysis_Analyzer::setDefault($analyzer);
	}

	$folder = $dms->getFolder($settings->_rootFolderID);
	echo "<pre>";
	tree($folder);
	echo "</pre>";

	$index->commit();
} else {
	printMLText("fulltextsearch_disabled");
}
UI::contentContainerEnd();
UI::htmlEndPage();
?>
