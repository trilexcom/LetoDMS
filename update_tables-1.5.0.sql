
CREATE TABLE `tblKeywordCategories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `owner` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=14 ;
# --------------------------------------------------------


CREATE TABLE `tblKeywords` (
  `id` int(11) NOT NULL auto_increment,
  `category` int(11) NOT NULL default '0',
  `keywords` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=12 ;


# --------------------------------------------------------
CREATE TABLE `tblDocumentLocks` (
	`document` INT NOT NULL ,
	`userID` INT NOT NULL ,
	PRIMARY KEY ( `document` )
) ENGINE = MYISAM ;

ALTER TABLE `tblDocuments` ADD `folderList` TEXT NOT NULL AFTER `folder` ;
ALTER TABLE `tblFolders` ADD INDEX ( `parent` ) ;

ALTER TABLE `tblDocumentContent` DROP `id` ;
ALTER TABLE `tblDocumentContent` ADD PRIMARY KEY (`document`, `version`) ;
ALTER TABLE `tblDocumentContent` CHANGE `version` `version` SMALLINT( 5 ) NOT
	NULL AUTO_INCREMENT ;
