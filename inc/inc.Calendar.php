<?
//    Copyright (C) 2010 Matteo Lucarelli
// 
//    Some code from PHP Calendar Class Version 1.4 (5th March 2001)
//    (C)2000-2001 David Wilkinson
//    URL:   http://www.cascade.org.uk/software/php/calendar/
//    Email: davidw@cascade.org.uk
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

// DB //////////////////////////////////////////////////////////////////////////

function getEvents($day, $month, $year){

	global $db;

	$date = mktime(12,0,0, $month, $day, $year);
	
	$queryStr = "SELECT * FROM tblEvents WHERE start <= " . $date . " AND stop >= " . $date;
	$ret = $db->getResultArray($queryStr);	
	return $ret;
}

function getEventsInInterval($start, $stop){

	global $db;

	$queryStr = "SELECT * FROM tblEvents WHERE ( start <= " . $start . " AND stop >= " . $start . " ) ".
	                                       "OR ( start <= " . $stop . " AND stop >= " . $stop . " ) ".
	                                       "OR ( start >= " . $start . " AND stop <= " . $stop . " )";
	$ret = $db->getResultArray($queryStr);	
	return $ret;
}

function addEvent($from, $to, $name, $comment ){

	global $db,$user;

	$queryStr = "INSERT INTO tblEvents (name, comment, start, stop, date, userID) VALUES ".
		"('".$name."', '".$comment."', ".$from.", ".$to.", ".mktime().", ".$user->getID().")";
	
	$ret = $db->getResult($queryStr);
	return $ret;
}

function getEvent($id){

	if (!is_numeric($id)) return false;

	global $db;
	
	$queryStr = "SELECT * FROM tblEvents WHERE id = " . $id;
	$ret = $db->getResultArray($queryStr);
	
	if (is_bool($ret) && $ret == false) return false;
	else if (count($ret) != 1) return false;
		
	return $ret[0];	
}

function editEvent($id, $from, $to, $name, $comment ){

	if (!is_numeric($id)) return false;

	global $db;
	
	$queryStr = "UPDATE tblEvents SET start = " . $from . ", stop = " . $to . ", name = '" . $name . "', comment = '" . $comment . "', date = " . mktime() . " WHERE id = ". $id;
	$ret = $db->getResult($queryStr);	
	return $ret;
}

function delEvent($id){

	if (!is_numeric($id)) return false;
	
	global $db;
	
	$queryStr = "DELETE FROM tblEvents WHERE id = " . $id;
	$ret = $db->getResult($queryStr);	
	return $ret;
}

// utilities ///////////////////////////////////////////////////////////////////

function generateCalendarArrays()
{
	global $dayNames,$monthNames,$dayNamesLong;
	
	$monthNames = array( getMLText("january"),
	                     getMLText("february"),
	                     getMLText("march"),
	                     getMLText("april"),
	                     getMLText("may"), 
	                     getMLText("june"),
	                     getMLText("july"), 
	                     getMLText("august"), 
	                     getMLText("september"), 
	                     getMLText("october"), 
	                     getMLText("november"), 
	                     getMLText("december") );
	                    
	$dayNamesLong = array( getMLText("sunday"),
	                       getMLText("monday"),
	                       getMLText("tuesday"),
	                       getMLText("wednesday"), 
	                       getMLText("thursday"),
	                       getMLText("friday"), 
	                       getMLText("saturday") );
	
	$dayNames = array();
	foreach ( $dayNamesLong as $dn ){
		 $dayNames[] = substr($dn,0,2);   
	}         
}

// Calculate the number of days in a month, taking into account leap years.
function getDaysInMonth($month, $year)
{
	if ($month < 1 || $month > 12) return 0;

	$daysInMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	$d = $daysInMonth[$month - 1];

	if ($month == 2){
	
		if ($year%4 == 0){
		
			if ($year%100 == 0){
			
				if ($year%400 == 0) $d = 29;
			}
			else $d = 29;
		}
	}
	return $d;
}

// Adjust dates to allow months > 12 and < 0 and day<0 or day>days of the month
function adjustDate(&$day,&$month,&$year)
{
	$d=getDate(mktime(12,0,0, $month, $day, $year));
	$month=$d["mon"];
	$day=$d["mday"];
	$year=$d["year"];
}

// output //////////////////////////////////////////////////////////////////////

// Generate the HTML for a given month
function getMonthHTML($month, $year)
{
	global $dayNames,$monthNames,$settings;

	if (!isset($monthNames)) generateCalendarArrays();
	if (!isset($dayNames)) generateCalendarArrays();

	$startDay = $settings->_firstDayOfWeek;

	$day=1;
	adjustDate($day,$month,$year);

	$daysInMonth = getDaysInMonth($month, $year);
	$date = getdate(mktime(12, 0, 0, $month, 1, $year));

	$first = $date["wday"];
	$monthName = $monthNames[$month - 1];

	$s  = "<table border=0>\n";
	
	$s .= "<tr>\n";
	$s .= "<td align=\"center\" class=\"header\" colspan=\"7\"><a href=\"../out/out.Calendar.php?mode=m&year=".$year."&month=".$month."\">".$monthName."</a></td>\n"; ;
	$s .= "</tr>\n";

	$s .= "<tr>\n";
	$s .= "<td class=\"header\">" . $dayNames[($startDay)%7] . "</td>\n";
	$s .= "<td class=\"header\">" . $dayNames[($startDay+1)%7] . "</td>\n";
	$s .= "<td class=\"header\">" . $dayNames[($startDay+2)%7] . "</td>\n";
	$s .= "<td class=\"header\">" . $dayNames[($startDay+3)%7] . "</td>\n";
	$s .= "<td class=\"header\">" . $dayNames[($startDay+4)%7] . "</td>\n";
	$s .= "<td class=\"header\">" . $dayNames[($startDay+5)%7] . "</td>\n";
	$s .= "<td class=\"header\">" . $dayNames[($startDay+6)%7] . "</td>\n";
	$s .= "</tr>\n";

	// We need to work out what date to start at so that the first appears in the correct column
	$d = $startDay + 1 - $first;
	while ($d > 1) $d -= 7;

	// Make sure we know when today is, so that we can use a different CSS style
	$today = getdate(time());

	while ($d <= $daysInMonth)
	{
		$s .= "<tr>\n";       
	    
		for ($i = 0; $i < 7; $i++){
		
			$class = ($year == $today["year"] && $month == $today["mon"] && $d == $today["mday"]) ? "today" : "";
			$s .= "<td class=\"$class\">";   
			    
			if ($d > 0 && $d <= $daysInMonth){

				$s .= "<a href=\"../out/out.Calendar.php?mode=w&year=".$year."&month=".$month."&day=".$d."\">".$d."</a>";
	        	}
			else $s .= "&nbsp;";
			
			$s .= "</td>\n";       
			$d++;
		}
		$s .= "</tr>\n";    
	}

	$s .= "</table>\n";

	return $s;  	
}

function printYearTable($year)
{
	print "<table class=\"calendaryear\"border=\"0\">\n";
	print "<tr>";
	print "<td>" . getMonthHTML(1 , $year) ."</td>\n";
	print "<td>" . getMonthHTML(2 , $year) ."</td>\n";
	print "<td>" . getMonthHTML(3 , $year) ."</td>\n";
	print "<td>" . getMonthHTML(4 , $year) ."</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>" . getMonthHTML(5 , $year) ."</td>\n";
	print "<td>" . getMonthHTML(6 , $year) ."</td>\n";
	print "<td>" . getMonthHTML(7 , $year) ."</td>\n";
	print "<td>" . getMonthHTML(8 , $year) ."</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>" . getMonthHTML(9 , $year) ."</td>\n";
	print "<td>" . getMonthHTML(10, $year) ."</td>\n";
	print "<td>" . getMonthHTML(11, $year) ."</td>\n";
	print "<td>" . getMonthHTML(12, $year) ."</td>\n";
	print "</tr>\n";
	print "</table>\n";
}

?>
