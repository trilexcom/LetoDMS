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
include("../inc/inc.ClassUI.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.Authentication.php");

$form = sanitizeString($_GET["form"]);
$selcats = sanitizeString($_GET["cats"]);

UI::htmlStartPage(getMLText("choose_target_category"));
UI::globalBanner();
UI::pageNavigation(getMLText("choose_target_category"));
?>

<script language="JavaScript">
var targetName = opener.document.<?php echo $form?>.categoryname<?php print $form ?>;
var targetID = opener.document.<?php echo $form?>.categoryid<?php print $form ?>;
$(document).ready(function(){
	$('#getcategories').click(function(){
//    alert($('#keywordta option:selected').text());
		var value = '';
		$('#keywordta option:selected').each(function(){
			value += ' ' + $(this).text();
		});
		targetName.value = value;
		targetID.value = $('#keywordta').val();
		window.close();
		return true;
	});
});
</script>

<?php
	UI::contentContainerStart();
	$categories = $dms->getDocumentCategories();
	$selcatsarr = explode(',', $selcats);
?>
<table>
	<tr>
		<td valign="top" class="inputDescription"><?php echo getMLText("categories")?>:</td>
		<td>
			<select id="keywordta" size="5" style="min-width: 100px;" multiple>
<?php
	foreach($categories as $category) {
		echo "<option value=\"".$category->getId()."\"";
		if(in_array($category->getID(), $selcatsarr))
			echo " selected";
		echo ">".$category->getName()."</option>\n";
	}
?>
			</select>
		</td>
	</tr>
	<tr>
	  <td></td>
		<td>
			<input type="button" id='getcategories' value="<?php echo getMLText("accept")?>"> &nbsp;&nbsp;
		</td>
	</tr>

</table>
<?php
	UI::contentContainerEnd();
	UI::htmlEndPage();
?>
