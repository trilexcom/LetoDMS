<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Uwe Steinmann
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

abstract class LetoDMS_Notify {
	/* User sending the notification
	 * Will only be used if the sender of one of the notify methods
	 * is not set
	 */
	protected $sender;

	abstract function toIndividual($sender, $recipient, $subject, $message);
	abstract function toGroup($sender, $groupRecipient, $subject, $message);
	abstract function toList($sender, $recipients, $subject, $message);

	function replaceMarker($text) {
		global $settings;

		return(str_replace(
			array('###SITENAME###', '###HTTP_ROOT###', '###URL_PREFIX###'),
			array($settings->_siteName, $settings->_httpRoot, "http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot),
			$text));
	}

	function setSender($user) {
		$this->sender = $user;
	}
}
?>
