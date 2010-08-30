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

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

if (isset($_GET["logname"])) $logname=$_GET["logname"];
else if (@readlink($settings->_contentDir."current.log")){
	$logname=basename(@readlink($settings->_contentDir."current.log"));
}else $logname=NULL;


UI::htmlStartPage(getMLText("backup_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");

UI::contentHeading(getMLText("log_management"));
UI::contentContainerStart();

$print_header=true;

$handle = opendir($settings->_contentDir);
$entries = array();
while ($e = readdir($handle)){
	if (is_dir($settings->_contentDir.$e)) continue;
	if (strpos($e,".log")==FALSE) continue;
	if (strcmp($e,"current.log")==0) continue;
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
	print "<td><a href=\"out.LogManagement.php?logname=".$entry."\">".$entry."</a></td>\n";
	print "<td>".getLongReadableDate(filectime($settings->_contentDir.$entry))."</td>\n";
	print "<td>".formatted_size(filesize($settings->_contentDir.$entry))."</td>\n";
	print "<td><ul class=\"actions\">";
	
	if (@readlink($settings->_contentDir."current.log")!=$settings->_contentDir.$entry)
		print "<li><a href=\"out.RemoveLog.php?logname=".$entry."\">".getMLText("rm_file")."</a></li>";
	
	print "<li><a href=\"../op/op.Download.php?logname=".$entry."\">".getMLText("download")."</a></li>";
		
	print "</ul></td>\n";	
	print "</tr>\n";
}

if ($print_header) printMLText("empty_notify_list");
else print "</table>\n";

UI::contentContainerEnd();

if (file_exists($settings->_contentDir.$logname)){

	UI::contentHeading("&nbsp;");
	UI::contentContainerStart();
	
	UI::contentSubHeading($logname);

	echo "<div class=\"logview\">";
	echo "<pre>\n";
	readfile($settings->_contentDir.$logname);
	echo "</pre>\n";
	echo "</div>";

	UI::contentContainerEnd();
}


UI::htmlEndPage();
?>
