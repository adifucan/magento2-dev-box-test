cd /var/www/magento2
php bin/magento deploy:mode:set developer
php bin/magento setup:static-content:deploy
#php bin/magento setup:di:compile
echo "* * * * * root /usr/local/bin/php /var/www/magento2/bin/magento cron:run  | grep -v \"Ran jobs by schedule\" >> /var/www/magento2/var/log/magento.cron.log" >> /etc/crontab
echo "* * * * * root /usr/local/bin/php /var/www/magento2/update/cron.php >> /var/www/magento2/var/log/update.cron.log" >> /etc/crontab
echo "* * * * * root /usr/local/bin/php /var/www/magento2/bin/magento setup:cron:run >> /var/www/magento2/var/log/setup.cron.log" >> /etc/crontab
service cron restart
