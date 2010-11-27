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

//---------------------------------------------------------------------------
class LetoDMS_KeywordCategory {
	var $_id;
	var $_ownerID;
	var $_name;
	var $_dms;

	function LetoDMS_KeywordCategory($id, $ownerID, $name) {
		$this->_id = $id;
		$this->_name = $name;
		$this->_ownerID = $ownerID;
		$this->_dms = null;
	}

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function getOwner() {
		if (!isset($this->_owner))
			$this->_owner = $this->_dms->getUser($this->_ownerID);
		return $this->_owner;
	}

	function setName($newName) {
		GLOBAL $db;

		$queryStr = "UPDATE tblKeywordCategories SET name = '$newName' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_name = $newName;
		return true;
	}

	function setOwner($user) {
		GLOBAL $db;

		$queryStr = "UPDATE tblKeywordCategories SET owner = " . $user->getID() . " WHERE id " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_ownerID = $user->getID();
		$this->_owner = $user;
		return true;
	}

	function getKeywordLists() {
		GLOBAL $db;

		$queryStr = "SELECT * FROM tblKeywords WHERE category = " . $this->_id;
		return $db->getResultArray($queryStr);
	}

	function editKeywordList($listID, $keywords) {
		GLOBAL $db;

		$queryStr = "UPDATE tblKeywords SET keywords = '$keywords' WHERE id = $listID";
		return $db->getResult($queryStr);
	}

	function addKeywordList($keywords) {
		GLOBAL $db;

		$queryStr = "INSERT INTO tblKeywords (category, keywords) VALUES (" . $this->_id . ", '$keywords')";
		return $db->getResult($queryStr);
	}

	function removeKeywordList($listID) {
		GLOBAL $db;

		$queryStr = "DELETE FROM tblKeywords WHERE id = $listID";
		return $db->getResult($queryStr);
	}

	function remove() {
		GLOBAL $db;

		$queryStr = "DELETE FROM tblKeywords WHERE category = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$queryStr = "DELETE FROM tblKeywordCategories WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		return true;
	}
}

?>
