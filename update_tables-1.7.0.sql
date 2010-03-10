--
-- dirID is the current target content subdirectory. The last file loaded
-- into MyDMS will be physically stored here. Is updated every time a new
-- file is uploaded.
--
-- dirPath is a essentially a foreign key from tblPathList, referencing the
-- parent directory path for dirID, relative to MyDMS's _contentDir.
--
CREATE TABLE `tblDirPath` (
  `dirID` int(11) NOT NULL auto_increment,
  `dirPath` varchar(255) NOT NULL,
  PRIMARY KEY (`dirPath`,`dirID`)
) ;

-- --------------------------------------------------------

CREATE TABLE `tblPathList` (
  `id` int(11) NOT NULL auto_increment,
  `parentPath` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ;

ALTER TABLE `tblDocumentContent` CHANGE `dir` `dir` VARCHAR( 255 ) NOT NULL ;
