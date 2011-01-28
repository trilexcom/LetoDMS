<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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

function formatted_size($size_bytes) { /* {{{ */
	if ($size_bytes>1000000000) return number_format($size_bytes/1000000000,1,".","")." GBytes";
	else if ($size_bytes>1000000) return number_format($size_bytes/1000000,1,".","")." MBytes";
	else if ($size_bytes>1000) return number_format($size_bytes/1000,1,".","")." KBytes";
	return number_format($size_bytes,0,"","")." Bytes";
} /* }}} */

function getReadableDate($timestamp) {
	return date("d.m.Y", $timestamp);
}

function getLongReadableDate($timestamp) {
	return date("d/m/Y H:i", $timestamp);
}

//
// The original string sanitizer, kept for reference.
//function sanitizeString($string) {
//	$string = str_replace("'",  "&#0039;", $string);
//	$string = str_replace("--", "", $string);
//	$string = str_replace("<",  "&lt;", $string);
//	$string = str_replace(">",  "&gt;", $string);
//	$string = str_replace("/*", "", $string);
//	$string = str_replace("*/", "", $string);
//	$string = str_replace("\"", "&quot;", $string);
//	
//	return $string;
//}

function sanitizeString($string) { /* {{{ */

	$string = (string) $string;
	if (get_magic_quotes_gpc()) {
		$string = stripslashes($string);
	}

	$string = str_replace("\\", "\\\\", $string);
	$string = str_replace("--", "\-\-", $string);
	$string = str_replace(";", "\;", $string);
	// Use HTML entities to represent the other characters that have special
	// meaning in SQL. These can be easily converted back to ASCII / UTF-8
	// with a decode function if need be.
	$string = str_replace("&", "&amp;", $string);
	$string = str_replace("%", "&#0037;", $string); // percent
	$string = str_replace("\"", "&quot;", $string); // double quote
	$string = str_replace("/*", "&#0047;&#0042;", $string); // start of comment
	$string = str_replace("*/", "&#0042;&#0047;", $string); // end of comment
	$string = str_replace("<", "&lt;", $string);
	$string = str_replace(">", "&gt;", $string);
	$string = str_replace("=", "&#0061;", $string);
	$string = str_replace(")", "&#0041;", $string);
	$string = str_replace("(", "&#0040;", $string);
	$string = str_replace("'", "&#0039;", $string);
	$string = str_replace("+", "&#0043;", $string);

	return trim($string);
} /* }}} */

function mydmsDecodeString($string) { /* {{{ */

	$string = (string)$string;

	$string = str_replace("&amp;", "&", $string);
	$string = str_replace("&#0037;", "%", $string); // percent
	$string = str_replace("&quot;", "\"", $string); // double quote
	$string = str_replace("&#0047;&#0042;", "/*", $string); // start of comment
	$string = str_replace("&#0042;&#0047;", "*/", $string); // end of comment
	$string = str_replace("&lt;", "<", $string);
	$string = str_replace("&gt;", ">", $string);
	$string = str_replace("&#0061;", "=", $string);
	$string = str_replace("&#0041;", ")", $string);
	$string = str_replace("&#0040;", "(", $string);
	$string = str_replace("&#0039;", "'", $string);
	$string = str_replace("&#0043;", "+", $string);

	return $string;
} /* }}} */

function createVersionigFile($document) { /* {{{ */
	global $settings, $dms;
	
	// if directory has been removed recreate it
	if (!file_exists($dms->contentDir . $document->getDir()))
		if (!LetoDMS_Core_File::makeDir($dms->contentDir . $document->getDir())) return false;
	
	$handle = fopen($dms->contentDir . $document->getDir() .$settings-> _versioningFileName , "wb");
	
	if (is_bool($handle)&&!$handle) return false;
	
	$tmp = mydmsDecodeString($document->getName())." (ID ".$document->getID().")\n\n";
	fwrite($handle, $tmp);

	$owner = $document->getOwner();
	$tmp = getMLText("owner")." = ".$owner->getFullName()." <".$owner->getEmail().">\n";
	fwrite($handle, $tmp);
	
	$tmp = getMLText("creation_date")." = ".getLongReadableDate($document->getDate())."\n";
	fwrite($handle, $tmp);
	
	$latestContent = $document->getLatestContent();
	$tmp = "\n### ".getMLText("current_version")." ###\n\n";
	fwrite($handle, $tmp);
	
	$tmp = getMLText("version")." = ".$latestContent->getVersion()."\n";
	fwrite($handle, $tmp);	
	
	$tmp = getMLText("file")." = ".$latestContent->getOriginalFileName()." (".$latestContent->getMimeType().")\n";
	fwrite($handle, $tmp);
	
	$tmp = getMLText("comment")." = ". mydmsDecodeString($latestContent->getComment())."\n";
	fwrite($handle, $tmp);
	
	$status = $latestContent->getStatus();
	$tmp = getMLText("status")." = ".getOverallStatusText($status["status"])."\n";
	fwrite($handle, $tmp);
	
	$reviewStatus = $latestContent->getReviewStatus();
	$tmp = "\n### ".getMLText("reviewers")." ###\n";
	fwrite($handle, $tmp);
	
	foreach ($reviewStatus as $r) {
	
		switch ($r["type"]) {
			case 0: // Reviewer is an individual.
				$required = $dms->getUser($r["required"]);
				if (!is_object($required)) $reqName = getMLText("unknown_user")." = ".$r["required"];
				else $reqName =  getMLText("user")." = ".$required->getFullName();
				break;
			case 1: // Reviewer is a group.
				$required = $dms->getGroup($r["required"]);
				if (!is_object($required)) $reqName = getMLText("unknown_group")." = ".$r["required"];
				else $reqName = getMLText("group")." = ".$required->getName();
				break;
		}

		$tmp = "\n".$reqName."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("status")." = ".getReviewStatusText($r["status"])."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("comment")." = ". mydmsDecodeString($r["comment"])."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("last_update")." = ".$r["date"]."\n";
		fwrite($handle, $tmp);

	}
	
	
	$approvalStatus = $latestContent->getApprovalStatus();
	$tmp = "\n### ".getMLText("approvers")." ###\n";
	fwrite($handle, $tmp);

	foreach ($approvalStatus as $r) {
	
		switch ($r["type"]) {
			case 0: // Reviewer is an individual.
				$required = $dms->getUser($r["required"]);
				if (!is_object($required)) $reqName = getMLText("unknown_user")." = ".$r["required"];
				else $reqName =  getMLText("user")." = ".$required->getFullName();
				break;
			case 1: // Reviewer is a group.
				$required = $dms->getGroup($r["required"]);
				if (!is_object($required)) $reqName = getMLText("unknown_group")." = ".$r["required"];
				else $reqName = getMLText("group")." = ".$required->getName();
				break;
		}

		$tmp = "\n".$reqName."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("status")." = ".getApprovalStatusText($r["status"])."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("comment")." = ". mydmsDecodeString($r["comment"])."\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("last_update")." = ".$r["date"]."\n";
		fwrite($handle, $tmp);
	
	}

	$versions = $document->getContent();	
	$tmp = "\n### ".getMLText("previous_versions")." ###\n";
	fwrite($handle, $tmp);

	for ($i = count($versions)-2; $i >= 0; $i--){
	
		$version = $versions[$i];
		$status = $version->getStatus();		
		
		$tmp = "\n".getMLText("version")." = ".$version->getVersion()."\n";
		fwrite($handle, $tmp);	
		
		$tmp = getMLText("file")." = ".$version->getOriginalFileName()." (".$version->getMimeType().")\n";
		fwrite($handle, $tmp);
		
		$tmp = getMLText("comment")." = ". mydmsDecodeString($version->getComment())."\n";
		fwrite($handle, $tmp);
		
		$status = $latestContent->getStatus();
		$tmp = getMLText("status")." = ".getOverallStatusText($status["status"])."\n";
		fwrite($handle, $tmp);
			
	}
	
	fclose($handle);
	return true;
} /* }}} */

function add_log_line($msg="") { /* {{{ */
	global $settings,$user;
	
	if ($settings->_logFileEnable!=TRUE) return;

	if ($settings->_logFileRotation=="h") $logname=date("YmdH", time());
	else if ($settings->_logFileRotation=="d") $logname=date("Ymd", time());
	else $logname=date("Ym", time());
	
	if($h = fopen($settings->_contentDir.$logname.".log", "a")) {
		fwrite($h,date("Y/m/d H:i", time())." ".$user->getLogin()." (".$_SERVER['REMOTE_ADDR'].") ".basename($_SERVER["REQUEST_URI"], ".php").$msg."\n");
		fclose($h);
	}
} /* }}} */

	function getFolderPathHTML($folder, $tagAll=false) { /* {{{ */
		$path = $folder->getPath();
		$txtpath = "";
		for ($i = 0; $i < count($path); $i++) {
			if ($i +1 < count($path)) {
				$txtpath .= "<a href=\"../out/out.ViewFolder.php?folderid=".$path[$i]->getID()."&showtree=".showtree()."\">".
					$path[$i]->getName()."</a> / ";
			}
			else {
				$txtpath .= ($tagAll ? "<a href=\"../out/out.ViewFolder.php?folderid=".$path[$i]->getID()."&showtree=".showtree()."\">".
										 $path[$i]->getName()."</a>" : $path[$i]->getName());
			}
		}
		return $txtpath;
	} /* }}} */
	
function showtree() { /* {{{ */
	global $settings;
	
	if (isset($_GET["showtree"])) return $_GET["showtree"];
	else if ($settings->_enableFolderTree==0) return 0;
	
	return 1;
} /* }}} */

?>
