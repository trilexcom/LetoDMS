-- mysql -uyouruser -pyourpassword yourdb < update.sql
-- this script must be executed when updating form a version < 2.0

-- --------------------------------------------------------

-- 
-- Table structure for events (calendar)
-- 

CREATE TABLE `tblEvents` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(150) default NULL,
  `comment` text,
  `start` int(12) default NULL,
  `stop` int(12) default NULL,
  `date` int(12) default NULL,
  `userID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ;
