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
 * Command for Magento final steps
 */
class MagentoPrepare extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:prepare')
            ->setDescription('Prepare Magento for usage')
            ->setHelp('This command allows you to perform final steps for Magento usage.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->executeCommands('cd /var/www/magento2 && php bin/magento deploy:mode:set developer', $output);

        if ($this->requestOption('static-deploy', $input, $output)) {
            $this->executeCommands('cd /var/www/magento2 && php bin/magento setup:static-content:deploy', $output);
        } elseif ($this->requestOption('static-grunt-compile', $input, $output)) {
            $this->executeCommands(
                [
                    'cd /var/www/magento2 && cp Gruntfile.js.sample Gruntfile.js'
                        . ' && cp package.json.sample package.json',
                    'cd /var/www/magento2 && npm install && grunt refresh'
                ],
                $output
            );
        }

        if ($this->requestOption('di-compile', $input, $output)) {
            $this->executeCommands('cd /var/www/magento2 && php bin/magento setup:di:compile', $output);
        }

        $crontab = implode(
            "\n",
            [
                '* * * * * /usr/local/bin/php /var/www/magento2/bin/magento cron:run | grep -v "Ran jobs by schedule"'
                    . ' >> /var/www/magento2/var/log/magento.cron.log',
                '* * * * * /usr/local/bin/php /var/www/magento2/update/cron.php'
                    . ' >> /var/www/magento2/var/log/update.cron.log',
                '* * * * * /usr/local/bin/php /var/www/magento2/bin/magento setup:cron:run'
                    . ' >> /var/www/magento2/var/log/setup.cron.log'
            ]
        );
        file_put_contents("/home/magento2/crontab.sample", $crontab . "\n");
        $this->executeCommands(['crontab /home/magento2/crontab.sample', 'crontab -l'], $output);

        // setup configs for integration tests
        copy(
            '/var/www/magento2/dev/tests/integration/phpunit.xml.dist',
            '/var/www/magento2/dev/tests/integration/phpunit.xml'
        );
        copy(
            '/var/www/magento2/dev/tests/integration/etc/config-global.php.dist',
            '/var/www/magento2/dev/tests/integration/etc/config-global.php'
        );
        copy(
            '/var/www/magento2/dev/tests/integration/etc/install-config-mysql.travis.php.dist',
            '/var/www/magento2/dev/tests/integration/etc/install-config-mysql.travis.php'
        );

        $output->writeln('To open magento go to <info>http://localhost:1748</info> Admin area: <info>http://localhost:1748/admin</info>, login: <info>admin</info>, password: <info>admin123</info>');
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsConfig()
    {
        return [
            'static-deploy' => [
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to pre-deploy all static contents.',
                'question' => 'Do you want to pre-deploy all static assets? %default%'
            ],
            'static-grunt-compile' => [
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to compile CSS out of LESS via Grunt.',
                'question' => 'Do you want to compile CSS out of LESS via Grunt? %default%'
            ],
            'di-compile' => [
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to create generated files beforehand.',
                'question' => 'Do you want to create generated files beforehand? %default%'
            ]
        ];
    }
}
