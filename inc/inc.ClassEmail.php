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

class Email {

	function toIndividual($sender, $recipient, $subject, $message) {
	
		global $settings;
		if ($settings->_enableEmail==FALSE) return 0;
		
		if ($recipient->getEmail()=="") return 0;

		if ((!is_object($sender) && strcasecmp(get_class($sender), "User")) ||
				(!is_object($recipient) && strcasecmp(get_class($recipient), "User"))) {
			return -1;
		}

		$header = "From: ". $sender->getFullName() ." <". $sender->getEmail() .">\r\n" .
			"Reply-To: ". $sender->getFullName() ." <". $sender->getEmail() .">\r\n";

		return (mail($recipient->getEmail(), $subject, $message, $header) ? 0 : -1);
	}

	function toGroup($sender, $groupRecipient, $subject, $message) {
	
		global $settings;
		if (!$settings->_enableEmail) return 0;

		if ((!is_object($sender) && strcasecmp(get_class($sender), "User")) ||
				(!is_object($groupRecipient) && strcasecmp(get_class($groupRecipient), "Group"))) {
			return -1;
		}

		$header = "From: ". $sender->getFullName() ." <". $sender->getEmail() .">\r\n" .
			"Reply-To: ". $sender->getFullName() ." <". $sender->getEmail() .">\r\n";

		$toList = "";
		foreach ($groupRecipient->getUsers() as $recipient) {
		
			if ($recipient->getEmail()!="")
				$toList .= (strlen($toList)==0 ? "" : ", ") . $recipient->getEmail();
		}

		if (strlen($toList)==0) {
			return -1;
		}

		return (mail($toList, $subject, $message, $header) ? 0 : -1);
	}

	function toList($sender, $recipients, $subject, $message) {
	
		global $settings;
		if (!$settings->_enableEmail) return 0;

		if ((!is_object($sender) && strcasecmp(get_class($sender), "User")) ||
				(!is_array($recipients) && count($recipients)==0)) {
			return -1;
		}

		$header = "From: ". $sender->getFullName() ." <". $sender->getEmail() .">\r\n" .
			"Reply-To: ". $sender->getFullName() ." <". $sender->getEmail() .">\r\n";

		$toList = "";
		foreach ($recipients as $recipient) {
			if (is_object($recipient) && !strcasecmp(get_class($recipient), "User")) {
			
				if ($recipient->getEmail()!="")
					$toList .= (strlen($toList)==0 ? "" : ", ") . $recipient->getEmail();
			}
		}

		if (strlen($toList)==0) {
			return -1;
		}

		return (mail($toList, $subject, $message, $header) ? 0 : -1);
	}
}
?>
