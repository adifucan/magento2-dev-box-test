<?php

function readValue($defaultValue = null)
{
    $input = rtrim(fgets(STDIN));
    return $input ?: $defaultValue;
}

$adminPath = 'admin';
echo 'Please enter backend admin path (', $adminPath , '): ';
$adminPath = readValue($adminPath);
$adminUserName = 'admin';
echo 'Please enter admin username (', $adminUserName , '): ';
$adminUserName = readValue($adminUserName);
$adminPassword = '123123q';
echo 'Please enter admin password (', $adminPassword , '): ';
$adminPassword = readValue($adminPassword);

$cmd = 'cd /var/www/magento2 && php bin/magento setup:install';
$cmd .= ' --base-url=http://localhost:1748/ --db-host=db --db-name=magento2';
$cmd .= ' --db-user=root --db-password=root --admin-firstname=Magento --admin-lastname=User';
$cmd .= ' --admin-email=user@example.com --admin-user=' . $adminUserName . ' --admin-password=' . $adminPassword;
$cmd .= ' --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1';
$cmd .= ' --backend-frontname=' . $adminPath;

echo shell_exec($cmd);