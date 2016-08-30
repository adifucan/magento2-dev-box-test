<?php

$fileName = '/root/.composer/auth.json';
if (!file_exists($fileName)) {
    echo "Enter your magento public key\n";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    $publicKey = '';
    if(strlen(trim($line)) == 0) {
        echo "ABORTING!\n";
        exit;
    } else {
        $publicKey = trim($line);
    }
    fclose($handle);
    echo "Enter your magento private key\n";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    $privateKey = '';
    if(strlen(trim($line)) == 0){
        echo "ABORTING!\n";
        exit;
    } else {
        $privateKey = trim($line);
    }
    fclose($handle);
    echo "\n";
    echo "Writing auth.json\n";
    $auth_json = '{"http-basic": {"repo.magento.com": {"username": "';
    $auth_json .= $publicKey . '", "password": "';
    $auth_json .= $privateKey;
    $auth_json .= '"}}}';
    file_put_contents($fileName, $auth_json);
}

if (!file_exists('/var/www/magento2/composer.json')) {
    `cd /var/www && composer create-project --repository-url=""https://repo.magento.com/"" magento/project-community-edition magento2`;
} else {
    `cd /var/www/magento2 && composer install`;
}
