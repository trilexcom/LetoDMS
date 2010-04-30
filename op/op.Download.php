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
include("../inc/inc.AccessUtils.php");
include("../inc/inc.ClassAccess.php");
include("../inc/inc.ClassDocument.php");
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (isset($_GET["version"])){

	// document download
	
	if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
	}

	$documentid = $_GET["documentid"];
	$document = getDocument($documentid);

	if (!is_object($document)) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));

	}
	$folder = $document->getFolder();
	$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

	if ($document->getAccessMode($user) < M_READ) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}

	if (!is_numeric($_GET["version"]) || intval($_GET["version"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
	}
	$version = $_GET["version"];
	$content = $document->getContentByVersion($version);

	if (!is_object($content)) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
	}
	
	header("Content-Type: application/force-download; name=\"" . mydmsDecodeString($content->getOriginalFileName()) . "\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: " . filesize($settings->_contentDir . $content->getPath() ));
	header("Content-Disposition: attachment; filename=\"" . mydmsDecodeString($content->getOriginalFileName()) . "\"");
	//header("Expires: 0");
	//header("Content-Type: " . $content->getMimeType());
	//header("Cache-Control: no-cache, must-revalidate");
	header("Cache-Control: must-revalidate");
	//header("Pragma: no-cache");

	readfile($settings->_contentDir . $content->getPath());

}else if (isset($_GET["file"])){

	// file download
	
	if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
	}

	$documentid = $_GET["documentid"];
	$document = getDocument($documentid);

	if (!is_object($document)) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));

	}
	$folder = $document->getFolder();
	$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

	if ($document->getAccessMode($user) < M_READ) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}

	if (!is_numeric($_GET["file"]) || intval($_GET["file"])<1) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_file_id"));
	}
	$fileid = $_GET["file"];
	$file = getDocumentFile($fileid);

	if (!is_object($file)) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_file_id"));
	}

	header("Content-Type: application/force-download; name=\"" . mydmsDecodeString($file->getOriginalFileName()) . "\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: " . filesize($settings->_contentDir . $file->getPath() ));
	header("Content-Disposition: attachment; filename=\"" . mydmsDecodeString($file->getOriginalFileName()) . "\"");
	//header("Expires: 0");
	//header("Content-Type: " . $content->getMimeType());
	//header("Cache-Control: no-cache, must-revalidate");
	header("Cache-Control: must-revalidate");
	//header("Pragma: no-cache");

	readfile($settings->_contentDir . $file->getPath());

}else if (isset($_GET["arkname"])){

	// backup download
	
	if (!$user->isAdmin()) {
		UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
	}

	if (!isset($_GET["arkname"]) || !file_exists($settings->_contentDir.$_GET["arkname"]) ) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_id"));
	}

	header("Content-Type: application/force-download; name=\"" . $_GET["arkname"] . "\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: " . filesize($settings->_contentDir . $_GET["arkname"] ));
	header("Content-Disposition: attachment; filename=\"" .$_GET["arkname"] . "\"");
	//header("Expires: 0");
	//header("Content-Type: " . $content->getMimeType());
	//header("Cache-Control: no-cache, must-revalidate");
	header("Cache-Control: must-revalidate");
	//header("Pragma: no-cache");	
	
	readfile($settings->_contentDir .$_GET["arkname"] );
	
}else if (isset($_GET["vfile"])){

	// versioning info download
	
	$documentid = $_GET["documentid"];
	$document = getDocument($documentid);

	if (!is_object($document)) {
		UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));

	}	
	
	header("Content-Type: application/force-download; name=\"" . $settings->_versioningFileName . "\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: " . filesize($settings->_contentDir.$document->getDir().$settings->_versioningFileName )."\"");
	header("Content-Disposition: attachment; filename=\"". $settings->_versioningFileName . "\"");
	//header("Expires: 0");
	//header("Content-Type: " . $content->getMimeType());
	//header("Cache-Control: no-cache, must-revalidate");
	header("Cache-Control: must-revalidate");
	//header("Pragma: no-cache");	
	
	readfile($settings->_contentDir . $document->getDir() .$settings->_versioningFileName);
}

exit();
?>
