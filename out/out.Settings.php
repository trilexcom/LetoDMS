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
UI::contentHeading(getMLText("settings"));
UI::contentContainerStart();

?>

<script language="JavaScript">
function ShowHide(strId)
{

  var objDiv = document.getElementById(strId);

  if (objDiv)
  {

    if(objDiv.style.display == 'block')
    {
      objDiv.style.display = 'none';
    }
    else
    {
      objDiv.style.display = 'block';
    }
  }
}
</script>


  <form action="../op/op.Settings.php" method="post" enctype="multipart/form-data" name="form0" >
  <input type="Hidden" name="action" value="saveSettings" />
<?php
if(!is_writeable($settings->_configFilePath)) {
	echo "<p>".getMLText("settings_notwritable")."</p>";
} else {
?>
  <input type="Submit" value="<?php printMLText("save");?>" />
<?php
}
?>


  <div class="contentHeading" onClick="ShowHide('siteID')" style="cursor:pointer">+ <?php printMLText("settings_Site");?></div>
  <div id="siteID" style="display:block">
    <table>
      <!--
        -- SETTINGS - SITE - DISPLAY
      -->
      <tr ><td><b> <?php printMLText("settings_Display");?></b></td> </tr>
      <tr title="<?php printMLText("settings_siteName_desc");?>">
        <td><?php printMLText("settings_siteName");?>:</td>
        <td><input name="siteName" value="<?php echo $settings->_siteName ?>"/></td>
      </tr>
      <tr title="<?php printMLText("settings_footNote_desc");?>">
        <td><?php printMLText("settings_footNote");?>:</td>
        <td><input name="footNote" value="<?php echo $settings->_footNote ?>" size="100"/></td>
      </tr>
      <tr title="<?php printMLText("settings_printDisclaimer_desc");?>">
        <td><?php printMLText("settings_printDisclaimer");?>:</td>
        <td><input name="printDisclaimer" type="checkbox" <?php if ($settings->_printDisclaimer) echo "checked" ?> /></td>
      </tr>
       <tr title="<?php printMLText("settings_language_desc");?>">
        <td><?php printMLText("settings_language");?>:</td>
        <td>
         <SELECT name="language">
            <?php
              $languages = getLanguages();
              foreach($languages as $language)
              {
                echo '<OPTION VALUE="' . $language . '" ';
                 if ($settings->_language==$language)
                   echo "SELECTED";
                echo '>' . $language . '</OPTION>';
             }
            ?>
          </SELECT>
        </td>
      </tr>
      <tr title="<?php printMLText("settings_theme_desc");?>">
        <td><?php printMLText("settings_theme");?>:</td>
        <td>
         <SELECT name="theme">
            <?php
              $themes = UI::getStyles();
              foreach($themes as $theme)
              {
                echo '<OPTION VALUE="' . $theme . '" ';
                 if ($settings->_theme==$theme)
                   echo "SELECTED";
                echo '>' . $theme . '</OPTION>';
             }
            ?>
          </SELECT>
        </td>
      </tr>

      <!--
        -- SETTINGS - SITE - EDITION
      -->
      <tr><td></td></tr><tr ><td><b> <?php printMLText("settings_Edition");?></b></td> </tr>
      <tr title="<?php printMLText("settings_strictFormCheck_desc");?>">
        <td><?php printMLText("settings_strictFormCheck");?>:</td>
        <td><input name="strictFormCheck" type="checkbox" <?php if ($settings->_strictFormCheck) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_viewOnlineFileTypes_desc");?>">
        <td><?php printMLText("settings_viewOnlineFileTypes");?>:</td>
        <td><input name="viewOnlineFileTypes" value="<?php echo $settings->getViewOnlineFileTypesToString() ?>" size="100" /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableConverting_desc");?>">
        <td><?php printMLText("settings_enableConverting");?>:</td>
        <td><input name="enableConverting" type="checkbox" <?php if ($settings->_enableConverting) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableEmail_desc");?>">
        <td><?php printMLText("settings_enableEmail");?>:</td>
        <td><input name="enableEmail" type="checkbox" <?php if ($settings->_enableEmail) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableUsersView_desc");?>">
        <td><?php printMLText("settings_enableUsersView");?>:</td>
        <td><input name="enableUsersView" type="checkbox" <?php if ($settings->_enableUsersView) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableFullSearch_desc");?>">
        <td><?php printMLText("settings_enableFullSearch");?>:</td>
        <td><input name="enableFullSearch" type="checkbox" <?php if ($settings->_enableFullSearch) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableFolderTree_desc");?>">
        <td><?php printMLText("settings_enableFolderTree");?>:</td>
        <td><input name="enableFolderTree" type="checkbox" <?php if ($settings->_enableFolderTree) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_expandFolderTree_desc");?>">
        <td><?php printMLText("settings_expandFolderTree");?>:</td>
        <td>
          <SELECT name="expandFolderTree">
            <OPTION VALUE="0" <?php if ($settings->_expandFolderTree==0) echo "SELECTED" ?> ><?php printMLText("settings_expandFolderTree_val0");?></OPTION>
            <OPTION VALUE="1" <?php if ($settings->_expandFolderTree==1) echo "SELECTED" ?> ><?php printMLText("settings_expandFolderTree_val1");?></OPTION>
            <OPTION VALUE="2" <?php if ($settings->_expandFolderTree==2) echo "SELECTED" ?> ><?php printMLText("settings_expandFolderTree_val2");?></OPTION>
          </SELECT>
      </tr>

      <!--
        -- SETTINGS - SITE - CALENDAR
      -->
     <tr><td></td></tr><tr ><td><b> <?php printMLText("settings_Calendar");?></b></td> </tr>
      <tr title="<?php printMLText("settings_enableCalendar_desc");?>">
        <td><?php printMLText("settings_enableCalendar");?>:</td>
        <td><input name="enableCalendar" type="checkbox" <?php if ($settings->_enableCalendar) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_calendarDefaultView_desc");?>">
        <td><?php printMLText("settings_calendarDefaultView");?>:</td>
        <td>
          <SELECT name="calendarDefaultView">
            <OPTION VALUE="w" <?php if ($settings->_calendarDefaultView=="w") echo "SELECTED" ?> ><?php printMLText("week_view");?></OPTION>
            <OPTION VALUE="m" <?php if ($settings->_calendarDefaultView=="m") echo "SELECTED" ?> ><?php printMLText("month_view");?></OPTION>
            <OPTION VALUE="y" <?php if ($settings->_calendarDefaultView=="y") echo "SELECTED" ?> ><?php printMLText("year_view");?></OPTION>
          </SELECT>
      </tr>
     <tr title="<?php printMLText("settings_firstDayOfWeek_desc");?>">
        <td><?php printMLText("settings_firstDayOfWeek");?>:</td>
        <td>
          <SELECT name="firstDayOfWeek">
            <OPTION VALUE="0" <?php if ($settings->_firstDayOfWeek=="0") echo "SELECTED" ?> ><?php printMLText("sunday");?></OPTION>
            <OPTION VALUE="1" <?php if ($settings->_firstDayOfWeek=="1") echo "SELECTED" ?> ><?php printMLText("monday");?></OPTION>
            <OPTION VALUE="2" <?php if ($settings->_firstDayOfWeek=="2") echo "SELECTED" ?> ><?php printMLText("tuesday");?></OPTION>
            <OPTION VALUE="3" <?php if ($settings->_firstDayOfWeek=="3") echo "SELECTED" ?> ><?php printMLText("wednesday");?></OPTION>
            <OPTION VALUE="4" <?php if ($settings->_firstDayOfWeek=="4") echo "SELECTED" ?> ><?php printMLText("thursday");?></OPTION>
            <OPTION VALUE="5" <?php if ($settings->_firstDayOfWeek=="5") echo "SELECTED" ?> ><?php printMLText("friday");?></OPTION>
            <OPTION VALUE="6" <?php if ($settings->_firstDayOfWeek=="6") echo "SELECTED" ?> ><?php printMLText("saturday");?></OPTION>
          </SELECT>
      </tr>
    </table>
  </div>
  <br>
  <div class="contentHeading" onClick="ShowHide('systemID')" style="cursor:pointer">+ <?php printMLText("settings_System");?></div>
  <div id="systemID" style="display:block">
    <table>
     <!--
        -- SETTINGS - SYSTEM - SERVER
      -->
      <tr ><td><b> <?php printMLText("settings_Server");?></b></td> </tr>
      <tr title="<?php printMLText("settings_rootDir_desc");?>">
        <td><?php printMLText("settings_rootDir");?>:</td>
        <td><input name="rootDir" value="<?php echo $settings->_rootDir ?>" size="100" /></td>
      </tr>
      <tr title="<?php printMLText("settings_httpRoot_desc");?>">
        <td><?php printMLText("settings_httpRoot");?>:</td>
        <td><input name="httpRoot" value="<?php echo $settings->_httpRoot ?>" size="100" /></td>
      </tr>
      <tr title="<?php printMLText("settings_contentDir_desc");?>">
        <td><?php printMLText("settings_contentDir");?>:</td>
        <td><input name="contentDir" value="<?php echo $settings->_contentDir ?>" size="100" /></td>
      </tr>
      <tr title="<?php printMLText("settings_stagingDir_desc");?>">
        <td><?php printMLText("settings_stagingDir");?>:</td>
        <td><input name="stagingDir" value="<?php echo $settings->_stagingDir ?>" size="100" /></td>
      </tr>
      <tr title="<?php printMLText("settings_luceneDir_desc");?>">
        <td><?php printMLText("settings_luceneDir");?>:</td>
        <td><input name="luceneDir" value="<?php echo $settings->_luceneDir ?>" size="100" /></td>
      </tr>
      <tr title="<?php printMLText("settings_logFileEnable_desc");?>">
        <td><?php printMLText("settings_logFileEnable");?>:</td>
        <td><input name="logFileEnable" type="checkbox" <?php if ($settings->_logFileEnable) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_logFileRotation_desc");?>">
        <td><?php printMLText("settings_logFileRotation");?>:</td>
        <td>
          <SELECT name="logFileRotation">
            <OPTION VALUE="h" <?php if ($settings->_logFileRotation=="h") echo "SELECTED" ?> ><?php printMLText("hourly");?></OPTION>
            <OPTION VALUE="d" <?php if ($settings->_logFileRotation=="d") echo "SELECTED" ?> ><?php printMLText("daily");?></OPTION>
            <OPTION VALUE="m" <?php if ($settings->_logFileRotation=="m") echo "SELECTED" ?> ><?php printMLText("monthly");?></OPTION>
          </SELECT>
      </tr>
      <tr title="<?php printMLText("settings_partitionSize_desc");?>">
        <td><?php printMLText("settings_partitionSize");?>:</td>
        <td><input name="partitionSize" value="<?php echo $settings->_partitionSize ?>" size="100" /></td>
      </tr>
      <!--
        -- SETTINGS - SYSTEM - AUTHENTICATION
      -->
      <tr ><td><b> <?php printMLText("settings_Authentication");?></b></td> </tr>
      <tr title="<?php printMLText("settings_enableGuestLogin_desc");?>">
        <td><?php printMLText("settings_enableGuestLogin");?>:</td>
        <td><input name="enableGuestLogin" type="checkbox" <?php if ($settings->_enableGuestLogin) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_restricted_desc");?>">
        <td><?php printMLText("settings_restricted");?>:</td>
        <td><input name="restricted" type="checkbox" <?php if ($settings->_restricted) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableUserImage_desc");?>">
        <td><?php printMLText("settings_enableUserImage");?>:</td>
        <td><input name="enableUserImage" type="checkbox" <?php if ($settings->_enableUserImage) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_disableSelfEdit_desc");?>">
        <td><?php printMLText("settings_disableSelfEdit");?>:</td>
        <td><input name="disableSelfEdit" type="checkbox" <?php if ($settings->_disableSelfEdit) echo "checked" ?> /></td>
      </tr>

      <!-- TODO Connectors -->

     <!--
        -- SETTINGS - SYSTEM - DATABASE
      -->
      <tr ><td><b> <?php printMLText("settings_Database");?></b></td> </tr>
      <tr title="<?php printMLText("settings_ADOdbPath_desc");?>">
        <td><?php printMLText("settings_ADOdbPath");?>:</td>
        <td><input name="ADOdbPath" value="<?php echo $settings->_ADOdbPath ?>" size="100" /></td>
      </tr>
      <tr title="<?php printMLText("settings_dbDriver_desc");?>">
        <td><?php printMLText("settings_dbDriver");?>:</td>
        <td><input name="dbDriver" value="<?php echo $settings->_dbDriver ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_dbHostname_desc");?>">
        <td><?php printMLText("settings_dbHostname");?>:</td>
        <td><input name="dbHostname" value="<?php echo $settings->_dbHostname ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_dbDatabase_desc");?>">
        <td><?php printMLText("settings_dbDatabase");?>:</td>
        <td><input name="dbDatabase" value="<?php echo $settings->_dbDatabase ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_dbUser_desc");?>">
        <td><?php printMLText("settings_dbUser");?>:</td>
        <td><input name="dbUser" value="<?php echo $settings->_dbUser ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_dbPass_desc");?>">
        <td><?php printMLText("settings_dbPass");?>:</td>
        <td><input name="dbPass" value="<?php echo $settings->_dbPass ?>" type="password" /></td>
      </tr>

     <!--
        -- SETTINGS - SYSTEM - SMTP
      -->
      <tr ><td><b> <?php printMLText("settings_SMTP");?></b></td> </tr>
      <tr title="<?php printMLText("settings_smtpServer_desc");?>">
        <td><?php printMLText("settings_smtpServer");?>:</td>
        <td><input name="smtpServer" value="<?php echo $settings->_smtpServer ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_smtpPort_desc");?>">
        <td><?php printMLText("settings_smtpPort");?>:</td>
        <td><input name="smtpPort" value="<?php echo $settings->_smtpPort ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_smtpSendFrom_desc");?>">
        <td><?php printMLText("settings_smtpSendFrom");?>:</td>
        <td><input name="smtpSendFrom" value="<?php echo $settings->_smtpSendFrom ?>" /></td>
      </tr>

    </table>
  </div>

  <br>
  <div class="contentHeading" onClick="ShowHide('advancedID')" style="cursor:pointer">+ <?php printMLText("settings_Advanced");?></div>
  <div id="advancedID" style="display:none">
    <table>
      <!--
        -- SETTINGS - ADVANCED - DISPLAY
      -->
      <tr ><td><b> <?php printMLText("settings_Display");?></b></td> </tr>
      <tr title="<?php printMLText("settings_siteDefaultPage_desc");?>">
        <td><?php printMLText("settings_siteDefaultPage");?>:</td>
        <td><input name="siteDefaultPage" value="<?php echo $settings->_siteDefaultPage ?>" size="100" /></td>
      </tr>
      <tr title="<?php printMLText("settings_rootFolderID_desc");?>">
        <td><?php printMLText("settings_rootFolderID");?>:</td>
        <td><input name="rootFolderID" value="<?php echo $settings->_rootFolderID ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_titleDisplayHack_desc");?>">
        <td><?php printMLText("settings_titleDisplayHack");?>:</td>
        <td><input name="titleDisplayHack" type="checkbox" <?php if ($settings->_titleDisplayHack) echo "checked" ?> /></td>
      </tr>

      <!--
        -- SETTINGS - ADVANCED - AUTHENTICATION
      -->
      <tr ><td><b> <?php printMLText("settings_Authentication");?></b></td> </tr>
      <tr title="<?php printMLText("settings_guestID_desc");?>">
        <td><?php printMLText("settings_guestID");?>:</td>
        <td><input name="guestID" value="<?php echo $settings->_guestID ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_adminIP_desc");?>">
        <td><?php printMLText("settings_adminIP");?>:</td>
        <td><input name="adminIP" value="<?php echo $settings->_adminIP ?>" /></td>
      </tr>

      <!--
        -- SETTINGS - ADVANCED - EDITION
      -->
      <tr ><td><b> <?php printMLText("settings_Edition");?></b></td> </tr>
      <tr title="<?php printMLText("settings_versioningFileName_desc");?>">
        <td><?php printMLText("settings_versioningFileName");?>:</td>
        <td><input name="versioningFileName" value="<?php echo $settings->_versioningFileName ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableAdminRevApp_desc");?>">
        <td><?php printMLText("settings_enableAdminRevApp");?>:</td>
        <td><input name="enableAdminRevApp" type="checkbox" <?php if ($settings->_enableAdminRevApp) echo "checked" ?> /></td>
      </tr>

      <!--
        -- SETTINGS - ADVANCED - SERVER
      -->
      <tr ><td><b> <?php printMLText("settings_Server");?></b></td> </tr>
      <tr title="<?php printMLText("settings_coreDir_desc");?>">
        <td><?php printMLText("settings_coreDir");?>:</td>
        <td><input name="coreDir" value="<?php echo $settings->_coreDir ?>" size="100" /></td>
      </tr>
      <tr title="<?php printMLText("settings_luceneClassDir_desc");?>">
        <td><?php printMLText("settings_luceneClassDir");?>:</td>
        <td><input name="luceneClassDir" value="<?php echo $settings->_luceneClassDir ?>" size="100" /></td>
      </tr>
      <tr title="<?php printMLText("settings_contentOffsetDir_desc");?>">
        <td><?php printMLText("settings_contentOffsetDir");?>:</td>
        <td><input name="contentOffsetDir" value="<?php echo $settings->_contentOffsetDir ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_maxDirID_desc");?>">
        <td><?php printMLText("settings_maxDirID");?>:</td>
        <td><input name="maxDirID" value="<?php echo $settings->_maxDirID ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_updateNotifyTime_desc");?>">
        <td><?php printMLText("settings_updateNotifyTime");?>:</td>
        <td><input name="updateNotifyTime" value="<?php echo $settings->_updateNotifyTime ?>" /></td>
      </tr>
      <tr title="<?php printMLText("settings_maxExecutionTime_desc");?>">
        <td><?php printMLText("settings_maxExecutionTime");?>:</td>
        <td><input name="maxExecutionTime" value="<?php echo $settings->_maxExecutionTime ?>" /></td>
      </tr>



    </table>
  </div>

	</form>


<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
