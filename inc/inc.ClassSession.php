<?php
/**
 * Implementation of the user object in the document management system
 *
 * @category   DMS
 * @package    LetoDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2011 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a user in the document management system
 *
 * @category   DMS
 * @package    LetoDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2011 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Session {
	/**
	 * @var object $db reference to database object. This must be an instance
	 *      of {@link LetoDMS_DatabaseAccess}.
	 * @access protected
	 */
	protected $db;

	/**
	 * @var array $data session data
	 * @access protected
	 */
	protected $data;

	/**
	 * @var string $id session id
	 * @access protected
	 */
	protected $id;

	/**
	 * Create a new instance of the session handler
	 *
	 * @param object $db object to access the underlying database
	 * @return object instance of LetoDMS_Session
	 */
	function __construct($db) { /* {{{ */
		$this->db = $db;
	} /* }}} */

	/**
	 * Load session by its id
	 *
	 * @param string $id id of session
	 * @return boolean true if successful otherwise false
	 */
	function load($id) { /* {{{ */
		$queryStr = "SELECT * FROM tblSessions WHERE id = '".$id."'";
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) == 0)
			return false;
		$queryStr = "UPDATE tblSessions SET lastAccess = " . mktime() . " WHERE id = '" . $id . "'";
		if (!$this->db->getResult($queryStr))
			return false;
		return $resArr[0];
	} /* }}} */

	/**
	 * Create a new session and saving the given data
	 *
	 * @param array $data data saved in session (the only fields supported
	 *        are userid, theme, language)
	 * @return string/boolean id of session of false in case of an error
	 */
	function create($data) { /* {{{ */
		$id = "" . rand() . mktime() . rand() . "";
		$id = md5($id);
		$queryStr = "INSERT INTO tblSessions (id, userID, lastAccess, theme, language) ".
		  "VALUES ('".$id."', ".$data['userid'].", ".mktime().", '".$data['theme']."', '".$data['lang']."')";
		if (!$this->db->getResult($queryStr)) {
			return false;
		}
		$this->id = $id;
		$this->data = $data;
		return $id;
	} /* }}} */

	/**
	 * Delete old sessions older than a given time
	 *
	 * @param integer $sec maximum number of seconds a session may live
	 * @return boolean true if successful otherwise false
	 */
	function deleteByTime($sec) {
		$queryStr = "DELETE FROM tblSessions WHERE " . mktime() . " - lastAccess > ".$sec;
		if (!$this->db->getResult($queryStr)) {
			return false;
		}
		return true;
	}

	/**
	 * Delete session by its id
	 *
	 * @param string $id id of session
	 * @return boolean true if successful otherwise false
	 */
	function delete($id) { /* {{{ */
		$queryStr = "DELETE FROM tblSessions WHERE id = '$id'";
		if (!$this->db->getResult($queryStr)) {
			return false;
		}
		return true;
	} /* }}} */
}
?>
