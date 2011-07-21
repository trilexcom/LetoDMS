<?php
/**
 * Reading and writing the configuration from and to an xml file
 *
 * @category   DMS
 * @package    LetoDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class for reading and writing the configuration file
 *
 * @category   DMS
 * @package    LetoDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011 Uwe Steinmann
 * @version    Release: @package_version@
 */
class Settings { /* {{{ */
	// Config File Path
	var $_configFilePath = null;

	// Name of site
	var $_siteName = "letoDMS";
	// Message to display at the bottom of every page.
	var $_footNote = "letoDMS free document management \"system - www.letodms.com";
	// if true the disclaimer message the lang.inc files will be print on the bottom of the page
	var $_printDisclaimer = true;
	// Default page on login
	var $_siteDefaultPage = "";
	// ID of guest-user used when logged in as guest
	var $_guestID = 2;
	// ID of root-folder
	var $_rootFolderID = 1;
	// If you want anybody to login as guest, set the following line to true
	var $_enableGuestLogin = false;
	// Restricted access: only allow users to log in if they have an entry in
	// the local database (irrespective of successful authentication with LDAP).
	var $_restricted = true;
	// Strict form checking
	var $_strictFormCheck = false;
	// Path to where letoDMS is located
	var $_rootDir = null;
	// Path to LetoDMS_Core
	var $_coreDir = null;
	// Path to LetoDMS_Lucene
	var $_luceneClassDir = null;
	// The relative path in the URL, after the domain part.
	var $_httpRoot = "/letodms/";
	// Where the uploaded files are stored (best to choose a directory that
	// is not accessible through your web-server)
	var $_contentDir = null;
	// Where the partitions of an uploaded file by the jumploader is saved
	var $_stagingDir = null;
	// Where the lucene fulltext index is saved
	var $_luceneDir = null;
	// enable/disable lucene fulltext search
	var $_enableFullSearch = true;
	// contentOffsetDirTo
	var $_contentOffsetDir = "1048576";
	// Maximum number of sub-directories per parent directory
	var $_maxDirID = 32700;
	// default language (name of a subfolder in folder "languages")
	var $_language = "English";
	// users are notified about document-changes that took place within the last $_updateNotifyTime seconds
	var $_updateNotifyTime = 86400;
	// files with one of the following endings can be viewed online
	var $_viewOnlineFileTypes = array();
	// enable/disable converting of files
	var $_enableConverting = false;
	// default style
	var $_theme = "clean";
	// Workaround for page titles that go over more than 2 lines.
	var $_titleDisplayHack = true;
	// enable/disable automatic email notification
	var $_enableEmail = true;
	// enable/disable group and user view for all users
	var $_enableUsersView = true;
	// enable/disable listing administrator as reviewer/approver
	var $_enableAdminRevApp = false;
	// the name of the versioning info file created by the backup tool
	var $_versioningFileName = "versioning_info.txt";
	// enable/disable log system
	var $_logFileEnable = true;
	// the log file rotation
	var $_logFileRotation = "d";
	// size of partitions for file upload by jumploader
	var $_partitionSize = 2000000;
	// enable/disable users images
	var $_enableUserImage = false;
	// enable/disable calendar
	var $_enableCalendar = true;
	// calendar default view ("w" for week,"m" for month,"y" for year)
	var $_calendarDefaultView = "y";
	// first day of the week (0=sunday, 1=monday, 6=saturday)
	var $_firstDayOfWeek = 0;
	// enable/disable display of the folder tree
	var $_enableFolderTree = true;
	// expandFolderTree
	var $_expandFolderTree = 1;
	// enable/disable editing of users own profile
	var $_disableSelfEdit = false;
	// if enabled admin can login only by specified IP addres
	var $_adminIP = "";
	// Max Execution Time
	var $_maxExecutionTime = null;
	// Path to adodb
	var $_ADOdbPath = null;
	// DB-Driver used by adodb (see adodb-readme)
	var $_dbDriver = "mysql";
	// DB-Server
	var $_dbHostname = "localhost";
	// database where the tables for mydms are stored (optional - see adodb-readme)
	var $_dbDatabase = null;
	// username for database-access
	var $_dbUser = null;
	// password for database-access
	var $_dbPass = null;
	// SMTP : server
	var $_smtpServer = null;
	// SMTP : port
	var $_smtpPort = null;
	// SMTP : send from
	var $_smtpSendFrom = null;
	// LDAP
	var $_ldapHost = ""; // URIs are supported, e.g.: ldaps://ldap.host.com
	var $_ldapPort = 389; // Optional.
	var $_ldapBaseDN = "";
	var $_ldapAccountDomainName = "";
	var $_ldapType = 1; // 0 = ldap; 1 = AD


	/**
	 * Constructor
	 *
	 * @param string $configFilePath path to config file
	 */
	function Settings($configFilePath='') { /* {{{ */
		if($configFilePath=='') {
			$configFilePath = $this->searchConfigFilePath();

			// set $_configFilePath
			$this->_configFilePath = $configFilePath;
		} else {
			$this->_configFilePath = $configFilePath;
		}

		// Load config file
		if (!defined("LETODMS_INSTALL")) {
			if(!file_exists($configFilePath)) {
				echo "You does not seem to have a valid configuration. Run the <a href=\"../install/install.php\">install tool</a> first.";
				exit;
			}
		}
		$this->load($configFilePath);

		// files with one of the following endings will be converted with the
		// given commands for windows users
		$this->_convertFileTypes = array(".doc" => "cscript \"" . $this->_rootDir."op/convert_word.js\" {SOURCE} {TARGET}",
										 ".xls" => "cscript \"".$this->_rootDir."op/convert_excel.js\" {SOURCE} {TARGET}",
										 ".ppt" => "cscript \"".$this->_rootDir."op/convert_pp.js\" {SOURCE} {TARGET}");
		// uncomment the next line for linux users
		// $this->_convertFileTypes = array(".doc" => "mswordview -o {TARGET} {SOURCE}");

		if (!is_null($this->_smtpServer))
			ini_set("SMTP", $this->_smtpServer);
		if (!is_null($this->_smtpPort))
			ini_set("smtp_port", $this->_smtpPort);
		if (!is_null($this->_smtpSendFrom))
			ini_set("sendmail_from", $this->_smtpSendFrom);
		if (!is_null($this->_maxExecutionTime))
			ini_set("max_execution_time", $this->_maxExecutionTime);
	} /* }}} */

	/**
	 * Check if a variable has the string 'true', 'on', 'yes' or 'y'
	 * and returns true.
	 *
	 * @param string $var value
	 * @return true/false
	 */
	function boolVal($var) { /* {{{ */
		$var = strtolower(strval($var));
		switch ($var) {
			case 'true':
			case 'on':
			case 'yes':
			case 'y':
				$out = true;
				break;
			default:
				$out = false;
		}
		return $out;
	} /* }}} */

	/**
	 * set $_viewOnlineFileTypes
	 *
	 * @param string $stringValue string value
	 *
	 */
  function setViewOnlineFileTypesFromString($stringValue) { /* {{{ */
    $this->_viewOnlineFileTypes = explode(";", $stringValue);
  } /* }}} */

	/**
	 * get $_viewOnlineFileTypes in a string value
	 *
	 * @return string value
	 *
	 */
  function getViewOnlineFileTypesToString() { /* {{{ */
    return implode(";", $this->_viewOnlineFileTypes);
  } /* }}} */

	/**
	 * Load config file
	 *
	 * @param string $configFilePath config file path
	 *
	 * @return true/false
	 */
	function load($configFilePath) { /* {{{ */
		$xml = simplexml_load_string(file_get_contents($configFilePath));

		// XML Path: /configuration/site/display
		$node = $xml->xpath('/configuration/site/display');
		$tab = $node[0]->attributes();
		$this->_siteName = strval($tab["siteName"]);
		$this->_footNote = strval($tab["footNote"]);
		$this->_printDisclaimer = Settings::boolVal($tab["printDisclaimer"]);
		$this->_language = strval($tab["language"]);
		$this->_theme = strval($tab["theme"]);

		// XML Path: /configuration/site/edition
		$node = $xml->xpath('/configuration/site/edition');
		$tab = $node[0]->attributes();
		$this->_strictFormCheck = Settings::boolVal($tab["strictFormCheck"]);
		$this->setViewOnlineFileTypesFromString(strval($tab["viewOnlineFileTypes"]));
		$this->_enableConverting = Settings::boolVal($tab["enableConverting"]);
		$this->_enableEmail = Settings::boolVal($tab["enableEmail"]);
		$this->_enableUsersView = Settings::boolVal($tab["enableUsersView"]);
		$this->_enableFolderTree = Settings::boolVal($tab["enableFolderTree"]);
		$this->_enableFullSearch = Settings::boolVal($tab["enableFullSearch"]);
		$this->_expandFolderTree = intval($tab["expandFolderTree"]);

		// XML Path: /configuration/site/calendar
		$node = $xml->xpath('/configuration/site/calendar');
		$tab = $node[0]->attributes();
		$this->_enableCalendar = Settings::boolVal($tab["enableCalendar"]);
		$this->_calendarDefaultView = strval($tab["calendarDefaultView"]);
		$this->_firstDayOfWeek = intval($tab["firstDayOfWeek"]);

		// XML Path: /configuration/system/server
		$node = $xml->xpath('/configuration/system/server');
		$tab = $node[0]->attributes();
		$this->_rootDir = strval($tab["rootDir"]);
		$this->_httpRoot = strval($tab["httpRoot"]);
		$this->_contentDir = strval($tab["contentDir"]);
		$this->_stagingDir = strval($tab["stagingDir"]);
		$this->_luceneDir = strval($tab["luceneDir"]);
		$this->_logFileEnable = Settings::boolVal($tab["logFileEnable"]);
		$this->_logFileRotation = strval($tab["logFileRotation"]);
		$this->_partitionSize = strval($tab["partitionSize"]);

		// XML Path: /configuration/system/authentication
		$node = $xml->xpath('/configuration/system/authentication');
		$tab = $node[0]->attributes();
		$this->_enableGuestLogin = Settings::boolVal($tab["enableGuestLogin"]);
		$this->_restricted = Settings::boolVal($tab["restricted"]);
		$this->_enableUserImage = Settings::boolVal($tab["enableUserImage"]);
		$this->_disableSelfEdit = Settings::boolVal($tab["disableSelfEdit"]);

		// XML Path: /configuration/system/authentication/connectors/connector
		// attributs mandatories : type enable
		$node = $xml->xpath('/configuration/system/authentication/connectors/connector');
		$this->_usersConnectors = array();
		foreach($node as $connectorNode)
		{
			$typeConn = strval($connectorNode["type"]);
			$params = array();
			foreach($connectorNode->attributes() as $attKey => $attValue)
			{
				if ($attKey=="enable")
					$params[$attKey] = Settings::boolVal($attValue);
				else
					$params[$attKey] = strval($attValue);
			}

			$this->_usersConnectors[$typeConn] = $params;

			// manage old settings parameters
			if ($params['enable'] && ($typeConn == "ldap"))
			{
				$this->_ldapHost = strVal($connectorNode["host"]);
				$this->_ldapPort = intVal($connectorNode["port"]);
				$this->_ldapBaseDN = strVal($connectorNode["baseDN"]);
				$this->_ldapType = 0;
			}
			else if ($params['enable'] && ($typeConn == "AD"))
			{
				$this->_ldapHost = strVal($connectorNode["host"]);
				$this->_ldapPort = intVal($connectorNode["port"]);
				$this->_ldapBaseDN = strVal($connectorNode["baseDN"]);
				$this->_ldapType = 1;
				$this->_ldapAccountDomainName = strVal($connectorNode["accountDomainName"]);
			}
		}

		// XML Path: /configuration/system/database
		$node = $xml->xpath('/configuration/system/database');
		$tab = $node[0]->attributes();
		$this->_ADOdbPath = strval($tab["ADOdbPath"]);
		$this->_dbDriver = strval($tab["dbDriver"]);
		$this->_dbHostname = strval($tab["dbHostname"]);
		$this->_dbDatabase = strval($tab["dbDatabase"]);
		$this->_dbUser = strval($tab["dbUser"]);
		$this->_dbPass = strval($tab["dbPass"]);

		// XML Path: /configuration/system/smtp
		$node = $xml->xpath('/configuration/system/smtp');
		if (!empty($node))
		{
			$tab = $node[0]->attributes();
			// smtpServer
			if (isset($tab["smtpServer"]))
				$this->_smtpServer = strval($tab["smtpServer"]);
			else
				$this->_smtpServer = ini_get("SMTP");
			// smtpPort
			if (isset($tab["smtpPort"]))
				$this->_smtpPort = strval($tab["smtpPort"]);
			else
				$this->_smtpPort = ini_get("smtp_port");
			// smtpSendFrom
			if (isset($tab["smtpSendFrom"]))
				$this->_smtpSendFrom = strval($tab["smtpSendFrom"]);
			else
				$this->_smtpSendFrom = ini_get("sendmail_from");
		}

		// XML Path: /configuration/advanced/display
		$node = $xml->xpath('/configuration/advanced/display');
		$tab = $node[0]->attributes();
		$this->_siteDefaultPage = strval($tab["siteDefaultPage"]);
		$this->_rootFolderID = intval($tab["rootFolderID"]);
		$this->_titleDisplayHack = Settings::boolval($tab["titleDisplayHack"]);

		// XML Path: /configuration/advanced/authentication
		$node = $xml->xpath('/configuration/advanced/authentication');
		$tab = $node[0]->attributes();
		$this->_guestID = intval($tab["guestID"]);
		$this->_adminIP = strval($tab["adminIP"]);

		// XML Path: /configuration/advanced/edition
		$node = $xml->xpath('/configuration/advanced/edition');
		$tab = $node[0]->attributes();
		$this->_enableAdminRevApp = Settings::boolval($tab["enableAdminRevApp"]);
		$this->_versioningFileName = strval($tab["versioningFileName"]);

		// XML Path: /configuration/advanced/server
		$node = $xml->xpath('/configuration/advanced/server');
		$tab = $node[0]->attributes();
		$this->_coreDir = strval($tab["coreDir"]);
		$this->_luceneClassDir = strval($tab["luceneClassDir"]);
		$this->_contentOffsetDir = strval($tab["contentOffsetDir"]);
		$this->_maxDirID = intval($tab["maxDirID"]);
		$this->_updateNotifyTime = intval($tab["updateNotifyTime"]);
		if (isset($tab["maxExecutionTime"]))
			$this->_maxExecutionTime = intval($tab["maxExecutionTime"]);
		else
			$this->_maxExecutionTime = ini_get("max_execution_time");
	} /* }}} */

	 /**
	 * set value for one attribut.
	 * Create attribut if not exists.
	 *
	 * @param SimpleXMLElement $node node
	 * @param string $attributName attribut name
	 * @param string $attributValue attribut value
	 *
	 * @return true/false
	 */
  function setXMLAttributValue($node, $attributName, $attributValue) { /* {{{ */
    if (is_bool($attributValue)) {
      if ($attributValue)
        $attributValue = "true";
      else
        $attributValue = "false";
    }

    if (isset($node[$attributName])) {
      $node[$attributName] = $attributValue;
    } else {
      $node->addAttribute($attributName, $attributValue);
    }
  } /* }}} */

	/**
	 * Get XML node, create it if not exists
	 *
	 * @param SimpleXMLElement $rootNode root node
	 * @param string $parentNodeName parent node name
	 * @param string $name name of node
	 *
	 * @return SimpleXMLElement
	 */
	function getXMLNode($rootNode, $parentNodeName, $name) { /* {{{ */
		$node = $rootNode->xpath($parentNodeName . '/' . $name);

		if (empty($node)) {
			$node = $xml->xpath($parentNodeName);
			$node = $node[0]->addChild($name);
		} else {
			$node = $node[0];
		}

		return $node;
	} /* }}} */

	/**
	 * Save config file
	 *
	 * @param string $configFilePath config file path
	 *
	 * @return true/false
	 */
	function save($configFilePath=NULL) { /* {{{ */
    if (is_null($configFilePath))
      $configFilePath = $this->_configFilePath;

    // Load
    $xml = simplexml_load_string(file_get_contents($configFilePath));
    $this->getXMLNode($xml, '/', 'configuration');

    // XML Path: /configuration/site/display
    $this->getXMLNode($xml, '/configuration', 'site');
    $node = $this->getXMLNode($xml, '/configuration/site', 'display');
    $this->setXMLAttributValue($node, "siteName", $this->_siteName);
    $this->setXMLAttributValue($node, "footNote", $this->_footNote);
    $this->setXMLAttributValue($node, "printDisclaimer", $this->_printDisclaimer);
    $this->setXMLAttributValue($node, "language", $this->_language);
    $this->setXMLAttributValue($node, "theme", $this->_theme);

    // XML Path: /configuration/site/edition
    $node = $this->getXMLNode($xml, '/configuration/site', 'edition');
    $this->setXMLAttributValue($node, "strictFormCheck", $this->_strictFormCheck);
    $this->setXMLAttributValue($node, "viewOnlineFileTypes", $this->getViewOnlineFileTypesToString());
    $this->setXMLAttributValue($node, "enableConverting", $this->_enableConverting);
    $this->setXMLAttributValue($node, "enableEmail", $this->_enableEmail);
    $this->setXMLAttributValue($node, "enableUsersView", $this->_enableUsersView);
    $this->setXMLAttributValue($node, "enableFolderTree", $this->_enableFolderTree);
    $this->setXMLAttributValue($node, "enableFullSearch", $this->_enableFullSearch);
    $this->setXMLAttributValue($node, "expandFolderTree", $this->_expandFolderTree);

    // XML Path: /configuration/site/calendar
    $node = $this->getXMLNode($xml, '/configuration/site', 'calendar');
    $this->setXMLAttributValue($node, "enableCalendar", $this->_enableCalendar);
    $this->setXMLAttributValue($node, "calendarDefaultView", $this->_calendarDefaultView);
    $this->setXMLAttributValue($node, "firstDayOfWeek", $this->_firstDayOfWeek);

    // XML Path: /configuration/system/server
    $this->getXMLNode($xml, '/configuration', 'system');
    $node = $this->getXMLNode($xml, '/configuration/system', 'server');
    $this->setXMLAttributValue($node, "rootDir", $this->_rootDir);
    $this->setXMLAttributValue($node, "httpRoot", $this->_httpRoot);
    $this->setXMLAttributValue($node, "contentDir", $this->_contentDir);
    $this->setXMLAttributValue($node, "stagingDir", $this->_stagingDir);
    $this->setXMLAttributValue($node, "luceneDir", $this->_luceneDir);
    $this->setXMLAttributValue($node, "logFileEnable", $this->_logFileEnable);
    $this->setXMLAttributValue($node, "logFileRotation", $this->_logFileRotation);
    $this->setXMLAttributValue($node, "partitionSize", $this->_partitionSize);

    // XML Path: /configuration/system/authentication
    $node = $this->getXMLNode($xml, '/configuration/system', 'authentication');
    $this->setXMLAttributValue($node, "enableGuestLogin", $this->_enableGuestLogin);
    $this->setXMLAttributValue($node, "restricted", $this->_restricted);
    $this->setXMLAttributValue($node, "enableUserImage", $this->_enableUserImage);
    $this->setXMLAttributValue($node, "disableSelfEdit", $this->_disableSelfEdit);

    // XML Path: /configuration/system/authentication/connectors
    foreach($this->_usersConnectors as $keyConn => $paramConn)
    {
      // search XML node
      $node = $xml->xpath('/configuration/system/authentication/connectors/connector[@type="'. $keyConn .'"]');

      // Just the first is configured
      if (isset($node))
      {
        if (count($node)>0)
        {
          $node = $node[0];
        }
        else
        {
          $nodeParent = $xml->xpath('/configuration/system/authentication/connectors');
          $node = $nodeParent[0]->addChild("connector");
        }

        foreach($paramConn as $key => $value)
        {
          $this->setXMLAttributValue($node, $key, $value);
        }

      } // isset($node)

    } // foreach

    // XML Path: /configuration/system/authentication/connectors
    // manage old settings parameters
    if (isset($this->_ldapHost) && (strlen($this->_ldapHost)>0))
    {
      if ($this->_ldapType == 1)
      {
        $node = $xml->xpath('/configuration/system/authentication/connectors/connector[@type="AD"]');
        $node = $node[0];
        $this->setXMLAttributValue($node, "accountDomainName", $this->_ldapAccountDomainName);
      }
      else
      {
        $node = $xml->xpath('/configuration/system/authentication/connectors/connector[@type="ldap"]');
        $node = $node[0];
      }

      $this->setXMLAttributValue($node, "host", $this->_ldapHost);
      $this->setXMLAttributValue($node, "port", $this->_ldapPort);
      $this->setXMLAttributValue($node, "baseDN", $this->_ldapBaseDN);
    }

    // XML Path: /configuration/system/database
    $node = $this->getXMLNode($xml, '/configuration/system', 'database');
    $this->setXMLAttributValue($node, "ADOdbPath", $this->_ADOdbPath);
    $this->setXMLAttributValue($node, "dbDriver", $this->_dbDriver);
    $this->setXMLAttributValue($node, "dbHostname", $this->_dbHostname);
    $this->setXMLAttributValue($node, "dbDatabase", $this->_dbDatabase);
    $this->setXMLAttributValue($node, "dbUser", $this->_dbUser);
    $this->setXMLAttributValue($node, "dbPass", $this->_dbPass);

    // XML Path: /configuration/system/smtp
    $node = $this->getXMLNode($xml, '/configuration/system', 'smtp');
    $this->setXMLAttributValue($node, "smtpServer", $this->_smtpServer);
    $this->setXMLAttributValue($node, "smtpPort", $this->_smtpPort);
    $this->setXMLAttributValue($node, "smtpSendFrom", $this->_smtpSendFrom);

    // XML Path: /configuration/advanced/display
    $this->getXMLNode($xml, '/configuration', 'advanced');
    $node = $this->getXMLNode($xml, '/configuration/advanced', 'display');
    $this->setXMLAttributValue($node, "siteDefaultPage", $this->_siteDefaultPage);
    $this->setXMLAttributValue($node, "rootFolderID", $this->_rootFolderID);
    $this->setXMLAttributValue($node, "titleDisplayHack", $this->_titleDisplayHack);

    // XML Path: /configuration/advanced/authentication
    $node = $this->getXMLNode($xml, '/configuration/advanced', 'authentication');
    $this->setXMLAttributValue($node, "guestID", $this->_guestID);
    $this->setXMLAttributValue($node, "adminIP", $this->_adminIP);

    // XML Path: /configuration/advanced/edition
    $node = $this->getXMLNode($xml, '/configuration/advanced', 'edition');
    $this->setXMLAttributValue($node, "enableAdminRevApp", $this->_enableAdminRevApp);
    $this->setXMLAttributValue($node, "versioningFileName", $this->_versioningFileName);

    // XML Path: /configuration/advanced/server
    $node = $this->getXMLNode($xml, '/configuration/advanced', 'server');
    $this->setXMLAttributValue($node, "coreDir", $this->_coreDir);
    $this->setXMLAttributValue($node, "luceneClassDir", $this->_luceneClassDir);
    $this->setXMLAttributValue($node, "contentOffsetDir", $this->_contentOffsetDir);
    $this->setXMLAttributValue($node, "maxDirID", $this->_maxDirID);
    $this->setXMLAttributValue($node, "updateNotifyTime", $this->_updateNotifyTime);
    $this->setXMLAttributValue($node, "maxExecutionTime", $this->_maxExecutionTime);


    // Save
    return $xml->asXML($configFilePath);
  } /* }}} */

	/**
	 * search and return Config File Path
	 * @return NULL|string Config File Path
	 */
	function searchConfigFilePath() { /* {{{ */
		$configFilePath = null;

		// Search config file
		$_tmp = dirname($_SERVER['SCRIPT_FILENAME']);
		if(is_link($_tmp))
		{
			$_arr = preg_split('/\//', $_tmp);
			array_pop($_arr);

			$configFilePath = implode('/', $_arr)."/conf/settings.xml";
		}
		else
		{
			if (file_exists("../conf/settings.xml"))
				$configFilePath = "../conf/settings.xml";
			else if (file_exists("conf/settings.xml"))
				$configFilePath = "conf/settings.xml";
			else
			{
				echo "Configuration file not found <br>";
				echo "Please create conf/settings.xml file. You can use installation procedure or 'conf/settings.xml.template' file to help you";
				exit;
			}
		}

		return $configFilePath;
	} /* }}} */

	/**
	 * Returns absolute path for configuration files respecting links
	 *
	 * @return NULL|string config directory
	 */
	function getConfigDir() { /* {{{ */
		$_tmp = dirname($_SERVER['SCRIPT_FILENAME']);
		$_arr = preg_split('/\//', $_tmp);
		array_pop($_arr);

		$configDir = implode('/', $_arr)."/conf/";
		return $configDir;
	} /* }}} */

	/**
	 * get URL from current page
	 *
	 * @return string
	 */
	function curPageURL() { /* {{{ */
	  $pageURL = 'http';

	  if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
	    $pageURL .= "s";
	  }

	  $pageURL .= "://";

	  if ($_SERVER["SERVER_PORT"] != "80") {
	    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	  } else {
	    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	  }

	  return $pageURL;
	} /* }}} */


	/**
	 * Searches a file in the include_path
	 *
	 * @param string $file name of file to search
	 * @return string path where file was found
	 */
	function findInIncPath($file) { /* {{{ */
		$incarr = explode(':', ini_get('include_path'));
		$found = '';
		foreach($incarr as $path) {
			if(file_exists($path.'/'.$file)) {
				$found = $path;
			}
		}
		return $found;
	} /* }}} */

	/**
	 * Check parameters
	 *
	 *  @return array
	 */
	function check() { /* {{{ */
		// suggestion rootdir
		if (file_exists("../inc/inc.Settings.php"))
			$rootDir = realpath ("../inc/inc.Settings.php");
		else if (file_exists("inc/inc.Settings.php"))
			$rootDir = realpath ("inc/inc.Settings.php");
		else {
			echo "Fatal error : inc/inc.Settings.php not found";
			exit;
		}
		$rootDir = str_replace ("\\", "/" , $rootDir);
		$rootDir = str_replace ("inc/inc.Settings.php", "" , $rootDir);

		// result
		$result = array();

		// $this->_rootDir
		if (!file_exists($this->_rootDir ."inc/inc.Settings.php")) {
			$result["rootDir"] = array(
				"status" => "notfound",
				"currentvalue" => $this->_rootDir,
				"suggestionvalue" => $rootDir
				);
		}

		// TODO
		// $this->_coreDir
		if($this->_coreDir) {
			if (!file_exists($this->_coreDir ."Core.php")) {
				$result["coreDir"] = array(
					"status" => "notfound",
					"currentvalue" => $this->_coreDir,
					"suggestionvalue" => $rootDir
					);
			}
		} else {
			$found = Settings::findInIncPath('LetoDMS/Core.php');
			if(!$found) {
				$result["coreDir"] = array(
					"status" => "notfound",
					"currentvalue" => $this->_coreDir,
					"suggestionvalue" => $rootDir
					);
			}
		}

		// $this->_httpRoot
		$tmp = $this->curPageURL();
		$tmp = str_replace ("install.php", "" , $tmp);
		if (strpos($tmp, $this->_httpRoot) === false) {
			$result["httpRoot"] = array(
				"status" => "notfound",
				"currentvalue" => $this->_httpRoot,
				"suggestionvalue" => $tmp
				);
		}

		// $this->_contentDir
		if (!file_exists($this->_contentDir)) {
			if (file_exists($rootDir.'data/')) {
					$result["contentDir"] = array(
						"status" => "notfound",
						"currentvalue" => $this->_contentDir,
						"suggestionvalue" => $rootDir . 'data/'
					);
			} else {
					$result["contentDir"] = array(
						"status" => "notfound",
						"currentvalue" => $this->_contentDir,
						"suggestion" => "createdirectory"
					);
			}
		} else {
			$errorMsgPerms = null;

			// perms
			if (!mkdir($this->_contentDir.'/_CHECK_TEST_')) {
				$errorMsgPerms .= "Create folder - ";
			}

			if (is_bool(file_put_contents($this->_contentDir.'/_CHECK_TEST_/_CHECK_TEST_', ""))) {
				$errorMsgPerms .= "Create file - ";
			}

			if (!unlink ($this->_contentDir.'/_CHECK_TEST_/_CHECK_TEST_')) {
				$errorMsgPerms .= "Delete file - ";
			}

			if (!rmdir($this->_contentDir.'/_CHECK_TEST_')) {
				$errorMsgPerms .= "Delete folder";
			}

			if (!is_null($errorMsgPerms)) {
				$result["contentDir"] = array(
					"status" => "perms",
					"currentvalue" => $this->_contentDir,
					"systemerror" => $errorMsgPerms
				);
			}
		}

		// $this->_stagingDir
		if (!file_exists($this->_stagingDir)) {
			$result["stagingDir"] = array(
				"status" => "notfound",
				"currentvalue" => $this->_stagingDir,
				"suggestionvalue" => $this->_contentDir . 'staging/'
			);
		}

		// $this->_luceneDir
		if (!file_exists($this->_luceneDir)) {
			$result["luceneDir"] = array(
				"status" => "notfound",
				"currentvalue" => $this->_luceneDir,
				"suggestionvalue" => $this->_contentDir . 'lucene/'
			);
		}

		// $this->_ADOdbPath
		$bCheckDB = true;
		if($this->_ADOdbPath) {
			if (!file_exists($this->_ADOdbPath."adodb/adodb.inc.php")) {
				$bCheckDB = false;
				if (file_exists($rootDir."adodb/adodb.inc.php")) {
					$result["ADOdbPath"] = array(
						"status" => "notfound",
						"currentvalue" => $this->_ADOdbPath,
						"suggestionvalue" => $rootDir
						);
				} else {
					$result["ADOdbPath"] = array(
						"status" => "notfound",
						"currentvalue" => $this->_ADOdbPath,
						"suggestion" => "installADOdb"
						);
				}
			}
		} else {
			$found = Settings::findInIncPath('adodb/adodb.inc.php');
			if(!$found) {
				$bCheckDB = false;
				$result["ADOdbPath"] = array(
					"status" => "notfound",
					"currentvalue" => $this->_ADOdbPath,
					"suggestion" => "installADOdb"
					);
			}
		}

		// database
		if ($bCheckDB) {
			try {
				include $this->_ADOdbPath."adodb/adodb.inc.php";

				$connTmp = ADONewConnection($this->_dbDriver);
				if (!$connTmp) {
					$result["dbDriver"] = array(
						"status" => "notfound",
						"currentvalue" => $this->_dbDriver,
						"suggestionvalue" => "mysql"
					);
				} else {
					$connTmp->Connect($this->_dbHostname, $this->_dbUser, $this->_dbPass, $this->_dbDatabase);
					if (!$connTmp->IsConnected())
					{
						$result["dbDatabase"] = array(
							"status" => "error",
							"currentvalue" => '[host, user, database] -> [' . $this->_dbHostname . ',' . $this->_dbUser . ',' . $this->_dbDatabase .']',
							"systemerror" => $connTmp->ErrorMsg()
							);
					}

					$connTmp->Disconnect();
				}
			} catch(Exception $e) {
				$result["dbDatabase"] = array(
					"status" => "error",
					"currentvalue" => '[host, user, database] -> [' . $settings->_dbHostname . ',' . $settings->_dbUser . ',' . $settings->_dbDatabase .']',
					"systemerror" => $e->getMessage()
				);
			}
		}

		return $result;
	} /* }}} */

	/**
	 * Check system configuration
	 *
	 * @return array
	 *
	 */
	function checkSystem() { /* {{{ */
		// result
		$result = array();

		// Check Apache configuration
		if (function_exists("apache_get_version")) {
			$loaded_extensions = apache_get_modules();
			if (!in_array("mod_rewrite", $loaded_extensions)) {
				$result["apache_mod_rewrite"] = array(
					"status" => "notfound",
					"suggestion" => "activate_module"
				);
			}
		}

		// Check PHP configuration
		$loaded_extensions = get_loaded_extensions();
		// gd2
		if (!in_array("gd", $loaded_extensions)) {
			$result["php_gd2"] = array(
				"status" => "notfound",
				"suggestion" => "activate_php_extension"
			);
		}

		// mbstring
		if (!in_array("mbstring", $loaded_extensions)) {
			$result["php_mbstring"] = array(
				"status" => "notfound",
				"suggestion" => "activate_php_extension"
			);
		}

		// database
		if (!in_array($this->_dbDriver, $loaded_extensions)) {
			$result["php_dbDriver"] = array(
				"status" => "notfound",
				"currentvalue" => $this->_dbDriver,
				"suggestion" => "activate_php_extension"
			);
		}


		return $result;
	} /* }}} */

} /* }}} */

?>
