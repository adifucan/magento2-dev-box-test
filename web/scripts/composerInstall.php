#!/usr/bin/env php
<?php

function readValue($defaultValue = null)
{
    $input = rtrim(fgets(STDIN));
    return $input ?: $defaultValue;
}

$fileName = '/home/magento2/.composer/auth.json';

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
    exec('magento-cloud');
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
        if (!file_exists('/home/magento2/.ssh/' . $keyName)) {
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
                        if (file_exists('/home/magento2/.ssh/' . $keyName)) {
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
        exec('ssh-keygen -t rsa -N "" -f /home/magento2/.ssh/' . $keyName);

    }


    chmod('/home/magento2/.ssh/' . $keyName, 0600);
    passthru('echo "IdentityFile /home/magento2/.ssh/' . $keyName . '" >> /etc/ssh/ssh_config');

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
        exec('magento-cloud ssh-key:add /home/magento2/.ssh/' . $keyName . '.pub');
    }

    $result = shell_exec('ssh -q -o "BatchMode=yes" idymogyzqpche-master-7rqtwti@ssh.us.magentosite.cloud "echo 2>&1" && echo $host SSH_OK || echo $host SSH_NOK');
    if (trim($result) == 'SSH_OK') {
        echo "SSH connection with the Magento Cloud can be established\n";
    } else {
        throw new \Exception('You selected to init project from the Magento Cloud, but SSH connection cannot be established. Please start from the beginning.');
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
    echo 'Which version of Magento you want to be installed (please, choose CE or EE) [CE]:';
    $version = strtoupper(readValue('CE')) == 'EE' ? 'enterprise' : 'community';

    passthru(
        'cd /var/www && composer create-project --repository-url=""https://repo.magento.com/"" magento/project-' . $version . '-edition magento2'
    );
} else {
    passthru('cd /var/www/magento2 && composer update');
}
