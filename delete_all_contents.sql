-- this delete all the db content except users 
-- mysql -uroot -ppassword mydms < delete_all_contents.sql


-- 
-- Table structure for table `tblACLs`
-- 

DROP TABLE `tblACLs`;

CREATE TABLE `tblACLs` (
  `id` int(11) NOT NULL auto_increment,
  `target` int(11) NOT NULL default '0',
  `targetType` tinyint(4) NOT NULL default '0',
  `userID` int(11) NOT NULL default '-1',
  `groupID` int(11) NOT NULL default '-1',
  `mode` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocumentApproveLog`
-- 

DROP TABLE `tblDocumentApproveLog`;

CREATE TABLE `tblDocumentApproveLog` (
  `approveLogID` int(11) NOT NULL auto_increment,
  `approveID` int(11) NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `comment` text NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `userID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`approveLogID`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocumentApprovers`
-- 

DROP TABLE `tblDocumentApprovers`;

CREATE TABLE `tblDocumentApprovers` (
  `approveID` int(11) NOT NULL auto_increment,
  `documentID` int(11) NOT NULL default '0',
  `version` smallint(5) unsigned NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  `required` int(11) NOT NULL default '0',
  PRIMARY KEY  (`approveID`),
  UNIQUE KEY `documentID` (`documentID`,`version`,`type`,`required`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocumentContent`
-- 

DROP TABLE `tblDocumentContent`;

CREATE TABLE `tblDocumentContent` (
  `document` int(11) NOT NULL default '0',
  `version` smallint(5) unsigned NOT NULL auto_increment,
  `comment` text,
  `date` int(12) default NULL,
  `createdBy` int(11) default NULL,
  `dir` varchar(255) NOT NULL default '',
  `orgFileName` varchar(150) NOT NULL default '',
  `fileType` varchar(10) NOT NULL default '',
  `mimeType` varchar(70) NOT NULL default '',
  PRIMARY KEY  (`document`,`version`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocumentLinks`
-- 

DROP TABLE `tblDocumentLinks`;

CREATE TABLE `tblDocumentLinks` (
  `id` int(11) NOT NULL auto_increment,
  `document` int(11) NOT NULL default '0',
  `target` int(11) NOT NULL default '0',
  `userID` int(11) NOT NULL default '0',
  `public` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ;


-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocumentFiles`
-- 

DROP TABLE `tblDocumentFiles`;

CREATE TABLE `tblDocumentFiles` (
  `id` int(11) NOT NULL auto_increment,
  `document` int(11) NOT NULL default '0',
  `userID` int(11) NOT NULL default '0',
  `comment` text,
  `name` varchar(150) default NULL,
  `date` int(12) default NULL,
  `dir` varchar(255) NOT NULL default '',
  `orgFileName` varchar(150) NOT NULL default '',
  `fileType` varchar(10) NOT NULL default '',
  `mimeType` varchar(70) NOT NULL default '',  
  PRIMARY KEY  (`id`)
) ;


-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocumentLocks`
-- 
DROP TABLE `tblDocumentLocks`;

CREATE TABLE `tblDocumentLocks` (
  `document` int(11) NOT NULL default '0',
  `userID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`document`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocumentReviewLog`
-- 
DROP TABLE `tblDocumentReviewLog`;

CREATE TABLE `tblDocumentReviewLog` (
  `reviewLogID` int(11) NOT NULL auto_increment,
  `reviewID` int(11) NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `comment` text NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `userID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`reviewLogID`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocumentReviewers`
-- 
DROP TABLE `tblDocumentReviewers`;

CREATE TABLE `tblDocumentReviewers` (
  `reviewID` int(11) NOT NULL auto_increment,
  `documentID` int(11) NOT NULL default '0',
  `version` smallint(5) unsigned NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  `required` int(11) NOT NULL default '0',
  PRIMARY KEY  (`reviewID`),
  UNIQUE KEY `documentID` (`documentID`,`version`,`type`,`required`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocumentStatus`
-- 
DROP TABLE `tblDocumentStatus`;

CREATE TABLE `tblDocumentStatus` (
  `statusID` int(11) NOT NULL auto_increment,
  `documentID` int(11) NOT NULL default '0',
  `version` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`statusID`),
  UNIQUE KEY `documentID` (`documentID`,`version`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocumentStatusLog`
-- 
DROP TABLE `tblDocumentStatusLog`;

CREATE TABLE `tblDocumentStatusLog` (
  `statusLogID` int(11) NOT NULL auto_increment,
  `statusID` int(11) NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `comment` text NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `userID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`statusLogID`),
  KEY `statusID` (`statusID`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblDocuments`
-- 
DROP TABLE `tblDocuments`;

CREATE TABLE `tblDocuments` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(150) default NULL,
  `comment` text,
  `date` int(12) default NULL,
  `expires` int(12) default NULL,
  `owner` int(11) default NULL,
  `folder` int(11) default NULL,
  `folderList` text NOT NULL,
  `inheritAccess` tinyint(1) NOT NULL default '1',
  `defaultAccess` tinyint(4) NOT NULL default '0',
  `locked` int(11) NOT NULL default '-1',
  `keywords` text NOT NULL,
  `sequence` double NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblFolders`
-- 
DROP TABLE `tblFolders`;

CREATE TABLE `tblFolders` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(70) default NULL,
  `parent` int(11) default NULL,
  `comment` text,
  `owner` int(11) default NULL,
  `inheritAccess` tinyint(1) NOT NULL default '1',
  `defaultAccess` tinyint(4) NOT NULL default '0',
  `sequence` double NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `parent` (`parent`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblKeywordCategories`
-- 
DROP TABLE `tblKeywordCategories`;

CREATE TABLE `tblKeywordCategories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `owner` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblKeywords`
-- 

DROP TABLE `tblKeywords`;

CREATE TABLE `tblKeywords` (
  `id` int(11) NOT NULL auto_increment,
  `category` int(11) NOT NULL default '0',
  `keywords` text NOT NULL,
  PRIMARY KEY  (`id`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblNotify`
-- 
DROP TABLE `tblNotify`;

CREATE TABLE `tblNotify` (
  `target` int(11) NOT NULL default '0',
  `targetType` int(11) NOT NULL default '0',
  `userID` int(11) NOT NULL default '-1',
  `groupID` int(11) NOT NULL default '-1',
  PRIMARY KEY  (`target`,`targetType`,`userID`,`groupID`)
) ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblSessions`
-- 
DROP TABLE `tblSessions`;

CREATE TABLE `tblSessions` (
  `id` varchar(50) NOT NULL default '',
  `userID` int(11) NOT NULL default '0',
  `lastAccess` int(11) NOT NULL default '0',
  `theme` varchar(30) NOT NULL default '',
  `language` varchar(30) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ;

-- --------------------------------------------------------

--
-- Initial content for database
--

INSERT INTO tblFolders VALUES (1, 'DMS', 0, 'DMS root', 1, 0, 2, 0);
