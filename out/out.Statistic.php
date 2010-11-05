<?php
//    MyDMS. Document Management System
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
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

// TODO: javascript open/close folder
if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("folders_and_documents_statistic"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");

?>
<style type="text/css">
.folderClass {
	list-style-image : url(<?php UI::printImgPath("folder_closed.gif");?>);
	list-style : url(<?php UI::printImgPath("folder_closed.gif");?>);
}

.documentClass {
	list-style-image : url(<?php UI::printImgPath("file.gif");?>);
	list-style : url(<?php UI::printImgPath("file.gif");?>);
}
</style>

<script language="JavaScript">

function showDocument(id) {
	url = "out.DetailedStatistic.php?documentid=" + id;
	alert(url);
}

function showFolder(id) {
	url = "out.DetailedStatistic.php?folderid=" + id;
	alert(url);
}

</script>

<?php

$folder_count=0;
$document_count=0;
$file_count=0;
$storage_size=0;

function getAccessColor($mode)
{
	if ($mode == M_NONE)
		return "gray";
	else if ($mode == M_READ)
		return "green";
	else if ($mode == M_READWRITE)
		return "blue";
	else // if ($mode == M_ALL)
		return "red";
}

function printFolder($folder)
{
	global $folder_count,$settings;
	
	$folder_count++;
	$folder_size=0;
	$doc_count=0;
	
	$color = $folder->inheritsAccess() ? "black" : getAccessColor($folder->getDefaultAccess());
	
	print "<li class=\"folderClass\">";
	print "<a style=\"color: $color\" href=\"out.ViewFolder.php?folderid=".$folder->getID()."\">".$folder->getName() ."</a>";
	
	$owner = $folder->getOwner();
	$color = getAccessColor(M_ALL);
	print " [<span style=\"color: $color\">".$owner->getFullName()."</span>] ";
	
	if (! $folder->inheritsAccess())
		printAccessList($folder);
	
	$subFolders = $folder->getSubFolders();
	$documents = $folder->getDocuments();
	
	print "<ul>";
	
	foreach ($subFolders as $sub) $folder_size += printFolder($sub);
	foreach ($documents as $document){
		$doc_count++;
		$folder_size += printDocument($document);
	}
		
	print "</ul>";
	
	print "<small>".formatted_size($folder_size).", ".$doc_count." ".getMLText("documents")."</small>\n";

	print "</li>";
	
	return $folder_size;
}

function printDocument($document)
{
	global $document_count,$file_count,$settings,$storage_size;
	
	$document_count++;
	
	$local_file_count=0;
	$folder_size=0;
	
	if (file_exists($settings->_contentDir.$document->getDir())){
		$handle = opendir($settings->_contentDir.$document->getDir());
		while ($entry = readdir($handle) )
		{
			if (is_dir($settings->_contentDir.$document->getDir().$entry)) continue;
			else{
				$local_file_count++;
				$folder_size += filesize($settings->_contentDir.$document->getDir().$entry);
			}

		}
		closedir($handle);
	}
	$storage_size += $folder_size;
	
	$color = $document->inheritsAccess() ? "black" : getAccessColor($document->getDefaultAccess());
	print "<li class=\"documentClass\">";
	print "<a style=\"color: $color\" href=\"out.ViewDocument.php?documentid=".$document->getID()."\">".$document->getName()."</a>";
	
	$owner = $document->getOwner();
	$color = getAccessColor(M_ALL);
	print " [<span style=\"color: $color\">".$owner->getFullName()."</span>] ";	
	
	if (! $document->inheritsAccess()) printAccessList($document);
		
	print "<small>".formatted_size($folder_size).", ".$local_file_count." ".getMLText("files")."</small>\n";		
	
	print "</li>";
	
	$file_count += $local_file_count;	
	return $folder_size;
}

function printAccessList($obj)
{
	$accessList = $obj->getAccessList();
	if (count($accessList["users"]) == 0 && count($accessList["groups"]) == 0)
		return;
	
	print " <span>(";
	
	for ($i = 0; $i < count($accessList["groups"]); $i++)
	{
		$group = $accessList["groups"][$i]->getGroup();
		$color = getAccessColor($accessList["groups"][$i]->getMode());
		print "<span style=\"color: $color\">".$group->getName()."</span>";
		if ($i+1 < count($accessList["groups"]) || count($accessList["users"]) > 0)
			print ", ";
	}
	for ($i = 0; $i < count($accessList["users"]); $i++)
	{
		$user = $accessList["users"][$i]->getUser();
		$color = getAccessColor($accessList["users"][$i]->getMode());
		print "<span style=\"color: $color\">".$user->getFullName()."</span>";
		if ($i+1 < count($accessList["users"]))
			print ", ";
	}
	print ")</span>";
}

UI::contentHeading(getMLText("folders_and_documents_statistic"));
UI::contentContainerStart();

print "<table><tr><td>\n";

print "<ul class=\"legend\">\n";
print "<li><span style=\"color:black\">".getMLText("access_inheritance")." </span></li>";
print "<li><span style=\"color:".getAccessColor(M_ALL)."\">".getMLText("access_mode_all")." </span></li>";
print "<li><span style=\"color:".getAccessColor(M_READWRITE)."\">".getMLText("access_mode_readwrite")." </span></li>";
print "<li><span style=\"color:".getAccessColor(M_READ)."\">".getMLText("access_mode_read")." </span></li>";
print "<li><span style=\"color:".getAccessColor(M_NONE)."\">".getMLText("access_mode_none")." </span></li>";
print "</ul>\n";

print "</td><td>\n";

print "<ul>\n";
printFolder(getFolder($settings->_rootFolderID));
print "</ul>\n";

print "</td></tr>";

print "<tr><td colspan=\"2\">";

print "<ul class=\"legend\">\n";
print "<li>".getMLText("folders").": ".$folder_count."</li>\n";
print "<li>".getMLText("documents").": ".$document_count."</li>\n";
print "<li>".getMLText("files").": ".$file_count."</li>\n";
print "<li>".getMLText("storage_size").": ".formatted_size($storage_size)."</li>\n";

print "</ul>\n";

print "</td></tr>";

print "</table>\n";

UI::contentContainerEnd();
UI::htmlEndPage();

?>
