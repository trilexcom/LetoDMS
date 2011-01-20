VERSION=3.0.0-RC1
SRC=CHANGELOG* inc conf index.php languages op out README reset_db.sql create_tables.sql create_tables-innodb.sql drop-tables-innodb.sql delete_all_contents.sql styles TODO UPDATE-* LICENSE Makefile package.xml

dist:
	mkdir -p tmp/letoDMS-$(VERSION)
	cp -a $(SRC) tmp/letoDMS-$(VERSION)
	(cd tmp; tar --exclude=.svn -czvf ../LetoDMS-$(VERSION).tar.gz letoDMS-$(VERSION))
	rm -rf tmp

pear:
	pear package

webdav:
	mkdir -p tmp/letoDMS-webdav-$(VERSION)
	cp webdav/* tmp/letoDMS-webdav-$(VERSION)
	(cd tmp; tar --exclude=.svn -czvf ../LetoDMS-webdav-$(VERSION).tar.gz letoDMS-webdav-$(VERSION))
	rm -rf tmp

doc:
	phpdoc -d inc -t html

.PHONY: webdav