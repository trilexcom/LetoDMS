<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
	$folderid = $settings->_rootFolderID;
}
else {
	$folderid = $_GET["folderid"];
}
$folder = getFolder($folderid);
if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$showtree=showtree();

if (isset($_GET["orderby"]) && strlen($_GET["orderby"])==1 ) {
	$orderby=$_GET["orderby"];
}else $orderby="";

$folderPathHTML = getFolderPathHTML($folder);

if ($folder->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("folder_title", array("foldername" => $folder->getName())));

UI::globalNavigation($folder);
UI::pageNavigation($folderPathHTML, "view_folder", $folder);

if ($settings->_enableFolderTree) UI::printTreeNavigation($folderid,$showtree);

UI::contentHeading(getMLText("folder_infos"));

$owner = $folder->getOwner();
UI::contentContainer("<table>\n<tr>\n".
			"<td>".getMLText("owner").":</td>\n".
			"<td><a class=\"infos\" href=\"mailto:".$owner->getEmail()."\">".$owner->getFullName()."</a>".
			"</td>\n</tr>\n<tr>\n".
			"<td>".getMLText("comment").":</td>\n".
			"<td>".$folder->getComment()."</td>\n</tr>\n</table>\n");

UI::contentHeading(getMLText("folder_contents"));
UI::contentContainerStart();

$subFolders = $folder->getSubFolders($orderby);
$subFolders = filterAccess($subFolders, $user, M_READ);
$documents = $folder->getDocuments($orderby);
$documents = filterAccess($documents, $user, M_READ);

if ((count($subFolders) > 0)||(count($documents) > 0)){
	print "<table class=\"folderView\">";
	print "<thead>\n<tr>\n";
	print "<th></th>\n";	
	print "<th><a href=\"../out/out.ViewFolder.php?folderid=". $folderid .($orderby=="n"?"":"&orderby=n")."\">".getMLText("name")."</a></th>\n";
	print "<th>".getMLText("owner")."</th>\n";
	print "<th>".getMLText("status")."</th>\n";
	print "<th>".getMLText("version")."</th>\n";
	print "<th>".getMLText("comment")."</th>\n";
	print "</tr>\n</thead>\n<tbody>\n";
}
else printMLText("empty_notify_list");


foreach($subFolders as $subFolder) {

	$owner = $subFolder->getOwner();
	$comment = $subFolder->getComment();
	if (strlen($comment) > 50) $comment = substr($comment, 0, 47) . "...";
	$subsub = $subFolder->getSubFolders();
	$subsub = filterAccess($subsub, $user, M_READ);
	$subdoc = $subFolder->getDocuments();
	$subdoc = filterAccess($subdoc, $user, M_READ);
	
	print "<tr class=\"folder\">";
	print "<td><img src=\"images/folder_closed.gif\" width=18 height=18 border=0></td>";
	print "<td><a href=\"out.ViewFolder.php?folderid=".$subFolder->getID()."&showtree=".$showtree."\">" . $subFolder->getName() . "</a></td>\n";
	print "<td>".$owner->getFullName()."</td>";
	print "<td colspan=\"2\"><small>".count($subsub)." ".getMLText("folders").", ".count($subdoc)." ".getMLText("documents")."</small></td>";
	print "<td>".$comment."</td>";
	print "</tr>\n";
}

foreach($documents as $document) {

	$owner = $document->getOwner();
	$comment = $document->getComment();
	if (strlen($comment) > 50) $comment = substr($comment, 0, 47) . "...";
	$docID = $document->getID();
	$latestContent = $document->getLatestContent();
	$version = $latestContent->getVersion();
	$status = $latestContent->getStatus();
	
	print "<tr>";
	
	if (file_exists($settings->_contentDir . $latestContent->getPath()))
		print "<td><a href=\"../op/op.Download.php?documentid=".$docID."&version=".$version."\"><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($latestContent->getFileType())."\" title=\"".$latestContent->getMimeType()."\"></a></td>";
	else print "<td><img class=\"mimeicon\" src=\"images/icons/".UI::getMimeIcon($latestContent->getFileType())."\" title=\"".$latestContent->getMimeType()."\"></td>";
	
	print "<td><a href=\"out.ViewDocument.php?documentid=".$docID."&showtree=".$showtree."\">" . $document->getName() . "</a></td>\n";
	print "<td>".$owner->getFullName()."</td>";
	print "<td>".getOverallStatusText($status["status"])."</td>";
	print "<td>".$version."</td>";
	print "<td>".$comment."</td>";
	print "</tr>\n";
}

if ((count($subFolders) > 0)||(count($documents) > 0)) echo "</tbody>\n</table>\n";

UI::contentContainerEnd();

if ($settings->_enableFolderTree) print "</td></tr></table>";

UI::htmlEndPage();
?>
