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
include("../inc/inc.Authentication.php");

if (isset($_POST["action"])) {
	$action = sanitizeString($_POST["action"]);
}
else {
	$action = sanitizeString($_GET["action"]);
}

//Neue Kategorie anlegen -----------------------------------------------------------------------------
if ($action == "addcategory") {

	if (isset($_POST["name"])) {
		$name = sanitizeString($_POST["name"]);
	}
	else {
		$name = sanitizeString($_GET["name"]);
	}
	
	$newCategory = addKeywordCategory($user->getID(), $name);
	if (!$newCategory) {
		UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
	}
}


//Kategorie löschen ----------------------------------------------------------------------------------
else if ($action == "removecategory") {
	if (isset($_POST["categoryid"])) {
		$categoryid = sanitizeString($_POST["categoryid"]);
	}
	else {
		$categoryid = sanitizeString($_GET["categoryid"]);
	}
	$category = getKeywordCategory($categoryid);
	if (is_object($category)) {
		$owner    = $category->getOwner();
		if ($owner->getID() != $user->getID()) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("access_denied"));
		}
		if (!$category->remove()) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
		}
	}
	else UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
}

//Kategorie bearbeiten: Neuer Name --------------------------------------------------------------------
else if ($action == "editcategory") {
	if (isset($_POST["categoryid"])) {
		$categoryid = sanitizeString($_POST["categoryid"]);
	}
	else {
		$categoryid = sanitizeString($_GET["categoryid"]);
	}
	$category = getKeywordCategory($categoryid);
	if (is_object($category)) {
		$owner = $category->getOwner();
		if ($owner->getID() != $user->getID()) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("access_denied"));
		}
		if (isset($_POST["name"])) {
			$name = sanitizeString($_POST["name"]);
		}
		else {
			$name = sanitizeString($_GET["name"]);
		}

		if (!$category->setName($name)) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
		}
	}
	else UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
}

//Kategorie bearbeiten: Neue Stichwortliste  ----------------------------------------------------------
else if ($action == "newkeywords") {
	if (isset($_POST["categoryid"])) {
		$categoryid = sanitizeString($_POST["categoryid"]);
	}
	else {
		$categoryid = sanitizeString($_GET["categoryid"]);
	}
	$category = getKeywordCategory($categoryid);
	if (is_object($category)) {
		$owner    = $category->getOwner();
		if ($owner->getID() != $user->getID()) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("access_denied"));
		}

		if (isset($_POST["keywords"])) {
			$keywords = sanitizeString($_POST["keywords"]);
		}
		else {
			$keywords = sanitizeString($_GET["keywords"]);
		}
		if (!$category->addKeywordList($keywords)) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
		}
	}
	else UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
}

//Kategorie bearbeiten: Stichwortliste bearbeiten ----------------------------------------------------------
else if ($action == "editkeywords") {
	if (isset($_POST["categoryid"])) {
		$categoryid = sanitizeString($_POST["categoryid"]);
	}
	else {
		$categoryid = sanitizeString($_GET["categoryid"]);
	}
	$category = getKeywordCategory($categoryid);
	if (is_object($category)) {
		$owner = $category->getOwner();
		if ($owner->getID() != $user->getID()) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("access_denied"));
		}

		if (isset($_POST["keywordsid"])) {
			$keywordsid = sanitizeString($_POST["keywordsid"]);
		}
		else {
			$keywordsid = sanitizeString($_GET["keywordsid"]);
		}
		if (!is_numeric($keywordsid)) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("unknown_keyword_category"));
		}
		if (!$category->editKeywordList($keywordsid, $keywords)) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
		}
	}
	else UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
}

//Kategorie bearbeiten: Neue Stichwortliste löschen ----------------------------------------------------------
else if ($action == "removekeywords") {
	if (isset($_POST["categoryid"])) {
		$categoryid = sanitizeString($_POST["categoryid"]);
	}
	else {
		$categoryid = sanitizeString($_GET["categoryid"]);
	}
	$category = getKeywordCategory($categoryid);
	if (is_object($category)) {
		$owner    = $category->getOwner();
		if ($owner->getID() != $user->getID()) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("access_denied"));
		}
		if (isset($_POST["keywordsid"])) {
			$keywordsid = sanitizeString($_POST["keywordsid"]);
		}
		else {
			$keywordsid = sanitizeString($_GET["keywordsid"]);
		}
		if (!is_numeric($keywordsid)) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("unknown_keyword_category"));
		}
		if (!$category->removeKeywordList($keywordsid)) {
			UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
		}
	}
	else UI::exitError(getMLText("personal_default_keywords"),getMLText("error_occured"));
}

header("Location:../out/out.UserDefaultKeywords.php");

?>
