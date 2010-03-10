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
$folderid = sanitizeString($_GET["folderid"]);

function printTree($path, $accessMode, $exclude, $level = 0)
{
	GLOBAL $user, $form;
	
	$folder = $path[$level];
	$subFolders = $folder->getSubFolders();
	$subFolders = filterAccess($subFolders, $user, M_READ);
	
	if ($level+1 < count($path))
		$nextFolderID = $path[$level+1]->getID();
	else
		$nextFolderID = -1;
	
	if ($level == 0) {
		print "<ul style='list-style-type: none;'>\n";
	}
	print "  <li>\n";
	print "<img class='treeicon' src=\"";
	if ($level == 0) UI::printImgPath("minus.png");
	else if (count($subFolders) > 0) UI::printImgPath("minus.png");
	else UI::printImgPath("blank.png");
	print "\" border=0>\n";
	if ($folder->getAccessMode($user) >= $accessMode) {
		print "<a class=\"foldertree_selectable\" href=\"javascript:folderSelected(" . $folder->getID() . ", '" . sanitizeString($folder->getName()) . "')\">";
		print "<img src=\"".UI::getImgPath("folder_opened.gif")."\" border=0>".$folder->getName()."</a>\n";
	}
	else
		print "<img src=\"".UI::getImgPath("folder_opened.gif")."\" width=18 height=18 border=0>".$folder->getName()."\n";
	print "  </li>\n";

	print "<ul style='list-style-type: none;'>\n";
	
	for ($i = 0; $i < count($subFolders); $i++) {
		if ($subFolders[$i]->getID() == $exclude)
			continue;
		
		if ($subFolders[$i]->getID() == $nextFolderID)
			printTree($path, $accessMode, $exclude, $level+1);
		else {
			print "<li>\n";
			$subFolders_ = $subFolders[$i]->getSubFolders();
			$subFolders_ = filterAccess($subFolders_, $user, M_READ);
			
			if (count($subFolders_) > 0)
				print "<a href=\"out.FolderChooser.php?form=$form&mode=$accessMode&exclude=$exclude&folderid=".$subFolders[$i]->getID()."\"><img class='treeicon' src=\"".UI::getImgPath("plus.png")."\" border=0></a>";
			else
				print "<img class='treeicon' src=\"".UI::getImgPath("blank.png")."\">";
			if ($subFolders[$i]->getAccessMode($user) >= $accessMode) {
				print "<a class=\"foldertree_selectable\" href=\"javascript:folderSelected(" . $subFolders[$i]->getID() . ", '" . sanitizeString($subFolders[$i]->getName()) . "')\">";
				print "<img src=\"".UI::getImgPath("folder_closed.gif")."\" border=0>".$subFolders[$i]->getName()."</a>\n";
			}
			else
				print "<img src=\"".UI::getImgPath("folder_closed.gif")."\" border=0>".$subFolders[$i]->getName();
			print "</li>\n";
		}
	}

	print "</ul>\n";
	if ($level == 0) {
		print "</ul>\n";
	}
	

}

UI::htmlStartPage(getMLText("choose_target_folder"));
UI::globalBanner();
UI::pageNavigation(getMLText("choose_target_folder"));
?>

<script language="JavaScript">
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
	$folder = getFolder($folderid);
	UI::contentContainerStart();
	printTree($folder->getPath(), $mode, $exclude);
	UI::contentContainerEnd();
?>


<script language="JavaScript">
targetName = opener.document.<?php echo $form?>.targetname;
targetID   = opener.document.<?php echo $form?>.targetid;
</script>

<?php
UI::htmlEndPage();
?>
