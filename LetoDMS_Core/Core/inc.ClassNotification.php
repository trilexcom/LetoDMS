<?php
/**
 * Implementation of a notification object
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a notification
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Core_Notification { /* {{{ */
	/**
	 * @var integer id of target (document or folder)
	 *
	 * @access protected
	 */
	var $_target;

	/**
	 * @var integer document or folder
	 *
	 * @access protected
	 */
	var $_targettype;

	/**
	 * @var integer id of user to notify
	 *
	 * @access protected
	 */
	var $_userid;

	/**
	 * @var integer id of group to notify
	 *
	 * @access protected
	 */
	var $_groupid;

	/**
	 * @var object reference to the dms instance this user belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	function LetoDMS_Core_Notification($target, $targettype, $userid, $groupid) {
		$this->_target = $target;
		$this->_targettype = $targettype;
		$this->_userid = $userid;
		$this->_groupid = $groupid;
	}

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getTarget() { return $this->_target; }

	function getTargetType() { return $this->_targettype; }

	function getUser() { return $this->_dms->getUser($this->_userid); }

	function getGroup() { return $this->_dms->getGroup($this->_groupid); }
} /* }}} */
?>
