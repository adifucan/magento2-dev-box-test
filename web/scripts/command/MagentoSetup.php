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

        if ($input->getOption('rabbitmq-install')) {
            $rabbitmqHost = $this->requestOption('rabbitmq-host', $input, $output);
            $rabbitmqPort = $this->requestOption('rabbitmq-port', $input, $output);

            $command .= sprintf(
                ' --amqp-virtualhost=/ --amqp-host=%s --amqp-port=%s --amqp-user=guest'
                    . ' --amqp-password=guest',
                $rabbitmqHost,
                $rabbitmqPort
            );
        }

        $this->executeCommands($command, $output);

        if (!file_exists('/var/www/magento2/var/composer_home')) {
            mkdir('/var/www/magento2/var/composer_home', 0777, true);
        }

        copy('/root/.composer/auth.json', '/var/www/magento2/var/composer_home/auth.json');

        if ($input->getOption('install-sample-data')) {
            $this->executeCommands(
                [
                    'cd /var/www/magento2 && php bin/magento sampledata:deploy',
                    'cd /var/www/magento2 && php bin/magento setup:upgrade'
                ],
                $output
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
                'opening' => true,
                'default' => 'admin',
                'description' => 'Magento backend path.',
                'question' => 'Please enter backend admin path %default%'
            ],
            'admin-user' => [
                'opening' => true,
                'default' => 'admin',
                'description' => 'Admin username.',
                'question' => 'Please enter backend admin username %default%'
            ],
            'admin-password' => [
                'opening' => true,
                'default' => '123123q',
                'description' => 'Admin password.',
                'question' => 'Please enter backend admin password %default%'
            ],
            'install-sample-data' => [
                'opening' => true,
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to install Sample Data.',
                'question' => 'Do you want to install Sample Data? %default%'
            ],
            'rabbitmq-install' => [
                'opening' => true,
                'valueRequired' => false,
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to install RabbitMQ.',
                'question' => 'Do you want to install RabbitMQ? %default%'
            ],
            'rabbitmq-host' => [
                'valueRequired' => false,
                'default' => 'rabbit',
                'description' => 'RabbitMQ host.',
                'question' => 'Please specify RabbitMQ host %default%'
            ],
            'rabbitmq-port' => [
                'valueRequired' => false,
                'default' => '5672',
                'description' => 'RabbitMQ port.',
                'question' => 'Please specify RabbitMQ port %default%'
            ]
        ];
    }
}
