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
include("../inc/inc.ClassUI.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Authentication.php");

$form = sanitizeString($_GET["form"]);
$mode = sanitizeString($_GET["mode"]);
$exclude = sanitizeString($_GET["exclude"]);

UI::htmlStartPage(getMLText("choose_target_folder"));
UI::globalBanner();
UI::pageNavigation(getMLText("choose_target_folder"));
?>

<script language="JavaScript">

function toggleTree(id){
	
	obj = document.getElementById("tree" + id);
	
	if ( obj.style.display == "none" ) obj.style.display = "";
	else obj.style.display = "none";
	
}

function decodeString(s) {
	s = new String(s);
	s = s.replace(/&amp;/, "&");
	s = s.replace(/&#0037;/, "%"); // percent
	s = s.replace(/&quot;/, "\""); // double quote
	s = s.replace(/&#0047;&#0042;/, "/*"); // start of comment
	s = s.replace(/&#0042;&#0047;/, "*/"); // end of comment
	s = s.replace(/&lt;/, "<");
	s = s.replace(/&gt;/, ">");
	s = s.replace(/&#0061;/, "=");
	s = s.replace(/&#0041;/, ")");
	s = s.replace(/&#0040;/, "(");
	s = s.replace(/&#0039;/, "'");
	s = s.replace(/&#0043;/, "+");

	return s;
}

var targetName;
var targetID;

function folderSelected(id, name) {
	targetName.value = decodeString(name);
	targetID.value = id;
	window.close();
	return true;
}
</script>


<?php
	UI::contentContainerStart();
	UI::printFoldersTree($mode, $exclude, $settings->_rootFolderID);
	UI::contentContainerEnd();
?>


<script language="JavaScript">
targetName = opener.document.<?php echo $form?>.targetname<?php print $form ?>;
targetID   = opener.document.<?php echo $form?>.targetid<?php print $form ?>;
</script>

<?php
UI::htmlEndPage();
?>
