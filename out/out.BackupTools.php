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
include("../inc/inc.DBInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

// funcion by shalless at rubix dot net dot au (php.net)
function dskspace($dir)
{
   $s = stat($dir);
   $space = $s["blocks"]*512;
   if (is_dir($dir))
   {
     $dh = opendir($dir);
     while (($file = readdir($dh)) !== false)
       if ($file != "." and $file != "..")
         $space += dskspace($dir."/".$file);
     closedir($dh);
   }
   return $space;
} 

UI::htmlStartPage(getMLText("backup_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");

UI::contentHeading(getMLText("backup_tools"));
UI::contentContainerStart();
print getMLText("space_used_on_data_folder")." : ".formatted_size(dskspace($settings->_contentDir));
UI::contentContainerEnd();

// versioning file creation ////////////////////////////////////////////////////

UI::contentHeading(getMLText("versioning_file_creation"));
UI::contentContainerStart();
print "<p>".getMLText("versioning_file_creation_warning")."</p>\n";

print "<form action=\"../op/op.CreateVersioningFiles.php\" name=\"form1\">";
UI::printFolderChooser("form1",M_READWRITE);
print "<input type='submit' name='' value='".getMLText("versioning_file_creation")."'/>";
print "</form>\n";

UI::contentContainerEnd();

// archive creation ////////////////////////////////////////////////////////////

UI::contentHeading(getMLText("archive_creation"));
UI::contentContainerStart();
print "<p>".getMLText("archive_creation_warning")."</p>\n";

print "<form action=\"../op/op.CreateFolderArchive.php\" name=\"form2\">";
UI::printFolderChooser("form2",M_READWRITE);
print "<input type=\"checkbox\" name=\"human_readable\" value=\"1\">".getMLText("human_readable");
print "<input type='submit' name='' value='".getMLText("archive_creation")."'/>";
print "</form>\n";

// list backup files
UI::contentSubHeading(getMLText("backup_list"));

$print_header=true;

$handle = opendir($settings->_contentDir);
$entries = array();
while ($e = readdir($handle)){
	if (is_dir($settings->_contentDir.$e)) continue;
	if (strpos($e,".tar.gz")==FALSE) continue;
	$entries[] = $e;
}
closedir($handle);

sort($entries);
$entries = array_reverse($entries);

foreach ($entries as $entry){

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
	$folder=$dms->getFolder((int)$folderid);
			
	print "<tr>\n";
	print "<td><a href=\"../op/op.Download.php?arkname=".$entry."\">".$entry."</a></td>\n";
	if (is_object($folder)) print "<td>".$folder->getName()."</td>\n";
	else print "<td>".getMLText("unknown_id")."</td>\n";
	print "<td>".getLongReadableDate(filectime($settings->_contentDir.$entry))."</td>\n";
	print "<td>".formatted_size(filesize($settings->_contentDir.$entry))."</td>\n";
	print "<td><ul class=\"actions\">";
	print "<li><a href=\"out.RemoveArchive.php?arkname=".$entry."\">".getMLText("backup_remove")."</a></li>";
	print "</ul></td>\n";	
	print "</tr>\n";
}

if ($print_header) printMLText("empty_notify_list");
else print "</table>\n";

UI::contentContainerEnd();

// dump creation ///////////////////////////////////////////////////////////////

UI::contentHeading(getMLText("dump_creation"));
UI::contentContainerStart();
print "<p>".getMLText("dump_creation_warning")."</p>\n";

print "<form action=\"../op/op.CreateDump.php\" name=\"form4\">";
print "<input type='submit' name='' value='".getMLText("dump_creation")."'/>";
print "</form>\n";

// list backup files
UI::contentSubHeading(getMLText("dump_list"));

$print_header=true;

$handle = opendir($settings->_contentDir);
$entries = array();
while ($e = readdir($handle)){
	if (is_dir($settings->_contentDir.$e)) continue;
	if (strpos($e,".sql.gz")==FALSE) continue;
	$entries[] = $e;
}
closedir($handle);

sort($entries);
$entries = array_reverse($entries);

foreach ($entries as $entry){

	if ($print_header){
		print "<table class=\"folderView\">\n";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";
		print "<th>".getMLText("creation_date")."</th>\n";
		print "<th>".getMLText("file_size")."</th>\n";
		print "<th></th>\n";
		print "</tr>\n</thead>\n<tbody>\n";
		$print_header=false;
	}

	print "<tr>\n";
	print "<td><a href=\"../op/op.Download.php?dumpname=".$entry."\">".$entry."</a></td>\n";
	print "<td>".getLongReadableDate(filesize($settings->_contentDir.$entry))."</td>\n";
	print "<td>".formatted_size(filesize($settings->_contentDir.$entry))."</td>\n";
	print "<td><ul class=\"actions\">";
	print "<li><a href=\"out.RemoveDump.php?dumpname=".$entry."\">".getMLText("dump_remove")."</a></li>";
	print "</ul></td>\n";	
	print "</tr>\n";
}

if ($print_header) printMLText("empty_notify_list");
else print "</table>\n";

UI::contentContainerEnd();

// files deletion //////////////////////////////////////////////////////////////

UI::contentHeading(getMLText("files_deletion"));
UI::contentContainerStart();
print "<p>".getMLText("files_deletion_warning")."</p>\n";

print "<form action=\"../out/out.RemoveFolderFiles.php\" name=\"form3\">";
UI::printFolderChooser("form3",M_READWRITE);
print "<input type='submit' name='' value='".getMLText("files_deletion")."'/>";
print "</form>\n";

UI::contentContainerEnd();

UI::htmlEndPage();
?>
