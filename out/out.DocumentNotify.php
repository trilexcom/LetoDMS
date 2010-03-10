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
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassDocument.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"]<1)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));

}

$documentid = $_GET["documentid"];
$document = getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$folder = $document->getFolder();
$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

if ($document->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

$notifyList = $document->getNotifyList();

UI::htmlStartPage(getMLText("document_title", array("documentname" => $document->getName())));
UI::globalNavigation($folder);
UI::pageNavigation($docPathHTML, "view_document");

?>
<script language="JavaScript">
function checkForm()
{
	msg = "";
	if ((document.form1.userid.options[document.form1.userid.selectedIndex].value == -1) && 
		(document.form1.groupid.options[document.form1.groupid.selectedIndex].value == -1))
			msg += "<?php printMLText("js_select_user_or_group");?>\n";
	if (msg != "")
	{
		alert(msg);
		return false;
	}
	else
		return true;
}
</script>

<?php
UI::contentHeading(getMLText("edit_existing_notify"));
UI::contentContainerStart();

$userNotifyIDs = array();
$groupNotifyIDs = array();

print "<table class=\"defaultView\">\n";
if ((count($notifyList["users"]) == 0) && (count($notifyList["groups"]) == 0)) {
	print "<tr><td>".getMLText("empty_notify_list")."</td></tr>";
}
else {
	foreach ($notifyList["users"] as $userNotify) {
		print "<tr>";
		print "<td><img src=\"images/usericon.gif\" class=\"mimeicon\"></td>";
		print "<td>" . $userNotify->getFullName() . "</td>";
		if ($user->isAdmin() || $user->getID() == $userNotify->getID()) {
			print "<td><a href=\"../op/op.DocumentNotify.php?documentid=". $documentid . "&action=delnotify&userid=".$userNotify->getID()."\"><img src=\"images/del.gif\" class=\"mimeicon\"></a>".getMLText("delete")."</td>";
		}else print "<td></td>";
		print "</tr>";
		$userNotifyIDs[] = $userNotify->getID();
	}
	foreach ($notifyList["groups"] as $groupNotify) {
		print "<tr>";
		print "<td><img src=\"images/groupicon.gif\" width=16 height=16 border=0></td>";
		print "<td>" . $groupNotify->getName() . "</td>";
		if ($user->isAdmin() || $groupNotify->isMember($user)) {
			print "<td><a href=\"../op/op.DocumentNotify.php?documentid=". $documentid . "&action=delnotify&groupid=".$groupNotify->getID()."\"><img src=\"images/del.gif\" class=\"mimeicon\"></a>".getMLText("delete")."</td>";
		}else print "<td></td>";
		print "</tr>";
		$groupNotifyIDs[] = $groupNotify->getID();
	}
}
print "</table>\n";

?>
<br>

<form action="../op/op.DocumentNotify.php" name="form1" onsubmit="return checkForm();">
<input type="Hidden" name="documentid" value="<?php print $documentid?>">
<input type="Hidden" name="action" value="addnotify">
<table>
	<tr>
		<td><?php printMLText("user");?>:</td>
		<td>
			<select name="userid">
				<option value="-1"><?php printMLText("select_one");?>
				<option value="-1">-------------------------------
				<?php
					if ($user->isAdmin()) {
						$allUsers = getAllUsers();
						foreach ($allUsers as $userObj) {
							if ($userObj->getID() != $settings->_guestID && !in_array($userObj->getID(), $userNotifyIDs))
								print "<option value=\"".$userObj->getID()."\">" . $userObj->getFullName() . "\n";
						}
					}
					else if ($user->getID() != $settings->_guestID && !in_array($user->getID(), $userNotifyIDs)) {
						print "<option value=\"".$user->getID()."\">" . $user->getFullName() . "\n";
					}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td><?php printMLText("group");?>:</td>
		<td>
			<select name="groupid">
				<option value="-1"><?php printMLText("select_one");?>
				<option value="-1">-------------------------------
				<?php
					$allGroups = getAllGroups();
					foreach ($allGroups as $groupObj) {
						if (($user->isAdmin() || $groupObj->isMember($user)) && !in_array($groupObj->getID(), $groupNotifyIDs)) {
							print "<option value=\"".$groupObj->getID()."\">" . $groupObj->getName() . "\n";
						}
					}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2"><input type="Submit" value="<?php printMLText("add") ?>"></td>
	</tr>
</table>
</form>

<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
