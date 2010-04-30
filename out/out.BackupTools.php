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
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

function getFolderOption($folder,$level,$last=FALSE)
{
	// draw an "ASCII tree"
	$spacer="";
	for ($i=0;$i<$level;$i++){
		if ($i<$level-1) $spacer .="|   ";
		else{
			if ($last) $spacer .="`-- ";
			else  $spacer .="|-- ";
		}
	}
	
	$ret="<option value='".$folder->getID()."'>".$spacer.$folder->getName()."</option>\n";
	
	$subFolders = $folder->getSubFolders();
	
	for ($i=0;$i<count($subFolders);$i++)
		$ret = $ret.getFolderOption($subFolders[$i],$level+1,($i==(count($subFolders)-1)));
		
	return $ret;
}

$folder_option_list = getFolderOption(getFolder($settings->_rootFolderID),0);

UI::htmlStartPage(getMLText("backup_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");

UI::contentHeading(getMLText("versioning_file_creation"));
UI::contentContainerStart();
print "<p>".getMLText("versioning_file_creation_warning")."</p>";

print "<form method=\"POST\" action=\"../op/op.CreateVersioningFiles.php\" name=\"form1\">";
print "<select name=\"folderid\"><option value=''>".getMLText("select_one")."</option>";
print $folder_option_list;
print "</select>";
print "<input type='submit' name='' value='".getMLText("versioning_file_creation")."'/>";
print "</form>";

UI::contentContainerEnd();

UI::contentHeading(getMLText("archive_creation"));
UI::contentContainerStart();
print "<p>".getMLText("archive_creation_warning")."</p>";

print "<form method=\"POST\" action=\"../op/op.CreateFolderArchive.php\" name=\"form2\">";
print "<select name=\"folderid\"><option value=''>".getMLText("select_one")."</option>";
print $folder_option_list;
print "</select>";
print "<input type='submit' name='' value='".getMLText("archive_creation")."'/>";
print "</form>";

// list backup files
UI::contentSubHeading(getMLText("backup_list"));

$print_header=true;

$handle = opendir($settings->_contentDir);
while ($entry = readdir($handle))
{
	if (!is_dir($settings->_contentDir.$entry)){
	
		if ($print_header){
			print "<table class=\"folderView\">\n";
			print "<thead>\n<tr>\n";
			print "<th></th>\n";
			print "<th>".getMLText("folder")."</th>\n";
			print "<th>".getMLText("creation_date")."</th>\n";
			print "<th>".getMLText("file_size")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			$print_header=false;
		}

		$folderid=substr($entry,strpos($entry,"_")+1);
		$folder=getFolder((int)$folderid);
				
		print "<tr>\n";
		print "<td><a href=\"../op/op.Download.php?arkname=".$entry."\">".$entry."</a></td>\n";
		if (is_object($folder)) print "<td>".$folder->getName()."</td>\n";
		else print "<td>".getMLText("unknown_id")."</td>\n";
		print "<td>".getLongReadableDate($entry)."</td>\n";
		print "<td>".formatted_size(filesize($settings->_contentDir.$entry))."</td>\n";
		print "<td><ul class=\"actions\">";
		print "<li><a href=\"out.RemoveArchive.php?arkname=".$entry."\">".getMLText("backup_remove")."</a></li>";
		print "</ul></td>\n";	
		print "</tr>\n";
	}

}
closedir($handle);

if ($print_header) printMLText("empty_notify_list");
else print "</table>\n";


UI::contentContainerEnd();

UI::contentHeading(getMLText("files_deletion"));
UI::contentContainerStart();
print "<p>".getMLText("files_deletion_warning")."</p>";

print "<form method=\"POST\" action=\"../out/out.RemoveFolderFiles.php\" name=\"form3\">";
print "<select name=\"folderid\"><option value=''>".getMLText("select_one")."</option>";
print $folder_option_list;
print "</select>";
print "<input type='submit' name='' value='".getMLText("files_deletion")."'/>";
print "</form>";

UI::contentContainerEnd();

UI::htmlEndPage();
?>
