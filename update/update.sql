-- mysql -uroot -ppassword mydms < update-2.0.sql


-- --------------------------------------------------------

-- 
-- New table for document-related files
-- 

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
-- Not longer required by new filesystem structure
-- 

DROP TABLE `tblDirPath`;
DROP TABLE `tblPathList`;


