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

/*
 * Repräsentiert einen Eintrag in der tblACLs für einen User.
 * Änderungen an der Berechtigung können nicht vorgenommen werden;
 * dafür sind die Klassen Folder und Document selbst
 * verantwortlich.
 */
class LetoDMS_UserAccess
{
	var $_user;
	var $_mode;

	function LetoDMS_UserAccess($user, $mode)
	{
		$this->_user = $user;
		$this->_mode = $mode;
	}

	function getUserID() { return $this->_user->getID(); }

	function getMode() { return $this->_mode; }

	function getUser() {
		return $this->_user;
	}
}


class LetoDMS_GroupAccess
{
	var $_group;
	var $_mode;

	function LetoDMS_GroupAccess($group, $mode)
	{
		$this->_group = $group;
		$this->_mode = $mode;
	}

	function getGroupID() { return $this->_group->getID(); }

	function getMode() { return $this->_mode; }

	function getGroup() {
		return $this->_group;
	}
}
?>
