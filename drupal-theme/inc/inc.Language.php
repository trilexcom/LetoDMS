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

function getLanguages()
{
	GLOBAL $settings;
	
	$languages = array();
	
	$path = $settings->_rootDir . "languages/";
	$handle = opendir($path);
	
	while ($entry = readdir($handle) )
	{
		if ($entry == ".." || $entry == ".")
			continue;
		else if (is_dir($path . $entry))
			array_push($languages, $entry);
	}
	closedir($handle);
	
	return $languages;
}

include $settings->_rootDir . "languages/" . $settings->_language . "/lang.inc";


function getMLText($key, $replace = array())
{
	GLOBAL $settings, $text;
	
	if (!isset($text[$key]))
		return "Error getting Text: " . $key . " (" . $settings->_language . ")";
	
	$tmpText = $text[$key];
	
	if (count($replace) == 0)
		return $tmpText;
	
	$keys = array_keys($replace);
	foreach ($keys as $key)
		$tmpText = str_replace("[".$key."]", $replace[$key], $tmpText);
	
	return $tmpText;
}

function printMLText($key, $replace = array())
{
	print getMLText($key, $replace);
}

function printReviewStatusText($status, $date=0) {
	if (is_null($status)) {
		print getMLText("status_unknown");
	}
	else {
		switch ($status) {
			case -2:
				print getMLText("status_reviewer_removed");
				break;
			case -1:
				print getMLText("status_reviewer_rejected").($date !=0 ? " ".$date : "");
				break;
			case 0:
				print getMLText("status_not_reviewed");
				break;
			case 1:
				print getMLText("status_reviewed").($date !=0 ? " ".$date : "");
				break;
			default:
				print getMLText("status_unknown");
				break;
		}
	}
}

function getReviewStatusText($status, $date=0) {
	if (is_null($status)) {
		return getMLText("status_unknown");
	}
	else {
		switch ($status) {
			case -2:
				return getMLText("status_reviewer_removed");
				break;
			case -1:
				return getMLText("status_reviewer_rejected").($date !=0 ? " ".$date : "");
				break;
			case 0:
				return getMLText("status_not_reviewed");
				break;
			case 1:
				return getMLText("status_reviewed").($date !=0 ? " ".$date : "");
				break;
			default:
				return getMLText("status_unknown");
				break;
		}
	}
}

function printApprovalStatusText($status, $date=0) {
	if (is_null($status)) {
		print getMLText("status_unknown");
	}
	else {
		switch ($status) {
			case -2:
				print getMLText("status_approver_removed");
				break;
			case -1:
				print getMLText("status_approval_rejected").($date !=0 ? " ".$date : "");
				break;
			case 0:
				print getMLText("status_not_approved");
				break;
			case 1:
				print getMLText("status_approved").($date !=0 ? " ".$date : "");
				break;
			default:
				print getMLText("status_unknown");
				break;
		}
	}
}

function getApprovalStatusText($status, $date=0) {
	if (is_null($status)) {
		return getMLText("status_unknown");
	}
	else {
		switch ($status) {
			case -2:
				return getMLText("status_approver_removed");
				break;
			case -1:
				return getMLText("status_approval_rejected").($date !=0 ? " ".$date : "");
				break;
			case 0:
				return getMLText("status_not_approved");
				break;
			case 1:
				return getMLText("status_approved").($date !=0 ? " ".$date : "");
				break;
			default:
				return getMLText("status_unknown");
				break;
		}
	}
}

function printOverallStatusText($status) {
	if (is_null($status)) {
		print getMLText("assumed_released");
	}
	else {
		switch($status) {
			case S_DRAFT_REV:
				print getMLText("draft_pending_review");
				break;
			case S_DRAFT_APP:
				print getMLText("draft_pending_approval");
				break;
			case S_RELEASED:
				print getMLText("released");
				break;
			case S_REJECTED:
				print getMLText("rejected");
				break;
			case S_OBSOLETE:
				print getMLText("obsolete");
				break;
			case S_EXPIRED:
				print getMLText("expired");
				break;
			default:
				print getMLText("status_unknown");
				break;
		}
	}
}

function getOverallStatusText($status) {
	if (is_null($status)) {
		return getMLText("assumed_released");
	}
	else {
		switch($status) {
			case S_DRAFT_REV:
				return getMLText("draft_pending_review");
				break;
			case S_DRAFT_APP:
				return getMLText("draft_pending_approval");
				break;
			case S_RELEASED:
				return getMLText("released");
				break;
			case S_REJECTED:
				return getMLText("rejected");
				break;
			case S_OBSOLETE:
				return getMLText("obsolete");
				break;
			case S_EXPIRED:
				return getMLText("expired");
				break;
			default:
				return getMLText("status_unknown");
				break;
		}
	}
}
?>
