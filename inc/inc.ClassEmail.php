<?php
/**
 * Implementation of notifation system using email
 *
 * @category   DMS
 * @package    LetoDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("inc.ClassNotify.php");

/**
 * Class to send email notifications to individuals or groups
 *
 * @category   DMS
 * @package    LetoDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Email extends LetoDMS_Notify {

	function toIndividual($sender, $recipient, $subject, $message) {
	
		global $settings;
		if ($settings->_enableEmail==FALSE) return 0;
		
		if ($recipient->getEmail()=="") return 0;

		if ((!is_object($sender) && strcasecmp(get_class($sender), "LetoDMS_Core_User")) ||
				(!is_object($recipient) && strcasecmp(get_class($recipient), "LetoDMS_Core_User"))) {
			return -1;
		}

		$header = "From: ". $sender->getFullName() ." <". $sender->getEmail() .">\r\n" .
			"Reply-To: ". $sender->getFullName() ." <". $sender->getEmail() .">\r\n";
			
		$message = getMLText("email_header")."\r\n\r\n".$message;
		$message .= "\r\n\r\n".getMLText("email_footer");

		return (mail($recipient->getEmail(), $this->replaceMarker($subject), $this->replaceMarker($message), $header) ? 0 : -1);
	}

	function toGroup($sender, $groupRecipient, $subject, $message) {
	
		global $settings;
		if (!$settings->_enableEmail) return 0;

		if ((!is_object($sender) && strcasecmp(get_class($sender), "LetoDMS_Core_User")) ||
				(!is_object($groupRecipient) && strcasecmp(get_class($groupRecipient), "LetoDMS_Core_Group"))) {
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
		
		$message = getMLText("email_header")."\r\n\r\n".$message;
		$message .= "\r\n\r\n".getMLText("email_footer");

		return (mail($toList, parent::replaceMarker($subject), parent::replaceMarker($message), $header) ? 0 : -1);
	}

	function toList($sender, $recipients, $subject, $message) {
	
		global $settings;
		if (!$settings->_enableEmail) return 0;

		if ((!is_object($sender) && strcasecmp(get_class($sender), "LetoDMS_Core_User")) ||
				(!is_array($recipients) && count($recipients)==0)) {
			return -1;
		}

		$header = "From: ". $sender->getFullName() ." <". $sender->getEmail() .">\r\n" .
			"Reply-To: ". $sender->getFullName() ." <". $sender->getEmail() .">\r\n";

		$toList = "";
		foreach ($recipients as $recipient) {
			if (is_object($recipient) && !strcasecmp(get_class($recipient), "LetoDMS_Core_User")) {
			
				if ($recipient->getEmail()!="")
					$toList .= (strlen($toList)==0 ? "" : ", ") . $recipient->getEmail();
			}
		}

		if (strlen($toList)==0) {
			return -1;
		}

		$message = getMLText("email_header")."\r\n\r\n".$message;
		$message .= "\r\n\r\n".getMLText("email_footer");

		return (mail($toList, $this->replaceMarker($subject), $this->replaceMarker($message), $header) ? 0 : -1);
	}
}
?>
