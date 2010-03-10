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
include("../inc/inc.ClassKeywords.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

$action = $_GET["action"];

//Neue Kategorie anlegen -----------------------------------------------------------------------------
if ($action == "addcategory") {
	
	$name = sanitizeString($_GET["name"]);
	if (is_object(getKeywordCategoryByName($name, $settings->_adminID))) {
		UI::exitError(getMLText("admin_tools"),getMLText("keyword_exists"));
	}
	$newCategory = addKeywordCategory($settings->_adminID, $name);
	if (!$newCategory) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
}

//Kategorie löschen ----------------------------------------------------------------------------------
else if ($action == "removecategory") {

	if (!isset($_GET["categoryid"]) || !is_numeric($_GET["categoryid"]) || intval($_GET["categoryid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_keyword_category"));
	}
	$categoryid = $_GET["categoryid"];
	$category = getKeywordCategory($categoryid);
	if (!is_object($category)) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_keyword_category"));
	}

	$owner = $category->getOwner();
	if ($owner->getID() != $settings->_adminID) {
		UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
	}
	if (!$category->remove()) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
}

//Kategorie bearbeiten: Neuer Name --------------------------------------------------------------------
else if ($action == "editcategory") {

	if (!isset($_GET["categoryid"]) || !is_numeric($_GET["categoryid"]) || intval($_GET["categoryid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_keyword_category"));
	}
	$categoryid = $_GET["categoryid"];
	$category = getKeywordCategory($categoryid);
	if (!is_object($category)) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_keyword_category"));
	}

	$owner    = $category->getOwner();
	if ($owner->getID() != $settings->_adminID) {
		UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
	}

	$name = sanitizeString($_GET["name"]);
	if (!$category->setName($name)) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
}

//Kategorie bearbeiten: Neue Stichwortliste  ----------------------------------------------------------
else if ($action == "newkeywords") {
	
	$categoryid = sanitizeString($_GET["categoryid"]);
	$category = getKeywordCategory($categoryid);
	$owner    = $category->getOwner();
	if ($owner->getID() != $settings->_adminID) {
		UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
	}

	$keywords = sanitizeString($_GET["keywords"]);
	
	if (!$category->addKeywordList($keywords)) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
}

//Kategorie bearbeiten: Stichwortliste bearbeiten ----------------------------------------------------------
else if ($action == "editkeywords")
{
	if (!isset($_GET["categoryid"]) || !is_numeric($_GET["categoryid"]) || intval($_GET["categoryid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_keyword_category"));
	}
	$categoryid = $_GET["categoryid"];
	$category = getKeywordCategory($categoryid);
	if (!is_object($category)) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_keyword_category"));

	}

	$owner    = $category->getOwner();
	if ($owner->getID() != $settings->_adminID) {
		UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
	}

	if (!isset($_GET["keywordsid"]) || !is_numeric($_GET["keywordsid"]) || intval($_GET["keywordsid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_id"));
	}
	$keywordsid = $_GET["keywordsid"];

	$keywords = sanitizeString($_GET["keywords"]);
	if (!$category->editKeywordList($keywordsid, $keywords)) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
}

//Kategorie bearbeiten: Neue Stichwortliste löschen ----------------------------------------------------------
else if ($action == "removekeywords") {
	
	if (!isset($_GET["categoryid"]) || !is_numeric($_GET["categoryid"]) || intval($_GET["categoryid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_keyword_category"));
	}
	$categoryid = $_GET["categoryid"];
	$category = getKeywordCategory($categoryid);
	if (!is_object($category)) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_keyword_category"));
	}

	$owner    = $category->getOwner();
	if ($owner->getID() != $settings->_adminID) {
		UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
	}

	if (!isset($_GET["keywordsid"]) || !is_numeric($_GET["keywordsid"]) || intval($_GET["keywordsid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("unknown_id"));
	}
	$keywordsid = $_GET["keywordsid"];

	if (!$category->removeKeywordList($keywordsid)) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
}
else {
	UI::exitError(getMLText("admin_tools"),getMLText("unknown_command"));
}

header("Location:../out/out.DefaultKeywords.php");

?>
