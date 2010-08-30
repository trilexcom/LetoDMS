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

if (!isset($theme) || strlen($theme)==0) {
	$theme = $settings->_theme;
}
if (strlen($theme)==0) {
	$theme="blue";
}

// for extension use LOWER CASE only
$icons = array();
$icons["txt"]  = "txt.png";
$icons["text"] = "txt.png";
$icons["doc"]  = "word.png";
$icons["dot"]  = "word.png";
$icons["docx"] = "word.png";
$icons["dotx"] = "word.png";
$icons["rtf"]  = "document.png";
$icons["xls"]  = "excel.png";
$icons["xlt"]  = "excel.png";
$icons["xlsx"] = "excel.png";
$icons["xltx"] = "excel.png";
$icons["ppt"]  = "powerpoint.png";
$icons["pot"]  = "powerpoint.png";
$icons["pptx"] = "powerpoint.png";
$icons["potx"] = "powerpoint.png";
$icons["exe"]  = "binary.png";
$icons["html"] = "html.png";
$icons["htm"]  = "html.png";
$icons["gif"]  = "image.png";
$icons["jpg"]  = "image.png";
$icons["jpeg"] = "image.png";
$icons["bmp"]  = "image.png";
$icons["png"]  = "image.png";
$icons["tif"]  = "image.png";
$icons["tiff"] = "image.png";
$icons["log"]  = "log.png";
$icons["midi"] = "midi.png";
$icons["pdf"]  = "pdf.png";
$icons["wav"]  = "sound.png";
$icons["mp3"]  = "sound.png";
$icons["c"]    = "source_c.png";
$icons["cpp"]  = "source_cpp.png";
$icons["h"]    = "source_h.png";
$icons["java"] = "source_java.png";
$icons["py"]   = "source_py.png";
$icons["tar"]  = "tar.png";
$icons["gz"]   = "gz.png";
$icons["7z"]   = "gz.png";
$icons["bz"]   = "gz.png";
$icons["bz2"]  = "gz.png";
$icons["tgz"]  = "gz.png";
$icons["zip"]  = "gz.png";
$icons["mpg"]  = "video.png";
$icons["avi"]  = "video.png";
$icons["tex"]  = "tex.png";
$icons["ods"]  = "ooo_spreadsheet.png";
$icons["ots"]  = "ooo_spreadsheet.png";
$icons["sxc"]  = "ooo_spreadsheet.png";
$icons["stc"]  = "ooo_spreadsheet.png";
$icons["odt"]  = "ooo_textdocument.png";
$icons["ott"]  = "ooo_textdocument.png";
$icons["sxw"]  = "ooo_textdocument.png";
$icons["stw"]  = "ooo_textdocument.png";
$icons["odp"]  = "ooo_presentation.png";
$icons["otp"]  = "ooo_presentation.png";
$icons["sxi"]  = "ooo_presentation.png";
$icons["sti"]  = "ooo_presentation.png";
$icons["odg"]  = "ooo_drawing.png";
$icons["otg"]  = "ooo_drawing.png";
$icons["sxd"]  = "ooo_drawing.png";
$icons["std"]  = "ooo_drawing.png";
$icons["odf"]  = "ooo_formula.png";
$icons["sxm"]  = "ooo_formula.png";
$icons["smf"]  = "ooo_formula.png";
$icons["mml"]  = "ooo_formula.png";

$icons["default"] = "default.png";

class UI {
	function getStyles() {
		GLOBAL $settings;

		$themes = array();
		$path = $settings->_rootDir . "styles/";
		$handle = opendir($path);

		while ($entry = readdir($handle) ) {
			if ($entry == ".." || $entry == ".")
				continue;
			else if (is_dir($path . $entry))
				array_push($themes, $entry);
		}
		closedir($handle);
		return $themes;
	}

	function htmlStartPage($title="", $bodyClass="") {
		global $theme, $settings;

		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\n".
			"\"http://www.w3.org/TR/html4/strict.dtd\">\n";
		echo "<html>\n<head>\n";
		echo "<link rel=\"STYLESHEET\" type=\"text/css\" href=\"../styles/".$theme."/style.css\"/>\n";
		echo "<link rel=\"STYLESHEET\" type=\"text/css\" href=\"../styles/print.css\" media=\"print\"/>\n";
		echo "<link rel='shortcut icon' href='../styles/".$theme."/favicon.ico' type='image/x-icon'/>\n";
		echo "<title>".(strlen($settings->_siteName)>0 ? $settings->_siteName : "LetoDMS").(strlen($title)>0 ? ": " : "").$title."</title>\n";
		echo "</head>\n";
		echo "<body".(strlen($bodyClass)>0 ? " class=\"".$bodyClass."\"" : "").">\n";
	}

	function htmlEndPage() {
		UI::footNote();
		echo "</body>\n</html>\n";
	}

	function footNote() {
		global $settings;
		
		if ($settings->_printDisclaimer){
			echo "<div class=\"disclaimer\">".getMLText("disclaimer")."</div>";
		}

		if (isset($settings->_footNote) && strlen((string)$settings->_footNote)>0) {
			echo "<div class=\"footNote\">".(string)$settings->_footNote."</div>";
		}
	
		return;
	}

	function globalBanner() {
		global $settings;

		echo "<div class=\"globalBox\" id=\"noNav\">\n";
		echo "<div class=\"globalTR\"></div>\n";
		echo "<div class=\"siteName\">".
			(strlen($settings->_siteName)>0 ? $settings->_siteName : "LetoDMS").
			"</div>\n";
		echo "<div style=\"clear: both; height: 0px; font-size:0;\">&nbsp;</div>\n".
			"</div>\n";
		return;
	}

	function globalNavigation($folder=null) {
		global $settings, $user;

		echo "<div class=\"globalBox\">\n";
		echo "<div class=\"globalTR\"></div>\n";
		echo "<ul class=\"globalNav\">\n";
		echo "<li id=\"first\"><a href=\"../out/out.ViewFolder.php?folderid=".$settings->_rootFolderID."\">".getMLText("content")."</a></li>\n";
		if ($settings->_enableCalendar) echo "<li><a href=\"../out/out.Calendar.php?mode=w\">".getMLText("calendar")."</a></li>\n";
		echo "<li><a href=\"../out/out.MyDocuments.php?inProcess=1\">".getMLText("my_documents")."</a></li>\n";
		echo "<li><a href=\"../out/out.MyAccount.php\">".getMLText("my_account")."</a></li>\n";
		if ($user->isAdmin()) echo "<li><a href=\"../out/out.AdminTools.php\">".getMLText("admin_tools")."</a></li>\n";
		echo "<li><a href=\"../out/out.Help.php\">".getMLText("help")."</a></li>\n";
		echo "<li id=\"search\">\n";
		echo "<form action=\"../op/op.Search.php\">";
		if ($folder!=null && is_object($folder) && !strcasecmp(get_class($folder), "Folder")) {
			echo "<input type=\"hidden\" name=\"folderid\" value=\"".$folder->getID()."\" />";
		}
		echo "<input type=\"hidden\" name=\"navBar\" value=\"1\" />";
		echo "<input type=\"hidden\" name=\"searchin[]\" value=\"1\" />";
		echo "<input type=\"hidden\" name=\"searchin[]\" value=\"2\" />";
		echo "<input type=\"hidden\" name=\"searchin[]\" value=\"3\" />";
		echo "<input name=\"query\" type=\"text\" size=\"20\" /><input type=\"submit\" value=\"".getMLText("search")."\" id=\"searchButton\"/></form>\n";
		echo "</li>\n</ul>\n";
		echo "<div class=\"siteName\">".
			(strlen($settings->_siteName)>0 ? $settings->_siteName : "LetoDMS").
			"</div>\n";
		echo "<span class=\"absSpacerNorm\"></span>\n";
		echo "<div id=\"signatory\">".getMLText("signed_in_as")." ".$user->getFullName().
			" (<a href=\"../op/op.Logout.php\">".getMLText("sign_out")."</a>).</div>\n";
		echo "<div style=\"clear: both; height: 0px; font-size:0;\">&nbsp;</div>\n".
			"</div>\n";
		return;
	}

	function pageNavigation($pageTitle, $pageType=null, $extra=null) {
		global $settings, $user;

		echo "<div class=\"headingContainer\">\n";
		// This spacer span is an awful hack, but it is the only way I know to
		// get the spacer to match the mainheading content's size.
		echo "<span class=\"absSpacerTitle\">".($settings->_titleDisplayHack ? $pageTitle : "")."</span>\n";
		echo "<div class=\"mainHeading\">".$pageTitle."</div>\n";
		echo "<div style=\"clear: both; height: 0px; font-size:0;\"></div>\n</div>\n";

		if ($pageType!=null && strcasecmp($pageType, "noNav")) {
			echo "<div class=\"localNavContainer\">\n";
			switch ($pageType) {
				case "view_folder":
					UI::folderNavigationBar($extra);
					break;
				case "view_document":
					UI::documentNavigationBar();
					break;
				case "my_documents":
					UI::myDocumentsNavigationBar();
					break;
				case "my_account":
					UI::accountNavigationBar();
					break;
				case "admin_tools":
					UI::adminToolsNavigationBar();
					break;
				case "calendar";
					UI::calendarNavigationBar($extra);
					break;
			}
			echo "<div style=\"clear: both; height: 0px; font-size:0;\"></div>\n</div>\n";
		}

		return;
	}

	function folderNavigationBar($folder) {
	
		global $user, $settings, $theme;

		if (!is_object($folder) || strcasecmp(get_class($folder), "Folder")) {
			echo "<ul class=\"localNav\">\n";
			echo "</ul>\n";
			return;
		}
		$accessMode = $folder->getAccessMode($user);
		$folderID = $folder->getID();
		echo "<ul class=\"localNav\">\n";
		if ($accessMode == M_READ && $user->getID() != $settings->_guestID) {
			echo "<li id=\"first\"><a href=\"../out/out.FolderNotify.php?folderid=". $folderID ."\">".getMLText("edit_folder_notify")."</a></li>\n";
		}
		else if ($accessMode >= M_READWRITE) {
			echo "<li id=\"first\"><a href=\"../out/out.AddSubFolder.php?folderid=". $folderID ."\">".getMLText("add_subfolder")."</a></li>\n";
			echo "<li><a href=\"../out/out.AddDocument.php?folderid=". $folderID ."\">".getMLText("add_document")."</a></li>\n";
			echo "<li><a href=\"../out/out.EditFolder.php?folderid=". $folderID ."\">".getMLText("edit_folder_props")."</a></li>\n";
			echo "<li><a href=\"../out/out.FolderNotify.php?folderid=". $folderID ."\">".getMLText("edit_existing_notify")."</a></li>\n";
			if ($folderID != $settings->_rootFolderID && $folder->getParent())
				echo "<li><a href=\"../out/out.MoveFolder.php?folderid=". $folderID ."\">".getMLText("move_folder")."</a></li>\n";
		}
		if ($accessMode == M_ALL) {
			if ($folderID != $settings->_rootFolderID && $folder->getParent())
				echo "<li><a href=\"../out/out.RemoveFolder.php?folderid=". $folderID ."\">".getMLText("rm_folder")."</a></li>\n";
			echo "<li><a href=\"../out/out.FolderAccess.php?folderid=". $folderID ."\">".getMLText("edit_folder_access")."</a></li>\n";
		}
		echo "</ul>\n";
		return;
	}

	function documentNavigationBar()	{
	
		global $user, $settings, $document;

		$accessMode = $document->getAccessMode($user);
		$docid=".php?documentid=" . $document->getID();

		echo "<ul class=\"localNav\">\n";
		if ($accessMode >= M_READWRITE) {
			if (!$document->isLocked()) {
				echo "<li id=\"first\"><a href=\"../out/out.UpdateDocument". $docid ."\">".getMLText("update_document")."</a></li>";
				echo "<li><a href=\"../op/op.LockDocument". $docid ."\">".getMLText("lock_document")."</a></li>";
				echo "<li><a href=\"../out/out.EditDocument". $docid ."\">".getMLText("edit_document_props")."</a></li>";
				echo "<li><a href=\"../out/out.MoveDocument". $docid ."\">".getMLText("move_document")."</a></li>";
			}
			else {
				$lockingUser = $document->getLockingUser();
				if (($lockingUser->getID() == $user->getID()) || ($document->getAccessMode($user) == M_ALL)) {
					echo "<li id=\"first\"><a href=\"../out/out.UpdateDocument". $docid ."\">".getMLText("update_document")."</a></li>";
					echo "<li><a href=\"../op/op.UnlockDocument". $docid ."\">".getMLText("unlock_document")."</a></li>";
					echo "<li><a href=\"../out/out.EditDocument". $docid ."\">".getMLText("edit_document_props")."</a></li>";
					echo "<li><a href=\"../out/out.MoveDocument". $docid ."\">".getMLText("move_document")."</a></li>";
					echo "<li><a href=\"../out/out.SetExpires". $docid ."\">".getMLText("expires")."</a></li>";
				}
			}
		}
		if ($accessMode == M_ALL) {
			echo "<li><a href=\"../out/out.RemoveDocument". $docid ."\">".getMLText("rm_document")."</a></li>";
			echo "<li><a href=\"../out/out.DocumentAccess". $docid ."\">".getMLText("edit_document_access")."</a></li>";
		}
		if ($accessMode >= M_READ && $user->getID() != $settings->_guestID) {
			echo "<li><a href=\"../out/out.DocumentNotify". $docid ."\">".getMLText("edit_existing_notify")."</a></li>";
		}
		echo "</ul>\n";
		return;
	}

	function accountNavigationBar() {
	
		global $settings,$user;
		
		echo "<ul class=\"localNav\">\n";
		echo "<li id=\"first\"><a href=\"../out/out.EditUserData.php\">".getMLText("edit_user_details")."</a></li>\n";
		
		if (!$user->isAdmin()) 
			echo "<li><a href=\"../out/out.UserDefaultKeywords.php\">".getMLText("edit_default_keywords")."</a></li>\n";
		
		if ($settings->_enableUsersView){
			echo "<li><a href=\"../out/out.UsrView.php\">".getMLText("users")."</a></li>\n";
			echo "<li><a href=\"../out/out.GroupView.php\">".getMLText("groups")."</a></li>\n";
		}
		echo "<li><a href=\"../out/out.ManageNotify.php\">".getMLText("edit_existing_notify")."</a></li>\n";	
		
		echo "</ul>\n";
		return;
	}

	function myDocumentsNavigationBar() {

		echo "<ul class=\"localNav\">\n";
		echo "<li id=\"first\"><a href=\"../out/out.MyDocuments.php?inProcess=1\">".getMLText("documents_in_process")."</a></li>\n";
		echo "<li><a href=\"../out/out.MyDocuments.php\">".getMLText("all_documents")."</a></li>\n";
		echo "<li><a href=\"../out/out.ReviewSummary.php\">".getMLText("review_summary")."</a></li>\n";
		echo "<li><a href=\"../out/out.ApprovalSummary.php\">".getMLText("approval_summary")."</a></li>\n";
		echo "</ul>\n";
		return;
	}

	function adminToolsNavigationBar() {
	
		global $settings;

		echo "<ul class=\"localNav\">\n";
		echo "<li id=\"first\"><a href=\"../out/out.Statistic.php\">".getMLText("folders_and_documents_statistic")."</a></li>\n";
		echo "<li><a href=\"../out/out.BackupTools.php\">".getMLText("backup_tools")."</a></li>\n";
		if ($settings->_logFileEnable) echo "<li><a href=\"../out/out.LogManagement.php\">".getMLText("log_management")."</a></li>\n";
		echo "<li><a href=\"../out/out.UsrMgr.php\">".getMLText("user_management")."</a></li>\n";
		echo "<li><a href=\"../out/out.GroupMgr.php\">".getMLText("group_management")."</a></li>\n";
		echo "<li><a href=\"../out/out.DefaultKeywords.php\">".getMLText("global_default_keywords")."</a></li>\n";
		echo "</ul>\n";
		return;
	}
	
	function calendarNavigationBar($d){
	
		$ds="&day=".$d[0]."&month=".$d[1]."&year=".$d[2];
	
		echo "<ul class=\"localNav\">\n";
		echo "<li><a href=\"../out/out.Calendar.php?mode=w".$ds."\">".getMLText("week_view")."</a></li>\n";
		echo "<li><a href=\"../out/out.Calendar.php?mode=m".$ds."\">".getMLText("month_view")."</a></li>\n";
		echo "<li><a href=\"../out/out.Calendar.php?mode=y".$ds."\">".getMLText("year_view")."</a></li>\n";
		echo "<li><a href=\"../out/out.AddEvent.php\">".getMLText("add_event")."</a></li>\n";
		echo "</ul>\n";
		return;
	
	}

	function pageList($pageNumber, $totalPages, $baseURI, $params) {

		if (!is_numeric($pageNumber) || !is_numeric($totalPages) || $totalPages<2) {
			return;
		}

		// Construct the basic URI based on the $_GET array. One could use a
		// regular expression to strip out the pg (page number) variable to
		// achieve the same effect. This seems to be less haphazard though...
		$resultsURI = $baseURI;
		$first=true;
		foreach ($params as $key=>$value) {
			// Don't include the page number in the basic URI. This is added in
			// during the list display loop.
			if (!strcasecmp($key, "pg")) {
				continue;
			}
			if (is_array($value)) {
				foreach ($value as $subvalue) {
					$resultsURI .= ($first ? "?" : "&").$key."%5B%5D=".$subvalue;
					$first = false;
				}
			}
			else {
					$resultsURI .= ($first ? "?" : "&").$key."=".$value;
			}
			$first = false;
		}

		echo "<div class=\"pageList\">";
		echo getMLText("results_page").": ";
		for ($i = 1; $i  <= $totalPages; $i++) {
			if ($i == $pageNumber)  echo "<span class=\"selected\">".$i."</span> ";
			else echo "<a href=\"".$resultsURI.($first ? "?" : "&")."pg=".$i."\">".$i."</a>"." ";
		}
		if ($totalPages>1) {
			echo "<a href=\"".$resultsURI.($first ? "?" : "&")."pg=all\">".getMLText("all_pages")."</a>"." ";
		}
		echo "</div>";

		return;
	}

	function contentContainer($content) {

		echo "<div class=\"contentContainer\">\n";
		echo "<div class=\"content\">\n";
		echo "<div class=\"content-l\"><div class=\"content-r\"><div class=\"content-br\"><div class=\"content-bl\">\n";
		echo $content;
		echo "</div></div></div></div>\n</div>\n</div>\n";
		return;
	}
	function contentContainerStart() {

		echo "<div class=\"contentContainer\">\n";
		echo "<div class=\"content\">\n";
		echo "<div class=\"content-l\"><div class=\"content-r\"><div class=\"content-br\"><div class=\"content-bl\">\n";
		return;
	}
	function contentContainerEnd() {

		echo "</div></div></div></div>\n</div>\n</div>\n";
		return;
	}

	function contentHeading($heading) {

		echo "<div class=\"contentHeading\">".$heading."</div>\n";
		return;
	}
	function contentSubHeading($heading, $first=false) {

		echo "<div class=\"contentSubHeading\"".($first ? " id=\"first\"" : "").">".$heading."</div>\n";
		return;
	}

	function getMimeIcon($fileType) {
		global $icons;

		$ext = strtolower(substr($fileType, 1));
		if (isset($icons[$ext])) {
			return $icons[$ext];
		}
		else {
			return $icons["default"];
		}
	}

	function printDateChooser($defDate = -1, $varName) {
	
		if ($defDate == -1)
			$defDate = mktime();
		$day   = date("d", $defDate);
		$month = date("m", $defDate);
		$year  = date("Y", $defDate);

		print "<select name=\"" . $varName . "day\">\n";
		for ($i = 1; $i <= 31; $i++)
		{
			print "<option value=\"" . $i . "\"";
			if (intval($day) == $i)
				print " selected";
			print ">" . $i . "</option>\n";
		}
		print "</select> \n";
		print "<select name=\"" . $varName . "month\">\n";
		for ($i = 1; $i <= 12; $i++)
		{
			print "<option value=\"" . $i . "\"";
			if (intval($month) == $i)
				print " selected";
			print ">" . $i . "</option>\n";
		}
		print "</select> \n";
		print "<select name=\"" . $varName . "year\">\n";	
		for ($i = $year-5 ; $i <= $year+5 ; $i++)
		{
			print "<option value=\"" . $i . "\"";
			if (intval($year) == $i)
				print " selected";
			print ">" . $i . "</option>\n";
		}
		print "</select>";
	}

	function printSequenceChooser($objArr, $keepID = -1) {
		if (count($objArr) > 0) {
			$max = $objArr[count($objArr)-1]->getSequence() + 1;
			$min = $objArr[0]->getSequence() - 1;
		}
		else {
			$max = 1.0;
		}
		print "<select name=\"sequence\">\n";
		if ($keepID != -1) {
			print "  <option value=\"keep\">" . getMLText("seq_keep");
		}
		print "  <option value=\"".$max."\">" . getMLText("seq_end");
		if (count($objArr) > 0) {
			print "  <option value=\"".$min."\">" . getMLText("seq_start");
		}
		for ($i = 0; $i < count($objArr) - 1; $i++) {
			if (($objArr[$i]->getID() == $keepID) || (($i + 1 < count($objArr)) && ($objArr[$i+1]->getID() == $keepID))) {
				continue;
			}
			$index = ($objArr[$i]->getSequence() + $objArr[$i+1]->getSequence()) / 2;
			print "  <option value=\"".$index."\">" . getMLText("seq_after", array("prevname" => $objArr[$i]->getName() ) );
		}
		print "</select>";
	}
	
	function printDocumentChooser($formName) {
		global $settings;
		?>
		<script language="JavaScript">
		var openDlg;
		function chooseDoc<?php print $formName ?>() {
			openDlg = open("../out/out.DocumentChooser.php?folderid=<?php echo $settings->_rootFolderID?>&form=<?php echo urlencode($formName)?>", "openDlg", "width=480,height=480,scrollbars=yes,resizable=yes,status=yes");
		}
		</script>
		<?php
		print "<input type=\"Hidden\" name=\"docid".$formName."\">";
		print "<input disabled name=\"docname".$formName."\">";
		print "&nbsp;&nbsp;<input type=\"Button\" value=\"".getMLText("document")."...\" onclick=\"chooseDoc".$formName."();\">";
	}

	function printFolderChooser($formName, $accessMode, $exclude = -1, $default = false) {
		global $settings;
		?>
		<script language="JavaScript">
		var openDlg;
		function chooseFolder<?php print $formName ?>() {
			openDlg = open("out.FolderChooser.php?form=<?php echo $formName?>&mode=<?php echo $accessMode?>&exclude=<?php echo $exclude?>", "openDlg", "width=480,height=480,scrollbars=yes,resizable=yes,status=yes");
		}
		</script>
		<?php
		print "<input type=\"Hidden\" name=\"targetid".$formName."\" value=\"". (($default) ? $default->getID() : "") ."\">";
		print "<input disabled name=\"targetname".$formName."\" value=\"". (($default) ? $default->getName() : "") ."\">";
		print "&nbsp;&nbsp;<input type=\"Button\" value=\"".getMLText("folder")."...\" onclick=\"chooseFolder".$formName."();\">";
	}

	function getImgPath($img) {
		global $theme;

		if ( is_file("../styles/$theme/images/$img") ) {
			return "../styles/$theme/images/$img";
		}
		else if ( is_file("../styles/$theme/img/$img") ) {
			return "../styles/$theme/img/$img";
		}
		return "../out/images/$img";
	}

	function printImgPath($img) {
		print UI::getImgPath($img);
	}
	
	function exitError($pagetitle,$error){
	
		UI::htmlStartPage($pagetitle);
		UI::globalNavigation();

		print "<div class=\"error\">";
		print $error;
		print "</div>";
		
		UI::htmlEndPage();
		
		add_log_line(" UI::exitError error=".$error." pagetitle=".$pagetitle);
		
		exit;	
	}

	// navigation flag is used for items links (navigation or selection)
	function printFoldersTree($accessMode, $exclude, $folderID, $currentFolderID=-1, $navigation=false)
	{	
		global $user, $form, $settings;
		
		// open the tree until the current folder
		$is_open=false;
		if ($currentFolderID!=-1){
			
			$currentFolder=getFolder($currentFolderID);
			
			if (is_object($currentFolder)){
			
				$parent=$currentFolder->getParent();
				
				while (is_object($parent)){
					if ($parent->getID()==$folderID){
						$is_open=true;
						break;
					}
					$parent=$parent->getParent();
				}
			}
		}
		
		$folder = getFolder($folderID);
		if (!is_object($folder)) return;
		
		$subFolders = $folder->getSubFolders();
		$subFolders = filterAccess($subFolders, $user, M_READ);
		
		if ($folderID == $settings->_rootFolderID) print "<ul style='list-style-type: none;'>\n";

		print "<li>\n";

		if (count($subFolders) > 0){
			print "<a href=\"javascript:toggleTree(".$folderID.")\"><img class='treeicon' name=\"treedot".$folderID."\" src=\"";	
			if ($is_open) UI::printImgPath("minus.png");
			else UI::printImgPath("plus.png");
			print "\" border=0></a>\n";
		}
		else{
			print "<img class='treeicon' src=\"";	
			UI::printImgPath("blank.png");
			print "\" border=0>\n";
		}

		if ($folder->getAccessMode($user) >= $accessMode) {

			if ($folderID != $currentFolderID){
			
				if ($navigation) print "<a href=\"../out/out.ViewFolder.php?folderid=" . $folderID . "&showtree=1\">";
				else print "<a class=\"foldertree_selectable\" href=\"javascript:folderSelected(" . $folderID . ", '" . sanitizeString($folder->getName()) . "')\">";

			}else print "<span class=\"selectedfoldertree\">";
			
			if ($is_open) print "<img src=\"".UI::getImgPath("folder_opened.gif")."\" border=0 name=\"treeimg".$folderID."\">".$folder->getName();
			else print "<img src=\"".UI::getImgPath("folder_closed.gif")."\" border=0 name=\"treeimg".$folderID."\">".$folder->getName();

			if ($folderID != $currentFolderID) print "</a>\n";
			else print "</span>";

		}
		else print "<img src=\"".UI::getImgPath("folder_closed.gif")."\" width=18 height=18 border=0>".$folder->getName()."\n";

		if ($is_open) print "<ul style='list-style-type: none;' id=\"tree".$folderID."\" >\n";
		else print "<ul style='list-style-type: none; display: none;' id=\"tree".$folderID."\" >\n";
		
		for ($i = 0; $i < count($subFolders); $i++) {
		
			if ($subFolders[$i]->getID() == $exclude) continue;
			
			UI::printFoldersTree( $accessMode, $exclude, $subFolders[$i]->getID(),$currentFolderID,$navigation);
		}

		print "</ul>\n";
		
		if ($folderID == $settings->_rootFolderID) print "</ul>\n";
	}
	
	function printTreeNavigation($folderid,$showtree){
	
		global $settings;
		
		?>
		<script language="JavaScript">
		function toggleTree(id){
			
			obj = document.getElementById("tree" + id);
			
			if ( obj.style.display == "none" ){
				obj.style.display = "";
				document["treeimg" + id].src = "<?php UI::printImgPath("folder_opened.gif"); ?>";
				document["treedot" + id].src = "<?php UI::printImgPath("minus.png"); ?>";
			}else{
				obj.style.display = "none";
				document["treeimg" + id].src = "<?php UI::printImgPath("folder_closed.gif"); ?>";
				document["treedot" + id].src = "<?php UI::printImgPath("plus.png"); ?>";
			}

		}
		</script>
		<?php
	
		print "<table width=\"100%\"><tr><td>";

		if ($showtree==1){

			UI::contentHeading("<a href=\"../out/out.ViewFolder.php?folderid=". $folderid."\"><img src=\"".UI::getImgPath("m.png")."\" border=0></a>");
			UI::contentContainerStart();
			UI::printFoldersTree(M_READ, -1, $settings->_rootFolderID, $folderid, true);
			UI::contentContainerEnd();

		}else{
			UI::contentHeading("<a href=\"../out/out.ViewFolder.php?folderid=". $folderid."&showtree=1\"><img src=\"".UI::getImgPath("p.png")."\" border=0></a>");
			UI::contentContainerStart();
			UI::contentContainerEnd();
		}

		print "</td><td>";
	}
}

?>
