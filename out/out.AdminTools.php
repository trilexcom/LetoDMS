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
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("admin_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");
UI::contentContainerStart();
?>
	<ul>
		<li class="first"><a href="../out/out.Statistic.php"><?php echo getMLText("folders_and_documents_statistic")?></a></li>
		<li><a href="../out/out.BackupTools.php"><?php echo getMLText("backup_tools")?></a></li>
<?php		
		if ($settings->_logFileEnable) echo "<li><a href=\"../out/out.LogManagement.php\">".getMLText("log_management")."</a></li>";
?>
		<li><a href="../out/out.UsrMgr.php"><?php echo getMLText("user_management")?></a></li>
		<li><a href="../out/out.GroupMgr.php"><?php echo getMLText("group_management")?></a></li>
		<li><a href="../out/out.DefaultKeywords.php"><?php echo getMLText("global_default_keywords")?></a></li>
		<li><a href="../out/out.Categories.php"><?php echo getMLText("global_document_categories")?></a></li>
		<li><a href="../out/out.Info.php"><?php echo getMLText("version_info")?></a></li>
<?php
if($settings->_enableFullSearch) {
?>
		<li><a href="../out/out.Indexer.php"><?php echo getMLText("update_fulltext_index")?></a></li>
		<li><a href="../out/out.Indexer.php?create=1"><?php echo getMLText("create_fulltext_index")?></a></li>
		<li><a href="../out/out.IndexInfo.php"><?php echo getMLText("fulltext_info")?></a></li>
<?php
}
?>
	<li><a href="../out/out.ImportCSV.php"><?php echo getMLText("importcsv")?></a></li>
	</ul>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
