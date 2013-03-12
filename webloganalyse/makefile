DIR=/var/www/weblog
install:
	mkdir $(DIR)
	cp filterausdruck $(DIR) && chmod 666 $(DIR)/filterausdruck
	cp index.html $(DIR)
	cp kommentierte_konfiguration.conf $(DIR)
	cp suchbegriffe $(DIR) && chmod 666 $(DIR)/suchbegriffe
	cp weblogauswertung.php $(DIR)
	cp wla_weblogauswertung.conf $(DIR) && chmod 666 $(DIR)/wla_weblogauswertung.conf
	./htaccess_erzeugen $(DIR)/
	cp weblog-von-ipcop-holen /etc/cron.d/
	chown -R www-data:www-data /var/www/weblog/
	/usr/bin/scp -P 222 root@ipcop:/var/log/squid/access.log $(DIR)


uninstall: 
	rm -rf /var/www/weblog
	rm /etc/cron.d/weblog-von-ipcop-holen


