ALTER TABLE `tblNotify` DROP `id`;
ALTER TABLE `tblNotify` ADD PRIMARY KEY ( `target` , `targetType` , `userID` , `groupID` ) ;
