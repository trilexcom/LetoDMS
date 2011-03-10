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

include("../inc/inc.Settings.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("admin_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");

$categories = $dms->getDocumentCategories();
?>

<script language="JavaScript">
obj = -1;
function showCategories(selectObj) {
	if (obj != -1)
		obj.style.display = "none";
	
	id = selectObj.options[selectObj.selectedIndex].value;
	if (id == -1)
		return;
	
	obj = document.getElementById("categories" + id);
	obj.style.display = "";
}
</script>
<?php

UI::contentHeading(getMLText("global_document_categories"));
UI::contentContainerStart();
?>
	<table>
	<tr>
		<td><?php echo getMLText("selection")?>:</td>
		<td>
			<select onchange="showCategories(this)" id="selector">
				<option value="-1"><?php echo getMLText("choose_category")?>
				<option value="0"><?php echo getMLText("new_document_category")?>

				<?php
				
				$selected=0;
				$count=2;				
				foreach ($categories as $category) {
				
					if (isset($_GET["categoryid"]) && $category->getID()==$_GET["categoryid"]) $selected=$count;				
					print "<option value=\"".$category->getID()."\">" . $category->getName();
					$count++;
				}
				?>
			</select>
			&nbsp;&nbsp;
		</td>

		<td id="categories0" style="display : none;">	
			<form action="../op/op.Categories.php" >
			<input type="Hidden" name="action" value="addcategory">
			<?php printMLText("name");?> : <input name="name">
			<input type="Submit" value="<?php printMLText("new_document_category"); ?>">
			</form>
		</td>
	
	<?php	
	
	foreach ($categories as $category) {
	
		print "<td id=\"categories".$category->getID()."\" style=\"display : none;\">";	
?>
			<table>
				<tr>
					<td colspan="2">
<?php
		if(!$category->isUsed()) {
?>
						<a href="../op/op.Categories.php?categoryid=<?php print $category->getID();?>&action=removecategory"><img src="images/del.gif" border="0"><?php printMLText("rm_document_category");?></a>
<?php
		} else {
?>
						<p><?= getMLText('category_in_use') ?></p>
<?php
		}
?>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php UI::contentSubHeading("");?>
					</td>
				</tr>				
				<tr>
					<td><?php echo getMLText("name")?>:</td>
					<td>
						<form action="../op/op.Categories.php" >
							<input type="Hidden" name="action" value="editcategory">
							<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
							<input name="name" value="<?php echo $category->getName()?>">&nbsp;
							<input type="Submit" value="<?php printMLText("save");?>">
						</form>
					</td>
				</tr>
				
			</table>
		</td>
<?php } ?>
	</tr></table>
	
<script language="JavaScript">

sel = document.getElementById("selector");
sel.selectedIndex=<?php print $selected ?>;
showCategories(sel);

</script>

	
<?php
UI::contentContainerEnd();

UI::htmlEndPage();
?>
