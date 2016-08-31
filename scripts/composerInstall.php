#!/usr/bin/env php
<?php

$fileName = '/root/.composer/auth.json';
passthru('echo $PATH');
exec('source /root/.bashrc');
exec('magento-cloud');
echo "Do you want to initialize from Magento Cloud? [yN]\n";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
$fromCloud = false;
if (strlen(trim($line)) > 0) {
    if (strtoupper(trim($line)) == 'Y') {
        $fromCloud = true;
    }
}

if ($fromCloud) {
    echo "What branch do you want to clone? [master]\n";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    $branch = 'master';
    if (strlen(trim($line)) > 0) {
        $branch = trim($line);
    }

    echo "Do you want to use existing ssh key? [Yn]\n";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    $existingKey = true;
    if (strlen(trim($line)) > 0) {
        if (strtoupper(trim($line)) == 'N') {
            $existingKey = false;
        }
    }

    if ($existingKey) {
        echo "What is the name of the SSH key to use with the Magento Cloud? [id_rsa]\n";
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        $keyName = 'id_rsa';
        if (strlen(trim($line)) > 0) {
            $keyName = trim($line);
        }
        if (!file_exists('/root/.ssh/' . $keyName)) {
            $positiveSolution = false;
            while (!$positiveSolution) {
                echo "File with the key does not exists, do you want to enter different name? [Yn]\n";
                $handle = fopen ("php://stdin","r");
                $line = fgets($handle);
                $enterNew = true;
                if (strlen(trim($line)) > 0) {
                    if (strtoupper(trim($line)) == 'N') {
                        throw new Exception("You selected to init project from the Magento Cloud, but SSH key for the Cloud is missing. Start from the beginning.");
                    }
                } else {
                    echo "What is the name of the SSH key to use with the Magento Cloud? [id_rsa]\n";
                    $handle = fopen ("php://stdin","r");
                    $line = fgets($handle);
                    $keyName = 'id_rsa';
                    if (strlen(trim($line)) > 0) {
                        $keyName = trim($line);
                        if (file_exists('/root/.ssh/' . $keyName)) {
                            $positiveSolution = true;
                        }
                    }
                }
            }
        }
    } else {
        echo "New key will be created. Enter the name of the SSH key [id_rsa]\n";
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        $keyName = 'id_rsa';
        if (strlen(trim($line)) > 0) {
            $keyName = trim($line);
        }
        exec('ssh-keygen -t rsa -N "" -f /root/.ssh/' . $keyName);

    }


    chmod('/root/.ssh/' . $keyName, 0600);
    passthru('echo "IdentityFile /root/.ssh/' . $keyName . '" >> /etc/ssh/ssh_config');
    passthru('ssh -v idymogyzqpche-master-7rqtwti@ssh.us.magentosite.cloud');

    echo "Do you want to add key to the Magento Cloud? [Yn]\n";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    $addKeyToCloud = true;
    if (strlen(trim($line)) > 0) {
        if (strtoupper(trim($line)) == 'N') {
            $addKeyToCloud = false;
        }
    }
    if ($addKeyToCloud) {
        exec('magento-cloud ssh-key:add /root/.ssh/' . $keyName . '.pub');
    }

    echo "Please select project to clone \n";
    passthru('magento-cloud project:list');
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    $project = false;
    if (strlen(trim($line)) > 0) {
        $project = trim($line);
    } else {
        $positiveSolution = false;
        while (!$positiveSolution) {
            echo "You haven't entered project name. Do you want to continue? [Yn]\n";
            $handle = fopen ("php://stdin","r");
            $line = fgets($handle);
            $enterNew = true;
            if (strlen(trim($line)) > 0) {
                if (strtoupper(trim($line)) == 'N') {
                    throw new Exception("You selected to init project from the Magento Cloud, but haven't provided project name. Please start from the beginning.");
                }
            } else {
                echo "Please select project to clone \n";
                passthru('magento-cloud project:list');
                $handle = fopen ("php://stdin","r");
                $line = fgets($handle);
                if (strlen(trim($line)) > 0) {
                    $project = trim($line);
                    $positiveSolution = true;
                }
            }
        }
    }
    $command = 'git clone --branch ' . $branch . ' ' . $project . '@git.us.magento.cloud:' . $project . '.git /var/www/magento2';
    echo $command . "\n";
    exec($command);
}

if (!file_exists($fileName)) {
    echo "Enter your magento public key\n";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    $publicKey = '';
    if (strlen(trim($line)) == 0) {
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

if (!$fromCloud && !file_exists('/var/www/magento2/composer.json')) {
    passthru('cd /var/www && composer create-project --repository-url=""https://repo.magento.com/"" magento/project-community-edition magento2');
} else {
    passthru('cd /var/www/magento2 && composer install');
}
