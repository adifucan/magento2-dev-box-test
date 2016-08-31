<?php
passthru('cd /var/www/magento2 && php bin/magento deploy:mode:set developer');
//passthru('cd /var/www/magento2 &&php bin/magento setup:static-content:deploy');
//passthru('cd /var/www/magento2 && php bin/magento setup:di:compile');
shell_exec('echo "* * * * * root /usr/local/bin/php /var/www/magento2/bin/magento cron:run | grep -v \"Ran jobs by schedule\" >> /var/www/magento2/var/log/magento.cron.log" >> /etc/crontab');
shell_exec('echo "* * * * * root /usr/local/bin/php /var/www/magento2/update/cron.php >> /var/www/magento2/var/log/update.cron.log" >> /etc/crontab');
shell_exec('echo "* * * * * root /usr/local/bin/php /var/www/magento2/bin/magento setup:cron:run >> /var/www/magento2/var/log/setup.cron.log" >> /etc/crontab');
passthru('service cron restart');
