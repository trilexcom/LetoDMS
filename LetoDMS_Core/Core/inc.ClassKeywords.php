<?php
/**
 * Implementation of keyword categories in the document management system
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a keyword category in the document management system
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_KeywordCategory {
	/**
	 * @var integer $_id id of keyword category
	 * @access protected
	 */
	var $_id;

	/**
	 * @var integer $_ownerID id of user who is the owner
	 * @access protected
	 */
	var $_ownerID;

	/**
	 * @var string $_name name of category
	 * @access protected
	 */
	var $_name;

	/**
	 * @var object $_dms reference to dms this category belongs to
	 * @access protected
	 */
	var $_dms;

	function LetoDMS_Core_KeywordCategory($id, $ownerID, $name) {
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
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblKeywordCategories SET name = '$newName' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_name = $newName;
		return true;
	}

	function setOwner($user) {
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblKeywordCategories SET owner = " . $user->getID() . " WHERE id " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_ownerID = $user->getID();
		$this->_owner = $user;
		return true;
	}

	function getKeywordLists() {
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblKeywords WHERE category = " . $this->_id;
		return $db->getResultArray($queryStr);
	}

	function editKeywordList($listID, $keywords) {
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblKeywords SET keywords = '$keywords' WHERE id = $listID";
		return $db->getResult($queryStr);
	}

	function addKeywordList($keywords) {
		$db = $this->_dms->getDB();

		$queryStr = "INSERT INTO tblKeywords (category, keywords) VALUES (" . $this->_id . ", '$keywords')";
		return $db->getResult($queryStr);
	}

	function removeKeywordList($listID) {
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM tblKeywords WHERE id = $listID";
		return $db->getResult($queryStr);
	}

	function remove() {
		$db = $this->_dms->getDB();

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
