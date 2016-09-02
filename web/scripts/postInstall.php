<?php

function readValue($defaultValue = null)
{
    $input = rtrim(fgets(STDIN));
    return $input ?: $defaultValue;
}

passthru('cd /var/www/magento2 && php bin/magento deploy:mode:set developer');

$response = true;
while ($response) {
    echo 'Do you want to pre-deploy all static assets (d) or just compile CSS out of LESS via Grunt (g) or none (N)? d/g/N: ';
    $deployStatic = readValue();
    $response = false;
    switch ($deployStatic) {
        case null:
        case 'N':
            return;
        case 'd':
            passthru('cd /var/www/magento2 && php bin/magento -f setup:static-content:deploy');
            break;
        case 'g':
            passthru('cd /var/www/magento2 && cp Gruntfile.js.sample Gruntfile.js && cp package.json.sample package.json');
            passthru('cd /var/www/magento2 && npm install && grunt refresh');
            break;
        default:
            $response = true;
    }
}

passthru('cd /var/www/magento2 && php bin/magento setup:di:compile');
shell_exec('echo "* * * * * root /usr/local/bin/php /var/www/magento2/bin/magento cron:run | grep -v \"Ran jobs by schedule\" >> /var/www/magento2/var/log/magento.cron.log" >> /etc/crontab');
shell_exec('echo "* * * * * root /usr/local/bin/php /var/www/magento2/update/cron.php >> /var/www/magento2/var/log/update.cron.log" >> /etc/crontab');
shell_exec('echo "* * * * * root /usr/local/bin/php /var/www/magento2/bin/magento setup:cron:run >> /var/www/magento2/var/log/setup.cron.log" >> /etc/crontab');
passthru('service cron restart');
