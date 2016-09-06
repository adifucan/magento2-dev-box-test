<?php

$options = getopt(
    '',
    [
        'install-rabbitmq::',
        'rabbit-host::',
        'rabbit-port::'
    ]
);

function readValue($defaultValue = null)
{
    $input = rtrim(fgets(STDIN));
    return $input ?: $defaultValue;
}

function readBooleanValue($defaultValue = null)
{
    $input = trim(fgets(STDIN));

    return strlen($input) || !is_bool($defaultValue) ? (strtolower($input) === 'y' ? true : false) : $defaultValue;
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
echo 'Do you want to install Sample Data? y/N: ';
$installSampleData = readBooleanValue();

$cmd = 'cd /var/www/magento2 && php bin/magento setup:install';
$cmd .= ' --base-url=http://localhost:1748/ --db-host=db --db-name=magento2';
$cmd .= ' --db-user=root --db-password=root --admin-firstname=Magento --admin-lastname=User';
$cmd .= ' --admin-email=user@example.com --admin-user=' . $adminUserName . ' --admin-password=' . $adminPassword;
$cmd .= ' --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1';
$cmd .= ' --backend-frontname=' . $adminPath;
if ($options['install-rabbitmq'] === 'y' && !empty($options['rabbit-host']) && !empty($options['rabbit-port'])) {
    $cmd .= ' --amqp-virtualhost=/ --amqp-host=' . $options['rabbit-host']
        . ' --amqp-port=' . $options['rabbit-port'] . ' --amqp-user=guest --amqp-password=guest ';
}

echo $cmd . "\n";

passthru($cmd);

if (!file_exists('/var/www/magento2/var/composer_home')) {
    mkdir('/var/www/magento2/var/composer_home', 0777, true);
}
copy('/root/.composer/auth.json', '/var/www/magento2/var/composer_home/auth.json');

if ($installSampleData) {
    passthru('cd /var/www/magento2 && php bin/magento sampledata:deploy');
    passthru('cd /var/www/magento2 && php bin/magento setup:upgrade');
}
