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

include("../inc/inc.Settings.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if ($user->isGuest()) {
	UI::exitError(getMLText("edit_default_keywords"),getMLText("access_denied"));
}

$categories = $dms->getAllUserKeywordCategories($user->getID());

UI::htmlStartPage(getMLText("edit_default_keywords"));
UI::globalNavigation();
UI::pageNavigation(getMLText("my_account"), "my_account");
?>
<script language="JavaScript">
obj = -1;
function showKeywords(selectObj) {
	if (obj != -1)
		obj.style.display = "none";

	id = selectObj.options[selectObj.selectedIndex].value;
	if (id == -1)
		return;

	obj = document.getElementById("keywords" + id);
	obj.style.display = "";
}
</script>
<?php
UI::contentHeading(getMLText("edit_default_keywords"));
UI::contentContainerStart();
?>
	<table>
	<tr>
		<td><?php echo getMLText("selection")?>:</td>
		<td>
			<select onchange="showKeywords(this)" id="selector">
				<option value="-1"><?php echo getMLText("choose_category")?>
				<option value="0"><?php echo getMLText("new_default_keyword_category")?>
				<?php

				$selected=0;
				$count=2;
				foreach ($categories as $category) {

					$owner = $category->getOwner();
					if ($owner->getID() != $user->getID()) continue;

					if (isset($_GET["categoryid"]) && $category->getID()==$_GET["categoryid"]) $selected=$count;
					print "<option value=\"".$category->getID()."\">" . $category->getName();
					$count++;
				}
				?>
			</select>
			&nbsp;&nbsp;
		</td>

		<td id="keywords0" style="display : none;">
			<form action="../op/op.UserDefaultKeywords.php" method="post" name="addcategory">
			<input type="Hidden" name="action" value="addcategory">
			<?php printMLText("name");?> : <input name="name">
			<input type="Submit" value="<?php printMLText("new_default_keyword_category"); ?>">
			</form>
		<td>
		<?php

	foreach ($categories as $category) {

		$owner = $category->getOwner();
		if ($owner->getID() != $user->getID()) continue;

		print "<td id=\"keywords".$category->getID()."\" style=\"display : none;\">";
	?>
			<table>
				<tr>
					<td colspan="2">
						<a href="../op/op.UserDefaultKeywords.php?categoryid=<?php print $category->getID();?>&action=removecategory"><img src="images/del.gif" border="0"><?php printMLText("rm_default_keyword_category");?></a>
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
						<form action="../op/op.UserDefaultKeywords.php" method="post" name="<?php echo "category".$category->getID()?>">
							<input type="Hidden" name="action" value="editcategory">
							<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
							<input name="name" value="<?php echo $category->getName()?>">
							<input type="Submit" value="<?php printMLText("save");?>">
						</form>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php UI::contentSubHeading("");?>
					</td>
				</tr>
				<tr>
					<td><?php echo getMLText("default_keywords")?>:</td>
					<td>
						<?php
							$lists = $category->getKeywordLists();
							if (count($lists) == 0)
								print getMLText("no_default_keywords");
							else
								foreach ($lists as $list) {
						?>
									<form action="../op/op.UserDefaultKeywords.php" method="post" name="<?php echo "cat".$category->getID().".".$list["id"]?>">
									<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
									<input type="Hidden" name="keywordsid" value="<?php echo $list["id"]?>">
									<input type="Hidden" name="action" value="editkeywords">
									<input name="keywords" value="<?php echo $list["keywords"]?>">
									<input name="action" value="editkeywords" type="Image" src="images/save.gif" title="<?php echo getMLText("save")?>" border="0">
									<!--	 <input name="action" value="removekeywords" type="Image" src="images/del.gif" title="<?php echo getMLText("delete")?>" border="0"> &nbsp; -->
									<a href="../op/op.UserDefaultKeywords.php?categoryid=<?php echo $category->getID()?>&keywordsid=<?php echo $list["id"]?>&action=removekeywords"><img src="images/del.gif" title="<?php echo getMLText("delete")?>" border=0></a>
									</form>
									<br>
						<?php }  ?>
					</td>
				</tr>
				<tr>
					<form action="../op/op.UserDefaultKeywords.php" method="post" name="<?php echo $category->getID().".add"?>">
					<td><input type="Submit" value="<?php printMLText("new_default_keywords");?>"></td>
					<td>
						<input type="Hidden" name="action" value="newkeywords">
						<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
						<input name="keywords">
					</td>
					</form>
				</tr>
			</table>
		</td>

<?php } ?>
	</tr></table>

<script language="JavaScript">

sel = document.getElementById("selector");
sel.selectedIndex=<?php print $selected ?>;
showKeywords(sel);

</script>


<?php
UI::contentContainerEnd();

UI::htmlEndPage();
?>
