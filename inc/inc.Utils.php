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

function getReadableDate($timestamp) {
	return date("d.m.Y", $timestamp);
}

function getLongReadableDate($timestamp) {
	return date("d.m.Y - H:i", $timestamp);
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

function sanitizeString($string) {

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
}

function mydmsDecodeString($string) {

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
}
?>
