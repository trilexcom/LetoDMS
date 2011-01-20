<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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
include("../inc/inc.ClassUI.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.Authentication.php");

$folderid = sanitizeString($_GET["folderid"]);
$form = sanitizeString($_GET["form"]);

function getImgPath($img) {
  global $theme;
  
  if ( is_file("../themes/$theme/images/$img") )
  {
    return "../themes/$theme/images/$img";
  }
  return "../out/images/$img";
}

function printTree($path, $level = 0)
{
	GLOBAL $user, $form;
	
	$folder = $path[$level];
	$subFolders = LetoDMS_Core_DMS::filterAccess($folder->getSubFolders(), $user, M_READ);
	$documents  = LetoDMS_Core_DMS::filterAccess($folder->getDocuments(), $user, M_READ);
	
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
	else if (count($subFolders) + count($documents) > 0) UI::printImgPath("minus.png");
	else UI::printImgPath("blank.png");
	print "\" border=0>\n";
	if ($folder->getAccessMode($user) >= M_READ) {
		print "<a class=\"foldertree_selectable\" href=\"javascript:folderSelected(" . $folder->getID() . ", '" . sanitizeString($folder->getName()) . "')\">";
		print "<img src=\"".UI::getImgPath("folder_opened.gif")."\" border=0>".$folder->getName()."</a>\n";
	}
	else
		print "<img src=\"".UI::getImgPath("folder_opened.gif")."\" width=18 height=18 border=0>".$folder->getName()."\n";
	print "  </li>\n";

	print "<ul style='list-style-type: none;'>";

	for ($i = 0; $i < count($subFolders); $i++) {
		if ($subFolders[$i]->getID() == $nextFolderID)
			printTree($path, $level+1);
		else {
			print "<li>\n";
			$subFolders_ = LetoDMS_Core_DMS::filterAccess($subFolders[$i]->getSubFolders(), $user, M_READ);
			$documents_  = LetoDMS_Core_DMS::filterAccess($subFolders[$i]->getDocuments(), $user, M_READ);
			
			if (count($subFolders_) + count($documents_) > 0)
				print "<a href=\"out.DocumentChooser.php?form=$form&folderid=".$subFolders[$i]->getID()."\"><img class='treeicon' src=\"".getImgPath("plus.png")."\" border=0></a>";
			else
				print "<img class='treeicon' src=\"".getImgPath("blank.png")."\">";
			print "<img src=\"".getImgPath("folder_closed.gif")."\" border=0>".$subFolders[$i]->getName()."\n";
			print "</li>";
		}
	}
	for ($i = 0; $i < count($documents); $i++) {
		print "<li>\n";
		print "<img class='treeicon' src=\"images/blank.png\">";
		print "<a  class=\"foldertree_selectable\" href=\"javascript:documentSelected(".$documents[$i]->getID().",'".sanitizeString($documents[$i]->getName())."');\"><img src=\"images/file.gif\" border=0>".$documents[$i]->getName()."</a>";
		print "</li>";
	}

	print "</ul>\n";
	if ($level == 0) {
		print "</ul>\n";
	}
	
}

UI::htmlStartPage(getMLText("choose_target_document"));
UI::globalBanner();
UI::pageNavigation(getMLText("choose_target_document"));
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

function documentSelected(id, name) {
	targetName.value = decodeString(name);
	targetID.value = id;
	window.close();
	return true;
}
</script>

<?php
	$folder = $dms->getFolder($folderid);
	UI::contentContainerStart();
	printTree($folder->getPath());
	UI::contentContainerEnd();
?>

<script language="JavaScript">
targetName = opener.document.<?php echo $form?>.docname<?php print $form ?>;
targetID   = opener.document.<?php echo $form?>.docid<?php print $form ?>;
</script>

<?php
UI::htmlEndPage();
?>
