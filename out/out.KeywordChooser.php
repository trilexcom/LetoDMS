<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2011 Uwe Steinmann
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
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

$allusers = $dms->getAllUsers();
$userids = array($user->getID());
foreach($allusers as $u) {
	if($u->isAdmin())
		$userids[] = $u->getId();
}
$categories = $dms->getAllKeywordCategories($userids);

UI::htmlStartPage(getMLText("use_default_keywords"));

?>


<script language="JavaScript">
var targetObj = opener.document.form1.keywords;
var myTA;


function insertKeywords(keywords) {

	if (navigator.appName == "Microsoft Internet Explorer") {
		myTA.value += " " + keywords;
	}
	//assuming Mozilla
	else {
		selStart = myTA.selectionStart;
		
		myTA.value = myTA.value.substring(0,myTA.selectionStart) + " " 
			+ keywords
			+ myTA.value.substring(myTA.selectionStart,myTA.value.length);
		
		myTA.selectionStart = selStart + keywords.length+1;
		myTA.selectionEnd = selStart + keywords.length+1;
	}				  
	myTA.focus();
}

function cancel() {
	window.close();
	return true;
}

function acceptKeywords() {
	targetObj.value = myTA.value;
	window.close();
	return true;
}

obj = new Array();
obj[0] = -1;
obj[1] = -1;
function showKeywords(which) {
	if (obj[which] != -1)
		obj[which].style.display = "none";
	
	list = document.getElementById("categories" + which);
	
	id = list.options[list.selectedIndex].value;
	if (id == -1)
		return;
	
	obj[which] = document.getElementById("keywords" + id);
	obj[which].style.display = "";
}
</script>

<div>
<?php
UI::contentHeading(getMLText("use_default_keywords"));
UI::contentContainerStart();
?>



<table>

	<tr>
		<td valign="top" class="inputDescription"><?php echo getMLText("keywords")?>:</td>
		<td><textarea id="keywordta" rows="5" cols="30"></textarea></td>
	</tr>
	
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	

	<tr>
		<td class="inputDescription"><?php echo getMLText("global_default_keywords")?>:</td>
		<td>
			<select onchange="showKeywords(0)" id="categories0">
				<option value="-1"><?php echo getMLText("choose_category")?>
				<?php
				foreach ($categories as $category) {
					$owner = $category->getOwner();
					if (!$owner->isAdmin())
						continue;
					
					print "<option value=\"".$category->getID()."\">" . $category->getName();
				}
				?>
			</select>
		</td>
	</tr>
<?php
	foreach ($categories as $category) {
		$owner = $category->getOwner();
		if (!$owner->isAdmin())
			continue;
?>
	<tr id="keywords<?php echo $category->getID()?>" style="display : none;">
		<td valign="top" class="inputDescription"><?php echo getMLText("default_keywords")?>:</td>
		<td>
			<?php
				$lists = $category->getKeywordLists();
				
				if (count($lists) == 0) print getMLText("no_default_keywords");
				else {	
					print "<ul>";
					foreach ($lists as $list) {
						print "<li><a href='javascript:insertKeywords(\"$list[keywords]\");'>$list[keywords]</a></li>";
					}
					print "</ul>";
				}
			?>
		</td>
	</tr>
<?php } ?>
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<tr>
		<td class="inputDescription"><?php echo getMLText("personal_default_keywords")?>:</td>
		<td>
			<select onchange="showKeywords(1)" id="categories1">
				<option value="-1"><?php echo getMLText("choose_category")?>
				<?php
				foreach ($categories as $category) {
					$owner = $category->getOwner();
					if ($owner->isAdmin())
						continue;
					
					print "<option value=\"".$category->getID()."\">" . $category->getName();
				}
				?>
			</select>
		</td>
	</tr>
<?php
	foreach ($categories as $category) {
		$owner = $category->getOwner();
		if ($owner->isAdmin())
			continue;
?>
	<tr id="keywords<?php echo $category->getID()?>" style="display : none;">
		<td valign="top" class="inputDescription"><?php echo getMLText("default_keywords")?>:</td>
		<td class="standardText">
			<?php
				$lists = $category->getKeywordLists();				
				if (count($lists) == 0) print getMLText("no_default_keywords");
				else {	
					print "<ul>";
					foreach ($lists as $list) {
						print "<li><a href='javascript:insertKeywords(\"$list[keywords]\");'>$list[keywords]</a></li>";
					}
					print "</ul>";
				}
			?>
		</td>
	</tr>
<?php } ?>
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<tr>
		<td colspan="2">
			<br>
			<input type="Button" onclick="acceptKeywords();" value="<?php echo getMLText("accept")?>"> &nbsp;&nbsp;
			<input type="Button" onclick="cancel();" value="<?php echo getMLText("cancel")?>">
		</td>
	</tr>
</table>

<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
</div>

<script language="JavaScript">
myTA = document.getElementById("keywordta");
myTA.value = targetObj.value;
myTA.focus();
</script>

</body>
</html>
