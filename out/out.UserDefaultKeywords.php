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
include("../inc/inc.AccessUtils.php");
include("../inc/inc.ClassAccess.php");
include("../inc/inc.ClassDocument.php");
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.ClassKeywords.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

$categories = getAllUserKeywordCategories($user->getID());

UI::htmlStartPage(getMLText("edit_default_keywords"));
UI::globalNavigation();
UI::pageNavigation(getMLText("my_account").":".getMLText("edit_default_keywords"), "my_account");
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
UI::contentHeading(getMLText("edit_default_keyword_category"));
UI::contentContainerStart();
?>
	<table>
	<tr>
		<td><?php echo getMLText("default_keyword_category")?>:</td>
		<td>
			<select onchange="showKeywords(this)">
				<option value="-1"><?php echo getMLText("choose_category")?>
				<?php
				foreach ($categories as $category) {
					$owner = $category->getOwner();
					if ($owner->getID() != $user->getID())
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
		if ($owner->getID() != $user->getID())
			continue;
	?>
		<tr id="keywords<?php echo $category->getID()?>" style="display : none;">
		<td colspan="2">
			<table>
				<tr>
					<td colspan="2"><hr size="1" width="100%" color="#000080" noshade></td>
				</tr>
				<tr>
					<td><?php echo getMLText("name")?>:</td>
					<td>
					<form action="../op/op.UserDefaultKeywords.php" method="post">
					<input type="Hidden" name="action" value="editcategory">
					<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
					<table><tr>
					<td><input name="name" value="<?php echo $category->getName()?>"></td>
					<td><input type="Image" src="images/save.gif" title="<?php echo getMLText("save")?>"></td>
					</tr></table>
					</form>
					</td>
				</tr>
				<tr>
					<td><?php echo getMLText("default_keywords")?>:</td>
					<td>
						<table>
						<?php
							$lists = $category->getKeywordLists();
							if (count($lists) == 0)
								print "<tr><td>" . getMLText("no_default_keywords") . "</td></tr>";
							else
								foreach ($lists as $list) {
						?>
									<tr>
										<form action="../op/op.UserDefaultKeywords.php" method="post">
										<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
										<input type="Hidden" name="keywordsid" value="<?php echo $list["id"]?>">
										<input type="Hidden" name="action" value="editkeywords">
										<td>
											<input name="keywords" value="<?php echo $list["keywords"]?>">
										</td>
										<td>
											 <input name="action" value="editkeywords" type="Image" src="images/save.gif" title="<?php echo getMLText("save")?>" border="0"> &nbsp;
										<!--	 <input name="action" value="removekeywords" type="Image" src="images/del.gif" title="<?php echo getMLText("delete")?>" border="0"> &nbsp; -->
											<a href="../op/op.UserDefaultKeywords.php?categoryid=<?php echo $category->getID()?>&keywordsid=<?php echo $list["id"]?>&action=removekeywords"><img src="images/del.gif" title="<?php echo getMLText("delete")?>" border=0></a>
										</td>
										</form>
									</tr>
						<?php }  ?>
						</table>
					</td>
					<td></td>
				</tr>
				<tr>
					<td><?php echo getMLText("new_default_keywords")?>:</td>
					<td>
						<form action="../op/op.UserDefaultKeywords.php" method="post">
							<input type="Hidden" name="action" value="newkeywords">
							<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
							<table><tr>
							<td><input name="keywords"></td>
							<td><input type="Image" src="images/save.gif" title="<?php echo getMLText("save")?>"></td>
							</tr></table>
						</form>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<a href="../op/op.UserDefaultKeywords.php?categoryid=<?php print $category->getID();?>&action=removecategory"><img src="images/del.gif" width="15" height="15" border="0" align="absmiddle" alt=""> <?php printMLText("rm_default_keyword_category");?></a>
					</td>
				</tr>
			</table>
		</td>
		</tr>
<?php } ?>
	</table>
<?php
UI::contentContainerEnd();

UI::contentHeading(getMLText("new_default_keyword_category"));
UI::contentContainerStart();
?>

<form action="../op/op.UserDefaultKeywords.php" method="post">
	<input type="Hidden" name="action" value="addcategory">
	<table>
		<tr>
			<td><?php printMLText("name");?>:</td>
			<td><input name="name"></td>
		</tr>
		<tr>
			<td colspan="2"><input type="Submit" value="<?php printMLText("add"); ?>"></td>
		</tr>
	</table>
</form>

<?php
UI::contentContainerEnd();

UI::htmlEndPage();
?>
