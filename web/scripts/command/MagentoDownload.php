<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command;

require_once __DIR__ . '/../AbstractCommand.php';

use MagentoDevBox\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for downloading Magento sources
 */
class MagentoDownload extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:download')
            ->setDescription('Download Magento sources')
            ->setHelp('This command allows you to download Magento sources.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $installFromCloud = $input->getOption('install-from-cloud');

        if ($installFromCloud) {
            $this->installFromCloud($input, $output);
        }

        $authFile = '/root/.composer/auth.json';

        if (!file_exists($authFile)) {
            $this->generateAuthFile($authFile, $input, $output);
        }

        if (!$installFromCloud && !file_exists('/var/www/magento2/composer.json')) {
            $version = strtoupper($this->requestOption('magento-edition', $input, $output)) == 'EE'
                ? 'enterprise'
                : 'community';
            $this->executeCommands(
                sprintf(
                    'cd /var/www/magento2 && composer create-project --repository-url=""https://repo.magento.com/""'
                        . ' magento/project-%s-edition .',
                    $version
                ),
                $output
            );
        } else {
            $this->executeCommands('cd /var/www/magento2 && composer install', $output);
            $this->executeCommands('cd /var/www/magento2 && composer update', $output);
        }
    }

    /**
     * Download sources from Magento Cloud
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    private function installFromCloud(InputInterface $input, OutputInterface $output)
    {
        $this->executeCommands('magento-cloud', $output);
        $branch = $this->requestOption('cloud-branch', $input, $output);

        if ($this->requestOption('cloud-use-existing-key', $input, $output)) {
            $keyName = $this->requestOption('cloud-key-name', $input, $output);

            while (!file_exists(sprintf('/root/.ssh/%s', $keyName))) {
                if ($this->requestOption('cloud-try-different-key', $input, $output, true)) {
                    $keyName = $this->requestOption('cloud-key-name', $input, $output, true);
                } else {
                    throw new \Exception(
                        'You selected to init project from the Magento Cloud,'
                        . ' but SSH key for the Cloud is missing. Start from the beginning.'
                    );
                }
            }
        } else {
            $keyName = $this->requestOption(
                'cloud-key-name',
                $input,
                $output,
                false,
                'New key will be created. Enter the name of the SSH key'
            );
            $this->executeCommands(sprintf('ssh-keygen -t rsa -N "" -f /root/.ssh/%s', $keyName), $output);
        }

        chmod(sprintf('/root/.ssh/%s', $keyName), 0600);
        $this->executeCommands(sprintf('echo "IdentityFile /root/.ssh/%s" >> /etc/ssh/ssh_config', $keyName), $output);

        if ($this->requestOption('cloud-add-key', $input, $output)) {
            $this->executeCommands(sprintf('magento-cloud ssh-key:add /root/.ssh/%s.pub', $keyName), $output);
        }

        $result = shell_exec(
            'ssh -q -o "BatchMode=yes" idymogyzqpche-master-7rqtwti@ssh.us.magentosite.cloud "echo 2>&1"'
                . ' && echo $host SSH_OK || echo $host SSH_NOK'
        );

        if (trim($result) == 'SSH_OK') {
            $output->writeln('SSH connection with the Magento Cloud can be established.');
        } else {
            throw new \Exception(
                'You selected to init project from the Magento Cloud, but SSH connection cannot be established.'
                    . ' Please start from the beginning.'
            );
        }

        $this->executeCommands('magento-cloud project:list', $output);
        $project = $this->requestOption('cloud-project', $input, $output);

        while (!$project) {
            if ($this->requestOption('cloud-continue-with-no-project', $input, $output, true)) {
                $this->executeCommands('magento-cloud project:list', $output);
                $project = $this->requestOption('cloud-project', $input, $output, true);
            } else {
                throw new \Exception(
                    'You selected to init project from the Magento Cloud, but haven\'t provided project name.'
                    . ' Please start from the beginning.'
                );
            }
        }

        $this->executeCommands(
            sprintf(
                'git clone --branch %s %s@git.us.magento.cloud:%s.git /var/www/magento2',
                $branch,
                $project,
                $project
            ),
            $output
        );
    }

    /**
     * Generate auth.json file
     *
     * @param string $authFile
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    private function generateAuthFile($authFile, InputInterface $input, OutputInterface $output)
    {
        $publicKey = $this->requestOption('magento-public-key', $input, $output);
        $privateKey = $this->requestOption('magento-private-key', $input, $output);
        $output->writeln('Writing auth.json');
        $json = sprintf(
            '{"http-basic": {"repo.magento.com": {"username": "%s", "password": "%s"}}}',
            $publicKey,
            $privateKey
        );
        file_put_contents($authFile, $json);
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsConfig()
    {
        return [
            'install-from-cloud' => [
                'initial' => true,
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to get sources from Magento Cloud.',
                'question' => 'Do you want to initialize from Magento Cloud? %default%'
            ],
            'cloud-branch' => [
                'default' => 'master',
                'description' => 'Magento Cloud branch to clone from.',
                'question' => 'What branch do you want to clone from? %default%'
            ],
            'cloud-use-existing-key' => [
                'boolean' => true,
                'default' => true,
                'description' => 'Whether to use existing SSH key for Magento Cloud.',
                'question' => 'Do you want to use existing SSH key? %default%'
            ],
            'cloud-key-name' => [
                'default' => 'id_rsa',
                'description' => 'Name of the SSH key to use with Magento Cloud.',
                'question' => 'What is the name of the SSH key to use with the Magento Cloud? %default%'
            ],
            'cloud-try-different-key' => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'File with the key does not exists, do you want to enter different name? %default%'
            ],
            'cloud-add-key' => [
                'boolean' => true,
                'default' => true,
                'description' => 'Whether to add SSH key to Magento Cloud.',
                'question' => 'Do you want to add key to the Magento Cloud? %default%'
            ],
            'cloud-project' => [
                'description' => 'Magento Cloud project to clone.',
                'question' => 'Please select project to clone'
            ],
            'cloud-continue-with-no-project' => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'You haven\'t entered project name. Do you want to continue? %default%'
            ],
            'magento-public-key' => [
                'description' => 'Composer public key for Magento.',
                'question' => 'Enter your Magento public key'
            ],
            'magento-private-key' => [
                'description' => 'Composer private key for Magento.',
                'question' => 'Enter your Magento private key'
            ],
            'magento-edition' => [
                'default' => 'CE',
                'description' => 'Edition of Magento to install.',
                'question' => 'Which version of Magento you want to be installed (please, choose CE or EE) %default%'
            ]
        ];
    }
}
