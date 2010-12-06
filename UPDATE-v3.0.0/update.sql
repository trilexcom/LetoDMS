ALTER TABLE tblUsers ADD COLUMN role smallint(1) NOT NULL default '0' AFTER isAdmin;
UPDATE tblUsers SET role = 1 WHERE isAdmin = 1;
UPDATE tblUsers SET role = 2 WHERE id = 2;
ALTER TABLE tblUsers DROP COLUMN isAdmin;
