<?php
//    MyDMS. Document Management System
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

include("../inc/inc.Settings.php");
include("../inc/inc.AccessUtils.php");
include("../inc/inc.ClassAccess.php");
include("../inc/inc.ClassDocument.php");
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.Calendar.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if ($_GET["mode"]) $mode=$_GET["mode"];

// get required date else use current
$currDate = time();

if (isset($_GET["year"])&&is_numeric($_GET["year"])) $year=$_GET["year"];
else $year = (int)date("Y", $currDate);
if (isset($_GET["month"])&&is_numeric($_GET["month"])) $month=$_GET["month"];
else $month = (int)date("m", $currDate);
if (isset($_GET["day"])&&is_numeric($_GET["day"])) $day=$_GET["day"];
else $day = (int)date("d", $currDate);

adjustDate($day,$month,$year);
     
UI::htmlStartPage(getMLText("calendar"));
UI::globalNavigation();
UI::pageNavigation(getMLText("calendar"), "calendar",array($day,$month,$year));


if ($mode=="y"){

	UI::contentHeading(getMLText("year_view")." : ".$year);
	UI::contentContainerStart();
	
	print "<a href=\"../out/out.Calendar.php?mode=y&year=".($year-1)."\"><img src=\"".UI::getImgPath("m.png")."\" border=0></a>&nbsp;";
	print "<a href=\"../out/out.Calendar.php?mode=y\"><img src=\"".UI::getImgPath("c.png")."\" border=0></a>&nbsp;";
	print "<a href=\"../out/out.Calendar.php?mode=y&year=".($year+1)."\"><img src=\"".UI::getImgPath("p.png")."\" border=0></a>&nbsp;";

	printYearTable($year);
	UI::contentContainerEnd();

}else if ($mode=="m"){

	if (!isset($dayNamesLong)) generateCalendarArrays();
	if (!isset($monthNames)) generateCalendarArrays();
	
	UI::contentHeading(getMLText("month_view")." : ".$monthNames[$month-1]. " ".$year);
	UI::contentContainerStart();
	
	print "<a href=\"../out/out.Calendar.php?mode=m&year=".($year)."&month=".($month-1)."\"><img src=\"".UI::getImgPath("m.png")."\" border=0></a>&nbsp;";
	print "<a href=\"../out/out.Calendar.php?mode=m\"><img src=\"".UI::getImgPath("c.png")."\" border=0></a>&nbsp;";
	print "<a href=\"../out/out.Calendar.php?mode=m&year=".($year)."&month=".($month+1)."\"><img src=\"".UI::getImgPath("p.png")."\" border=0></a>&nbsp;";
	
	$days=getDaysInMonth($month, $year);
	$today = getdate(time());
	
	$events = getEventsInInterval(mktime(0,0,0, $month, 1, $year), mktime(23,59,59, $month, $days, $year));
	
	echo "<table class='calendarmonth'>\n";
	
	for ($i=1; $i<=$days; $i++){
	
		// separate weeks
		$date = getdate(mktime(12, 0, 0, $month, $i, $year));
		if (($date["wday"]==$settings->_firstDayOfWeek) && ($i!=1))
			echo "<tr><td class='separator' colspan='".(count($events)+2)."'>&nbsp;</td></tr>\n";
		
		// highlight today
		$class = ($year == $today["year"] && $month == $today["mon"] && $i == $today["mday"]) ? "todayHeader" : "header";
		
		echo "<tr>";
		echo "<td class='".$class."'><a href=\"../out/out.Calendar.php?mode=w&year=".($year)."&month=".($month)."&day=".($i)."\">".$i."</a></td>";
		echo "<td class='".$class."'><a href=\"../out/out.Calendar.php?mode=w&year=".($year)."&month=".($month)."&day=".($i)."\">".$dayNamesLong[$date["wday"]]."</a></td>";
		
		if ($class=="todayHeader") $class="today";
		else $class="";
		
		$xdate=mktime(0, 0, 0, $month, $i, $year);
		foreach ($events as $event){
			if (($event["start"]<=$xdate)&&($event["stop"]>=$xdate)){
			
				if (strlen($event['name']) > 25) $event['name'] = substr($event['name'], 0, 22) . "...";
				print "<td class='".$class."'><a href=\"../out/out.ViewEvent.php?id=".$event['id']."\">".$event['name']."</a></td>";
			}else{
				print "<td class='".$class."'>&nbsp;</td>";
			}
		}
		
		echo "</tr>\n";	
	}
	echo "</table>\n";

	UI::contentContainerEnd();
	
}else{

	if (!isset($dayNamesLong)) generateCalendarArrays();
	if (!isset($monthNames)) generateCalendarArrays();
	
	// get the week interval - TODO: $GET
	$datestart=getdate(mktime(0,0,0,$month,$day,$year));
	while($datestart["wday"]!=$settings->_firstDayOfWeek){
		$datestart=getdate(mktime(0,0,0,$datestart["mon"],$datestart["mday"]-1,$datestart["year"]));
	}
		
	$datestop=getdate(mktime(23,59,59,$month,$day,$year));
	if ($datestop["wday"]==$settings->_firstDayOfWeek){
		$datestop=getdate(mktime(23,59,59,$datestop["mon"],$datestop["mday"]+1,$datestop["year"]));
	}
	while($datestop["wday"]!=$settings->_firstDayOfWeek){
		$datestop=getdate(mktime(23,59,59,$datestop["mon"],$datestop["mday"]+1,$datestop["year"]));
	}
	$datestop=getdate(mktime(23,59,59,$datestop["mon"],$datestop["mday"]-1,$datestop["year"]));
	
	$starttime=mktime(0,0,0,$datestart["mon"],$datestart["mday"],$datestart["year"]);
	$stoptime=mktime(23,59,59,$datestop["mon"],$datestop["mday"],$datestop["year"]);
	
	$today = getdate(time());
	$events = getEventsInInterval($starttime,$stoptime);
	
	UI::contentHeading(getMLText("week_view")." : ".getReadableDate(mktime(12, 0, 0, $month, $day, $year)));
	UI::contentContainerStart();
	
	print "<a href=\"../out/out.Calendar.php?mode=w&year=".($year)."&month=".($month)."&day=".($day-7)."\"><img src=\"".UI::getImgPath("m.png")."\" border=0></a>&nbsp;";
	print "<a href=\"../out/out.Calendar.php?mode=w\"><img src=\"".UI::getImgPath("c.png")."\" border=0></a>&nbsp;";
	print "<a href=\"../out/out.Calendar.php?mode=w&year=".($year)."&month=".($month)."&day=".($day+7)."\"><img src=\"".UI::getImgPath("p.png")."\" border=0></a>&nbsp;";
	
	echo "<table class='calendarweek'>\n";
	
	for ($i=$starttime; $i<$stoptime; $i += 86400){
	
		$date = getdate($i);
		
		// highlight today
		$class = ($date["year"] == $today["year"] && $date["mon"] == $today["mon"] && $date["mday"]  == $today["mday"]) ? "todayHeader" : "header";
		
		echo "<tr>";
		echo "<td class='".$class."'>".getReadableDate($i)."</td>";
		echo "<td class='".$class."'>".$dayNamesLong[$date["wday"]]."</td>";
		
		if ($class=="todayHeader") $class="today";
		else $class="";
		
		foreach ($events as $event){
			if (($event["start"]<=$i)&&($event["stop"]>=$i)){
				print "<td class='".$class."'><a href=\"../out/out.ViewEvent.php?id=".$event['id']."\">".$event['name']."</a></td>";
			}else{
				print "<td class='".$class."'>&nbsp;</td>";
			}
		}
		
		echo "</tr>\n";	
	}
	echo "</table>\n";

	UI::contentContainerEnd();
}

UI::htmlEndPage();
?>
