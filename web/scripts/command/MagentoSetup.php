<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command;

require_once __DIR__.'/AbstractCommand.php';

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for Magento installation
 */
class MagentoSetup extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:setup')
            ->setDescription('Install Magento')
            ->setHelp('This command allows you to install Magento.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = sprintf(
            'cd /var/www/magento2 && php bin/magento setup:install'
                . ' --base-url=http://localhost:1748/ --db-host=db --db-name=magento2'
                . ' --db-user=root --db-password=root --admin-firstname=Magento --admin-lastname=User'
                . ' --admin-email=user@example.com --admin-user=%s --admin-password=%s'
                . ' --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1'
                . ' --backend-frontname=%s',
            $input->getOption('admin-user'),
            $input->getOption('admin-password'),
            $input->getOption('backend-path')
        );

        if ($input->getOption('install-rabbitmq')) {
            $command .= ' --amqp-virtualhost=/ --amqp-host=rabbit --amqp-port=5672 --amqp-user=guest'
                . ' --amqp-password=guest';
        }

        $this->shell($output, $command);

        if (!file_exists('/var/www/magento2/var/composer_home')) {
            mkdir('/var/www/magento2/var/composer_home', 0777, true);
        }

        copy('/root/.composer/auth.json', '/var/www/magento2/var/composer_home/auth.json');

        if ($input->getOption('install-sample-data')) {
            $this->shell(
                $output,
                [
                    'cd /var/www/magento2 && php bin/magento sampledata:deploy',
                    'cd /var/www/magento2 && php bin/magento setup:upgrade'
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsConfig()
    {
        return [
            'backend-path' => [
                'isInitial' => true,
                'defaultValue' => 'admin',
                'description' => 'Magento backend path.',
                'question' => 'Please enter backend admin path %default%'
            ],
            'admin-user' => [
                'isInitial' => true,
                'defaultValue' => 'admin',
                'description' => 'Admin username.',
                'question' => 'Please enter backend admin username %default%'
            ],
            'admin-password' => [
                'isInitial' => true,
                'defaultValue' => '123123q',
                'description' => 'Admin password.',
                'question' => 'Please enter backend admin password %default%'
            ],
            'install-sample-data' => [
                'isInitial' => true,
                'isBoolean' => true,
                'defaultValue' => false,
                'description' => 'Whether to install Sample Data.',
                'question' => 'Do you want to install Sample Data? %default%'
            ],
            'install-rabbitmq' => [
                'isRequired' => true,
                'isBoolean' => true,
                'defaultValue' => false,
                'description' => 'Whether to install RabbitMQ.'
            ]
        ];
    }
}
