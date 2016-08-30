cd /var/www/magento2
php bin/magento deploy:mode:set developer
php bin/magento setup:static-content:deploy
#php bin/magento setup:di:compile
echo "* * * * * root /usr/local/bin/php /var/www/magento2/bin/magento cron:run" >> /etc/crontab
echo "* * * * * root /usr/local/bin/php /var/www/magento2/update/cron.php" >> /etc/crontab
echo "* * * * * root /usr/local/bin/php /var/www/magento2/bin/magento setup:cron:run" >> /etc/crontab
