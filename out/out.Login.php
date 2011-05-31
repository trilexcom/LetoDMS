<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");

UI::htmlStartPage(getMLText("sign_in"), "login");
UI::globalBanner();
UI::pageNavigation(getMLText("sign_in"));
?>
<script language="JavaScript">
function checkForm()
{
	msg = "";
	if (document.form1.login.value == "") msg += "<?php printMLText("js_no_login");?>\n";
	if (document.form1.pwd.value == "") msg += "<?php printMLText("js_no_pwd");?>\n";
	if (msg != "")
	{
		alert(msg);
		return false;
	}
	else
		return true;
}

function guestLogin()
{
	url = "../op/op.Login.php?login=guest" + 
		"&sesstheme=" + document.form1.sesstheme.options[document.form1.sesstheme.options.selectedIndex].value +
		"&lang=" + document.form1.lang.options[document.form1.lang.options.selectedIndex].value;
	if (document.form1.referuri) {
		url += "&referuri=" + escape(document.form1.referuri.value);
	}
	document.location.href = url;
}

</script>
<?php UI::contentContainerStart(); ?>
<form action="../op/op.Login.php" method="post" name="form1" onsubmit="return checkForm();">
<?php
if (isset($_GET["referuri"]) && strlen($_GET["referuri"])>0) {
	$refer=$_GET["referuri"];
}
else if (isset($_POST["referuri"]) && strlen($_POST["referuri"])>0) {
	$refer=$_POST["referuri"];
}
if (isset($refer) && strlen($refer)>0) {
	echo "<input type='hidden' name='referuri' value='".$refer."'/>";
}
?>
	<table border="0">
		<tr>
			<td><?php printMLText("user_login");?></td>
			<td><input name="login" id="login"></td>
		</tr>
		<tr>
			<td><?php printMLText("password");?></td>
			<td><input name="pwd" type="Password"></td>
		</tr>
		<tr>
			<td><?php printMLText("language");?></td>
			<td>
			<?php
				print "<select name=\"lang\">";
				print "<option value=\"\">-";
				$languages = getLanguages();
				foreach ($languages as $currLang) {
					print "<option value=\"".$currLang."\">".$currLang;
				}
				print "</select>";
			?>
			</td>
		</tr>
		<tr>
			<td><?php printMLText("theme");?></td>
		<td>
			<?php
				print "<select name=\"sesstheme\">";
				print "<option value=\"\">-";
				$themes = UI::getStyles();
				foreach ($themes as $currTheme) {
					print "<option value=\"".$currTheme."\">".$currTheme;
				}
				print "</select>";
			?>
		</td>
		</tr>
		<tr>
			<td colspan="2"><input type="Submit" value="<?php printMLText("submit_login") ?>"></td>
		</tr>
	</table>
</form>
<?php UI::contentContainerEnd(); ?>
<?php
	if ($settings->_enableGuestLogin)
		print "<p><a href=\"javascript:guestLogin()\">" . getMLText("guest_login") . "</a></p>\n";
?>
<script language="JavaScript">document.form1.login.focus();</script>
<?php
	UI::htmlEndPage();
?>
